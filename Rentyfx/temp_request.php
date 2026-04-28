<?php
session_start();
include("db_connect.php"); // Database connection file

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// Check if itemID is provided in the URL
if (!isset($_GET['itemID'])) {
    die("❌ Error: No product ID provided.");
}

$itemID = $_GET['itemID'];  // Get itemID from the URL
$userID = $_SESSION['userID']; // Get userID from session

// Fetch item details and vendor ID
$stmt = $conn->prepare("SELECT i.*, v.userID AS vendorID FROM items i
                      JOIN vendors v ON i.vendorID = v.vendorID
                      WHERE i.itemID = ?");
$stmt->bind_param("s", $itemID);
$stmt->execute();
$itemResult = $stmt->get_result();

if ($itemResult->num_rows === 0) {
    die("❌ Error: Item not found.");
}
$item = $itemResult->fetch_assoc();
$vendorID = $item['vendorID'];

// Fetch user and buyer information
$stmt = $conn->prepare("SELECT u.*, b.preferredPaymentMethod, b.deliveryAddress 
                      FROM users u
                      LEFT JOIN buyers b ON u.userID = b.userID
                      WHERE u.userID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $paymentMethod = $conn->real_escape_string($_POST['paymentMethod']);
    $deliveryAddress = $conn->real_escape_string($_POST['deliveryAddress']);

    // Validation
    if (empty($paymentMethod) || empty($deliveryAddress)) {
        $error = "❌ All fields are required.";
    } else {
        // Update or insert buyer information
        if ($user['preferredPaymentMethod'] === null) {
            $stmt = $conn->prepare("INSERT INTO buyers (buyerID, userID, preferredPaymentMethod, deliveryAddress) 
                                  VALUES (?, ?, ?, ?)");
            $buyerID = 'BUY' . uniqid();
            $stmt->bind_param("ssss", $buyerID, $userID, $paymentMethod, $deliveryAddress);
        } else {
            $stmt = $conn->prepare("UPDATE buyers SET 
                                  preferredPaymentMethod = ?,
                                  deliveryAddress = ? 
                                  WHERE userID = ?");
            $stmt->bind_param("sss", $paymentMethod, $deliveryAddress, $userID);
        }
        $stmt->execute();

        // Create purchase request
        $requestID = 'REQ' . uniqid();
        $token = bin2hex(random_bytes(16));

        $stmt = $conn->prepare("INSERT INTO temppurchaserequests 
                              (RequestID, UserID, VendorID, RentalItemID, Token)
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $requestID, $userID, $vendorID, $itemID, $token);

        if ($stmt->execute()) {
            $success = "🎉 Rental request submitted successfully!";
        } else {
            $error = "❌ Error submitting request: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Request - <?= htmlspecialchars($item['title']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .card {
            margin-top: 20px;
        }

        .preview-card {
            background-color: #f8f9fa;
        }

        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <?php include("navbar.php"); ?>

    <div class="container">
        <h2 class="my-4">Complete Rental Request</h2>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Item Preview -->
            <div class="col-md-6">
                <div class="card preview-card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($item['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($item['description']) ?></p>
                        <p class="h4 text-success">Rs. <?= number_format($item['price'], 2) ?>/day</p>
                    </div>
                </div>
            </div>

            <!-- User Details Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Information</h5>
                        <form method="POST">
                            <div class="form-group">
                                <label>Name:</label>
                                <input type="text" class="form-control"
                                    value="<?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?>"
                                    disabled>
                            </div>

                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                                    disabled>
                            </div>

                            <div class="form-group">
                                <label>Phone Number:</label>
                                <input type="text" class="form-control"
                                    value="<?= htmlspecialchars($user['phoneNumber']) ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label>Preferred Payment Method:</label>
                                <select name="paymentMethod" class="form-control" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Credit Card" <?= $user['preferredPaymentMethod'] === 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
                                    <option value="PayPal" <?= $user['preferredPaymentMethod'] === 'PayPal' ? 'selected' : '' ?>>PayPal</option>
                                    <option value="Bank Transfer" <?= $user['preferredPaymentMethod'] === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Delivery Address:</label>
                                <textarea name="deliveryAddress" class="form-control" rows="3"
                                    required><?= htmlspecialchars($user['deliveryAddress'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                Submit Rental Request
                            </button>
                        </form>
                        <a href="product_details.php?itemID=<?= htmlspecialchars($item['itemID']) ?>" class="btn btn-secondary mt-3">Back to Product</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php $conn->close(); ?>
