<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('manager');

header('Content-Type: application/json; charset=utf-8');

$restaurantId = (int)($_SESSION['restaurant_id'] ?? 0);
if (!$restaurantId) {
    http_response_code(400);
    echo json_encode(['error' => 'Restaurant is not assigned.']);
    exit;
}

$stmt = $conn->prepare("SELECT
    o.id,
    o.table_id,
    t.table_number,
    o.customer_name,
    o.status,
    o.total_amount,
    o.created_at
FROM orders o
LEFT JOIN tables t ON t.id = o.table_id AND t.restaurant_id = o.restaurant_id
WHERE o.restaurant_id = ?
  AND o.created_at >= NOW() - INTERVAL 15 SECOND
ORDER BY o.created_at DESC");
$stmt->bind_param('i', $restaurantId);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'id' => (int)$row['id'],
        'table_number' => $row['table_number'] ?? 'N/A',
        'customer_name' => $row['customer_name'] ?? '',
        'status' => $row['status'],
        'total_amount' => (float)$row['total_amount'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode($orders);
