<?php
session_start();
include("db_connect.php");

// Ensure user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];
$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $storeName = trim($_POST['storeName']);
    $businessAddress = trim($_POST['businessAddress']);

    if (empty($storeName) || empty($businessAddress)) {
        $errorMessage = "All fields are required.";
    } else {
        $conn->begin_transaction(); // Start transaction

        try {
            // Insert vendor details
            $stmt = $conn->prepare("INSERT INTO vendors (vendorID, userID, storeName, BusinessAddress, rating, joinedAt) 
                                    VALUES (?, ?, ?, ?, ?, NOW())");
            $vendorID = uniqid("VND");
            $rating = 0.00;

            $stmt->bind_param("ssssd", $vendorID, $userID, $storeName, $businessAddress, $rating);
            $stmt->execute();
            $stmt->close();

            // Update user role to 'vendor'
            $updateStmt = $conn->prepare("UPDATE users SET role = 'vendor' WHERE userID = ?");
            $updateStmt->bind_param("s", $userID);
            $updateStmt->execute();
            $updateStmt->close();

            $conn->commit(); // Commit transaction

            // Update session role
            $_SESSION['role'] = "vendor";

            // Redirect to products listing page
            header("Location: products_listing.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback(); // Rollback in case of error
            $errorMessage = "Error: Could not register as vendor.";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Vendor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include("navbar.php"); ?>

    <div class="container mt-5">
        <div class="card mx-auto shadow p-4" style="max-width: 500px;">
            <h2 class="text-center">Register as a Vendor</h2>
            <hr>
            <?php if (!empty($errorMessage)) : ?>
                <div class="alert alert-danger text-center"><?= $errorMessage; ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="storeName" class="form-label">Store Name:</label>
                    <input type="text" class="form-control" id="storeName" name="storeName" required>
                </div>
                <div class="mb-3">
                    <label for="businessAddress" class="form-label">Business Address:</label>
                    <input type="text" class="form-control" id="businessAddress" name="businessAddress" required>
                </div>
                <button type="submit" class="btn btn-warning w-100">Register as Vendor</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
