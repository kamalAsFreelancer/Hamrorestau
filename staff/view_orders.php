<?php
include_once("../includes/auth.php");
requireLogin();
include_once("../includes/db.php");

if ($_SESSION['role'] !== 'waiter') exit("Access Denied");

$restaurantId = $_SESSION['restaurant_id'];
$tableId = isset($_GET['table_id']) ? intval($_GET['table_id']) : 0;
if ($tableId <= 0) exit("Invalid table");

// Fetch table
$stmt = $conn->prepare("SELECT table_number FROM tables WHERE id=? AND restaurant_id=?");
$stmt->bind_param("ii", $tableId, $restaurantId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) exit("Table not found");
$table = $res->fetch_assoc();

// Fetch latest order
$stmt = $conn->prepare("SELECT id, status FROM orders WHERE table_id=? AND restaurant_id=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("ii", $tableId, $restaurantId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $order = $res->fetch_assoc();
} else {
    // Create draft
    $insert = $conn->prepare("INSERT INTO orders (table_id, restaurant_id, status, created_at) VALUES (?, ?, 'draft', NOW())");
    $insert->bind_param("ii", $tableId, $restaurantId);
    $insert->execute();
    $order = ['id' => $insert->insert_id, 'status' => 'draft'];
}

$isDraft = $order['status'] === 'draft';
$isSubmitted = $order['status'] === 'submitted';

// AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = $order['id'];
    $action = $_POST['action'];

    if ($action === 'update_quantity' && $isDraft) {
        $stmt = $conn->prepare("UPDATE order_items SET quantity=? WHERE id=? AND order_id=?");
        $stmt->bind_param("iii", $_POST['quantity'], $_POST['order_item_id'], $orderId);
        $stmt->execute();
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'remove_item' && $isDraft) {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE id=? AND order_id=?");
        $stmt->bind_param("ii", $_POST['order_item_id'], $orderId);
        $stmt->execute();
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'submit_order' && $isDraft) {
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE order_id=?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
        if ($count > 0) {
            $stmt = $conn->prepare("UPDATE orders SET status='submitted', submitted_at=NOW() WHERE id=?");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE tables SET status='Occupied' WHERE id=?");
            $stmt->bind_param("i", $tableId);
            $stmt->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No items in order']);
        }
        exit;
    }

    if ($action === 'checkout' && $isSubmitted) {
        // Finalize bill
        $stmt = $conn->prepare("SELECT SUM(quantity * price) as total FROM order_items WHERE order_id=?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        $stmt = $conn->prepare("UPDATE orders SET total_amount=?, status='paid' WHERE id=?");
        $stmt->bind_param("di", $total, $orderId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE tables SET status='Available' WHERE id=?");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();

        echo json_encode(['success' => true, 'total' => $total]);
        exit;
    }
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.id as order_item_id, oi.quantity, m.name, m.price
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order['id']);
$stmt->execute();
$res = $stmt->get_result();

$orderItems = [];
$total = 0;
while ($row = $res->fetch_assoc()) {
    $row['total_price'] = $row['price'] * $row['quantity'];
    $total += $row['total_price'];
    $orderItems[] = $row;
}

// Update total_amount in DB
$stmt = $conn->prepare("UPDATE orders SET total_amount=? WHERE id=?");
$stmt->bind_param("di", $total, $order['id']);
$stmt->execute();

include('../includes/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h1>Orders for Table #<?= htmlspecialchars($table['table_number']) ?></h1>
    <p>Status: <strong><?= htmlspecialchars(ucfirst($order['status'])) ?></strong></p>

    <?php if (empty($orderItems)): ?>
        <p>No items in this order yet.</p>
    <?php else: ?>
        <table border="1" cellpadding="10" cellspacing="0" style="width:100%; max-width:700px; border-collapse: collapse;">
            <thead>
                <tr style="background-color:#34495e; color:#fff;">
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="order-items-body">
                <?php foreach ($orderItems as $item): ?>
                <tr data-id="<?= $item['order_item_id'] ?>">
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= number_format($item['price'], 2) ?></td>
                    <td>
                        <?php if ($isDraft): ?>
                            <button class="qty-btn" data-change="-1">-</button>
                            <span class="quantity"><?= $item['quantity'] ?></span>
                            <button class="qty-btn" data-change="1">+</button>
                        <?php else: ?>
                            <?= $item['quantity'] ?>
                        <?php endif; ?>
                    </td>
                    <td class="total-price"><?= number_format($item['total_price'], 2) ?></td>
                    <td>
                        <?php if ($isDraft): ?>
                            <button class="remove-btn">Remove</button>
                        <?php else: ?> - <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:bold;">
                    <td colspan="3" style="text-align:right;">Total:</td>
                    <td id="order-total"><?= number_format($total, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <?php if ($isDraft): ?>
        <button id="submit-btn" style="margin-top:20px;">Submit Order</button>
    <?php elseif ($isSubmitted): ?>
        <button id="checkout-btn" style="margin-top:20px;">Checkout</button>
    <?php endif; ?>

    <a href="table_management.php" style="margin-left: 20px;">Back to Tables</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableId = <?= json_encode($tableId) ?>;

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('#order-items-body tr').forEach(row => {
            total += parseFloat(row.querySelector('.total-price').textContent);
        });
        document.getElementById('order-total').textContent = total.toFixed(2);
    }

    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tr = btn.closest('tr');
            const orderItemId = tr.dataset.id;
            const qtySpan = tr.querySelector('.quantity');
            let qty = parseInt(qtySpan.textContent);
            const change = parseInt(btn.dataset.change);
            const newQty = qty + change;
            if (newQty < 1) return;

            fetch('view_orders.php?table_id=' + tableId, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'update_quantity',
                    order_item_id: orderItemId,
                    quantity: newQty
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    qtySpan.textContent = newQty;
                    const price = parseFloat(tr.children[1].textContent);
                    tr.querySelector('.total-price').textContent = (price * newQty).toFixed(2);
                    updateTotal();
                }
            });
        });
    });

    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Remove this item?')) return;
            const tr = btn.closest('tr');
            const orderItemId = tr.dataset.id;

            fetch('view_orders.php?table_id=' + tableId, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'remove_item',
                    order_item_id: orderItemId
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    tr.remove();
                    updateTotal();
                }
            });
        });
    });

    document.getElementById('submit-btn')?.addEventListener('click', () => {
        if (!confirm('Submit the order?')) return;
        fetch('view_orders.php?table_id=' + tableId, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'submit_order'})
        }).then(res => res.json()).then(data => {
            if (data.success) location.reload();
        });
    });

    document.getElementById('checkout-btn')?.addEventListener('click', () => {
        if (!confirm('Checkout and mark bill as paid?')) return;
        fetch('view_orders.php?table_id=' + tableId, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'checkout'})
        }).then(res => res.json()).then(data => {
            if (data.success) {
                alert("Checkout complete. Total: Rs. " + data.total.toFixed(2));
                window.location.href = 'table_management.php';
            }
        });
    });
});
</script>

<?php include('../includes/footer.php'); ?>
