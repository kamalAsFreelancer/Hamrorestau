<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) exit("Access Denied");

$restaurantId = $_SESSION['restaurant_id'];

// Handle cancel and complete actions
if (isset($_GET['cancel_item'])) {
    $oid = (int)$_GET['cancel_item'];
    $conn->query("UPDATE orders SET status='Cancelled' WHERE id=$oid AND restaurant_id=$restaurantId");
    header("Location: orders.php");
    exit;
}

if (isset($_GET['complete_order'])) {
    $oid = (int)$_GET['complete_order'];
    $conn->query("UPDATE orders SET status='Completed' WHERE id=$oid AND restaurant_id=$restaurantId");
    header("Location: orders.php");
    exit;
}

// Fetch orders grouped by table/place
$stmt = $conn->prepare("
    SELECT o.id as order_id, o.table_id, o.place, o.status AS order_status, t.table_number,
           m.name AS menu_name, oi.quantity, oi.price, oi.status AS item_status
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menus m ON oi.menu_id = m.id
    WHERE o.restaurant_id = ?
    ORDER BY o.place, t.table_number, o.id
");
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$result = $stmt->get_result();

$ordersByTable = [];
while ($row = $result->fetch_assoc()) {
    $tableKey = $row['place'] . ' - Table ' . $row['table_number'];
    $ordersByTable[$tableKey][] = $row;
}

include('../includes/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h1>Manage Orders</h1>

    <?php foreach ($ordersByTable as $tableKey => $orders): ?>
        <h3><?= htmlspecialchars($tableKey) ?></h3>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th>Menu Item</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php $total = 0; ?>
            <?php foreach ($orders as $order): ?>
                <?php $lineTotal = $order['price'] * $order['quantity']; $total += $lineTotal; ?>
                <tr>
                    <td><?= htmlspecialchars($order['menu_name']) ?></td>
                    <td><?= $order['quantity'] ?></td>
                    <td>$<?= number_format($order['price'], 2) ?></td>
                    <td><?= htmlspecialchars($order['item_status']) ?></td>
                    <td><a href="?cancel_item=<?= $order['order_id'] ?>" onclick="return confirm('Cancel this order?')">Cancel</a></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Total:</strong></td>
                <td><strong>$<?= number_format($total, 2) ?></strong></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: right;">
                    <a href="?complete_order=<?= $orders[0]['order_id'] ?>" onclick="return confirm('Mark order as completed?')">Mark as Completed</a>
                    <a href="generate_invoice.php?id=<?= $order['id'] ?>">🧾 Generate Bill</a>

                </td>
            </tr>
        </table>
        <br>
    <?php endforeach; ?>
</div>

<?php include('../includes/footer.php'); ?>
