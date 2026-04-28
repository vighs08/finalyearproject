<?php
session_start();
require 'db_connect.php'; // Ensure this file contains a proper connection setup

// ✅ Redirect to login if no session is found
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

/**
 * Function to prepare and execute SQL queries securely
 * Returns the result set or exits on error
 */
function executeQuery($conn, $query, $params) {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    $stmt->execute();

    return $stmt->get_result();
}

// --- Fetch user data ---
$userQuery = "SELECT First_Name, Last_Name, email, phoneNumber, role FROM users WHERE userID = ?";
$userResult = executeQuery($conn, $userQuery, [$userID]);
$user = $userResult->fetch_assoc();

// --- Fetch buyer data ---
$buyerQuery = "SELECT preferredPaymentMethod, deliveryAddress FROM buyers WHERE userID = ?";
$buyerResult = executeQuery($conn, $buyerQuery, [$userID]);
$buyer = $buyerResult->fetch_assoc();

// --- Fetch transaction data ---
$orderQuery = "SELECT transactionDate, paymentStatus, deliveryStatus, totalAmount 
               FROM transactions WHERE buyerID = ?";
$orderResult = executeQuery($conn, $orderQuery, [$userID]);

$orders = [];
while ($row = $orderResult->fetch_assoc()) {
    $orders[] = $row;
}

// --- Fetch pending purchase requests ---
$pendingQuery = "SELECT tpr.Token, tpr.RequestDate, tpr.Status AS orderStatus, i.title
                 FROM temppurchaserequests tpr
                 JOIN items i ON tpr.RentalItemID = i.itemID
                 WHERE tpr.UserID = ? AND tpr.Status = 'Pending'";
$pendingResult = executeQuery($conn, $pendingQuery, [$userID]);

