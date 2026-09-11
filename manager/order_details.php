<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) exit("Access Denied");

$orderId = (int)($_GET['order_id'] ?? 0);
$restaurantId = (int)($_SESSION['restaurant_id'] ?? 0);
if (!$orderId || !$restaurantId) exit("Invalid order.");

$orderStmt = $conn->prepare("SELECT o.id, o.customer_name, o.total_amount, o.status, t.table_number, o.created_at, o.cancelled_at FROM orders o LEFT JOIN restaurant_tables t ON o.table_id = t.id WHERE o.id = ? AND o.restaurant_id = ?");
$orderStmt->bind_param("ii", $orderId, $restaurantId);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
if (!$order) exit("Order not found or access denied.");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['new_status'])) {
    $itemId = (int)$_POST['item_id'];
    $newStatus = $_POST['new_status'];
    $validStatuses = ['pending', 'preparing', 'ready', 'served', 'cancelled'];
    if (!in_array($newStatus, $validStatuses, true)) exit('Invalid status value');

    $updateStmt = $conn->prepare("UPDATE order_items oi JOIN orders o ON oi.order_id=o.id SET oi.status=? WHERE oi.id=? AND oi.order_id=? AND o.restaurant_id=?");
    $updateStmt->bind_param("siii", $newStatus, $itemId, $orderId, $restaurantId);
    $updateStmt->execute();
    header("Location: order_details.php?order_id=$orderId");
    exit;
}

$itemsStmt = $conn->prepare("SELECT oi.id, oi.menu_name AS name, oi.quantity, oi.unit_price, oi.status FROM order_items oi WHERE oi.order_id = ? ORDER BY oi.id");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result();

include('header/header.php');
include('sidebar.php');
?>
<div class="main-content">
    <h1>Order Details - #<?= $order['id'] ?></h1>
    <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name'] ?? 'Walk-in') ?></p>
    <p><strong>Table:</strong> <?= htmlspecialchars($order['table_number'] ?? 'N/A') ?></p>
    <p><strong>Order Status:</strong> <?= ucfirst(htmlspecialchars($order['status'])) ?></p>
    <p><strong>Order Created:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
    <?php if ($order['cancelled_at']): ?><p><strong>Cancelled At:</strong> <?= htmlspecialchars($order['cancelled_at']) ?></p><?php endif; ?>
    <p><strong>Total Amount:</strong> Rs.<?= number_format((float)$order['total_amount'], 2) ?></p>

    <h2>Ordered Items</h2>
    <table border="1" cellpadding="8" style="width:100%;max-width:800px;">
        <thead><tr><th>Item Name</th><th>Quantity</th><th>Price (each)</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($item = $orderItems->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td>Rs.<?= number_format((float)$item['unit_price'], 2) ?></td>
                <td><?= htmlspecialchars(ucfirst($item['status'])) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <br>
    <form action="print_bill.php" method="GET" target="_blank" style="display:inline"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit">Print Bill</button></form>
    <a href="orders.php"><button type="button">Back to Orders</button></a>
</div>
<?php include('../includes/footer.php'); ?>
