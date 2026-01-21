<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$page_title = "Dashboard";
$user_role = getCurrentUserRole();
$conn = getDBConnection();

$stats = [];

if ($user_role == 'tenant') {
    $user_id = (int)$_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE tenant_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_bookings'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE tenant_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['pending_bookings'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE tenant_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['approved_bookings'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE tenant_id = ? AND status = 'confirmed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['confirmed_bookings'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
} elseif ($user_role == 'landlord') {
    $user_id = (int)$_SESSION['user_id'];
    
    // Total properties
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM properties WHERE landlord_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_properties'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    // Available properties
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM properties WHERE landlord_id = ? AND status = 'available'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['available_properties'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    // Total bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE landlord_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_bookings'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    // Pending requests
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE landlord_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['pending_requests'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
} elseif ($user_role == 'admin') {
    // Total users
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $result->fetch_assoc()['total'];
    
    // Total properties
    $result = $conn->query("SELECT COUNT(*) as total FROM properties");
    $stats['total_properties'] = $result->fetch_assoc()['total'];
    
    // Total bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings");
    $stats['total_bookings'] = $result->fetch_assoc()['total'];
    
    // Pending bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
    $stats['pending_bookings'] = $result->fetch_assoc()['total'];
}

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
            <p class="user-role-badge"><?php echo ucfirst($user_role); ?></p>
        </div>
        
        <div class="stats-grid">
            <?php if ($user_role == 'tenant'): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_bookings']; ?></h3>
                        <p>Pending Bookings</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['approved_bookings']; ?></h3>
                        <p>Approved Bookings</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-home"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['confirmed_bookings']; ?></h3>
                        <p>Confirmed Rentals</p>
                    </div>
                </div>
                
            <?php elseif ($user_role == 'landlord'): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_properties']; ?></h3>
                        <p>Total Properties</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-home"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['available_properties']; ?></h3>
                        <p>Available Properties</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bell"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_requests']; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>
                
            <?php elseif ($user_role == 'admin'): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_properties']; ?></h3>
                        <p>Total Properties</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_bookings']; ?></h3>
                        <p>Pending Bookings</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <?php if ($user_role == 'tenant'): ?>
                    <a href="properties.php" class="action-card">
                        <i class="fas fa-search"></i>
                        <h3>Browse Properties</h3>
                        <p>Find your perfect home</p>
                    </a>
                    <a href="my_bookings.php" class="action-card">
                        <i class="fas fa-calendar"></i>
                        <h3>My Bookings</h3>
                        <p>View booking status</p>
                    </a>
                    <a href="profile.php" class="action-card">
                        <i class="fas fa-user"></i>
                        <h3>My Profile</h3>
                        <p>Update your information</p>
                    </a>
                    
                <?php elseif ($user_role == 'landlord'): ?>
                    <a href="add_property.php" class="action-card">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Add Property</h3>
                        <p>List a new property</p>
                    </a>
                    <a href="my_properties.php" class="action-card">
                        <i class="fas fa-home"></i>
                        <h3>My Properties</h3>
                        <p>Manage your listings</p>
                    </a>
                    <a href="booking_requests.php" class="action-card">
                        <i class="fas fa-inbox"></i>
                        <h3>Booking Requests</h3>
                        <p>Review tenant requests</p>
                    </a>
                    <a href="profile.php" class="action-card">
                        <i class="fas fa-user"></i>
                        <h3>My Profile</h3>
                        <p>Update your information</p>
                    </a>
                    
                <?php elseif ($user_role == 'admin'): ?>
                    <a href="admin_users.php" class="action-card">
                        <i class="fas fa-users"></i>
                        <h3>Manage Users</h3>
                        <p>View and manage all users</p>
                    </a>
                    <a href="admin_properties.php" class="action-card">
                        <i class="fas fa-building"></i>
                        <h3>All Properties</h3>
                        <p>Oversee all listings</p>
                    </a>
                    <a href="admin_bookings.php" class="action-card">
                        <i class="fas fa-list"></i>
                        <h3>All Bookings</h3>
                        <p>Monitor all bookings</p>
                    </a>
                    <a href="profile.php" class="action-card">
                        <i class="fas fa-user"></i>
                        <h3>My Profile</h3>
                        <p>Update your information</p>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

