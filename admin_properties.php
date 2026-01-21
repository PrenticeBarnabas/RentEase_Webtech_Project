<?php
require_once __DIR__ . '/config/config.php';
requireRole('admin');

$page_title = "All Properties";
$conn = getDBConnection();

if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $property_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $stmt->close();
    redirect('admin_properties.php');
}

$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

$query = "SELECT p.*, u.full_name as landlord_name, u.email as landlord_email,
         (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) as booking_count,
         (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
         FROM properties p
         JOIN users u ON p.landlord_id = u.id WHERE 1=1";

$params = [];
$types = '';

if (!empty($category_filter)) {
    $query .= " AND p.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$properties = $stmt->get_result();
$stmt->close();

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>All Properties</h1>
            <p>Oversee all property listings in the system</p>
        </div>
        
        <div class="filters-bar">
            <form method="GET" action="" class="filter-form-inline">
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <option value="apartment" <?php echo $category_filter == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                        <option value="flat" <?php echo $category_filter == 'flat' ? 'selected' : ''; ?>>Flat</option>
                        <option value="house" <?php echo $category_filter == 'house' ? 'selected' : ''; ?>>House</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">All Status</option>
                        <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="booked" <?php echo $status_filter == 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="unavailable" <?php echo $status_filter == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="admin_properties.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        
        <?php if ($properties->num_rows > 0): ?>
            <div class="properties-grid">
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
                            <span class="property-status status-<?php echo $property['status']; ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </span>
                        </div>
                        <div class="property-content">
                            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                            <p class="property-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                            </p>
                            <p class="property-landlord">
                                <strong>Landlord:</strong> <?php echo htmlspecialchars($property['landlord_name']); ?>
                            </p>
                            <div class="property-details">
                                <span class="property-category"><?php echo ucfirst($property['category']); ?></span>
                                <?php if ($property['bedrooms'] > 0): ?>
                                    <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?></span>
                                <?php endif; ?>
                                <?php if ($property['bathrooms'] > 0): ?>
                                    <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="property-stats">
                                <p><i class="fas fa-calendar-check"></i> <?php echo $property['booking_count']; ?> Bookings</p>
                            </div>
                            <div class="property-footer">
                                <div class="property-price">
                                    <strong>৳<?php echo number_format($property['rent_price'], 2); ?></strong>
                                    <span>/month</span>
                                </div>
                                <div class="property-actions">
                                    <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="?delete=<?php echo $property['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-building"></i>
                <h3>No properties found</h3>
                <p>No properties match your filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

