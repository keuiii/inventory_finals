<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
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

function addInventoryTransaction($con, $type, $product_id, $quantity, $remarks)
{
    $transactionTypes = [
        'Add' => 1,
        'Remove' => -1,
        'Sale' => -1,
        'Return' => 1,
        'Adjustment' => 0,
    ];
    $stmt = $con->prepare("INSERT INTO inventory_transactions (transaction_type, product_id, quantity, remarks, transaction_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$type, $product_id, $quantity, $remarks]);
    if ($type === 'Adjustment') {
        $con->prepare("UPDATE products SET product_stock = ? WHERE products_id = ?")->execute([$quantity, $product_id]);
    } elseif (isset($transactionTypes[$type])) {
        $stockChange = $transactionTypes[$type] * $quantity;
        $con->prepare("UPDATE products SET product_stock = product_stock + ? WHERE products_id = ?")->execute([$stockChange, $product_id]);
    }
}

$message = '';
$products = $con->query("SELECT products_id, product_name, product_stock, price FROM products")->fetchAll();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    if ($product_id && $quantity > 0) {
        // Add or update cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        $message = "Product added to cart.";
    }
}

if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    unset($_SESSION['cart'][$product_id]);
}

if (isset($_POST['place_order'])) {
    $staff_id = $_SESSION['user_id'];
    $items = $_SESSION['cart'];
    if (count($items) == 0) {
        $message = 'Cart is empty.';
    } else {
        $con->beginTransaction();
        try {
            $total = 0;
            // Calculate total
            foreach ($items as $pid => $qty) {
                foreach ($products as $prod) {
                    if ($prod['products_id'] == $pid) {
                        $total += $prod['price'] * $qty;
                    }
                }
            }
            // Insert order
            $stmt = $con->prepare("INSERT INTO orders (staff_id, order_date, total_amount, status) VALUES (?, NOW(), ?, 'Completed')");
            $stmt->execute([$staff_id, $total]);
            $order_id = $con->lastInsertId();

            // Insert order items & update stock (and inventory log)
            foreach ($items as $pid => $qty) {
                foreach ($products as $prod) {
                    if ($prod['products_id'] == $pid) {
                        $price = $prod['price'];
                        // Insert order item
                        $stmt = $con->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$order_id, $pid, $qty, $price]);
                        // Inventory transaction: Sale
                        addInventoryTransaction($con, 'Sale', $pid, $qty, "Order #$order_id Sale");
                    }
                }
            }
            $con->commit();
            $_SESSION['cart'] = [];
            $message = "Order placed successfully!";
        } catch (Exception $e) {
            $con->rollBack();
            $message = "Order failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales - Create Order</title>
    <style>
        body { font-family: Arial, sans-serif; background: #ecf0f1; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px #ccc; }
        h1, h2 { color: #2c3e50; }
        label { margin-top: 10px; display: block; }
        input, select { padding: 8px; width: 100%; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ccc;}
        input[type="number"] { width: 70px; display: inline-block; }
        input[type="submit"] { background: #2980b9; color: #fff; border: none; cursor: pointer; width: auto; }
        input[type="submit"]:hover { background: #3498db; }
        table { margin-top: 30px; border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #2980b9; color: #fff; }
        .success-message { color: green; margin-top: 10px; }
        .cart-actions button { background: none; border: none; color: #e74c3c; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h1>Create Sales Order</h1>
    <?php if ($message): ?>
        <p class="success-message"><?= $message ?></p>
    <?php endif; ?>
    <form method="POST" style="margin-bottom:30px;">
        <label>Product</label>
        <select name="product_id" required>
            <option value="">Select Product</option>
            <?php foreach ($products as $prod): ?>
                <option value="<?= $prod['products_id'] ?>">
                    <?= htmlspecialchars($prod['product_name']) ?> (Stock: <?= $prod['product_stock'] ?>, ₱<?= number_format($prod['price'],2) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <label>Quantity</label>
        <input type="number" name="quantity" min="1" max="999" required>
        <input type="submit" name="add_to_cart" value="Add to Cart">
    </form>

    <h2>Cart</h2>
    <form method="POST">
    <table>
        <thead>
            <tr>
                <th>Product</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th><th>Remove</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $cart = $_SESSION['cart'];
        $total = 0;
        foreach ($cart as $pid => $qty):
            foreach ($products as $prod) {
                if ($prod['products_id'] == $pid) {
                    $subtotal = $prod['price'] * $qty;
                    $total += $subtotal;
        ?>
            <tr>
                <td><?= htmlspecialchars($prod['product_name']) ?></td>
                <td><?= $qty ?></td>
                <td>₱<?= number_format($prod['price'],2) ?></td>
                <td>₱<?= number_format($subtotal,2) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                        <input type="submit" name="remove_from_cart" value="Remove">
                    </form>
                </td>
            </tr>
        <?php } } endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="text-align:right;">Total:</th>
                <th colspan="2">₱<?= number_format($total,2) ?></th>
            </tr>
        </tfoot>
    </table>
    </form>

    <form method="POST">
        <input type="submit" name="place_order" value="Place Order">
    </form>
    <br>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>