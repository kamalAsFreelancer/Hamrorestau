<?php
session_start();
include("../includes/db.php");

// Check if user is authenticated and has proper role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['kitchen', 'bartender', 'manager'])) {
    die("Unauthorized access.");
}

// Check required POST fields
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    die("Missing data.");
}

$orderId = intval($_POST['id']);
$newStatus = $_POST['status'];
$restaurantId = $_SESSION['restaurant_id'];

// Validate status
$validStatuses = ['preparing', 'ready'];
if (!in_array($newStatus, $validStatuses)) {
    die("Invalid status.");
}

// Update the order status
$stmt = $conn->prepare("UPDATE order_items SET status = ? WHERE id = ? ");
$stmt->bind_param("si", $newStatus, $orderId);

if ($stmt->execute()) {
    header("Location: dashboard.php?status_updated=1");
    exit();
} else {
    echo "Failed to update order status.";
}
?>
