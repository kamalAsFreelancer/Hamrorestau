<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) exit("Access Denied");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("sii", $status, $orderId, $_SESSION['restaurant_id']);
    $stmt->execute();
}

header("Location: orders.php");
exit();
