<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$restaurantId = $_SESSION['restaurant_id'];

// Get orders updated in last 15 seconds
$stmt = $conn->prepare("
    SELECT o.id, o.table_number, o.status, o.updated_at,
           u.name AS customer_name,
           SUM(oi.quantity * oi.price) AS total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN users u ON o.customer_id = u.id
    WHERE o.restaurant_id = ?
      AND o.updated_at >= NOW() - INTERVAL 15 SECOND
    GROUP BY o.id
    ORDER BY o.updated_at DESC
");

$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

header('Content-Type: application/json');
echo json_encode($orders);
?>
