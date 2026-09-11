<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('manager');
require_once __DIR__ . '/../includes/db.php';

$restaurantId = (int)($_SESSION['restaurant_id'] ?? 0);
if (!$restaurantId) exit('Restaurant ID missing in session');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tableId = filter_input(INPUT_POST, 'table_id', FILTER_VALIDATE_INT);

    if (!$tableId) {
        $message = 'Invalid table.';
    } elseif ($action === 'reserve') {
        $reservedBy = trim($_POST['reserved_by'] ?? '') ?: ($_SESSION['username'] ?? 'Manager');
        $stmt = $conn->prepare("UPDATE tables SET status='reserved', reserved_by=?, reserved_at=NOW() WHERE id=? AND restaurant_id=?");
        $stmt->bind_param('sii', $reservedBy, $tableId, $restaurantId);
        $message = $stmt->execute() ? 'Table reserved successfully.' : 'Unable to reserve table.';
    } elseif ($action === 'occupy') {
        $stmt = $conn->prepare("UPDATE tables SET status='occupied' WHERE id=? AND restaurant_id=? AND status IN ('available','reserved')");
        $stmt->bind_param('ii', $tableId, $restaurantId);
        $message = $stmt->execute() && $stmt->affected_rows ? 'Table marked as occupied.' : 'Table could not be occupied.';
    } elseif ($action === 'free') {
        $stmt = $conn->prepare("UPDATE tables SET status='available', reserved_by=NULL, reserved_at=NULL WHERE id=? AND restaurant_id=?");
        $stmt->bind_param('ii', $tableId, $restaurantId);
        $message = $stmt->execute() ? 'Table is available now.' : 'Unable to free table.';
    } elseif ($action === 'create_order') {
        $customerName = trim($_POST['customer_name'] ?? '') ?: 'Walk-in Customer';
        $check = $conn->prepare("SELECT id FROM tables WHERE id=? AND restaurant_id=? AND status='occupied'");
        $check->bind_param('ii', $tableId, $restaurantId);
        $check->execute();
        if (!$check->get_result()->fetch_assoc()) {
            $message = 'Table must be occupied before creating an order.';
        } else {
            $stmt = $conn->prepare("INSERT INTO orders (customer_name,total_amount,status,restaurant_id,table_id) VALUES (?,0,'pending',?,?)");
            $stmt->bind_param('sii', $customerName, $restaurantId, $tableId);
            $message = $stmt->execute() ? 'Order created successfully.' : 'Unable to create order.';
        }
    }
}

$stmt = $conn->prepare('SELECT * FROM tables WHERE restaurant_id=? ORDER BY id ASC');
$stmt->bind_param('i', $restaurantId);
$stmt->execute();
$tables = $stmt->get_result();
include 'header/header.php';
include 'sidebar.php';
?>
<div class="main-content">
<h1>Table Management</h1>
<?php if ($message): ?><div style="padding:10px;margin-bottom:15px"><?=htmlspecialchars($message)?></div><?php endif; ?>
<table border="1" cellpadding="8" cellspacing="0" width="100%"><thead><tr><th>Table</th><th>Seats</th><th>Status</th><th>Reserved By</th><th>Reserved At</th><th>Actions</th></tr></thead><tbody>
<?php while($table=$tables->fetch_assoc()): ?><tr>
<td><?=htmlspecialchars($table['table_number'])?></td><td><?=intval($table['seats'])?></td><td><?=htmlspecialchars(ucfirst($table['status']))?></td><td><?=htmlspecialchars($table['reserved_by']??'-')?></td><td><?=htmlspecialchars($table['reserved_at']??'-')?></td><td>
<form method="post"><input type="hidden" name="table_id" value="<?=intval($table['id'])?>">
<?php if($table['status']==='available'): ?><input name="reserved_by" placeholder="Reserved by"><button name="action" value="reserve">Reserve</button> <button name="action" value="occupy">Occupy</button>
<?php elseif($table['status']==='reserved'): ?><button name="action" value="occupy">Occupy</button> <button name="action" value="free">Free</button>
<?php else: ?><button name="action" value="free">Free</button> <input name="customer_name" placeholder="Customer name"><button name="action" value="create_order">Create Order</button><?php endif; ?></form>
</td></tr><?php endwhile; ?></tbody></table></div>
<?php include '../includes/footer.php'; ?>
