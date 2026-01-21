<?php
require_once __DIR__ . '/config/config.php';
requireRole('tenant');

$page_title = "My Bookings";
$conn = getDBConnection();

$user_id = $_SESSION['user_id'];

if (isset($_GET['cancel']) && $_GET['cancel'] > 0) {
    $booking_id = (int)$_GET['cancel'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND tenant_id = ? AND status IN ('pending', 'approved')");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $stmt->close();
    redirect('my_bookings.php');
}

if (isset($_GET['confirm']) && $_GET['confirm'] > 0) {
    $booking_id = (int)$_GET['confirm'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND tenant_id = ? AND status = 'approved'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    
    $stmt2 = $conn->prepare("UPDATE properties SET status = 'booked' WHERE id = (SELECT property_id FROM bookings WHERE id = ?)");
    $stmt2->bind_param("i", $booking_id);
    $stmt2->execute();
    $stmt2->close();
    
    $stmt->close();
    redirect('my_bookings.php');
}

$stmt = $conn->prepare("SELECT b.*, p.title, p.location, p.rent_price, p.category,
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image
                       FROM bookings b
                       JOIN properties p ON b.property_id = p.id
                       WHERE b.tenant_id = ?
                       ORDER BY b.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>My Bookings</h1>
            <p>Manage your booking requests and rental history</p>
        </div>
        
        <?php if ($bookings->num_rows > 0): ?>
            <div class="bookings-list">
                <?php while ($booking = $bookings->fetch_assoc()): ?>
                    <div class="booking-card">
                        <div class="booking-image">
                            <?php if ($booking['property_image']): ?>
                                <img src="<?php echo htmlspecialchars($booking['property_image']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-home"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="booking-content">
                            <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                            <p class="booking-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['location']); ?>
                            </p>
                            <div class="booking-details">
                                <p><strong>Category:</strong> <?php echo ucfirst($booking['category']); ?></p>
                                <p><strong>Rent:</strong> ৳<?php echo number_format($booking['rent_price'], 2); ?>/month</p>
                                <p><strong>Booking Date:</strong> <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></p>
                                <?php if ($booking['move_in_date']): ?>
                                    <p><strong>Move-in Date:</strong> <?php echo date('F j, Y', strtotime($booking['move_in_date'])); ?></p>
                                <?php endif; ?>
                                <p><strong>Status:</strong> 
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </p>
                                <?php if ($booking['message']): ?>
                                    <p><strong>Your Message:</strong> <?php echo htmlspecialchars($booking['message']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="booking-actions">
                                <a href="property_details.php?id=<?php echo $booking['property_id']; ?>" class="btn btn-secondary">View Property</a>
                                <?php if ($booking['status'] == 'pending' || $booking['status'] == 'approved'): ?>
                                    <a href="?cancel=<?php echo $booking['id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                                <?php endif; ?>
                                <?php if ($booking['status'] == 'approved'): ?>
                                    <a href="?confirm=<?php echo $booking['id']; ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('Confirm this booking? This will finalize your rental agreement.')">Confirm Booking</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-calendar-times"></i>
                <h3>No bookings found</h3>
                <p>You haven't made any booking requests yet.</p>
                <a href="properties.php" class="btn btn-primary">Browse Properties</a>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

