<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) {
    exit("Access Denied");
}

// Ensure only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$orderId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';

if (!$orderId || $action !== 'cancel') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Request']);
    exit;
}

$restaurantId = $_SESSION['restaurant_id'];

// Cancel the order
$stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND restaurant_id = ?");
$stmt->bind_param("ii", $orderId, $restaurantId);

if ($stmt->execute()) {
    header("Location: orders.php?msg=Order Cancelled");
    exit;
} else {
    echo "Error cancelling order.";
}
