<?php
require_once __DIR__ . '/config/config.php';
requireRole('landlord');

$page_title = "My Properties";
$conn = getDBConnection();

$user_id = $_SESSION['user_id'];

if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $property_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM properties WHERE id = ? AND landlord_id = ?");
    $stmt->bind_param("ii", $property_id, $user_id);
    $stmt->execute();
    $stmt->close();
    redirect('my_properties.php');
}

$stmt = $conn->prepare("SELECT p.*, 
                       (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) as total_bookings,
                       (SELECT COUNT(*) FROM bookings WHERE property_id = p.id AND status = 'pending') as pending_bookings,
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                       FROM properties p
                       WHERE p.landlord_id = ?
                       ORDER BY p.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$properties = $stmt->get_result();
$stmt->close();

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>My Properties</h1>
            <a href="add_property.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Property
            </a>
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
                                <p><i class="fas fa-calendar-check"></i> <?php echo $property['total_bookings']; ?> Total Bookings</p>
                                <?php if ($property['pending_bookings'] > 0): ?>
                                    <p class="pending-notice"><i class="fas fa-bell"></i> <?php echo $property['pending_bookings']; ?> Pending</p>
                                <?php endif; ?>
                            </div>
                            <div class="property-footer">
                                <div class="property-price">
                                    <strong>৳<?php echo number_format($property['rent_price'], 2); ?></strong>
                                    <span>/month</span>
                                </div>
                                <div class="property-actions">
                                    <a href="edit_property.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
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
                <i class="fas fa-home"></i>
                <h3>No properties listed</h3>
                <p>Start by adding your first property to the platform.</p>
                <a href="add_property.php" class="btn btn-primary">Add Property</a>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

