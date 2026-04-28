<?php
session_start();
require 'db_connect.php'; // Ensure database connection is included

// ✅ Redirect to login if no session is found
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$vendorID = $_SESSION['userID'];

// Fetch buyers associated with the vendor
$buyersQuery = "SELECT DISTINCT b.*, u.First_Name, u.Last_Name, u.phoneNumber, t.totalAmount
                FROM buyers b 
                INNER JOIN users u ON b.userID = u.userID
                INNER JOIN transactions t ON b.buyerID = t.buyerID
                WHERE t.sellerID = ?";
$buyersResult = executeQuery($conn, $buyersQuery, [$vendorID]);

?>

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
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($buyer = $buyersResult->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($buyer['First_Name'] . " " . $buyer['Last_Name']) ?></td>
                            <td><?= htmlspecialchars($buyer['phoneNumber']) ?></td>
                            <td><?= htmlspecialchars($buyer['preferredPaymentMethod']) ?></td>
                            <td><?= htmlspecialchars($buyer['deliveryAddress']) ?></td>
                            <td>$<?= number_format($buyer['totalAmount'], 2) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="alert alert-info">No buyers found.</div>
        <?php } ?>
    </div>
</div>
