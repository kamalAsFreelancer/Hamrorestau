<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!checkRole('manager')) {
    http_response_code(403);
    exit('Access Denied');
}

$restaurantId = (int)($_SESSION['restaurant_id'] ?? 0);
$restaurant = null;
if ($restaurantId > 0) {
    $stmt = $conn->prepare('SELECT id, name FROM restaurants WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $restaurantId);
    $stmt->execute();
    $restaurant = $stmt->get_result()->fetch_assoc();
}
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-content">
    <h2><i class="fas fa-utensils"></i> <span>Manager Panel</span></h2>
    <?php if ($restaurant): ?><div class="restaurant-name"><?= htmlspecialchars($restaurant['name']) ?></div><?php endif; ?>
    <ul>
      <li><a href="dashboard.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
      <li><a href="overview.php"><i class="fa-solid fa-globe"></i><span>Restaurant Overview</span></a></li>
      <li><a href="menu.php"><i class="fas fa-concierge-bell"></i><span>Menu</span></a></li>
      <li><a href="orders.php"><i class="fas fa-receipt"></i><span>Orders</span></a></li>
      <li><a href="reservations.php"><i class="fas fa-calendar-check"></i><span>Tables & Reservations</span></a></li>
      <li><a href="report.php"><i class="fas fa-file-alt"></i><span>Reports</span></a></li>
      <li><a href="settings.php"><i class="fas fa-cogs"></i><span>Settings</span></a></li>
      <li><a href="create_order.php"><i class="fas fa-plus-circle"></i><span>New Order</span></a></li>
      <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
    </ul>
  </div>
</div>
<button id="toggle-btn" title="Toggle Sidebar"><i class="fas fa-bars"></i></button>
<script>
(() => {
  const btn = document.getElementById('toggle-btn');
  const sidebar = document.getElementById('sidebar');
  if (btn && sidebar) btn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    btn.classList.toggle('active');
  });
})();
</script>
