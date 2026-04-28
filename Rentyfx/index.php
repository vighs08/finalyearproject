<?php
session_start();
include("db_connect.php"); // Database connection

// Check if user is logged in
$userLoggedIn = isset($_SESSION['userID']);

if ($userLoggedIn) {
    // Fetch user details from database
    $userID = $_SESSION['userID'];
    $query = "SELECT First_Name, role FROM users WHERE userID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        $_SESSION['userName'] = $userData['First_Name']; // Store in session
        $_SESSION['role'] = $userData['role']; // Store role
    } else {
        $userLoggedIn = false; // Fallback in case of session error
    }
    $stmt->close();
}

$userName = $userLoggedIn ? $_SESSION['userName'] : "";
$userRole = $userLoggedIn ? $_SESSION['role'] : "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rentifyx - Grow Your Business</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #1e1e1e;
            background-color: #f8f9fa;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            color: #1a73e8;
            margin: 0;
        }

        nav a {
            margin: 0 10px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }

        .cta-button {
            background-color: #1a73e8;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-transform: uppercase;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }

        /* Hero Section Styles */
        .hero-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 60px 40px;
            background-color: #e6f4ea;
            min-height: calc(100vh - 80px);
        }

        .hero-text h2 {
            font-size: 2.5rem;
            margin: 0 0 15px 0;
        }

        .hero-text p {
            font-size: 1.1rem;
            color: #555;
            max-width: 500px;
        }

        .hero-image img {
            max-width: 400px;
            border-radius: 10px;
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #333;
            color: #fff;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <?php include("authNav.php"); ?>


    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-text">
            <h2>Grow your business with Rentifyx</h2>
            <p>
                With innovative tools and user-friendly features, Rentifyx helps you manage your rentals,
                bookings, and services seamlessly in one place. Get started today!
            </p>

            <!-- Show Different Button Based on User Role -->
            <?php if ($userLoggedIn): ?>
                <?php if ($userRole === 'vendor'): ?>
                    <a href="products_listing.php" class="cta-button">Go to Vendor Profile</a>
                <?php else: ?>
                    <a href="userprofile.php" class="cta-button">Go to Profile</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="signup.php" class="cta-button">Sign Up</a>
            <?php endif; ?>
        </div>
        <div class="hero-image">
            <img src="media/logo2.png" alt="Hero Image" />
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Rentifyx. All rights not yet reserved.</p>
    </footer>
</body>

</html>
