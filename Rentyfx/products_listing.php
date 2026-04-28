<?php
session_start();
include("db_connect.php"); // Database connection file

// Fetch categories for filtering
$categorySql = "SELECT categoryID, categoryName FROM categories";
$categoryResult = $conn->query($categorySql);

// Get selected category from URL (if any)
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : "";
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch category name for display in heading
$categoryName = "All Products";
if (!empty($selectedCategory)) {
    $categoryNameQuery = $conn->prepare("SELECT categoryName FROM categories WHERE categoryID = ?");
    $categoryNameQuery->bind_param("s", $selectedCategory);
    $categoryNameQuery->execute();
    $categoryNameResult = $categoryNameQuery->get_result();
    if ($categoryNameResult->num_rows > 0) {
        $categoryRow = $categoryNameResult->fetch_assoc();
        $categoryName = htmlspecialchars($categoryRow["categoryName"]);
    } else {
        $categoryName = "Category Not Found";
        $selectedCategory = ""; // Reset filter if category is invalid
    }
    $categoryNameQuery->close();
}

// Fetch products, filtered by category or search
$sql = "SELECT i.itemID, i.title, i.description, i.price, i.categoryID, img.imageUrl
        FROM items i
        LEFT JOIN item_images img ON i.itemID = img.itemID
        WHERE 1";

if (!empty($selectedCategory)) {
    $sql .= " AND i.categoryID = ?";
}

if (!empty($searchQuery)) {
    $sql .= " AND i.title LIKE ?";
}

$sql .= " ORDER BY i.createdAt DESC";
$stmt = $conn->prepare($sql);

if (!empty($selectedCategory) && !empty($searchQuery)) {
    $searchParam = "%" . $searchQuery . "%";
    $stmt->bind_param("ss", $selectedCategory, $searchParam);
} elseif (!empty($selectedCategory)) {
    $stmt->bind_param("s", $selectedCategory);
} elseif (!empty($searchQuery)) {
    $searchParam = "%" . $searchQuery . "%";
    $stmt->bind_param("s", $searchParam);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Listing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
        }
        .category-navbar {
            background: white;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        .category-link {
            text-decoration: none;
            color: black;
            padding: 10px 15px;
            display: inline-block;
            transition: background 0.3s;
            border-radius: 5px;
        }
        .category-link:hover {
            background: #ddd;
        }
        .active-category {
            font-weight: bold;
            color: #007bff;
        }
        .product-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            transition: transform 0.2s;
            background: white;
            text-align: center;
        }
        .product-card:hover {
            transform: scale(1.05);
        }
        .product-img {
            width: 100%;
            height: 250px;
            object-fit: contain;
            border-radius: 10px;
            background-color: #f0f0f0;
        }
        .product-title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        .product-price {
            color: green;
            font-size: 16px;
            font-weight: bold;
        }
        .product-description {
            font-size: 14px;
            color: #777;
            height: 50px;
            overflow: hidden;
        }
        .details-link {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>

<!-- Include Navbar -->
<?php include("navbar.php"); ?>

<!-- Category Filter Navbar -->
<div class="container category-navbar">
    <a href="products_listing.php" class="category-link <?= empty($selectedCategory) ? 'active-category' : '' ?>">All</a>
    <?php while ($category = $categoryResult->fetch_assoc()): ?>
        <a href="products_listing.php?category=<?= htmlspecialchars($category['categoryID']); ?>"
           class="category-link <?= ($selectedCategory == $category['categoryID']) ? 'active-category' : '' ?>">
            <?= htmlspecialchars($category['categoryName']); ?>
        </a>
    <?php endwhile; ?>
</div>

<!-- Product Listings -->
<div class="container mt-4">
    <h2 class="text-center mb-4"><?= $categoryName; ?></h2>
    <div class="row">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="col-md-4 mb-4">';
                echo '<a href="productsDetails.php?itemID=' . htmlspecialchars($row["itemID"]) . '" class="details-link">';
                echo '<div class="product-card p-3">';
                
                // Check if the product has an image
                if (!empty($row["imageUrl"])) {
                    echo '<img src="' . htmlspecialchars($row["imageUrl"]) . '" alt="Product Image" class="product-img">';
                } else {
                    echo '<div class="product-img">No Image</div>';
                }

                echo '<div class="product-title">' . htmlspecialchars($row["title"]) . '</div>';
                echo '<div class="product-description">' . htmlspecialchars($row["description"]) . '</div>';
                echo '<div class="product-price">Price: Rs. ' . number_format($row["price"], 2) . '</div>';
                echo '</div>';
                echo '</a>';
                echo '</div>';
            }
        } else {
            echo '<p class="text-center">No products found.</p>';
        }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
