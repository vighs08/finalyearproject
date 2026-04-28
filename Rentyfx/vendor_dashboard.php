<?php
session_start();
require 'db_connect.php'; // Database connection

// ✅ Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// Ensure that item deletion is handled correctly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteItemID'])) {
    // Get the item ID from the POST request
    $deleteItemID = $_POST['deleteItemID'];

    // Check if itemID is not empty
    if (!empty($deleteItemID)) {
        // Delete the item from the database
        $deleteQuery = "DELETE FROM items WHERE itemID = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("s", $deleteItemID);
        
        if ($stmt->execute()) {
            // Successfully deleted
            header("Location: products_listing.php"); // Redirect after successful deletion
            exit();
        } else {
            echo "Error: Could not delete item."; // Debug message if deletion fails
        }

        // Close the statement
        $stmt->close();
    }
}

// Fetch user personal details and vendor business details
$vendorQuery = "SELECT u.userID, u.First_Name, u.Last_Name, u.email, u.phoneNumber, 
                        v.vendorID, v.storeName, v.businessAddress 
                FROM users u 
                LEFT JOIN vendors v ON u.userID = v.userID 
                WHERE u.userID = ?";

$stmt = $conn->prepare($vendorQuery);
$stmt->bind_param("s", $_SESSION['userID']);
$stmt->execute();
$vendorResult = $stmt->get_result();
$vendor = $vendorResult->fetch_assoc();
$stmt->close();

// Ensure we have both IDs
$userID = $_SESSION['userID']; // User ID for personal details
$vendorID = $vendor['vendorID'] ?? null; // Vendor ID for business details

// Initialize results to null before use (to avoid warnings)
$buyersResult = null;
$enquiriesResult = null;
$earningsResult = null;
$totalEarnings = 0.00;

// Fetch Buyers (Check if vendorID exists before querying)
if ($vendorID) {
    $buyersQuery = "SELECT DISTINCT b.*, u.First_Name, u.phoneNumber 
                    FROM buyers b 
                    INNER JOIN users u ON b.userID = u.userID
                    INNER JOIN transactions t ON b.buyerID = t.buyerID
                    WHERE t.sellerID = ?";
    $stmt = $conn->prepare($buyersQuery);
    $stmt->bind_param("s", $vendorID);
    $stmt->execute();
    $buyersResult = $stmt->get_result();
    $stmt->close();
}

// Fetch Listings (Check if vendorID exists before querying)
$listingsResult = null;
if ($vendorID) {
    $listingsQuery = "SELECT * FROM items WHERE vendorID = ?";
    $stmt = $conn->prepare($listingsQuery);
    $stmt->bind_param("s", $vendorID);
    $stmt->execute();
    $listingsResult = $stmt->get_result();
    $stmt->close();
}

// Fetch Enquiries (Check if vendorID exists before querying)
if ($vendorID) {
    $enquiriesQuery = "SELECT * FROM temppurchaserequests WHERE VendorID = ?";
    $stmt = $conn->prepare($enquiriesQuery);
    $stmt->bind_param("s", $vendorID);
    $stmt->execute();
    $enquiriesResult = $stmt->get_result();
    $stmt->close();
}

// Fetch Earnings (Check if vendorID exists before querying)
if ($vendorID) {
    $earningsQuery = "SELECT transactionID, transactionDate, totalAmount FROM transactions WHERE sellerID = ?";
    $stmt = $conn->prepare($earningsQuery);
    $stmt->bind_param("s", $vendorID);
    $stmt->execute();
    $earningsResult = $stmt->get_result();
    $stmt->close();

    // Calculate Total Earnings
    $totalQuery = "SELECT SUM(totalAmount) AS total FROM transactions WHERE sellerID = ?";
    $stmt = $conn->prepare($totalQuery);
    $stmt->bind_param("s", $vendorID);
    $stmt->execute();
    $totalResult = $stmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $totalEarnings = $totalRow['total'] ?? 0.00;
    $stmt->close();
}

$conn->close();
?>

