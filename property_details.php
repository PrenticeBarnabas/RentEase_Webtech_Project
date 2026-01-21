<?php
require_once __DIR__ . '/config/config.php';

$page_title = "Property Details";
$conn = getDBConnection();

$property_id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT p.*, u.full_name as landlord_name, u.email as landlord_email, u.phone as landlord_phone 
                       FROM properties p 
                       JOIN users u ON p.landlord_id = u.id 
                       WHERE p.id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    redirect('properties.php');
}

$stmt = $conn->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$images = $stmt->get_result();
$stmt->close();

$has_booking = false;
if (isLoggedIn() && hasRole('tenant')) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE tenant_id = ? AND property_id = ? AND status IN ('pending', 'approved')");
    $stmt->bind_param("ii", $user_id, $property_id);
    $stmt->execute();
    $has_booking = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="property-details-page">
            <div class="property-images">
                <?php if ($images->num_rows > 0): ?>
                    <div class="main-image">
                        <?php 
                        $first_image = $images->fetch_assoc();
                        $images->data_seek(0); // Reset pointer
                        ?>
                        <img src="<?php echo htmlspecialchars($first_image['image_path']); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" id="mainImage">
                    </div>
                    <?php if ($images->num_rows > 1): ?>
                        <div class="image-thumbnails">
                            <?php while ($image = $images->fetch_assoc()): ?>
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="Property image" 
                                     onclick="document.getElementById('mainImage').src = this.src">
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-image-large">
                        <i class="fas fa-home"></i>
                        <p>No Images Available</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="property-info">
                <div class="property-header">
                    <h1><?php echo htmlspecialchars($property['title']); ?></h1>
                    <span class="property-category-badge"><?php echo ucfirst($property['category']); ?></span>
                </div>
                
                <div class="property-price-large">
                    <strong>৳<?php echo number_format($property['rent_price'], 2); ?></strong>
                    <span>/month</span>
                </div>
                
                <div class="property-meta">
                    <p class="location">
                        <i class="fas fa-map-marker-alt"></i> 
                        <?php echo htmlspecialchars($property['location']); ?>, <?php echo htmlspecialchars($property['area']); ?>
                    </p>
                    <div class="property-specs">
                        <?php if ($property['bedrooms'] > 0): ?>
                            <div class="spec">
                                <i class="fas fa-bed"></i>
                                <span><?php echo $property['bedrooms']; ?> Bedroom<?php echo $property['bedrooms'] > 1 ? 's' : ''; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($property['bathrooms'] > 0): ?>
                            <div class="spec">
                                <i class="fas fa-bath"></i>
                                <span><?php echo $property['bathrooms']; ?> Bathroom<?php echo $property['bathrooms'] > 1 ? 's' : ''; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($property['square_feet']): ?>
                            <div class="spec">
                                <i class="fas fa-ruler-combined"></i>
                                <span><?php echo number_format($property['square_feet']); ?> sq ft</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="property-description-full">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                </div>
                
                <div class="property-address">
                    <h3>Address</h3>
                    <p><?php echo nl2br(htmlspecialchars($property['address'])); ?></p>
                </div>
                
                <?php if ($property['available_from']): ?>
                    <div class="property-availability">
                        <h3>Available From</h3>
                        <p><?php echo date('F j, Y', strtotime($property['available_from'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="landlord-info">
                    <h3>Landlord Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($property['landlord_name']); ?></p>
                    <?php if ($property['landlord_email']): ?>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($property['landlord_email']); ?></p>
                    <?php endif; ?>
                    <?php if ($property['landlord_phone']): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($property['landlord_phone']); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (isLoggedIn() && hasRole('tenant') && $property['status'] == 'available'): ?>
                    <div class="booking-section">
                        <?php if ($has_booking): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You already have a pending or approved booking for this property.
                            </div>
                            <a href="my_bookings.php" class="btn btn-primary btn-block">View My Bookings</a>
                        <?php else: ?>
                            <a href="book_property.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-large btn-block">
                                <i class="fas fa-calendar-check"></i> Book This Property
                            </a>
                        <?php endif; ?>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <div class="booking-section">
                        <a href="login.php" class="btn btn-primary btn-large btn-block">
                            <i class="fas fa-sign-in-alt"></i> Login to Book
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

