<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-content">
    <h2><i class="fas fa-crown"></i> <span>Super Admin</span></h2>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="restaurants.php"><i class="fas fa-store"></i> <span>Restaurants</span></a></li>
        <li><a href="managers.php"><i class="fas fa-user-tie"></i> <span>Manager</span></a></li>
        <li><a href="settings.php"><i class="fas fa-cogs"></i> <span>Settings</span></a></li>
        <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>
</div>

<!-- Toggle Button for Collapse -->
<button id="toggle-btn"><i class="fas fa-bars"></i></button>


<script>
document.getElementById("toggle-btn").addEventListener("click", function() {
    document.getElementById("sidebar").classList.toggle("collapsed");
});
</script>
