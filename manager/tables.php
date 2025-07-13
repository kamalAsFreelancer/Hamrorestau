<?php
include("../includes/auth.php");
requireLogin();

if (!checkRole('manager')) {
    exit("Access Denied");
}

include("../includes/db.php");

if (!isset($_SESSION['restaurant_id'])) {
    exit("Restaurant ID missing in session");
}
$restaurantId = (int)$_SESSION['restaurant_id'];

$message = '';

// Handle actions: reserve, occupy, free, create_order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tableId = (int)($_POST['table_id'] ?? 0);
    $username = $_SESSION['username'] ?? 'Manager';

    if ($tableId > 0) {
        if ($action === 'reserve') {
            $reservedBy = $_POST['reserved_by'] ?? $username;
            $reservedAt = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE tables SET status='reserved', reserved_by=?, reserved_at=? WHERE id=? AND restaurant_id=?");
            $stmt->bind_param('ssii', $reservedBy, $reservedAt, $tableId, $restaurantId);
            $stmt->execute();
            $message = "Table reserved successfully.";

        } elseif ($action === 'occupy') {
            // Mark table as occupied only if reserved or available
            $stmt = $conn->prepare("UPDATE tables SET status='occupied' WHERE id=? AND restaurant_id=? AND status IN ('available', 'reserved')");
            $stmt->bind_param('ii', $tableId, $restaurantId);
            $stmt->execute();
            $message = "Table marked as occupied.";

        } elseif ($action === 'free') {
            // Free table: status = available, clear reservation info
            $stmt = $conn->prepare("UPDATE tables SET status='available', reserved_by=NULL, reserved_at=NULL WHERE id=? AND restaurant_id=?");
            $stmt->bind_param('ii', $tableId, $restaurantId);
            $stmt->execute();
            $message = "Table freed and available now.";

        } elseif ($action === 'create_order') {
            // Create order for occupied table
            // Check if table is occupied first
            $stmt = $conn->prepare("SELECT status FROM tables WHERE id=? AND restaurant_id=?");
            $stmt->bind_param('ii', $tableId, $restaurantId);
            $stmt->execute();
            $result = $stmt->get_result();
            $table = $result->fetch_assoc();

            if ($table && $table['status'] === 'occupied') {
                $customerName = trim($_POST['customer_name'] ?? 'Walk-in Customer');
                if ($customerName === '') $customerName = 'Walk-in Customer';

                // Insert order
                $stmt = $conn->prepare("INSERT INTO orders (customer_name, total_amount, status, restaurant_id, created_at, table_number, table_id) VALUES (?, 0, 'pending', ?, NOW(), (SELECT table_number FROM tables WHERE id=?), ?)");
                $stmt->bind_param('siii', $customerName, $restaurantId, $tableId, $tableId);
                $stmt->execute();

                $message = "Order created successfully for table #$tableId.";

            } else {
                $message = "Cannot create order: Table is not occupied.";
            }
        }
    }
}

// Fetch all tables for this restaurant
$stmt = $conn->prepare("SELECT * FROM tables WHERE restaurant_id = ? ORDER BY CAST(table_number AS UNSIGNED) ASC");
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$tables = $stmt->get_result();

include('header/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h1>Table Management</h1>

    <?php if ($message): ?>
        <div style="padding:10px; background:#e0ffe0; border:1px solid green; margin-bottom:20px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>Table Number</th>
                <th>Seats</th>
                <th>Status</th>
                <th>Reserved By</th>
                <th>Reserved At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($table = $tables->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($table['table_number']) ?></td>
                    <td><?= (int)$table['seats'] ?></td>
                    <td><?= ucfirst($table['status']) ?></td>
                    <td><?= htmlspecialchars($table['reserved_by'] ?? '-') ?></td>
                    <td><?= $table['reserved_at'] ?? '-' ?></td>
                    <td>
                        <form style="display:inline-block;" method="post">
                            <input type="hidden" name="table_id" value="<?= $table['id'] ?>">
                            <?php if ($table['status'] === 'available'): ?>
                                <input type="text" name="reserved_by" placeholder="Reserved By" required>
                                <button type="submit" name="action" value="reserve">Reserve</button>
                                <button type="submit" name="action" value="occupy">Mark Occupied</button>
                            <?php elseif ($table['status'] === 'reserved'): ?>
                                <button type="submit" name="action" value="occupy">Mark Occupied</button>
                                <button type="submit" name="action" value="free">Free Table</button>
                            <?php elseif ($table['status'] === 'occupied'): ?>
                                <button type="submit" name="action" value="free">Free Table</button>
                                <br><br>
                                <input type="text" name="customer_name" placeholder="Customer Name" required>
                                <button type="submit" name="action" value="create_order">Create Order</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

<?php include('../includes/footer.php'); ?>
