<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h2><i class="fas fa-crown"></i> Super Admin</h2>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="restaurants.php"><i class="fas fa-store"></i> Restaurants</a></li>
        <li><a href="managers.php"><i class="fas fa-user-tie"></i> Managers</a></li>
        <li><a href="settings.php"><i class="fas fa-cogs"></i> Settings</a></li>
        <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Toggle Button for Collapse -->
<button id="toggle-btn"><i class="fas fa-bars"></i></button>


<script>
document.getElementById("toggle-btn").addEventListener("click", function() {
    document.getElementById("sidebar").classList.toggle("collapsed");
});
</script>
