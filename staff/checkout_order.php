<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

// Only waiter and manager can checkout
if (!in_array($_SESSION['role'], ['waiter', 'manager'])) {
    exit("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    exit("Invalid request");
}

$orderId = intval($_POST['order_id']);
$restaurantId = $_SESSION['restaurant_id'];

// First, fetch the order and its table_id
$stmt = $conn->prepare("SELECT table_id, status FROM orders WHERE id = ? AND restaurant_id = ?");
$stmt->bind_param("ii", $orderId, $restaurantId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit("Order not found");
}

$order = $result->fetch_assoc();

// You can check if order is eligible for checkout here, for example:
if (!in_array($order['status'], ['completed', 'submitted', 'ready'])) {
    exit("Order cannot be checked out in its current status");
}

// Begin transaction to ensure consistency
$conn->begin_transaction();

try {
    // Update order status to 'checked_out'
    $updateOrder = $conn->prepare("UPDATE orders SET status = 'checked_out' WHERE id = ?");
    $updateOrder->bind_param("i", $orderId);
    $updateOrder->execute();

    // Update table status to 'Available'
    $updateTable = $conn->prepare("UPDATE tables SET status = 'Available' WHERE id = ?");
    $updateTable->bind_param("i", $order['table_id']);
    $updateTable->execute();

    $conn->commit();

    // Redirect or send success message
    header("Location: table_management.php?message=checkout_success");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    exit("Checkout failed: " . $e->getMessage());
}
?>
