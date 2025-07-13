<?php
include("../includes/db.php");

$restaurantId = $_SESSION['restaurant_id'] ?? 0;
$restaurants = $conn->query("SELECT id, name FROM restaurants WHERE id = $restaurantId");
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-content">
    <h2><i class="fas fa-utensils"></i> <span>Manager Panel</span></h2>

    <ul>
      <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
      <li><a href="overview.php"><i class="fa-solid fa-globe"></i> <span>Restaurant Overview</span></a></li>
      <li><a href="menu.php"><i class="fas fa-concierge-bell"></i> <span>Menu</span></a></li>
      <li><a href="orders.php"><i class="fas fa-receipt"></i> <span>Orders</span></a></li>
      <li><a href="tables.php"><i class="fas fa-chair"></i> <span>Table Management</span></a></li>
      <li><a href="reservations.php"><i class="fas fa-calendar-check"></i> <span>Reservations</span></a></li>
      <li><a href="staff.php"><i class="fas fa-user-friends"></i> <span>Staff</span></a></li>
      <li><a href="report.php"><i class="fas fa-file-alt"></i> <span>Reports</span></a></li>
      <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>

    <hr>

    <table>
      <thead>
        <tr><th>ID</th><th>Name</th></tr>
      </thead>
      <tbody>
        <?php while ($r = $restaurants->fetch_assoc()): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<button id="toggle-btn" title="Toggle Sidebar"><i class="fas fa-bars"></i></button>

<script>
  const toggleBtn = document.getElementById('toggle-btn');
  const sidebar = document.getElementById('sidebar');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    toggleBtn.classList.toggle('active');
  });
</script>
