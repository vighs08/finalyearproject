<?php
session_start();
include("db_connect.php");

$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $role = "user"; // Default role

    $userID = uniqid("USR_"); // Generate a unique userID

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($phoneNumber)) {
        $errorMessage = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid email format.";
    } elseif (strlen($phoneNumber) !== 10 || !ctype_digit($phoneNumber)) {
        $errorMessage = "Phone number must be exactly 10 digits.";
    } elseif (strlen($password) < 6) {
        $errorMessage = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        if (!$checkStmt) {
            die("SQL Error (Check Email): " . $conn->error);
        }
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $errorMessage = "Email is already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (userID, email, password, First_Name, Last_Name, phoneNumber, role, createdAt) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) {
                die("SQL Error (Insert User): " . $conn->error);
            }
            $stmt->bind_param("sssssss", $userID, $email, $hashedPassword, $firstName, $lastName, $phoneNumber, $role);

            if ($stmt->execute()) {
                // ✅ Automatically log in the user after signup
                $_SESSION['userID'] = $userID;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

                // ✅ Redirect to product listings page after successful signup
                header("Location: products_listing.php");
                exit();
            } else {
                $errorMessage = "Error during registration. Please try again.";
            }
        }
        $checkStmt->close();
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
    <title>Signup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?php include("authNav.php"); ?>
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh; background: #f8f9fa;">
        <div class="p-4 bg-white rounded shadow" style="width: 400px;">
            <h2 class="text-center">Signup</h2>
            <p class="text-center mt-1">
                Already have an account? <a href="login.php">Login</a>
            </p>
            <?php if (!empty($errorMessage)) : ?>
                <div class="alert alert-danger text-center"><?= $errorMessage; ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="firstName" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="firstName" name="firstName" required />
                </div>
                <div class="mb-3">
                    <label for="lastName" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="lastName" name="lastName" required />
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required />
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required />
                </div>
                <div class="mb-3">
                    <label for="phoneNumber" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" required />
                </div>
                <input type="hidden" name="role" value="user" />
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
