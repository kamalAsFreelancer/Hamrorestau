<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

// Access control: only waiter and manager
if (!in_array($_SESSION['role'], ['waiter', 'manager'])) {
    exit("Access Denied");
}

if (!isset($_GET['table_id'])) {
    exit("Table ID is missing.");
}

$tableId = intval($_GET['table_id']);
$restaurantId = $_SESSION['restaurant_id'];

// Fetch latest order for the table
$orderStmt = $conn->prepare("
    SELECT id, status, created_at
    FROM orders
    WHERE table_id = ? AND restaurant_id = ? AND status IN ('pending', 'preparing', 'ready')
    ORDER BY created_at DESC
    LIMIT 1
");
$orderStmt->bind_param("ii", $tableId, $restaurantId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    exit("No orders found for this table.");
}

$order = $orderResult->fetch_assoc();

// Fetch order items with status
$itemStmt = $conn->prepare("
    SELECT oi.id, oi.quantity, oi.price, oi.status, m.name 
    FROM order_items oi 
    JOIN menus m ON oi.menu_id = m.id 
    WHERE oi.order_id = ?
");
$itemStmt->bind_param("i", $order['id']);
$itemStmt->execute();
$itemsResult = $itemStmt->get_result();

$totalAmount = 0;
$allReady = true;
$orderItems = [];

while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
    $subtotal = $item['price'] * $item['quantity'];
    $totalAmount += $subtotal;

    if ($item['status'] !== 'ready') {
        $allReady = false;
    }
}

// Auto-update order status based on item statuses
$newStatus = $allReady ? 'ready' : 'pending';
if ($order['status'] !== $newStatus) {
    $updateStatusStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $updateStatusStmt->bind_param("si", $newStatus, $order['id']);
    $updateStatusStmt->execute();
    $order['status'] = $newStatus; // Reflect change in current view
}

include('../includes/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h2>Orders for Table #<?= htmlspecialchars($tableId) ?></h2>
    <p>Status: <strong><?= htmlspecialchars(ucfirst($order['status'])) ?></strong></p>
    <p>Order Id: #<strong><?= htmlspecialchars(ucfirst($order['id'])) ?></strong></p>


    <?php if (empty($orderItems)): ?>
        <p>No items in this order.</p>
    <?php else: ?>
        <table width="100%" border="1" cellpadding="5" cellspacing="0" style="max-width:700px; margin-bottom:20px;">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price (Rs)</th>
                    <th>Qty</th>
                    <th>Subtotal (Rs)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= number_format($item['price'], 2) ?></td>
                    <td><?= intval($item['quantity']) ?></td>
                    <td><?= number_format($subtotal, 2) ?></td>
                    <td><?= htmlspecialchars($item['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                    <td colspan="2" style="font-weight: bold;">Rs. <?= number_format($totalAmount, 2) ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($order['status'] === 'ready'): ?>
        <form method="POST" action="checkout_order.php" style="margin-top: 20px;">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
            <input type="hidden" name="table_id" value="<?= htmlspecialchars($tableId) ?>">
            <button type="submit" style="padding: 12px 20px; background: #27ae60; color: white; border: none; border-radius: 8px; cursor: pointer;">
                Checkout & Clear Bill
            </button>
        </form>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
