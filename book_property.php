<?php
require_once __DIR__ . '/config/config.php';
requireRole('tenant');

$page_title = "Book Property";
$error = '';
$success = '';
$conn = getDBConnection();

$property_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND status = 'available'");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    redirect('properties.php');
}

// Check if user already has a booking for this property
$stmt = $conn->prepare("SELECT id FROM bookings WHERE tenant_id = ? AND property_id = ? AND status IN ('pending', 'approved')");
$stmt->bind_param("ii", $user_id, $property_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    redirect('property_details.php?id=' . $property_id);
}
$stmt->close();

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $move_in_date = $_POST['move_in_date'] ?? '';
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($move_in_date)) {
        $error = 'Please select a move-in date.';
    } else {
        $booking_date = date('Y-m-d');
        $landlord_id = $property['landlord_id'];
        
        $stmt = $conn->prepare("INSERT INTO bookings (tenant_id, property_id, landlord_id, booking_date, move_in_date, message, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiiiss", $user_id, $property_id, $landlord_id, $booking_date, $move_in_date, $message);
        
        if ($stmt->execute()) {
            $success = 'Booking request submitted successfully! The landlord will review your request.';
            header('refresh:2;url=my_bookings.php');
        } else {
            $error = 'Failed to submit booking request. Please try again.';
        }
        $stmt->close();
    }
}

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Book Property</h1>
        </div>
        
        <div class="booking-container">
            <div class="booking-form-card">
                <h2><?php echo htmlspecialchars($property['title']); ?></h2>
                <p class="property-location">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                </p>
                <p class="property-price">
                    <strong>৳<?php echo number_format($property['rent_price'], 2); ?></strong> /month
                </p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="booking-form">
                    <div class="form-group">
                        <label for="move_in_date">Preferred Move-in Date *</label>
                        <input type="date" name="move_in_date" id="move_in_date" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message to Landlord</label>
                        <textarea name="message" id="message" rows="5" 
                                  placeholder="Tell the landlord about yourself and why you're interested in this property..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <a href="property_details.php?id=<?php echo $property_id; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Submit Booking Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

