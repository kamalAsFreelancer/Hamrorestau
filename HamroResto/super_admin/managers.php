<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('super_admin')) exit("Access Denied");

// Add manager
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $restaurant_id = $_POST['restaurant_id'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role, restaurant_id) VALUES (?, ?, 'manager', ?)");
    $stmt->bind_param("ssi", $username, $password, $restaurant_id);
    if ($stmt->execute()) $_SESSION['message'] = "Manager added.";
    else $_SESSION['message'] = "Error: " . $conn->error;
    header("Location: managers.php");
    exit();
}

$restaurants = $conn->query("SELECT * FROM restaurants");
$managers = $conn->query("SELECT u.username, r.name AS restaurant_name FROM users u JOIN restaurants r ON u.restaurant_id = r.id WHERE u.role='manager'");

include("header.php");
include('sidebar.php');
?>
<div class="main-content">
<h2>Add Manager</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <select name="restaurant_id" required>
        <option value="">Select Restaurant</option>
        <?php while ($r = $restaurants->fetch_assoc()): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
        <?php endwhile; ?>
    </select><br><br>
    <button type="submit">Add Manager</button>
</form>

<h2>All Managers</h2>
<table border="1">
    <thead>
    <tr><th>Username</th><th>Restaurant</th></tr>
    </thead>
    <?php while ($m = $managers->fetch_assoc()): ?>
        <tbody>
        <tr>
            <td><?= htmlspecialchars($m['username']) ?></td>
            <td><?= htmlspecialchars($m['restaurant_name']) ?></td>
        </tr>
    </tbody>
    <?php endwhile; ?>
</table>
</div>

<?php include('../includes/footer.php'); ?>
