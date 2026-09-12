<?php
include("../includes/auth.php");
requireLogin();

if (!checkRole('manager')) {
    exit("Access Denied");
}

include("../includes/db.php");

if (!isset($_GET['order_id'])) {
    exit("Order ID is missing");
}

$orderId = (int)$_GET['order_id'];
$restaurantId = (int)$_SESSION['restaurant_id'];

// Fetch order info
$orderStmt = $conn->prepare("
    SELECT o.id, o.customer_name, o.total_amount, o.status, t.table_number, o.created_at, o.cancelled_at
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE o.id = ? AND o.restaurant_id = ?
");
$orderStmt->bind_param("ii", $orderId, $restaurantId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    exit("Order not found or access denied");
}
$order = $orderResult->fetch_assoc();

// Fetch order items
$itemsStmt = $conn->prepare("
    SELECT m.name, oi.quantity, oi.price
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Bill - Order #<?= $order['id'] ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            width: 300px;
            margin: auto;
            padding: 10px;
        }
        h2, h3, h4, p {
            text-align: center;
            margin: 2px 0;
        }
        table {
            width: 100%;
            margin-top: 10px;
            font-size: 14px;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 4px 0;
        }
        tfoot td {
            font-weight: bold;
        }
        .total {
            border-top: 1px dashed black;
            margin-top: 5px;
        }
        .print-btn {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
    <script>
        window.onload = function () {
            window.print(); // Automatically open print dialog
        };
    </script>
</head>
<body>
    <h2><strong>Restaurant Name</strong></h2>
    <p>------------------------------</p>
    <p><strong>Order ID:</strong> #<?= $order['id'] ?></p>
    <p><strong>Table:</strong> <?= $order['table_number'] ?? 'N/A' ?></p>
    <p><strong>Date:</strong> <?= $order['created_at'] ?></p>
    <p>------------------------------</p>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th style="text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;
            while ($item = $orderItems->fetch_assoc()):
                $subtotal = $item['quantity'] * $item['price'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td style="text-align:right;">Rs.<?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <p class="total">------------------------------</p>
    <table>
        <tfoot>
            <tr>
                <td><strong>Total</strong></td>
                <td></td>
                <td style="text-align:right;"><strong>Rs.<?= number_format($total, 2) ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <p>------------------------------</p>
    <p>Thank you for visiting!</p>

    <div class="print-btn">
        <button onclick="window.print()">Print Again</button>
    </div>
</body>
</html>
