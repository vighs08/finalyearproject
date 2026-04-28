<?php
// ✅ Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("db_connect.php"); // Database connection

// ✅ Check if user is logged in
$userLoggedIn = isset($_SESSION['userID']);
$userRole = $userLoggedIn && isset($_SESSION['role']) ? $_SESSION['role'] : "";
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";

// ✅ Handle logout directly in this file
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy session
    header("Location: login.php"); // Redirect to login page
    exit();
}
?>

<!-- ✅ Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <!-- ✅ Left Side: Logo & Home Button -->
        <a class="navbar-brand text-primary fw-bold" href="products_listing.php">RentifyX</a>
        
        <!-- ✅ Search Bar in Middle -->
        <form class="d-flex mx-auto" action="products_listing.php" method="GET">
            <input class="form-control me-2" type="search" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search products..." aria-label="Search">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </form>

        <!-- ✅ Right Side: Profile & Logout -->
        <div class="navbar-nav ms-auto">
            <?php if ($userLoggedIn): ?>
                <?php if ($userRole === 'user'): ?>
                    <a href="buyerProfile.php" class="btn btn-outline-dark me-2">Buyer Profile</a>
                <?php elseif ($userRole === 'vendor'): ?>
                    <a href="vendor_dashboard.php" class="btn btn-outline-dark me-2">Vendor Dashboard</a>
                <?php endif; ?>
                <a href="?logout=true" class="btn btn-danger">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                <a href="signup.php" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ✅ Navbar Styling -->
<style>
    .navbar {
        padding: 10px 20px;
        border-bottom: 1px solid #ddd;
    }
    .form-control {
        width: 300px;
    }
</style>
