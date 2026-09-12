<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

// Only waiter and manager can place orders
if (!in_array($_SESSION['role'], ['waiter', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Read raw JSON input
$input = json_decode(file_get_contents("php://input"), true);

// Validate inputs
$menuId = isset($input['menu_id']) ? intval($input['menu_id']) : 0;
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;
$tableId = isset($input['table_id']) ? intval($input['table_id']) : 0;
$restaurantId = $_SESSION['restaurant_id'];

if ($menuId <= 0 || $quantity <= 0 || $tableId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// ✅ Check if there is an existing open order (pending, preparing, or ready)
$orderStmt = $conn->prepare("SELECT id FROM orders WHERE table_id = ? AND restaurant_id = ? AND status IN ('pending', 'preparing', 'ready') ORDER BY created_at DESC LIMIT 1");
$orderStmt->bind_param("ii", $tableId, $restaurantId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderRow = $orderResult->fetch_assoc()) {
    $orderId = $orderRow['id'];
} else {
    // Create new order
    $insertOrderStmt = $conn->prepare("INSERT INTO orders (table_id, restaurant_id, status, created_at, total_amount) VALUES (?, ?, 'pending', NOW(), 0)");
    $insertOrderStmt->bind_param("ii", $tableId, $restaurantId);
    if (!$insertOrderStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create order']);
        exit;
    }
    $orderId = $insertOrderStmt->insert_id;
}

// ✅ Get price of the menu item
$priceStmt = $conn->prepare("SELECT price FROM menus WHERE id = ? AND restaurant_id = ?");
$priceStmt->bind_param("ii", $menuId, $restaurantId);
$priceStmt->execute();
$priceResult = $priceStmt->get_result();

if (!$menu = $priceResult->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Invalid menu item']);
    exit;
}

$price = $menu['price'];
$subtotal = $price * $quantity;

// ✅ Add item to order_items
$itemStmt = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
$itemStmt->bind_param("iiid", $orderId, $menuId, $quantity, $price);
if (!$itemStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to add item']);
    exit;
}

// ✅ Update total_amount in orders table
$updateTotalStmt = $conn->prepare("UPDATE orders SET total_amount = total_amount + ? WHERE id = ?");
$updateTotalStmt->bind_param("di", $subtotal, $orderId);
$updateTotalStmt->execute();

echo json_encode(['success' => true, 'message' => 'Item added to order and total updated']);
?>
