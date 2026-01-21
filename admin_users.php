<?php
require_once __DIR__ . '/config/config.php';
requireRole('admin');

$page_title = "Manage Users";
$conn = getDBConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['activate', 'deactivate', 'suspend'])) {
        $status = $action == 'activate' ? 'active' : ($action == 'suspend' ? 'suspended' : 'inactive');
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("si", $status, $user_id);
        $stmt->execute();
        $stmt->close();
        redirect('admin_users.php');
    }
    
    if ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        redirect('admin_users.php');
    }
}

$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

$query = "SELECT u.*, 
         (SELECT COUNT(*) FROM properties WHERE landlord_id = u.id) as property_count,
         (SELECT COUNT(*) FROM bookings WHERE tenant_id = u.id) as booking_count
         FROM users u WHERE 1=1";

$params = [];
$types = '';

if (!empty($role_filter)) {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $query .= " AND u.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Manage Users</h1>
        </div>
        
        <div class="filters-bar">
            <form method="GET" action="" class="filter-form-inline">
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select name="role" id="role">
                        <option value="">All Roles</option>
                        <option value="tenant" <?php echo $role_filter == 'tenant' ? 'selected' : ''; ?>>Tenant</option>
                        <option value="landlord" <?php echo $role_filter == 'landlord' ? 'selected' : ''; ?>>Landlord</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="admin_users.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Properties</th>
                        <th>Bookings</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['property_count']; ?></td>
                                <td><?php echo $user['booking_count']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($user['role'] != 'admin'): ?>
                                            <?php if ($user['status'] != 'active'): ?>
                                                <a href="?action=activate&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Activate">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['status'] != 'inactive'): ?>
                                                <a href="?action=deactivate&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Deactivate"
                                                   onclick="return confirm('Deactivate this user?')">
                                                    <i class="fas fa-pause"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['status'] != 'suspended'): ?>
                                                <a href="?action=suspend&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Suspend"
                                                   onclick="return confirm('Suspend this user?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>

