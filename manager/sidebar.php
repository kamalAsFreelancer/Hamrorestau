<?php
include("../includes/db.php");

// Fetch restaurants for the sidebar table (for example)
$restaurantId = $_SESSION['restaurant_id'];
$restaurants = $conn->query("SELECT id, name FROM restaurants WHERE id = $restaurantId");
?>
<div class="sidebar" id="sidebar">
  <h2><i class="fas fa-utensils"></i> <span>Manager Panel</span></h2>
  <ul>
    <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
    <li><a href="menu.php"><i class="fas fa-concierge-bell"></i> <span>Menu</span></a></li>
    <li><a href="orders.php"><i class="fas fa-receipt"></i> <span>Orders</span></a></li>
    <li><a href="staff.php"><i class="fas fa-user-friends"></i> <span>Staff</span></a></li>
    <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
    <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
  </ul>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = $restaurants->fetch_assoc()): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><span><?= htmlspecialchars($r['name']) ?></span></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<button id="toggle-btn"><i class="fas fa-bars"></i></button>

<script>
  const toggleBtn = document.getElementById('toggle-btn');
  const sidebar = document.getElementById('sidebar');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
</script>