<!-- Your HTML code goes here -->



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard | RentifyX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --dark-accent: #2c3e50;
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
            padding: 15px;
            box-shadow: 3px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar h4 {
            color: var(--primary-color);
            font-weight: 700;
        }

        .nav-link {
            color: #ecf0f1 !important;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 8px 0;
            transition: 0.2s ease;
        }

        .nav-link.active {
            background: var(--primary-color);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        th {
            background: var(--dark-accent);
            color: white;
        }

        .table-hover tbody tr:hover {
            background: #f8fff9;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <h4 class="text-white">RentifyX Vendor</h4>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link active" href="#" onclick="showSection(event, 'dashboard')">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="showSection(event, 'buyers')">Buyers</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="showSection(event, 'listings')">Listings</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="showSection(event, 'enquiries')">Enquiries</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="showSection(event, 'earnings')">Earnings</a></li>
        <li class="nav-item mt-3 m-2">
                <a class="nav-link bg-warning text-dark" href="addProducts.php">Rent Items</a>
        </li>
    </ul>
</nav>

<!-- Main Content -->
<main class="main-content">
    <div id="dashboard">
        <h3>Welcome, <?= htmlspecialchars($vendor['First_Name'] ?? 'Vendor') ?>!</h3>
        <div class="card mt-3">
            <div class="card-header">Your Details</div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($vendor['First_Name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($vendor['Last_Name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($vendor['email'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($vendor['phoneNumber'] ?? 'N/A') ?></td>
                        </tr>
                    </tbody>
                </table>
                <h5 class="mt-3">Business Information</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr><th>Business Name</th><th>Business Address</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($vendor['storeName'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($vendor['businessAddress'] ?? 'N/A') ?></td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-3">
                    <a href="editVenderInfo.php" class="btn btn-primary">Edit Info</a>
                </div>
            </div>
        </div>
    </div>

    <div id="buyers" class="d-none">
    <div class="card">
        <div class="card-header">Buyers Information</div>
        <div class="card-body">
            <?php if ($buyersResult->num_rows > 0) { ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Buyer Name</th>
                            <th>Phone Number</th>
                            <th>Preferred Payment Method</th>
                            <th>Delivery Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($buyer = $buyersResult->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($buyer['First_Name']) ?></td>
                                <td><?= htmlspecialchars($buyer['phoneNumber']) ?></td>
                                <td><?= htmlspecialchars($buyer['preferredPaymentMethod']) ?></td>
                                <td><?= htmlspecialchars($buyer['deliveryAddress']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="alert alert-info">No buyers found.</div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- HTML for displaying listings with delete option -->
<div id="listings" class="d-none">
    <div class="card">
        <div class="card-header">Your Listings</div>
        <div class="card-body">
            <?php if ($listingsResult->num_rows > 0) { ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th> <!-- Added Actions column -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($listing = $listingsResult->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($listing['title']) ?></td>
                                <td>Rs. <?= number_format($listing['price'], 2) ?></td>
                                <td><?= htmlspecialchars($listing['status']) ?></td>
                                <td>
                                    <!-- Edit Button -->
                                    <a href="editProduct.php?itemID=<?= htmlspecialchars($listing['itemID']) ?>" class="btn btn-warning btn-sm m-1">Update</a>

                                    <!-- Delete Button with Form -->
                                    <form action="" method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                                        <input type="hidden" name="deleteItemID" value="<?= htmlspecialchars($listing['itemID']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="alert alert-info">No listings found.</div>
            <?php } ?>
        </div>
    </div>
</div>

<script>
    function confirmDelete() {
        return confirm("Are you sure you want to delete this listing?");
    }
</script>


<div id="enquiries" class="d-none">
    <div class="card">
        <div class="card-header">Enquiries</div>
        <div class="card-body">
            <?php if ($enquiriesResult->num_rows > 0) { ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Item Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($enquiry = $enquiriesResult->fetch_assoc()) { ?>
                            <tr>
                                <td>#<?= htmlspecialchars($enquiry['RequestID']) ?></td>
                                <td><?= htmlspecialchars($enquiry['RentalItemID']) ?></td>
                                <td><?= htmlspecialchars($enquiry['Status']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="alert alert-info">No enquiries found.</div>
            <?php } ?>
        </div>
    </div>
</div>

<div id="earnings" class="d-none">
    <div class="card">
        <div class="card-header">Earnings</div>
        <div class="card-body">
            <?php if ($earningsResult->num_rows > 0) { ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($earning = $earningsResult->fetch_assoc()) { ?>
                            <tr>
                                <td>#<?= htmlspecialchars($earning['transactionID']) ?></td>
                                <td><?= htmlspecialchars($earning['transactionDate']) ?></td>
                                <td>$<?= number_format($earning['totalAmount'], 2) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <h5 class="mt-3">Total Earnings: <strong>$<?= number_format($totalEarnings, 2) ?></strong></h5>
            <?php } else { ?>
                <div class="alert alert-info">No earnings found.</div>
            <?php } ?>
        </div>
    </div>
</div>

</main>

<script>
   function showSection(event, sectionId) {
    event.preventDefault();
    document.querySelectorAll('.main-content > div').forEach(el => el.classList.add('d-none'));
    document.getElementById(sectionId).classList.remove('d-none');
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    event.target.classList.add('active');
}

</script>

</body>
</html>
