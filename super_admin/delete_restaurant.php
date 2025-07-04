<?php
include("../includes/auth.php");
requireLogin();
if (!checkRole('super_admin')) exit("Access Denied");
include("../includes/db.php");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid restaurant ID.");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM restaurants WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: restaurants.php?msg=deleted");
    exit();
} else {
    die("Error deleting restaurant: " . $conn->error);
}
?>
