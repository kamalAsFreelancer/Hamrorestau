<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('waiter') && !checkRole('manager')) {
    exit("Access Denied");
}

$restaurantId = (int)$_SESSION['restaurant_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tableId = intval($_POST['table_id'] ?? 0);
    $reservedBy = trim($_POST['reserved_by'] ?? '');
    $now = date('Y-m-d H:i:s');

    if ($tableId > 0 && $reservedBy !== '') {
        // Check if table is available
        $stmt = $conn->prepare("SELECT status FROM tables WHERE id = ? AND restaurant_id = ?");
        $stmt->bind_param("ii", $tableId, $restaurantId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && $result['status'] === 'available') {
            $updateStmt = $conn->prepare("UPDATE tables SET status = 'reserved', reserved_by = ?, reserved_at = ? WHERE id = ? AND restaurant_id = ?");
            $updateStmt->bind_param("ssii", $reservedBy, $now, $tableId, $restaurantId);
            $updateStmt->execute();
            $message = "Table reserved successfully.";
        } else {
            $message = "Table is not available for reservation.";
        }
    } else {
        $message = "Please select a table and enter reservation name.";
    }
}

// Fetch available tables
$stmt = $conn->prepare("SELECT id, table_number, seats FROM tables WHERE restaurant_id = ? AND status = 'available' ORDER BY table_number ASC");
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$availableTables = $stmt->get_result();

include('header/header.php');
include('sidebar.php');
?>

<div class="main-content">
  <h1>Reserve a Table</h1>
  <?php if ($message): ?>
    <p style="color:<?= strpos($message, 'success') !== false ? 'green' : 'red' ?>"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <form method="POST">
    <label for="table_id">Select Table:</label>
    <select name="table_id" id="table_id" required>
      <option value="">-- Select Table --</option>
      <?php while ($table = $availableTables->fetch_assoc()): ?>
        <option value="<?= $table['id'] ?>">
          Table <?= htmlspecialchars($table['table_number']) ?> (Seats: <?= (int)$table['seats'] ?>)
        </option>
      <?php endwhile; ?>
    </select>
    <br><br>

    <label for="reserved_by">Reserved By (Name):</label>
    <input type="text" name="reserved_by" id="reserved_by" required>
    <br><br>

    <button type="submit">Reserve Table</button>
  </form>
</div>

<?php include('../includes/footer.php'); ?>
