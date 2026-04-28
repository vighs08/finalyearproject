<?php
session_start();
require 'db_connect.php'; // Database connection file

// Check if itemID is provided in the URL
if (!isset($_GET['itemID'])) {
    die("❌ Error: No product ID provided.");
}

// Get the itemID from the URL
$itemID = $_GET['itemID'];

// Fetch current product details including images
$sql = "SELECT i.title, i.description, i.price, img.imageUrl, img.imageURL2, img.imageURL3 
        FROM items i
        LEFT JOIN item_images img ON i.itemID = img.itemID
        WHERE i.itemID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $itemID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("❌ Error: Product not found.");
}

$product = $result->fetch_assoc();
$stmt->close();

// Handle product edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    // Handle image upload if images are provided
    $imageUrl = $product['imageUrl'];
    $imageURL2 = $product['imageURL2'];
    $imageURL3 = $product['imageURL3'];

    // Check if new images are uploaded
    if ($_FILES['imageUrl']['error'] === UPLOAD_ERR_OK) {
        $imageUrl = 'uploads/' . basename($_FILES['imageUrl']['name']);
        move_uploaded_file($_FILES['imageUrl']['tmp_name'], $imageUrl);
    }

    if ($_FILES['imageURL2']['error'] === UPLOAD_ERR_OK) {
        $imageURL2 = 'uploads/' . basename($_FILES['imageURL2']['name']);
        move_uploaded_file($_FILES['imageURL2']['tmp_name'], $imageURL2);
    }

    if ($_FILES['imageURL3']['error'] === UPLOAD_ERR_OK) {
        $imageURL3 = 'uploads/' . basename($_FILES['imageURL3']['name']);
        move_uploaded_file($_FILES['imageURL3']['tmp_name'], $imageURL3);
    }

    // Update product details in the database
    $updateQuery = "UPDATE items SET title = ?, description = ?, price = ? WHERE itemID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssds", $title, $description, $price, $itemID);
    $stmt->execute();

    // Update product images
    $updateImagesQuery = "UPDATE item_images SET imageUrl = ?, imageURL2 = ?, imageURL3 = ? WHERE itemID = ?";
    $stmt = $conn->prepare($updateImagesQuery);
    $stmt->bind_param("ssss", $imageUrl, $imageURL2, $imageURL3, $itemID);
    $stmt->execute();

    // Redirect to vendor dashboard after successful update
    header("Location: vendor_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | RentifyX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
        }
        .product-img {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
            background-color: #f0f0f0;
        }
        .form-control {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container mt-5">
    <h2>Edit Product: <?= htmlspecialchars($product["title"]) ?></h2>
    <div class="card mt-4">
        <div class="card-header">Edit Product Details</div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">Product Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($product["title"]) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($product["description"]) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" class="form-control" id="price" name="price" value="<?= htmlspecialchars($product["price"]) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="imageUrl" class="form-label">Main Image</label>
                    <input type="file" class="form-control" id="imageUrl" name="imageUrl">
                    <img src="<?= htmlspecialchars($product["imageUrl"]) ?>" alt="Main Image" class="product-img mt-2">
                </div>

                <div class="mb-3">
                    <label for="imageURL2" class="form-label">Additional Image 1</label>
                    <input type="file" class="form-control" id="imageURL2" name="imageURL2">
                    <img src="<?= htmlspecialchars($product["imageURL2"]) ?>" alt="Additional Image 1" class="product-img mt-2">
                </div>

                <div class="mb-3">
                    <label for="imageURL3" class="form-label">Additional Image 2</label>
                    <input type="file" class="form-control" id="imageURL3" name="imageURL3">
                    <img src="<?= htmlspecialchars($product["imageURL3"]) ?>" alt="Additional Image 2" class="product-img mt-2">
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="vendor_dashboard.php" class="btn btn-secondary ml-2">Back to Dashboard</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
