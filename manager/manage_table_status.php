<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager') && !checkRole('waiter')) {
    exit("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tableId = (int)($_POST['table_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';

    $validStatuses = ['available', 'reserved', 'occupied'];
    if (!in_array($newStatus, $validStatuses) || $tableId <= 0) {
        exit("Invalid request.");
    }

    $restaurantId = (int)$_SESSION['restaurant_id'];

    // Check table ownership
    $stmt = $conn->prepare("SELECT id FROM tables WHERE id = ? AND restaurant_id = ?");
    $stmt->bind_param("ii", $tableId, $restaurantId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        exit("Table not found.");
    }

    if ($newStatus === 'available') {
        // Clear reservation info
        $updateStmt = $conn->prepare("UPDATE tables SET status = ?, reserved_by = NULL, reserved_at = NULL WHERE id = ? AND restaurant_id = ?");
        $updateStmt->bind_param("sii", $newStatus, $tableId, $restaurantId);
    } elseif ($newStatus === 'reserved') {
        $now = date('Y-m-d H:i:s');
        $reservedBy = $_SESSION['username'] ?? 'Unknown';
        $updateStmt = $conn->prepare("UPDATE tables SET status = ?, reserved_by = ?, reserved_at = ? WHERE id = ? AND restaurant_id = ?");
        $updateStmt->bind_param("ssssi", $newStatus, $reservedBy, $now, $tableId, $restaurantId);
    } else { // occupied
        $updateStmt = $conn->prepare("UPDATE tables SET status = ? WHERE id = ? AND restaurant_id = ?");
        $updateStmt->bind_param("sii", $newStatus, $tableId, $restaurantId);
    }

    if ($updateStmt->execute()) {
        header("Location: tables.php?message=Table status updated successfully");
        exit;
    } else {
        exit("Failed to update table status.");
    }
} else {
    exit("Invalid request method.");
}
