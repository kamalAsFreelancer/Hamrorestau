<?php
// Make sure session is started and role is set
$role = $_SESSION['role'] ?? '';

// Define menu items per staff role
$menuItems = [
    'waiter' => [
        ['href' => 'table_management.php', 'icon' => 'fas fa-chair', 'label' => 'Table Management'],
        ['href' => 'take_order.php', 'icon' => 'fas fa-receipt', 'label' => 'Take Orders'],
        ['href' => 'view_orders.php', 'icon' => 'fas fa-eye', 'label' => 'View Orders'],
    ],
    'kitchen' => [
        ['href' => 'dashboard.php', 'icon' => 'fas fa-fire', 'label' => 'Kitchen Orders'],
    ],
    'bartender' => [
        ['href' => 'dashboard.php', 'icon' => 'fas fa-cocktail', 'label' => 'Bar Orders'],
    ],
];

// Use the menu for current role or empty array if role not found
$menu = $menuItems[$role] ?? [];
?>

<div class="sidebar" id="sidebar">
  <div class="sidebar-content">
    <h2><i class="fas fa-user-cog"></i> <span>Staff Panel (<?= htmlspecialchars(ucfirst($role)) ?>)</span></h2>
    <ul>
      <?php foreach ($menu as $item): ?>
        <li>
          <a href="<?= htmlspecialchars($item['href']) ?>">
            <i class="<?= htmlspecialchars($item['icon']) ?>"></i> <span><?= htmlspecialchars($item['label']) ?></span>
          </a>
        </li>
      <?php endforeach; ?>
      <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
  </div>
</div>

<button id="toggle-btn"><i class="fas fa-bars"></i></button>

<script>
  const toggleBtn = document.getElementById('toggle-btn');
  const sidebar = document.getElementById('sidebar');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
</script>

<style>
  /* Basic styling for sidebar */
  .sidebar-content {
    padding: 0;
    margin-top: 65px;
  }

  .sidebar.collapsed ul li a span {
    display: none;
  }

  .sidebar.collapsed h2 span {
    display: none;
  }

  #sidebar {
    width: 250px;
    background: #2c3e50;
    color: #ecf0f1;
    height: 100vh;
    position: fixed;
    padding-top: 20px;
    transition: width 0.3s;
    z-index: 1000;
  }
  #sidebar.collapsed {
    width: 60px;
  }
  #sidebar h2 {
    font-size: 1.5em;
    text-align: center;
    margin-bottom: 20px;
  }
  #sidebar ul {
    list-style: none;
    padding-left: 0;
  }
  #sidebar ul li {
    padding: 15px 20px;
  }
  #sidebar ul li a {
    color: #ecf0f1;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
  }
  #sidebar ul li a:hover {
    background: #34495e;
    border-radius: 4px;
  }
  #toggle-btn {
    position: fixed;
    top: 20px;
    left: 15px;
    background:#34495e;
    border: none;
    border-radius: 0;
    width: 200px;
    height: 38px;
    color: #fff;
    font-size: 1.4rem;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(230, 126, 34, 0.5);
    transition: left 0.3s ease, background 0.3s ease;
    z-index: 1100;
  }
  #sidebar.collapsed + #toggle-btn {
    left: 15px;
    width: 38px;
  }
</style>
