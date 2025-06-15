<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$full_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$dsn = 'mysql:host=localhost;dbname=inventory_db';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];

$con = new PDO($dsn, $username, $password, $options);

// Count stats
$totalProducts = $con->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCategories = $con->query("SELECT COUNT(DISTINCT category) FROM products")->fetchColumn();
$totalSalesToday = $con->query("SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = CURDATE()")->fetchColumn() ?? 0;
$totalUsers = $con->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background-color: #ecf0f1;
        }

        .sidebar {
            width: 200px;
            height: 100vh;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
            position: relative;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 4px;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #34495e;
        }

        .sidebar ul li.has-submenu > a {
            cursor: pointer;
        }

        .sidebar ul .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: #34495e;
            margin-top: 5px;
            border-radius: 4px;
            padding: 0;
        }

        .sidebar ul li.has-submenu:hover > .submenu {
            max-height: 500px; /* enough height for all submenu items */
        }

        .sidebar ul .submenu li a {
            padding: 10px 20px;
            display: block;
            color: #fff;
            text-decoration: none;
        }

        .sidebar ul .submenu li a:hover {
            background-color: #2980b9;
        }

        .main-content {
            margin-left: 220px;
            padding: 20px;
        }

        .main-content h1 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            background: white;
            padding: 20px;
            flex: 1 1 200px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .card h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .card p {
            font-size: 24px;
            font-weight: bold;
            color: #2980b9;
        }

        .back-btn {
            display: inline-block;
            margin-top: 30px;
            background: #34495e;
            color: #fff;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="products.php">Products</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li class="has-submenu">
            <a href="#" style="background-color:#34495e;">Sales <span style="float:right;">&#9660;</span></a>
            <ul class="submenu">
                <li><a href="add_transaction.php">Inventory Transactions</a></li>
                <li><a href="sales.php">Sales Orders</a></li>
                <li><a href="sales_report.php">Sales Report</a></li>
            </ul>
        </li>
        <li><a href="suppliers.php">Suppliers</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <h1>Welcome, <?= htmlspecialchars($full_name); ?>!</h1>

    <div class="cards">
        <div class="card">
            <h3>Total Products</h3>
            <p><?= $totalProducts ?></p>
        </div>
        <div class="card">
            <h3>Total Categories</h3>
            <p><?= $totalCategories ?></p>
        </div>
        <div class="card">
            <h3>Total Sales Today</h3>
            <p>₱<?= number_format($totalSalesToday, 2) ?></p>
        </div>
        <div class="card">
            <h3>Total Users</h3>
            <p><?= $totalUsers ?></p>
        </div>
    </div>

    <a href="logout.php" class="back-btn" style="background:#e74c3c;">Logout</a>
</div>

</body>
</html>
