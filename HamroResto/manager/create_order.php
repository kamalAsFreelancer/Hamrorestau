<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) {
    exit("Access Denied");
}

if (!isset($_SESSION['restaurant_id'])) {
    exit("Restaurant ID missing in session");
}

$restaurantId = (int)$_SESSION['restaurant_id'];

// Get table ID from GET or POST
$tableId = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tableId = (int)($_POST['table_id'] ?? 0);
}

// Validate table
$stmt = $conn->prepare("SELECT * FROM tables WHERE id = ? AND restaurant_id = ?");
$stmt->bind_param("ii", $tableId, $restaurantId);
$stmt->execute();
$table = $stmt->get_result()->fetch_assoc();

if (!$table) {
    exit("Table not found.");
}

// Check if table is occupied
if ($table['status'] === 'occupied') {
    include('header/header.php');
    include('sidebar.php');
    echo "<div class='main-content'>";
    echo "<h2>Table {$table['table_number']} is already occupied.</h2>";
    echo "<a href='tables.php'>Back to Tables</a>";
    echo "</div>";
    include('../includes/footer.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    if ($customerName === '') {
        $error = "Customer name is required.";
    } else {
        // Insert new order with status pending
        $insertOrder = $conn->prepare("INSERT INTO orders (customer_name, total_amount, status, restaurant_id, created_at, table_number, table_id) VALUES (?, 0, 'pending', ?, NOW(), ?, ?)");
        $insertOrder->bind_param("siii", $customerName, $restaurantId, $table['table_number'], $tableId);
        if ($insertOrder->execute()) {
            // Update table status to occupied
            $updateTable = $conn->prepare("UPDATE tables SET status='occupied' WHERE id = ?");
            $updateTable->bind_param("i", $tableId);
            $updateTable->execute();

            $orderId = $insertOrder->insert_id;

            // Redirect to order details or menu selection page to add items
            header("Location: order_details.php?order_id=" . $orderId);
            exit;
        } else {
            $error = "Failed to create order.";
        }
    }
}

include('header/header.php');
include('sidebar.php');
?>

<div class="main-content">
  <h1>Create Order for Table <?= htmlspecialchars($table['table_number']) ?></h1>

  <?php if (!empty($error)): ?>
  <div style="color: red;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="table_id" value="<?= $tableId ?>">
    <label for="customer_name">Customer Name:</label><br>
    <input type="text" name="customer_name" id="customer_name" required><br><br>
    <button type="submit">Create Order</button>
  </form>

  <p><a href="tables.php">Back to Tables</a></p>
</div>

<?php include('../includes/footer.php'); ?>
