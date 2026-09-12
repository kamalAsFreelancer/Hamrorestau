<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

$orderId = $_GET['id'] ?? null;
$restaurantId = $_SESSION['restaurant_id'];

// Fetch order and items
$stmt = $conn->prepare("
    SELECT o.id, o.created_at, t.table_number
    FROM orders o 
    JOIN tables t ON o.table_id = t.id
    WHERE o.id = ? AND o.restaurant_id = ?
");
$stmt->bind_param("ii", $orderId, $restaurantId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

$itemStmt = $conn->prepare("
    SELECT m.name, m.price, oi.quantity 
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
");
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$items = $itemStmt->get_result();

// Settings (tax/service charge)
$tax = 10; $service = 5; // Hardcoded; or fetch from DB if settings table

// Totals
$subtotal = 0;
foreach ($items as $row) {
    $subtotal += $row['price'] * $row['quantity'];
}
$taxAmount = $subtotal * ($tax / 100);
$serviceAmount = $subtotal * ($service / 100);
$total = $subtotal + $taxAmount + $serviceAmount;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?= $order['id'] ?></title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: auto; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>🧾 Invoice #<?= $order['id'] ?></h2>
    <p><strong>Date:</strong> <?= $order['created_at'] ?></p>
    <p><strong>Table:</strong> <?= $order['table_number'] ?></p>

    <table>
        <thead>
            <tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td>$<?= number_format($item['price'], 2) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="right">Subtotal</td><td>$<?= number_format($subtotal, 2) ?></td></tr>
            <tr><td colspan="3" class="right">Tax (<?= $tax ?>%)</td><td>$<?= number_format($taxAmount, 2) ?></td></tr>
            <tr><td colspan="3" class="right">Service (<?= $service ?>%)</td><td>$<?= number_format($serviceAmount, 2) ?></td></tr>
            <tr><th colspan="3" class="right">Total</th><th>$<?= number_format($total, 2) ?></th></tr>
        </tfoot>
    </table>

    <br>
    <div style="text-align: center;">
        <button onclick="window.print()">🖨️ Print</button>
        <button onclick="window.location.href='orders.php'">🔙 Back</button>
    </div>
</body>
</html>
