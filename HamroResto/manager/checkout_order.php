<?php
include("../includes/auth.php");
requireLogin();

if (!in_array($_SESSION['role'], ['waiter', 'manager'])) {
    exit("Access Denied");
}

include("../includes/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    exit("Invalid request");
}

$orderId = intval($_POST['order_id']);
$restaurantId = (int)$_SESSION['restaurant_id'];

// Fetch the order and its table_id
$stmt = $conn->prepare("SELECT table_id, status FROM orders WHERE id = ? AND restaurant_id = ?");
$stmt->bind_param("ii", $orderId, $restaurantId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit("Order not found");
}

$order = $result->fetch_assoc();

// Only allow checkout if order status is one of these:
$allowedStatuses = ['completed', 'submitted', 'ready'];
if (!in_array($order['status'], $allowedStatuses)) {
    exit("Order cannot be checked out in its current status");
}

$conn->begin_transaction();

try {
    // Update order status to 'checked_out'
    $updateOrder = $conn->prepare("UPDATE orders SET status = 'checked_out' WHERE id = ?");
    $updateOrder->bind_param("i", $orderId);
    $updateOrder->execute();

    // Free the table (set status to 'Available')
    $updateTable = $conn->prepare("UPDATE tables SET status = 'Available' WHERE id = ?");
    $updateTable->bind_param("i", $order['table_id']);
    $updateTable->execute();

    $conn->commit();

    header("Location: orders.php?message=checkout_success");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    exit("Checkout failed: " . $e->getMessage());
}