$pendingOrders = [];
while ($row = $pendingResult->fetch_assoc()) {
    $pendingOrders[] = $row;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentifyX User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --dark-accent: #2c3e50;
            --transition-speed: 0.3s;
            --hover-brightness: 1.15;
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: var(--dark-accent);
            transition: all var(--transition-speed);
            z-index: 1000;
            box-shadow: 3px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar h4 {
            color: var(--primary-color);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-link {
            color: #ecf0f1 !important;
            padding: 12px 20px !important;
            border-radius: 8px;
            margin: 8px 0;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-link:before {
            content: '';
            position: absolute;
            left: -100%;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
        }

        .nav-link:hover:before {
            left: 0;
        }

        .nav-link.active {
            background: var(--primary-color) !important;
            transform: translateX(10px);
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 60px;
            transition: margin var(--transition-speed);
            background: #f8f9fa;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 1.2rem;
            border-bottom: 3px solid var(--secondary-color);
        }

        table {
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
        }

        th {
            background: var(--dark-accent) !important;
            color: white !important;
            font-weight: 600 !important;
            padding: 1rem !important;
        }

        td {
            background: white !important;
            padding: 1rem !important;
            border-bottom: 2px solid #f8f9fa !important;
            transition: all 0.2s ease;
        }

        tr:hover td {
            background: #f8fff9 !important;
            transform: scale(1.02);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
        }

        .status-badge.Pending {
            background: #f1c40f;
            color: var(--dark-accent);
        }

        .status-badge.Completed {
            background: var(--primary-color);
            color: white;
        }

        .loading-spinner {
            border-color: var(--primary-color);
            border-right-color: transparent;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }

        .progress-tracker {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            width: 100%;
            transition: width 0.5s ease;
        }

        .theme-toggle {
            border: 2px solid var(--dark-accent);
            transition: all 0.3s ease;
            padding: 8px 12px !important;
        }

        .theme-toggle:hover {
            transform: rotate(180deg);
            border-color: var(--primary-color);
            background: var(--dark-accent);
            color: white !important;
        }

        .header-nav {
            background: var(--dark-accent);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 900;
        }

        .header-nav .nav-wrapper {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-left: var(--sidebar-width);
        }

        .header-nav .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .header-nav .nav-links {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }

        .header-nav .nav-links li {
            margin: 0 10px;
        }

        .header-nav .nav-links li a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }

        .header-nav .nav-user {
            font-size: 1rem;
        }

        /* Force elements with this class to stay black regardless of theme */
        .text-dark-accent {
            color: #000 !important;
        }
    </style>
</head>

<body>
    <div class="loading-spinner spinner-border text-primary" role="status" style="display: none;">
        <span class="visually-hidden">Loading...</span>
    </div>

<!-- Sidebar -->
<nav class="sidebar">
        <h4 class="text-white">RentifyX Dashboard</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="order_history.php">Order History</a></li>
            <li class="nav-item"><a class="nav-link" href="pending_orders.php">Pending Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="help_center.php">Help Center</a></li>
            <li class="nav-item mt-3 m-2">
                <a class="nav-link bg-warning text-dark" href="vendorRegistration.php">Register as Vendor</a>
            </li>
        </ul>
    </nav>
    

    <!-- Header Navigation Bar -->
    <header class="header-nav">
        <div class="nav-wrapper">
            <ul class="nav-links">
                <li><a href="products_listing.php">Home</a></li>
                <li><a href="#">About Us</a></li>
            </ul>
            <div class="nav-user">
                Hello, <?= htmlspecialchars($user['First_Name'] ?? 'User') ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark-accent">Hello,
                <?= htmlspecialchars($user['First_Name'] ?? 'User') . ' ' . htmlspecialchars($user['Last_Name'] ?? '') ?>
            </h4>
            <button class="btn theme-toggle" onclick="toggleTheme()">🌓 Toggle Theme</button>
        </div>

        <div id="content-container">
            <h3 class="text-dark-accent mb-4">Welcome to RentifyX!</h3>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById('content-container').innerHTML = `
            <div class="card">
                <div class="card-header">Your Details</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Payment Method</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= htmlspecialchars($user['First_Name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['Last_Name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['phoneNumber'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($buyer['preferredPaymentMethod'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($buyer['deliveryAddress'] ?? 'N/A') ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <a href="edit_data.php" class="btn btn-primary">Edit</a>
                    </div>
                </div>
            </div>
            `;
        });

        function loadContent(event, section) {
            event.preventDefault();
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');

            let contentContainer = document.getElementById('content-container');
            contentContainer.style.opacity = '0';
            document.querySelector('.loading-spinner').style.display = 'block';

            setTimeout(() => {
                let content = '';
                if (section === 'dashboard') {
                    content = `
                    <div class="card">
                        <div class="card-header">Your Details</div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Payment Method</th>
                                        <th>Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?= htmlspecialchars($user['First_Name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($user['Last_Name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($user['phoneNumber'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($buyer['preferredPaymentMethod'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($buyer['deliveryAddress'] ?? 'N/A') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mt-3">
                                <a href="edit_data.php" class="btn btn-primary">Edit</a>
                            </div>
                        </div>
                    </div>`;
                } else if (section === 'order-history') {
                    content = "<h3 class='mb-4'>Your Order History</h3>";
                    <?php if (!empty($orders)): ?>
                        content += `
                                            <div class="card">
                                                <div class="card-header">Transaction Records</div>
                                                <div class="card-body p-0">
                                                    <table class='table table-hover mb-0'>
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Payment</th>
                                                                <th>Delivery</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>`;
                        <?php foreach ($orders as $order): ?>
                            content += `
                                                                <tr>
                                                                    <td><?= htmlspecialchars($order['transactionDate']) ?></td>
                                                                    <td>
                                                                        <span class="status-badge <?= htmlspecialchars($order['paymentStatus']) ?>">
                                                                            <?= htmlspecialchars($order['paymentStatus']) ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="status-badge <?= htmlspecialchars($order['deliveryStatus']) ?>">
                                                                            <?= htmlspecialchars($order['deliveryStatus']) ?>
                                                                        </span>
                                                                        <div class="progress-tracker">
                                                                            <div class="progress-fill"></div>
                                                                        </div>
                                                                    </td>
                                                                    <td>₹<?= htmlspecialchars($order['totalAmount']) ?></td>
                                                                </tr>`;
                        <?php endforeach; ?>
                        content += `</tbody></table></div></div>`;
                    <?php else: ?>
                        content += "<div class='alert alert-info'>You have no orders yet.</div>";
                    <?php endif; ?>
                } else if (section === 'pending-orders') {
                    content = "<h3 class='mb-4'>Pending Order Status</h3>";
                    <?php if (!empty($pendingOrders)): ?>
                        content += `
                                            <div class="card">
                                                <div class="card-header">Active Requests</div>
                                                <div class="card-body p-0">
                                                    <table class='table table-hover mb-0'>
                                                        <thead>
                                                            <tr>
                                                                <th>Item</th>
                                                                <th>Token</th>
                                                                <th>Request Date</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>`;
                        <?php foreach ($pendingOrders as $pendingOrder): ?>
                            content += `
                                                                <tr>
                                                                    <td><?= htmlspecialchars($pendingOrder['title']) ?></td>
                                                                    <td>#<?= htmlspecialchars($pendingOrder['Token']) ?></td>
                                                                    <td><?= htmlspecialchars($pendingOrder['RequestDate']) ?></td>
                                                                    <td>
                                                                        <span class="status-badge <?= htmlspecialchars($pendingOrder['orderStatus']) ?>">
                                                                            <?= htmlspecialchars($pendingOrder['orderStatus']) ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>`;
                        <?php endforeach; ?>
                        content += `</tbody></table></div></div>`;
                    <?php else: ?>
                        content += "<div class='alert alert-info'>No pending orders found.</div>";
                    <?php endif; ?>
                } else {
                    content = `<div class="card">
                                <div class="card-header">Help Center</div>
                                <div class="card-body">
                                    <h5>Contact Support</h5>
                                    <p>Email: support@rentifyx.com<br>Phone: +91 98765 43210</p>
                                </div>
                            </div>`;
                }

                contentContainer.innerHTML = content;
                setTimeout(() => {
                    contentContainer.style.transition = 'opacity 0.4s ease';
                    contentContainer.style.opacity = '1';
                }, 50);
                document.querySelector('.loading-spinner').style.display = 'none';
            }, 500);
        }

        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.getAttribute('data-bs-theme') === 'dark';
            html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');

            if (!isDark) {
                document.documentElement.style.setProperty('--dark-accent', '#1a1a1a');
                document.documentElement.style.setProperty('--primary-color', '#27ae60');
            } else {
                document.documentElement.style.setProperty('--dark-accent', '#2c3e50');
                document.documentElement.style.setProperty('--primary-color', '#2ecc71');
            }
        }
    </script>
</body>

</html>