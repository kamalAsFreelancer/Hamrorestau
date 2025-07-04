<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");
include("../includes/header.php");

if (!checkRole('manager')) {
    exit("Access Denied");
}

// Get restaurant_id from session
$restaurantId = $_SESSION['restaurant_id'];

// Total Orders
$totalOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE restaurant_id = $restaurantId");
$totalOrders = $totalOrdersResult->fetch_assoc()['total'] ?? 0;

// Pending Orders
$pendingOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE restaurant_id = $restaurantId AND status = 'pending'");
$pendingOrders = $pendingOrdersResult->fetch_assoc()['total'] ?? 0;

// Completed Orders
$completedOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE restaurant_id = $restaurantId AND status = 'completed'");
$completedOrders = $completedOrdersResult->fetch_assoc()['total'] ?? 0;

// Total Staff
$totalStaffResult = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'staff' AND restaurant_id = $restaurantId");
$totalStaff = $totalStaffResult->fetch_assoc()['total'] ?? 0;

// Total Menu Items
$totalMenuResult = $conn->query("SELECT COUNT(*) AS total FROM menus WHERE restaurant_id = $restaurantId");
$totalMenu = $totalMenuResult->fetch_assoc()['total'] ?? 0;

// Recent orders (last 5)
$recentOrders = $conn->query("SELECT * FROM orders WHERE restaurant_id = $restaurantId ORDER BY created_at DESC LIMIT 5");

include('sidebar.php');  // your manager sidebar
?>

<div class="main-content">
  <h1>Manager Dashboard</h1>
  <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>

  <div class="dashboard-cards">
    <div class="card">
      <h3>Total Orders</h3>
      <p><?= $totalOrders ?></p>
    </div>
    <div class="card">
      <h3>Pending Orders</h3>
      <p><?= $pendingOrders ?></p>
    </div>
    <div class="card">
      <h3>Completed Orders</h3>
      <p><?= $completedOrders ?></p>
    </div>
    <div class="card">
      <h3>Total Staff</h3>
      <p><?= $totalStaff ?></p>
    </div>
    <div class="card">
      <h3>Total Menu Items</h3>
      <p><?= $totalMenu ?></p>
    </div>
  </div>

  <h2>Recent Orders</h2>
  <table border="1" cellpadding="5" cellspacing="0">
    <thead>
      <tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Status</th>
        <th>Total</th>
        <th>Created At</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($order = $recentOrders->fetch_assoc()): ?>
      <tr>
        <td><?= $order['id'] ?></td>
        <td><?= htmlspecialchars($order['customer_name']) ?></td>
        <td><?= htmlspecialchars(ucfirst($order['status'])) ?></td>
        <td>$<?= number_format($order['total'], 2) ?></td>
        <td><?= $order['created_at'] ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
