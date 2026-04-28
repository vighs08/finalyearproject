<?php
session_start();
include("db_connect.php");

// Handle Logout Directly
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Ensure only vendors can access this page
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit();
}

// Get vendor details
$userID = $_SESSION['userID'];
$sql = "SELECT u.First_Name, u.Last_Name, u.email, v.storeName, v.BusinessAddress 
        FROM users u 
        JOIN vendors v ON u.userID = v.userID
        WHERE u.userID = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("⚠️ SQL Error: " . $conn->error);
}

$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$vendor = $result->fetch_assoc();

$stmt->close();
$conn->close();

// If vendor details are not found
if (!$vendor || !is_array($vendor)) {
    die("⚠️ Error: Vendor details not found in the database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include("navbar.php"); ?>

    <div class="container mt-5">
        <div class="card mx-auto shadow p-4" style="max-width: 500px;">
            <h2 class="text-center">Vendor Profile</h2>
            <hr>

            <p><strong>Name:</strong> <?= htmlspecialchars($vendor['First_Name']) . " " . htmlspecialchars($vendor['Last_Name']); ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($vendor['email']); ?></p>
            <p><strong>Store Name:</strong> <?= htmlspecialchars($vendor['storeName']); ?></p>
            <p><strong>Business Address:</strong> <?= htmlspecialchars($vendor['BusinessAddress']); ?></p>

            <a href="addProducts.php" class="btn btn-success w-100 mb-3">Rent Items</a>

            <!-- Logout Button -->
            <a href="?logout=true" class="btn btn-danger w-100">Logout</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
