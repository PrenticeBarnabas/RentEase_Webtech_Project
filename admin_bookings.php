<?php
require_once __DIR__ . '/config/config.php';
requireRole('admin');

$page_title = "All Bookings";
$conn = getDBConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject', 'cancel'])) {
        $status = $action == 'approve' ? 'approved' : ($action == 'reject' ? 'rejected' : 'cancelled');
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        $stmt->execute();
        $stmt->close();
        redirect('admin_bookings.php');
    }
    
    if ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        redirect('admin_bookings.php');
    }
}

$status_filter = $_GET['status'] ?? '';

$query = "SELECT b.*, 
         p.title as property_title, p.location, p.rent_price,
         t.full_name as tenant_name, t.email as tenant_email,
         l.full_name as landlord_name, l.email as landlord_email
         FROM bookings b
         JOIN properties p ON b.property_id = p.id
         JOIN users t ON b.tenant_id = t.id
         JOIN users l ON b.landlord_id = l.id WHERE 1=1";

$params = [];
$types = '';

if (!empty($status_filter)) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>All Bookings</h1>
            <p>Monitor and manage all booking requests in the system</p>
        </div>
        
        <div class="filters-bar">
            <form method="GET" action="" class="filter-form-inline">
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="admin_bookings.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        
        <?php if ($bookings->num_rows > 0): ?>
            <div class="bookings-list">
                <?php while ($booking = $bookings->fetch_assoc()): ?>
                    <div class="booking-card">
                        <div class="booking-content">
                            <h3><?php echo htmlspecialchars($booking['property_title']); ?></h3>
                            <p class="booking-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['location']); ?>
                            </p>
                            
                            <div class="booking-details-grid">
                                <div class="booking-details">
                                    <h4>Tenant Information</h4>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['tenant_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['tenant_email']); ?></p>
                                </div>
                                
                                <div class="booking-details">
                                    <h4>Landlord Information</h4>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['landlord_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['landlord_email']); ?></p>
                                </div>
                                
                                <div class="booking-details">
                                    <h4>Booking Details</h4>
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
                                        <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="booking-actions">
                                <a href="property_details.php?id=<?php echo $booking['property_id']; ?>" class="btn btn-secondary">View Property</a>
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="?action=approve&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('Approve this booking?')">Approve</a>
                                    <a href="?action=reject&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Reject this booking?')">Reject</a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-calendar-times"></i>
                <h3>No bookings found</h3>
                <p>No bookings match your filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

