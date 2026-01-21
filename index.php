<?php
$page_title = "Home";
include 'includes/header.php';
?>
<main class="main-content">
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Welcome to RentEase</h1>
                <p class="hero-subtitle">Your Complete Landlord-Tenant Management Solution</p>
                <p>Simplify rental management, streamline bookings, and enhance communication between landlords and tenants.</p>
                <div class="hero-buttons">
                    <?php if (!isLoggedIn()): ?>
                        <a href="properties.php" class="btn btn-secondary">Browse Properties</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                        <a href="properties.php" class="btn btn-secondary">Browse Properties</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2 class="section-title">Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-user-tie"></i>
                    <h3>For Tenants</h3>
                    <ul>
                        <li>Browse available properties</li>
                        <li>Filter by category and location</li>
                        <li>Book properties easily</li>
                        <li>Manage booking requests</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <i class="fas fa-building"></i>
                    <h3>For Landlords</h3>
                    <ul>
                        <li>List your properties</li>
                        <li>Manage property details</li>
                        <li>Handle booking requests</li>
                        <li>Track tenant information</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>

