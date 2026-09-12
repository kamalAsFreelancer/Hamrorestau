<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

// Only allow staff to access this page
if (!in_array($_SESSION['role'], ['waiter', 'kitchen', 'bartender'])) {
    exit("Access Denied");
}

$staffId = $_SESSION['user_id'];
$restaurantId = $_SESSION['restaurant_id'];

// Fetch staff role
$stmt = $conn->prepare("SELECT role FROM staff WHERE id = ? AND restaurant_id = ?");
$stmt->bind_param("ii", $staffId, $restaurantId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    exit("Staff not found.");
}

$staff = $result->fetch_assoc();
$role = $staff['role'];

// Role-based data fetching can be done here
// Example: For waiter, fetch tables; for kitchen, fetch pending kitchen orders; for bartender, fetch drink orders

// Fetch tables (for waiter)
$tables = [];
if ($role === 'waiter') {
    $tableStmt = $conn->prepare("SELECT id, table_number, status FROM tables WHERE restaurant_id = ?");
    $tableStmt->bind_param("i", $restaurantId);
    $tableStmt->execute();
    $tables = $tableStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch kitchen orders (for kitchen)
$kitchenOrders = [];
if ($role === 'kitchen') {
    $kitchenStmt = $conn->prepare("
        SELECT o.table_id, oi.status, o.created_at,m.name,oi.id,oi.quantity,t.table_number
        FROM orders o
        JOIN order_items oi ON o.id=oi.order_id
        JOIN menus m ON oi.menu_id=m.id
        JOIN tables t ON o.table_id=t.id
        WHERE o.restaurant_id = ? AND oi.status IN ('pending', 'preparing')
        ORDER BY o.created_at DESC
    ");
    $kitchenStmt->bind_param("i", $restaurantId);
    $kitchenStmt->execute();
    $kitchenOrders = $kitchenStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch bartender orders (for bartender)
$bartenderOrders = [];
if ($role === 'bartender') {
    $bartenderStmt = $conn->prepare("
        SELECT o.table_id, oi.status, o.created_at,m.name,oi.id, oi.quantity
        FROM orders o
        JOIN order_items oi ON o.id=oi.order_id
        JOIN menus m ON oi.menu_id=m.id
        WHERE o.restaurant_id = ? AND oi.status IN ('pending', 'preparing')
        ORDER BY o.created_at DESC
    ");
    $bartenderStmt->bind_param("i", $restaurantId);
    $bartenderStmt->execute();
    $bartenderOrders = $bartenderStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

include('../includes/header.php');
include('sidebar.php');
?>

<div class="main-content">
  <h1>Staff Dashboard</h1>
  <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (Role: <?= htmlspecialchars(ucfirst($role)) ?>)</p>

  <?php if ($role === 'waiter'): ?>
    <h2>Manage Tables & Orders</h2>
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; margin-bottom: 20px;">
      <thead>
        <tr><th>Table Number</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($tables as $table): ?>
          <tr>
            <td><?= $table['table_number'] ?></td>
            <td><?= htmlspecialchars($table['status']) ?></td>
            <td>
              <a href="take_order.php?table_id=<?= $table['id'] ?>"><button>Take Order</button></a>
              <!-- You can add more actions here -->
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php elseif ($role === 'kitchen'): ?>
    <h2>Kitchen Orders</h2>
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; margin-bottom: 20px;">
      <thead>
        <tr><th>Order ID</th><th>Table</th><th>Name</th><th>Qty</th><th>Status</th><th>Created At</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($kitchenOrders as $order): ?>
          <tr>
            <td><?= $order['id'] ?></td>
            <td><?= $order['table_number'] ?></td>
            <td><?= $order['name'] ?></td>
            <td><?= $order['quantity'] ?></td>
            <td><?= htmlspecialchars($order['status']) ?></td>
            <td><?= $order['created_at'] ?></td>
            <td>
              <form method="POST" action="update_order_status.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <select name="status">
                  <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                  <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                </select>
                <button type="submit">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php elseif ($role === 'bartender'): ?>
    <h2>Bartender Orders</h2>
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; margin-bottom: 20px;">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Table</th>
          <th>Name</th>
          <th>Qty</th>
          <th>Status</th>
          <th>Created At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bartenderOrders as $order): ?>
          <tr>
            <td><?= $order['id'] ?></td>
            <td><?= $order['table_id'] ?></td>
            <td><?= $order['name'] ?></td>
            <td><?= htmlspecialchars($order['status']) ?></td>
            <td><?= $order['created_at'] ?></td>
            <td>
              <form method="POST" action="update_order_status.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <select name="status">
                  <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                  <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                </select>
                <button type="submit">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>Your role does not have a dashboard view yet.</p>
  <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
