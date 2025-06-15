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

$message = '';
$transactionTypes = [
    'Add' => 1,
    'Remove' => -1,
    'Sale' => -1,
    'Return' => 1,
    'Adjustment' => 0,
];

function addInventoryTransaction($con, $type, $product_id, $quantity, $remarks)
{
    global $transactionTypes;
    // Insert transaction
    $stmt = $con->prepare("INSERT INTO inventory_transactions (transaction_type, product_id, quantity, remarks, transaction_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$type, $product_id, $quantity, $remarks]);
    // Update product stock
    if ($type === 'Adjustment') {
        $con->prepare("UPDATE products SET product_stock = ? WHERE products_id = ?")->execute([$quantity, $product_id]);
    } elseif (isset($transactionTypes[$type])) {
        $stockChange = $transactionTypes[$type] * $quantity;
        $con->prepare("UPDATE products SET product_stock = product_stock + ? WHERE products_id = ?")->execute([$stockChange, $product_id]);
    }
}

if (isset($_POST['add_transaction'])) {
    $type = $_POST['type'];
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    $remarks = $_POST['remarks'];
    if ($type && $product_id && $quantity > 0) {
        addInventoryTransaction($con, $type, $product_id, $quantity, $remarks);
        $message = "Transaction successfully added.";
    } else {
        $message = "Please fill out all fields.";
    }
}

$products = $con->query("SELECT products_id, product_name, product_stock FROM products")->fetchAll();
$stmt = $con->query("SELECT it.transaction_id, it.transaction_type, p.product_name, it.quantity, it.remarks, it.transaction_date FROM inventory_transactions it JOIN products p ON it.product_id = p.products_id ORDER BY it.transaction_date DESC");
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Inventory Transaction</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ecf0f1; }
        .container { margin: 40px auto; width: 800px; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px #ccc; }
        h1, h2 { color: #2c3e50; }
        label { display: block; margin-top: 15px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ccc; }
        input[type="submit"] { width: auto; background: #2980b9; color: #fff; border: none; margin-top: 20px; cursor: pointer; }
        input[type="submit"]:hover { background: #3498db; }
        .success-message { color: green; margin-top: 10px; }
        table { margin-top: 30px; border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #2980b9; color: #fff; }
        a { color: #2980b9; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
    <script>
    function toggleAdjustment(val) {
        const label = document.getElementById('quantityLabel');
        const input = document.getElementById('quantityInput');
        if (val === 'Adjustment') {
            label.textContent = 'New Stock Value';
            input.min = 0;
        } else {
            label.textContent = 'Quantity';
            input.min = 1;
        }
    }
    </script>
</head>
<body>
<div class="container">
    <h1>Add Inventory Transaction</h1>
    <?php if ($message): ?>
        <p class="success-message"><?= $message; ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Type</label>
        <select name="type" required onchange="toggleAdjustment(this.value)">
            <option value="">Select</option>
            <option value="Add">Add (Stock In)</option>
            <option value="Remove">Remove (Stock Out)</option>
            <option value="Sale">Sale</option>
            <option value="Return">Return</option>
            <option value="Adjustment">Adjustment</option>
        </select>

        <label>Product</label>
        <select name="product_id" required>
            <option value="">Select Product</option>
            <?php foreach ($products as $row): ?>
                <option value="<?= $row['products_id']; ?>">
                    <?= htmlspecialchars($row['product_name']); ?> (Stock: <?= $row['product_stock']; ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label id="quantityLabel">Quantity</label>
        <input type="number" name="quantity" id="quantityInput" min="1" required>
        <label>Remarks</label>
        <input type="text" name="remarks">
        <input type="submit" name="add_transaction" value="Save">
    </form>

    <h2>Transaction History</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Remarks</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?= $transaction['transaction_id']; ?></td>
                    <td><?= htmlspecialchars($transaction['product_name']); ?></td>
                    <td><?= $transaction['transaction_type']; ?></td>
                    <td><?= $transaction['quantity']; ?></td>
                    <td><?= htmlspecialchars($transaction['remarks']); ?></td>
                    <td><?= $transaction['transaction_date']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>