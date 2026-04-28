<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit();
}

$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $categoryName = trim($_POST['categoryName']);
    $description = trim($_POST['description']);
    $userID = $_SESSION['userID']; // Vendor ID

    if (empty($categoryName)) {
        $errorMessage = "Category name is required.";
    } else {
        $categoryID = uniqid("CAT_"); // Generate Unique Category ID

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO categories (categoryID, categoryName, description, userID, createdAt) VALUES (?, ?, ?, ?, NOW())");

        // Check if the prepare statement failed
        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("ssss", $categoryID, $categoryName, $description, $userID);

        if ($stmt->execute()) {
            $successMessage = "Category added successfully!";
        } else {
            $errorMessage = "Error: Could not add category.";
        }

        $stmt->close();
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Category</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include("navbar.php"); ?>

    <div class="container mt-5">
        <div class="card mx-auto shadow p-4" style="max-width: 500px;">
            <h2 class="text-center">Create Category</h2>
            <hr>
            <?php if (!empty($successMessage)) : ?>
                <div class="alert alert-success text-center"><?= $successMessage; ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)) : ?>
                <div class="alert alert-danger text-center"><?= $errorMessage; ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="categoryName" class="form-label">Category Name</label>
                    <input type="text" class="form-control" id="categoryName" name="categoryName" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description (Optional)</label>
                    <textarea class="form-control" id="description" name="description"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create Category</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
