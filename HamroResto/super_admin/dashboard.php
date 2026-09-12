<?php
include("../includes/auth.php");
requireLogin();
if (!checkRole('super_admin')) exit("Access Denied");
include("../includes/db.php");
include("header.php");
include('sidebar.php');

// Fetch stats
$totalRestaurants = $conn->query("SELECT COUNT(*) AS total FROM restaurants")->fetch_assoc()['total'];
$totalManagers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='manager'")->fetch_assoc()['total'];
$totalStaffs = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='staff'")->fetch_assoc()['total'];

// Latest 5 restaurants
$latestRestaurants = $conn->query("SELECT * FROM restaurants ORDER BY id DESC LIMIT 5");
?>

<div class="main-content">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h1>
    <p>Here’s your system overview:</p>

    <div class="cards">
        <div class="card">
            <h3>Total Restaurants</h3>
            <p><?= $totalRestaurants ?></p>
        </div>
        <div class="card">
            <h3>Total Managers</h3>
            <p><?= $totalManagers ?></p>
        </div>
        <div class="card">
            <h3>Total Staffs</h3>
            <p><?= $totalStaffs ?></p>
        </div>
    </div>

    <h2>Latest Restaurants Added</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $latestRestaurants->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Summary Chart</h2>
    <canvas id="summaryChart" width="600" height="300"></canvas>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('summaryChart').getContext('2d');
const summaryChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Restaurants', 'Managers', 'Staffs'],
        datasets: [{
            label: 'Count',
            data: [<?= $totalRestaurants ?>, <?= $totalManagers ?>, <?= $totalStaffs ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                precision: 0
            }
        }
    }
});
</script>

<?php include("../includes/footer.php"); ?>
