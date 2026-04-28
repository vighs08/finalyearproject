<?php
session_start();
include("db_connect.php"); // Database connection file

// Check if itemID is provided in the URL
if (!isset($_GET['itemID'])) {
    die("❌ Error: No product ID provided.");
}

$itemID = $_GET['itemID'];

// Fetch product details including multiple images
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product["title"]); ?> - Product Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 900px;
        }

        .product-img-container {
            width: 100%;
            height: 400px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .product-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .details-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
        }

        .product-title {
            font-size: 24px;
            font-weight: bold;
        }

        .product-price {
            color: green;
            font-size: 22px;
            font-weight: bold;
        }

        .thumbnail-container {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .thumbnail:hover {
            transform: scale(1.1);
            border-color: #007bff;
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="container mt-5">
        <div class="details-card">
            <!-- Main Product Image -->
            <div class="text-center product-img-container">
                <img id="mainImage"
                    src="<?= !empty($product["imageUrl"]) ? htmlspecialchars($product["imageUrl"]) : 'placeholder.png'; ?>"
                    alt="Product Image" class="product-img">
            </div>

            <!-- Thumbnail Gallery -->
            <div class="thumbnail-container">
                <?php if (!empty($product["imageUrl"])): ?>
                    <img src="<?= htmlspecialchars($product["imageUrl"]); ?>" alt="Product Image" class="thumbnail"
                        onclick="changeImage('<?= htmlspecialchars($product["imageUrl"]); ?>')">
                <?php endif; ?>

                <?php if (!empty($product["imageURL2"])): ?>
                    <img src="<?= htmlspecialchars($product["imageURL2"]); ?>" alt="Product Image 2" class="thumbnail"
                        onclick="changeImage('<?= htmlspecialchars($product["imageURL2"]); ?>')">
                <?php endif; ?>

                <?php if (!empty($product["imageURL3"])): ?>
                    <img src="<?= htmlspecialchars($product["imageURL3"]); ?>" alt="Product Image 3" class="thumbnail"
                        onclick="changeImage('<?= htmlspecialchars($product["imageURL3"]); ?>')">
                <?php endif; ?>
            </div>

            <!-- Product Details -->
            <h2 class="product-title mt-4"><?= htmlspecialchars($product["title"]); ?></h2>
            <p class="product-description"><?= htmlspecialchars($product["description"]); ?></p>
            <p class="product-price">Price: Rs. <?= number_format($product["price"], 2); ?></p>

            <a href="products_listing.php" class="btn btn-secondary">Back to Listings</a>
            <a href="temp_request.php?itemID=<?= htmlspecialchars($item['itemID']) ?>" class="btn btn-primary">Rent Now</a>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeImage(imageSrc) {
            document.getElementById("mainImage").src = imageSrc;
        }
    </script>
</body>

</html>

<?php
$conn->close();
?>
