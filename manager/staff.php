<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) exit("Access Denied");

$restaurantId = $_SESSION['restaurant_id'];

// Handle Add Staff
if (isset($_POST['add_staff'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'staff';

    if ($username && $_POST['password']) {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, restaurant_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $password, $role, $restaurantId);
        $stmt->execute();
        header("Location: staff.php");
        exit;
    }
}

// Handle Delete Staff
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND restaurant_id = ?");
    $stmt->bind_param("ii", $id, $restaurantId);
    $stmt->execute();
    header("Location: staff.php");
    exit;
}

// Fetch staff members for this restaurant
$stmt = $conn->prepare("SELECT id, username FROM users WHERE restaurant_id = ? AND role = 'staff' ORDER BY created_at DESC");
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$result = $stmt->get_result();

include('../includes/header.php');
include('sidebar.php');
?>

<div class="main-content">
  <h1>Staff Management</h1>

  <!-- Add Staff Form -->
  <form method="POST" style="margin-bottom: 20px;">
    <input type="text" name="username" placeholder="Name" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="add_staff">Add Staff</button>
  </form>

  <!-- Staff List -->
  <table border="1" cellpadding="8" cellspacing="0" style="width:100%;">
    <thead>
      <tr>
        <th>ID</th><th>Name</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($staff = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $staff['id'] ?></td>
        <td><?= htmlspecialchars($staff['username']) ?></td>
        <td>
          <a href="?delete=<?= $staff['id'] ?>" onclick="return confirm('Delete this staff member?')">Delete</a>
          <!-- Edit functionality can be added later -->
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include('../includes/footer.php'); ?>
