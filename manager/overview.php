<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) exit("Access Denied");

$restaurantId = $_SESSION['restaurant_id'];

// Basic Info
$restStmt = $conn->prepare("SELECT name, location FROM restaurants WHERE id = ?");
$restStmt->bind_param("i", $restaurantId);
$restStmt->execute();
$rest = $restStmt->get_result()->fetch_assoc();

// Total Orders Today
$orderStmt = $conn->prepare("SELECT COUNT(*) AS total_orders, SUM(total_amount) AS total_income 
                             FROM orders 
                             WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()");
$orderStmt->bind_param("i", $restaurantId);
$orderStmt->execute();
$orderData = $orderStmt->get_result()->fetch_assoc();

// Tables Info
$tableStmt = $conn->prepare("SELECT 
    COUNT(*) AS total, 
    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied, 
    SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) AS vacant 
    FROM tables 
    WHERE restaurant_id = ?");
$tableStmt->bind_param("i", $restaurantId);
$tableStmt->execute();
$tableData = $tableStmt->get_result()->fetch_assoc();

// Active Staff
$staffStmt = $conn->prepare("SELECT COUNT(*) AS staff_count FROM users WHERE restaurant_id = ? AND role IN ('waiter','bartender','chef')");
$staffStmt->bind_param("i", $restaurantId);
$staffStmt->execute();
$staffCount = $staffStmt->get_result()->fetch_assoc()['staff_count'];

// Department Order Count
$deptStmt = $conn->prepare("
    SELECT c.department, COUNT(*) AS count 
    FROM order_items oi 
    JOIN menus m ON oi.menu_id = m.id 
    JOIN categories c ON m.category_id = c.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.restaurant_id = ? AND DATE(o.created_at) = CURDATE()
    GROUP BY c.department");
$deptStmt->bind_param("i", $restaurantId);
$deptStmt->execute();
$deptResult = $deptStmt->get_result();

$departments = ['kitchen' => 0, 'bar' => 0, 'desert' => 0];
while ($row = $deptResult->fetch_assoc()) {
    $departments[$row['department']] = $row['count'];
}

// Recent Orders
$recentOrders = $conn->prepare("
    SELECT o.id, o.total_amount, o.created_at, t.table_number 
    FROM orders o 
    LEFT JOIN tables t ON o.table_id = t.id 
    WHERE o.restaurant_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5");
$recentOrders->bind_param("i", $restaurantId);
$recentOrders->execute();
$recentOrdersResult = $recentOrders->get_result();

include("header/header.php");
include("sidebar.php");
?>

<div class="main-content">
    <h1>📊 Restaurant Overview</h1>

    <section class="overview-grid">
        <div class="card">
            <h3>🏠 Restaurant</h3>
            <p><strong><?= htmlspecialchars($rest['name']) ?></strong></p>
            <p><?= htmlspecialchars($rest['location']) ?></p>
        </div>
        <div class="card">
            <h3>📦 Orders Today</h3>
            <p>Total: <?= $orderData['total_orders'] ?></p>
            <p>Income: Rs. <?= number_format($orderData['total_income'] ?: 0, 2) ?></p>
        </div>
        <div class="card">
            <h3>🪑 Tables</h3>
            <p>Occupied: <?= $tableData['occupied'] ?> / <?= $tableData['total'] ?></p>
            <p>Vacant: <?= $tableData['vacant'] ?></p>
        </div>
        <div class="card">
            <h3>👥 Staff</h3>
            <p>Active Staff: <?= $staffCount ?></p>
        </div>
    </section>

    <h2>📍 Department Activity (Today)</h2>
    <div class="overview-grid">
        <div class="card"><h4>🍳 Kitchen</h4><p><?= $departments['kitchen'] ?></p></div>
        <div class="card"><h4>🍷 Bar</h4><p><?= $departments['bar'] ?></p></div>
        <div class="card"><h4>🍰 Desert</h4><p><?= $departments['desert'] ?></p></div>
    </div>

    <h2>🕓 Recent Orders</h2>
    <table border="1" cellpadding="8" width="100%">
        <thead>
            <tr><th>ID</th><th>Table</th><th>Total</th><th>Time</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $recentOrdersResult->fetch_assoc()): ?>
            <tr>
                <td>#<?= $row['id'] ?></td>
                <td><?= $row['table_number'] ?: 'N/A' ?></td>
                <td>Rs. <?= number_format($row['total_amount'], 2) ?></td>
                <td><?= date("h:i A", strtotime($row['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include("../includes/footer.php"); ?>
