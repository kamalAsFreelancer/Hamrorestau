<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) {
    exit("Access Denied");
}

$restaurantId = (int)$_SESSION['restaurant_id'];

$orderIdFilter = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['pending', 'preparing', 'completed', 'cancelled', 'submitted', 'ready', 'checked_out'];
if (!in_array($statusFilter, $validStatuses)) {
    $statusFilter = '';
}

$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total for pagination
$countQuery = "SELECT COUNT(*) AS total FROM orders WHERE restaurant_id = ?";
$params = [$restaurantId];
$types = "i";

if ($orderIdFilter > 0) {
    $countQuery .= " AND id = ?";
    $params[] = $orderIdFilter;
    $types .= "i";
}
if ($statusFilter !== '') {
    $countQuery .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$stmtCount = $conn->prepare($countQuery);
$stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$totalOrders = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
$stmtCount->close();

$totalPages = ceil($totalOrders / $limit);

// Fetch orders
$query = "SELECT o.*, t.table_number 
          FROM orders o 
          LEFT JOIN tables t ON o.table_id = t.id 
          WHERE o.restaurant_id = ?";
$params = [$restaurantId];
$types = "i";

if ($orderIdFilter > 0) {
    $query .= " AND o.id = ?";
    $params[] = $orderIdFilter;
    $types .= "i";
}
if ($statusFilter !== '') {
    $query .= " AND o.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

include('header/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h1>Orders</h1>

    <form method="GET" action="orders.php" style="margin-bottom:20px;">
        <label for="order_id">Filter by Order ID:</label>
        <input type="number" name="order_id" id="order_id" value="<?= htmlspecialchars($orderIdFilter) ?>" />

        <label for="status">Filter by status:</label>
        <select name="status" id="status">
            <option value="">-- All --</option>
            <?php foreach ($validStatuses as $status): ?>
                <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                    <?= ucfirst($status) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Apply</button>
    </form>

    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Table Number</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="7" style="text-align:center;">No orders found.</td></tr>
            <?php else: ?>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= htmlspecialchars($order['table_number'] ?? 'N/A') ?></td>
                        <td><?= ucfirst($order['status']) ?></td>
                        <td>Rs.<?= number_format($order['total_amount'], 2) ?></td>
                        <td><?= $order['created_at'] ?></td>
                        <td>
                            <a href="order_details.php?order_id=<?= $order['id'] ?>">
                                <button type="button">View</button>
                            </a>

                            <?php if (!in_array($order['status'], ['completed', 'cancelled', 'checked_out'])): ?>
                                <form method="POST" action="update_order_status.php" style="display:inline;" onsubmit="return confirm('Cancel this order?');">
                                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="delete_btn">Cancel</button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($order['status'], ['completed', 'submitted', 'ready'])): ?>
                                <form method="POST" action="checkout_order.php" style="display:inline;" onsubmit="return confirm('Checkout this order and free the table?');">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="checkout_btn">Checkout</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
        <div style="margin-top:20px;">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?>
                    <strong><?= $p ?></strong>
                <?php else: ?>
                    <a href="?page=<?= $p ?><?= $orderIdFilter ? '&order_id=' . $orderIdFilter : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?>"><?= $p ?></a>
                <?php endif; ?>
                &nbsp;
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.checkout_btn {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 5px 8px;
    cursor: pointer;
    border-radius: 4px;
}
.checkout_btn:hover {
    background-color: #218838;
}
</style>

<?php include('../includes/footer.php'); ?>
