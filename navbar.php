    <!-- Toggle Button -->
  <button id="toggle-btn">
    <i class="fas fa-bars"></i>
  </button>
  
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <h2><i class="fas fa-utensils"></i> <span>Restaurant</span></h2>
    <ul>
      <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
      <li><a href="menu.php"><i class="fas fa-concierge-bell"></i> <span>Menu</span></a></li>
      <li><a href="orders.php"><i class="fas fa-receipt"></i> <span>Orders</span></a></li>
      <li><a href="staff.php"><i class="fas fa-user-friends"></i> <span>Staff</span></a></li>
      <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
  </div>


  <!-- JavaScript Toggle -->
  <script>
    const toggleBtn = document.getElementById("toggle-btn");
    const sidebar = document.getElementById("sidebar");

    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
    });
  </script>