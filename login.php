<?php
require_once __DIR__ . '/config/config.php';

$page_title = "Login";
$error = '';
$allowed_roles = ['tenant', 'landlord']; // admins authenticate via admin_login.php
$show_modal = false;
$selected_role = '';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_role = sanitizeInput($_POST['role'] ?? '');
    $identifier = sanitizeInput($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $show_modal = true; // keep modal open on post

    if (empty($selected_role) || !in_array($selected_role, $allowed_roles, true)) {
        $error = 'Please select Tenant or Landlord to continue.';
    } elseif (empty($identifier) || empty($password)) {
        $error = 'Please enter your email/username and password.';
    } else {
        $conn = getDBConnection();

        $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, status FROM users WHERE (email = ? OR username = ?) AND role = ?");
        $stmt->bind_param("sss", $identifier, $identifier, $selected_role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if ($user['status'] !== 'active') {
                $error = 'Your account is not active. Please contact administrator.';
            } elseif (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect to dashboard
                redirect('dashboard.php');
            } else {
                $error = 'Invalid credentials.';
            }
        } else {
            $error = 'Invalid credentials or role selection.';
        }

        $stmt->close();
        closeDBConnection($conn);
    }
}

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card login-choice-card">
                <h1 class="login-title">Log In</h1>
                <p class="login-subtitle">Choose your role to continue</p>

                <div class="login-role-buttons">
                    <button class="role-login-btn" data-role="landlord">
                        <i class="fas fa-user-tie"></i>
                        <span>Landlord</span>
                    </button>
                    <button class="role-login-btn" data-role="tenant">
                        <i class="fas fa-user"></i>
                        <span>Tenant</span>
                    </button>
                </div>

                <p class="auth-link">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>

    <div id="loginModal" class="modal <?php echo $show_modal ? 'open' : ''; ?>">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close" aria-label="Close login window">&times;</button>
            <div class="modal-header">
                <h3 id="modalRoleTitle">Login</h3>
                <p class="modal-subtitle">Sign in as <span id="modalRoleLabel"><?php echo $selected_role ? ucfirst($selected_role) : 'User'; ?></span></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form modal-form">
                <input type="hidden" name="role" id="loginRoleField" value="<?php echo htmlspecialchars($selected_role); ?>">

                <div class="form-group">
                    <label for="identifier">Email or Username *</label>
                    <input type="text" name="identifier" id="identifier" required
                           value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Continue</button>
            </form>

            <div class="modal-footer-note">
                <small>Need an account? <a href="register.php">Register</a></small>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const roleButtons = document.querySelectorAll('.role-login-btn');
            const loginModal = document.getElementById('loginModal');
            const modalClose = loginModal ? loginModal.querySelector('.modal-close') : null;
            const modalOverlay = loginModal ? loginModal.querySelector('.modal-overlay') : null;
            const roleField = document.getElementById('loginRoleField');
            const roleLabel = document.getElementById('modalRoleLabel');

            const openModal = (role) => {
                if (!loginModal) return;
                if (roleField) roleField.value = role;
                if (roleLabel) roleLabel.textContent = role ? role.charAt(0).toUpperCase() + role.slice(1) : 'User';
                loginModal.classList.add('open');
            };

            const closeModal = () => {
                if (loginModal) loginModal.classList.remove('open');
            };

            roleButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    openModal(btn.dataset.role || '');
                });
            });

            if (modalClose) modalClose.addEventListener('click', closeModal);
            if (modalOverlay) modalOverlay.addEventListener('click', closeModal);

            if (loginModal && loginModal.classList.contains('open') && roleField && roleField.value) {
                openModal(roleField.value);
            }
        })();
    </script>
</main>
<?php include 'includes/footer.php'; ?>

