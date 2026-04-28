

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --dark-accent: #2c3e50;
            --transition-speed: 0.3s;
            --hover-brightness: 1.15;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: var(--dark-accent);
            transition: all var(--transition-speed);
            z-index: 1000;
            box-shadow: 3px 0 20px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }

        .sidebar h4 {
            color: var(--primary-color);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-link {
            color: #ecf0f1 !important;
            padding: 12px 20px !important;
            border-radius: 8px;
            margin: 8px 0;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-link:before {
            content: '';
            position: absolute;
            left: -100%;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
        }

        .nav-link:hover:before {
            left: 0;
        }

        .nav-link.active {
            background: var(--primary-color) !important;
            transform: translateX(10px);
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header-nav {
            background: var(--dark-accent);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 900;
        }

        .header-nav .nav-wrapper {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-left: var(--sidebar-width);
        }

        .header-nav .nav-links {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }

        .header-nav .nav-links li {
            margin: 0 10px;
        }

        .header-nav .nav-links li a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }

        .header-nav .nav-user {
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>



<!-- Header Navigation -->
<header class="header-nav">
    <div class="nav-wrapper">
        <ul class="nav-links">
            <li><a href="products_listing.php">Home</a></li>
            <li><a href="#">About Us</a></li>
        </ul>
        <div class="nav-user">
            Hello, <?= isset($_SESSION['userName']) ? htmlspecialchars($_SESSION['userName']) : 'Guest' ?>
        </div>
    </div>
</header>

</body>
</html>
