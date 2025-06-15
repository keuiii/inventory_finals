<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$dsn = 'mysql:host=localhost;dbname=inventory_db';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];
$con = new PDO($dsn, $username, $password, $options);

// Show all orders with total and status
$orders = $con->query("
    SELECT o.order_id, o.order_date, o.total_amount, o.status
    FROM orders o
    ORDER BY o.order_date DESC
")->fetchAll();

// Sales summary per day/month
$salesByDay = $con->query("
    SELECT DATE(order_date) as day, SUM(total_amount) as total
    FROM orders GROUP BY day ORDER BY day DESC
")->fetchAll();

$salesByMonth = $con->query("
    SELECT DATE_FORMAT(order_date, '%Y-%m') as month, SUM(total_amount) as total
    FROM orders GROUP BY month ORDER BY month DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        body { font-family: Arial, sans-serif; background: #ecf0f1; }
        .container { margin: 40px auto; width: 1000px; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px #ccc; }
        h1, h2 { color: #2c3e50; }
        table { margin-top: 30px; border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #2980b9; color: #fff; }
        a { color: #2980b9; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>Sales Report</h1>
    <h2>All Orders</h2>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Total Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $row): ?>
            <tr>
                <td><?= $row['order_id'] ?></td>
                <td><?= $row['order_date'] ?></td>
                <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                <td><?= $row['status'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Sales by Day</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Total Sales</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($salesByDay as $row): ?>
            <tr>
                <td><?= $row['day'] ?></td>
                <td>₱<?= number_format($row['total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Sales by Month</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Total Sales</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($salesByMonth as $row): ?>
            <tr>
                <td><?= $row['month'] ?></td>
                <td>₱<?= number_format($row['total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>