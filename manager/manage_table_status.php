<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('manager');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reservations.php');
    exit;
}

$tableId = filter_input(INPUT_POST, 'table_id', FILTER_VALIDATE_INT);
$newStatus = $_POST['new_status'] ?? '';
$validStatuses = ['available', 'reserved', 'occupied'];
$restaurantId = (int)($_SESSION['restaurant_id'] ?? 0);

if (!$tableId || !in_array($newStatus, $validStatuses, true) || !$restaurantId) {
    http_response_code(400);
    exit('Invalid request.');
}

if ($newStatus === 'available') {
    $stmt = $conn->prepare('UPDATE tables SET status = ?, reserved_by = NULL, reserved_at = NULL WHERE id = ? AND restaurant_id = ?');
    $stmt->bind_param('sii', $newStatus, $tableId, $restaurantId);
} elseif ($newStatus === 'reserved') {
    $reservedBy = $_SESSION['username'] ?? 'Manager';
    $stmt = $conn->prepare('UPDATE tables SET status = ?, reserved_by = ?, reserved_at = NOW() WHERE id = ? AND restaurant_id = ?');
    $stmt->bind_param('ssii', $newStatus, $reservedBy, $tableId, $restaurantId);
} else {
    $stmt = $conn->prepare('UPDATE tables SET status = ?, reserved_by = NULL, reserved_at = NULL WHERE id = ? AND restaurant_id = ?');
    $stmt->bind_param('sii', $newStatus, $tableId, $restaurantId);
}

if (!$stmt->execute() || $stmt->affected_rows < 1) {
    http_response_code(404);
    exit('Table not found or was not changed.');
}

header('Location: reservations.php?message=' . urlencode('Table status updated successfully'));
exit;
