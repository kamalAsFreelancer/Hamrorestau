<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('staff')) {
    exit("Access Denied");
}

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

// --- Prepare data for charts ---

// Daily Orders - last 7 days
$orderData = $conn->prepare("
    SELECT DATE(created_at) AS day, COUNT(*) AS total 
    FROM orders 
    WHERE restaurant_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");
$orderData->bind_param("i", $restaurantId);
$orderData->execute();
$orderData = $orderData->get_result();

$dailyLabels = [];
$dailyCounts = [];
while ($row = $orderData->fetch_assoc()) {
    $dailyLabels[] = $row['day'];
    $dailyCounts[] = (int)$row['total'];
}

// Revenue Report - last 7 days
$revenueStmt = $conn->prepare("
    SELECT DATE(o.created_at) AS day, IFNULL(SUM(oi.price * oi.quantity),0) AS revenue
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.restaurant_id = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(o.created_at)
    ORDER BY DATE(o.created_at) ASC
");
$revenueStmt->bind_param("i", $restaurantId);
$revenueStmt->execute();
$revenueData = $revenueStmt->get_result();

$revenueLabels = [];
$revenueTotals = [];
while ($row = $revenueData->fetch_assoc()) {
    $revenueLabels[] = $row['day'];
    $revenueTotals[] = (float)$row['revenue'];
}

// Top Selling Items - last 30 days
$topStmt = $conn->prepare("
    SELECT m.name, SUM(oi.quantity) AS sold
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.restaurant_id = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY m.id
    ORDER BY sold DESC
    LIMIT 5
");
$topStmt->bind_param("i", $restaurantId);
$topStmt->execute();
$topData = $topStmt->get_result();

$topLabels = [];
$topSold = [];
while ($row = $topData->fetch_assoc()) {
    $topLabels[] = $row['name'];
    $topSold[] = (int)$row['sold'];
}

include('../includes/header.php');
include('sidebar.php');
?>

<div class="main-content">
  <h1>Staff Dashboard</h1>
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
  <table border="1" cellpadding="5" cellspacing="0" style="width:100%; margin-bottom: 40px;">
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
        <td>$<?= number_format($order['total_amount'], 2) ?></td>
        <td><?= $order['created_at'] ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Chart.js Charts -->
  <h2>📈 Daily Orders (Last 7 Days)</h2>
  <canvas id="ordersChart" height="100"></canvas>

  <h2>💵 Revenue Report (Last 7 Days)</h2>
  <canvas id="revenueChart" height="100"></canvas>

  <h2>🔥 Top Selling Items (Last 30 Days)</h2>
  <canvas id="topItemsChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dailyLabels = <?= json_encode($dailyLabels) ?>;
const dailyCounts = <?= json_encode($dailyCounts) ?>;

const revenueLabels = <?= json_encode($revenueLabels) ?>;
const revenueTotals = <?= json_encode($revenueTotals) ?>;

const topLabels = <?= json_encode($topLabels) ?>;
const topSold = <?= json_encode($topSold) ?>;

// Daily Orders Chart
new Chart(document.getElementById("ordersChart"), {
  type: 'line',
  data: {
    labels: dailyLabels,
    datasets: [{
      label: 'Orders',
      data: dailyCounts,
      borderColor: 'blue',
      backgroundColor: 'rgba(0, 0, 255, 0.2)',
      fill: true,
      tension: 0.3
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: { beginAtZero: true, stepSize: 1 }
    }
  }
});

// Revenue Chart
new Chart(document.getElementById("revenueChart"), {
  type: 'bar',
  data: {
    labels: revenueLabels,
    datasets: [{
      label: 'Revenue ($)',
      data: revenueTotals,
      backgroundColor: 'green'
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: { beginAtZero: true }
    }
  }
});

// Top Items Pie Chart
new Chart(document.getElementById("topItemsChart"), {
  type: 'pie',
  data: {
    labels: topLabels,
    datasets: [{
      label: 'Sold',
      data: topSold,
      backgroundColor: ['#e74c3c', '#f39c12', '#f1c40f', '#3498db', '#9b59b6']
    }]
  },
  options: {
    responsive: true
  }
});
</script>

<?php include('../includes/footer.php'); ?>
