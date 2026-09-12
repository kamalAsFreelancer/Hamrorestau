<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!in_array($_SESSION['role'], ['waiter', 'manager'])) {
    exit("Access Denied");
}

$restaurantId = $_SESSION['restaurant_id'];

// Add Table
if (isset($_POST['add_table'])) {
    $tableNumber = intval($_POST['table_number']);
    if ($tableNumber > 0) {
        $stmt = $conn->prepare("INSERT INTO tables (table_number, status, restaurant_id) VALUES (?, 'Available', ?)");
        $stmt->bind_param("ii", $tableNumber, $restaurantId);
        $stmt->execute();
        header("Location: table_management.php");
        exit();
    }
}

// Update Table Status
if (isset($_POST['update_status'])) {
    $tableId = intval($_POST['table_id']);
    $status = $_POST['status'];
    $allowed_status = ['Available', 'Occupied', 'Reserved'];
    if (in_array($status, $allowed_status)) {
        $stmt = $conn->prepare("UPDATE tables SET status=? WHERE id=? AND restaurant_id=?");
        $stmt->bind_param("sii", $status, $tableId, $restaurantId);
        $stmt->execute();
        header("Location: table_management.php");
        exit();
    }
}

// Delete Table
if (isset($_GET['delete'])) {
    $tableId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tables WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("ii", $tableId, $restaurantId);
    $stmt->execute();
    header("Location: table_management.php");
    exit();
}

// Fetch tables
$stmt = $conn->prepare("SELECT id, table_number, status FROM tables WHERE restaurant_id=? ORDER BY table_number ASC");
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$tables = $stmt->get_result();

// Fetch orders count & bill sum per table
$orderStmt = $conn->prepare("
    SELECT table_id, COUNT(*) AS order_count, SUM(total_amount) AS total_bill
    FROM orders 
    WHERE restaurant_id = ? AND status != 'cancelled'
    GROUP BY table_id
");
$orderStmt->bind_param("i", $restaurantId);
$orderStmt->execute();
$orderResults = $orderStmt->get_result();

$ordersData = [];
while ($row = $orderResults->fetch_assoc()) {
    $ordersData[$row['table_id']] = $row;
}

include('../includes/header.php');
include('sidebar.php');
?>

<style>
  .cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
  }

  .table-card {
    border-radius: 10px;
    color: #fff;
    width: 250px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: 0.3s ease;
  }
  .available {
    background-color: #2ecc71;
  }
  .occupied {
    background-color: #e74c3c;
  }
  .reserved {
    background-color: #f39c12;
  }
  .table-card:hover {
    transform: translateY(-5px);
  }
  .buttons {
    margin-top: 15px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .buttons a, .buttons button {
    padding: 8px;
    text-align: center;
    font-weight: bold;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    color: #fff;
  }
  .btn-order { background-color: #2980b9; }
  .btn-view { background-color: #8e44ad; }
  .btn-bill { background-color: #16a085; }
  .btn-checkout { background-color: #27ae60; }
  .btn-clear { background-color: #c0392b; }
  .btn-delete { background-color: #d35400; }
</style>

<script>
function performAction(tableId, action) {
  if (!confirm(`Are you sure you want to ${action} this table?`)) return;
  fetch('update_table_status.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `table_id=${tableId}&action=${action}`
  })
  .then(res => res.text())
  .then(data => {
    if (data.trim() === 'success') location.reload();
    else alert("Failed: " + data);
  });
}
</script>
<div class="main-content">

<h2 style="text-align:center">Table Management</h2>
<form method="POST" style="text-align:center; margin:20px">
  <input type="number" name="table_number" placeholder="Enter Table No." required>
  <button name="add_table">Add Table</button>
</form>

<div class="cards-container">
<?php while ($table = $tables->fetch_assoc()):
  $statusClass = strtolower($table['status']);
?>
  <div class="table-card <?= $statusClass ?>">
    <h3>Table #<?= $table['table_number'] ?></h3>
    <p>Status: <?= $table['status'] ?></p>
    <form method="POST">
      <input type="hidden" name="table_id" value="<?= $table['id'] ?>">
      <select name="status">
        <option value="Available" <?= $table['status']=='Available'?'selected':'' ?>>Available</option>
        <option value="Occupied" <?= $table['status']=='Occupied'?'selected':'' ?>>Occupied</option>
        <option value="Reserved" <?= $table['status']=='Reserved'?'selected':'' ?>>Reserved</option>
      </select>
      <button type="submit" name="update_status">Update</button>
    </form>

    <div class="buttons">
      <?php if ($_SESSION['role'] === 'waiter'): ?>
        <a class="btn-order" href="take_order.php?table_id=<?= $table['id'] ?>">Take Order</a>
      <?php endif; ?>
      <?php if (!empty($ordersData[$table['id']])): ?>
        <a class="btn-view" href="table_orders.php?table_id=<?= $table['id'] ?>">View Orders</a>
      <?php endif; ?>
      <?php if ($table['status'] === 'Occupied'): ?>
        <button class="btn-checkout" onclick="performAction(<?= $table['id'] ?>, 'checkout')">Checkout</button>
        <button class="btn-clear" onclick="performAction(<?= $table['id'] ?>, 'clear')">Clear Table</button>
      <?php endif; ?>
      <form method="GET" onsubmit="return confirm('Delete table?')">
        <input type="hidden" name="delete" value="<?= $table['id'] ?>">
        <button type="submit" class="btn-delete">Delete Table</button>
      </form>
    </div>
  </div>
<?php endwhile; ?>
</div>
</div>

<?php include('../includes/footer.php'); ?>
