<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

if (!in_array($_SESSION['role'] ?? '', ['kitchen', 'bartender', 'manager'], true)) {
    http_response_code(403);
    exit('Unauthorized access.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$itemId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$newStatus = $_POST['status'] ?? '';
$restaurantId = (int)($_SESSION['restaurant_id'] ?? 0);
$validStatuses = ['preparing', 'ready'];

if (!$itemId || !$restaurantId || !in_array($newStatus, $validStatuses, true)) {
    http_response_code(400);
    exit('Invalid request.');
}

// The item must belong to the logged-in restaurant.
$stmt = $conn->prepare('UPDATE order_items oi INNER JOIN orders o ON o.id = oi.order_id SET oi.status = ? WHERE oi.id = ? AND o.restaurant_id = ?');
$stmt->bind_param('sii', $newStatus, $itemId, $restaurantId);

if (!$stmt->execute() || $stmt->affected_rows < 1) {
    http_response_code(404);
    exit('Order item not found.');
}

header('Location: dashboard.php?status_updated=1');
exit;
