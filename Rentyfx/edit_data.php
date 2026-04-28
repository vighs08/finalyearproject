<?php
session_start();
require 'db_connect.php'; // Database connection file

// Ensure user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form inputs
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $preferredPaymentMethod = trim($_POST['preferredPaymentMethod']);
    $deliveryAddress = trim($_POST['deliveryAddress']);

    // **Update Users Table** (For Name & Phone)
    $updateUserQuery = "UPDATE users SET First_Name = ?, Last_Name = ?, phoneNumber = ? WHERE userID = ?";
    $stmt = $conn->prepare($updateUserQuery);
    $stmt->bind_param("ssss", $first_name, $last_name, $phoneNumber, $userID);
    $stmt->execute();
    $stmt->close();

    // **Insert or Update Buyers Table (For Payment & Address)**
    $updateBuyerQuery = "INSERT INTO buyers (buyerID, userID, preferredPaymentMethod, deliveryAddress)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE 
                         preferredPaymentMethod = VALUES(preferredPaymentMethod), 
                         deliveryAddress = VALUES(deliveryAddress)";
    $stmt = $conn->prepare($updateBuyerQuery);
    $stmt->bind_param("ssss", $userID, $userID, $preferredPaymentMethod, $deliveryAddress);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    // Redirect to profile with success message
    echo "<script>alert('Profile updated successfully!'); window.location.href='buyerProfile.php';</script>";
    exit();
}

// **Fetch Data to Pre-Fill Form**
$userQuery = "SELECT First_Name, Last_Name, phoneNumber FROM users WHERE userID = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("s", $userID);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$buyerQuery = "SELECT preferredPaymentMethod, deliveryAddress FROM buyers WHERE userID = ?";
$stmt = $conn->prepare($buyerQuery);
$stmt->bind_param("s", $userID);
$stmt->execute();
$buyer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - RentifyX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --dark-accent: #2c3e50;
            --transition-speed: 0.3s;
        }
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: var(--dark-accent);
            transition: all var(--transition-speed);
            z-index: 1000;
            box-shadow: 3px 0 20px rgba(0, 0, 0, 0.1);
            color: white;
            padding: 20px;
        }
        .nav-link {
            color: #ecf0f1 !important;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-link:hover {
            background: var(--primary-color);
        }
        .nav-link.active {
            background: var(--primary-color);
            font-weight: bold;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-submit {
            background: var(--primary-color);
            color: white;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }
        .btn-submit:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4>RentifyX Dashboard</h4>
    <a href="buyerProfile.php" class="nav-link active">Dashboard</a>
    <a href="orders.php" class="nav-link">Order History</a>
    <a href="pending_orders.php" class="nav-link">Pending Orders</a>
    <a href="help_center.php" class="nav-link">Help Center</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2 class="mb-3">Edit Profile</h2>
    <div class="card p-4">
        <form method="POST" action="edit_data.php">
            <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['First_Name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['Last_Name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phoneNumber" class="form-control" value="<?= htmlspecialchars($user['phoneNumber'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Preferred Payment Method</label>
                <input type="text" name="preferredPaymentMethod" class="form-control" value="<?= htmlspecialchars($buyer['preferredPaymentMethod'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Delivery Address</label>
                <textarea name="deliveryAddress" class="form-control" rows="3" required><?= htmlspecialchars($buyer['deliveryAddress'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit">Save Changes</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
