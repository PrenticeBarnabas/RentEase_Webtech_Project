<?php
require_once __DIR__ . '/config/config.php';

$page_title = "Properties";
$conn = getDBConnection();

$category = $_GET['category'] ?? '';
$area = $_GET['area'] ?? '';
$search = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

$query = "SELECT p.*, u.full_name as landlord_name, 
         (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
         FROM properties p 
         JOIN users u ON p.landlord_id = u.id 
         WHERE p.status = 'available'";

$params = [];
$types = '';

if (!empty($category)) {
    $query .= " AND p.category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($area)) {
    $query .= " AND p.area LIKE ?";
    $params[] = "%$area%";
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if (!empty($min_price)) {
    $query .= " AND p.rent_price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if (!empty($max_price)) {
    $query .= " AND p.rent_price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$properties = $stmt->get_result();

// Get unique areas for filter
$areas_result = $conn->query("SELECT DISTINCT area FROM properties WHERE status = 'available' ORDER BY area");
$areas = [];
while ($row = $areas_result->fetch_assoc()) {
    $areas[] = $row['area'];
}

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Browse Properties</h1>
            <p>Find your perfect home</p>
        </div>
        
        <div class="properties-layout">
            <aside class="filters-sidebar">
                <h3>Filter Properties</h3>
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" placeholder="Search properties..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <option value="apartment" <?php echo $category == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <option value="flat" <?php echo $category == 'flat' ? 'selected' : ''; ?>>Flat</option>
                            <option value="house" <?php echo $category == 'house' ? 'selected' : ''; ?>>House</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="area">Area</label>
                        <input type="text" name="area" id="area" placeholder="Enter area..." 
                               value="<?php echo htmlspecialchars($area); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="min_price">Min Price</label>
                        <input type="number" name="min_price" id="min_price" placeholder="Min" 
                               value="<?php echo htmlspecialchars($min_price); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_price">Max Price</label>
                        <input type="number" name="max_price" id="max_price" placeholder="Max" 
                               value="<?php echo htmlspecialchars($max_price); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                    <a href="properties.php" class="btn btn-secondary btn-block">Clear Filters</a>
                </form>
            </aside>
            
            <div class="properties-grid">
                <?php if ($properties->num_rows > 0): ?>
                    <?php while ($property = $properties->fetch_assoc()): ?>
                        <div class="property-card">
                            <div class="property-image">
                                <?php if ($property['primary_image']): ?>
                                    <img src="<?php echo htmlspecialchars($property['primary_image']); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-home"></i>
                                        <p>No Image</p>
                                    </div>
                                <?php endif; ?>
                                <span class="property-category"><?php echo ucfirst($property['category']); ?></span>
                            </div>
                            <div class="property-content">
                                <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                                <p class="property-location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                                </p>
                                <div class="property-details">
                                    <?php if ($property['bedrooms'] > 0): ?>
                                        <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bed</span>
                                    <?php endif; ?>
                                    <?php if ($property['bathrooms'] > 0): ?>
                                        <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bath</span>
                                    <?php endif; ?>
                                    <?php if ($property['square_feet']): ?>
                                        <span><i class="fas fa-ruler-combined"></i> <?php echo number_format($property['square_feet']); ?> sq ft</span>
                                    <?php endif; ?>
                                </div>
                                <p class="property-description"><?php echo htmlspecialchars(substr($property['description'], 0, 100)); ?>...</p>
                                <div class="property-footer">
                                    <div class="property-price">
                                        <strong>৳<?php echo number_format($property['rent_price'], 2); ?></strong>
                                        <span>/month</span>
                                    </div>
                                    <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No properties found</h3>
                        <p>Try adjusting your filters or search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

