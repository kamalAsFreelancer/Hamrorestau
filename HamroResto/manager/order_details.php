<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) {
    exit("Access Denied");
}

if (!isset($_GET['order_id'])) {
    exit("Order ID is missing");
}

$orderId = (int)$_GET['order_id'];
$restaurantId = (int)$_SESSION['restaurant_id'];

// Fetch order info
$orderStmt = $conn->prepare("
    SELECT o.id, o.customer_name, o.total_amount, o.status, t.table_number, o.created_at, o.cancelled_at
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    WHERE o.id = ? AND o.restaurant_id = ?
");
$orderStmt->bind_param("ii", $orderId, $restaurantId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    exit("Order not found or access denied");
}
$order = $orderResult->fetch_assoc();

// Handle item status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['new_status'])) {
    $itemId = (int)$_POST['item_id'];
    $newStatus = $_POST['new_status'];

    $validStatuses = ['pending', 'preparing', 'ready', 'served'];
    if (!in_array($newStatus, $validStatuses)) {
        die("Invalid status value");
    }

    $updateStmt = $conn->prepare("UPDATE order_items SET status = ? WHERE id = ? AND order_id = ?");
    $updateStmt->bind_param("sii", $newStatus, $itemId, $orderId);
    $updateStmt->execute();

    header("Location: order_details.php?order_id=$orderId");
    exit();
}

// Fetch order items
$itemsStmt = $conn->prepare("
    SELECT oi.id, m.name, oi.quantity, oi.price, oi.status
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result();

include('header/header.php');
include('sidebar.php');
?>
<style>
    button {
    padding: 10px 20px;
    margin-right: 5px;
    border: none;
    text-decoration: none;
    border-radius: 6px;
    background-color: #3498db;
    color: white;
    cursor: pointer;

}
</style>

<div class="main-content">
    <h1>Order Details - #<?= $order['id'] ?></h1>
    <p><strong>Table Number:</strong> <?= htmlspecialchars($order['table_number']) ?></p>
    <p><strong>Order Status:</strong> <?= ucfirst(htmlspecialchars($order['status'])) ?></p>
    <p><strong>Order Created:</strong> <?= $order['created_at'] ?></p>
    <?php if ($order['cancelled_at']) : ?>
        <p><strong>Cancelled At:</strong> <?= $order['cancelled_at'] ?></p>
    <?php endif; ?>
    <p><strong>Total Amount:</strong> Rs.<?= number_format($order['total_amount'], 2) ?></p>

    <h2>Ordered Items</h2>
    <table border="1" cellpadding="8" style="width:100%; max-width:800px;">
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Price (each)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $orderItems->fetch_assoc()) : ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>Rs.<?= number_format($item['price'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>    
    </table>

    <br>

    <!-- Print Bill Button -->
    <form action="print_bill.php" method="GET" target="_blank" style="display:inline;">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <button type="submit">🖨️ Print Bill</button>
    </form>

    &nbsp;&nbsp;

    <br><br>

    <a href="orders.php"><button>← Back to Orders</button></a>
</div>

<?php include('../includes/footer.php'); ?>
