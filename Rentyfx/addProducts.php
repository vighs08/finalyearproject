<?php
session_start();
include("db_connect.php");
include("config.php");
require 'vendor/autoload.php'; // Load Cloudinary SDK

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

// Ensure user is logged in
if (!isset($_SESSION['userID'])) {
    die("❌ Error: User session not found. Please log in.");
}

$userID = $_SESSION['userID'];
$errorMessage = "";
$successMessage = "";

// ✅ Debugging: Check session value
echo "✅ Debug: Session userID is " . $userID . "<br>";

// Fetch vendorID using userID
$vendorCheck = $conn->prepare("SELECT vendorID FROM vendors WHERE userID = ?");
$vendorCheck->bind_param("s", $userID);
$vendorCheck->execute();
$vendorResult = $vendorCheck->get_result();

if ($vendorResult->num_rows == 0) {
    die("❌ Error: Vendor does not exist. Please register as a vendor before adding products.");
} else {
    $vendorRow = $vendorResult->fetch_assoc();
    $vendorID = $vendorRow['vendorID']; // Assign correct vendor ID
}

// Fetch predefined categories dynamically
$categories = [];
$categoryQuery = "SELECT categoryID, categoryName FROM categories";
$categoryResult = $conn->query($categoryQuery);
if ($categoryResult->num_rows > 0) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[$row['categoryID']] = $row['categoryName'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Capture form data
        $itemName = trim($_POST['itemName']);
        $itemDescription = trim($_POST['itemDescription']);
        $price = trim($_POST['price']);
        $categoryID = $_POST['categoryID']; // Selected category

        // Validate form inputs
        if (empty($itemName) || empty($itemDescription) || empty($price) || empty($categoryID) || $_FILES['itemImages']['error'][0] !== UPLOAD_ERR_OK) {
            throw new Exception("All fields and at least one image are required.");
        }

        // Configure Cloudinary
        Configuration::instance([
            'cloud' => [
                'cloud_name' => CLOUDINARY_CLOUD_NAME,
                'api_key' => CLOUDINARY_API_KEY,
                'api_secret' => CLOUDINARY_API_SECRET
            ]
        ]);

        $upload = new UploadApi();
        $itemID = uniqid("ITEM_");

        // Insert item details into database
        $stmt = $conn->prepare("INSERT INTO items (itemID, vendorID, categoryID, title, description, price, status, createdAt) 
                                VALUES (?, ?, ?, ?, ?, ?, 'available', NOW())");
        if (!$stmt) {
            throw new Exception("SQL Error: " . $conn->error);
        }
        $stmt->bind_param("ssssss", $itemID, $vendorID, $categoryID, $itemName, $itemDescription, $price);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting item: " . $stmt->error);
        }
        $stmt->close();

        // Process multiple image uploads
        $imageURLs = [];
        foreach ($_FILES['itemImages']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName)) {
                $uploadResult = $upload->upload($tmpName);
                $imageURLs[] = $uploadResult['secure_url'];
            }
        }

        // Store image URLs in database
        if (!empty($imageURLs)) {
            $imageStmt = $conn->prepare("INSERT INTO item_images (imageID, itemID, imageUrl, imageURL2, imageURL3, createdAt) VALUES (?, ?, ?, ?, ?, NOW())");
            $imageID = uniqid("IMG_");
            $img1 = $imageURLs[0] ?? NULL;
            $img2 = $imageURLs[1] ?? NULL;
            $img3 = $imageURLs[2] ?? NULL;
            $imageStmt->bind_param("sssss", $imageID, $itemID, $img1, $img2, $img3);
            if (!$imageStmt->execute()) {
                throw new Exception("Error inserting images: " . $imageStmt->error);
            }
            $imageStmt->close();
        }

        // Redirect to products page on success
        header("Location: products_listing.php");
        exit();

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List an Item</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f7f7f7;
            font-family: 'Arial', sans-serif;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #007bff;
            color: white;
            font-size: 1.5rem;
            text-align: center;
            padding: 20px;
        }

        .form-label {
            font-weight: 500;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .alert {
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 10px;
        }

        .file-input {
            background-color: #f1f1f1;
            border-radius: 10px;
            padding: 10px;
        }

        .mb-3 {
            margin-bottom: 1.5rem !important;
        }

        /* Custom error message styling */
        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
            border-color: #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .card-body {
            padding: 2rem;
        }
    </style>
    <script>
        function validateForm() {
            let itemName = document.getElementById("itemName").value.trim();
            let itemDescription = document.getElementById("itemDescription").value.trim();
            let price = document.getElementById("price").value.trim();
            let categoryID = document.getElementById("categoryID").value;
            let itemImages = document.getElementById("itemImages").files;

            if (itemName === "" || itemDescription === "" || price === "" || categoryID === "" || itemImages.length === 0) {
                alert("All fields are required, including at least one image.");
                return false;
            }
            if (isNaN(price) || price <= 0) {
                alert("Price must be a valid positive number.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container">
    <div class="card mx-auto shadow-lg p-4" style="max-width: 600px;">
        <div class="card-header">
            <h4>List an Item for Rent</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($successMessage)) : ?>
                <div class="alert alert-success text-center"><?= $successMessage; ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)) : ?>
                <div class="alert alert-danger text-center"><?= $errorMessage; ?></div>
            <?php endif; ?>
            <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="mb-4">
                    <label for="categoryID" class="form-label">Select Category</label>
                    <select class="form-control" id="categoryID" name="categoryID" required>
                        <option value="">-- Select a Category --</option>
                        <?php foreach ($categories as $categoryID => $categoryName): ?>
                            <option value="<?= htmlspecialchars($categoryID); ?>"><?= htmlspecialchars($categoryName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="itemName" class="form-label">Item Name</label>
                    <input type="text" class="form-control" id="itemName" name="itemName" required>
                </div>

                <div class="mb-4">
                    <label for="itemDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="itemDescription" name="itemDescription" rows="4" required></textarea>
                </div>

                <div class="mb-4">
                    <label for="price" class="form-label">Price per day (Rs)</label>
                    <input type="number" class="form-control" id="price" name="price" min="0" required>
                </div>

                <div class="mb-4">
                    <label for="itemImages" class="form-label">Upload Item Images (Max 3)</label>
                    <input type="file" class="form-control file-input" id="itemImages" name="itemImages[]" multiple required>
                </div>

                <button type="submit" class="btn btn-success w-100">List Item</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
