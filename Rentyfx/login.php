<?php
session_start();
include("db_connect.php"); // Database connection file

$errorMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $errorMessage = "Email and password must be entered.";
    } else {
        // Fetch user details, including role
        $stmt = $conn->prepare("SELECT userID, role, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // ✅ Store user ID and role in session
                $_SESSION['userID'] = $row['userID'];
                $_SESSION['role'] = $row['role']; // 🔥 Fix: Store role in session
                
                header("Location: products_listing.php"); // Redirect to main page
                exit();
            } else {
                $errorMessage = "Invalid password.";
            }
        } else {
            $errorMessage = "User not found.";
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
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById("loginForm");

            form.addEventListener("submit", function (event) {
                const email = document.getElementById("email").value.trim();
                const password = document.getElementById("password").value.trim();
                const errorMessage = document.getElementById("error-message");

                errorMessage.textContent = ""; // Clear previous errors

                if (!email || !password) {
                    errorMessage.textContent = "Email and password must be entered.";
                    event.preventDefault();
                }
            });
        });
    </script>
</head>
<body>
<?php include("authNav.php"); ?>
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh; background: #f8f9fa;">
        <div class="p-4 bg-white rounded shadow" style="width: 400px;">
            <h2 class="text-center">Welcome Back</h2>
            <p class="text-center mt-1">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </p>
            <?php if (!empty($errorMessage)) : ?>
                <div class="alert alert-danger text-center"><?= $errorMessage; ?></div>
            <?php endif; ?>
            <form id="loginForm" action="" method="POST">
                <input
                    type="email"
                    name="email"
                    id="email"
                    placeholder="Email"
                    class="form-control mb-3"
                    required
                />
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Password"
                    class="form-control mb-3"
                    required
                />
                <button type="submit" class="btn btn-primary w-100">Log In</button>
                <div id="error-message" class="text-danger mt-3 text-center"></div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
