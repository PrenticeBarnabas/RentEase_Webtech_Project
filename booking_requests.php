<?php
require_once __DIR__ . '/config/config.php';
requireRole('landlord');

$page_title = "Booking Requests";
$conn = getDBConnection();

$user_id = $_SESSION['user_id'];

if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $status = $action == 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND landlord_id = ?");
        $stmt->bind_param("sii", $status, $booking_id, $user_id);
        $stmt->execute();
        $stmt->close();
        redirect('booking_requests.php');
    }
}

$stmt = $conn->prepare("SELECT b.*, p.title, p.location, p.rent_price, p.category,
                       u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone,
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image
                       FROM bookings b
                       JOIN properties p ON b.property_id = p.id
                       JOIN users u ON b.tenant_id = u.id
                       WHERE b.landlord_id = ?
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
            <h1>Booking Requests</h1>
            <p>Review and manage booking requests for your properties</p>
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
                                <h4>Tenant Information</h4>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['tenant_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['tenant_email']); ?></p>
                                <?php if ($booking['tenant_phone']): ?>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['tenant_phone']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="booking-details">
                                <h4>Booking Details</h4>
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
                                    <p><strong>Tenant Message:</strong> <?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="booking-actions">
                                <a href="property_details.php?id=<?php echo $booking['property_id']; ?>" class="btn btn-secondary">View Property</a>
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="?action=approve&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('Approve this booking request?')">Approve</a>
                                    <a href="?action=reject&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Reject this booking request?')">Reject</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-inbox"></i>
                <h3>No booking requests</h3>
                <p>You don't have any booking requests yet.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

