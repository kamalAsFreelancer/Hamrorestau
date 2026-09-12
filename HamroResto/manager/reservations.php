<?php
include("../includes/auth.php");
requireLogin();
if (!checkRole('manager')) exit("Access Denied");
include("../includes/db.php");

$restaurantId = $_SESSION['restaurant_id'];

// Handle add table
if (isset($_POST['add_table'])) {
    $table_number = $_POST['table_number'];
    $seats = (int)$_POST['seats'];
    if (!empty($table_number) && $seats > 0) {
        $stmt = $conn->prepare("INSERT INTO tables (restaurant_id, table_number, seats) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $restaurantId, $table_number, $seats);
        $stmt->execute();
    }
    header("Location: reservations.php");
    exit;
}

// Handle reserve / free table
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == "reserve") {
        $stmt = $conn->prepare("UPDATE tables SET status='reserved', reserved_by=?, reserved_at=NOW() WHERE id=? AND restaurant_id=?");
        $name = "Customer"; // Or use form input
        $stmt->bind_param("sii", $name, $id, $restaurantId);
    } elseif ($action == "occupy") {
        $stmt = $conn->prepare("UPDATE tables SET status='occupied' WHERE id=? AND restaurant_id=?");
        $stmt->bind_param("ii", $id, $restaurantId);
    } elseif ($action == "free") {
        $stmt = $conn->prepare("UPDATE tables SET status='available', reserved_by=NULL, reserved_at=NULL WHERE id=? AND restaurant_id=?");
        $stmt->bind_param("ii", $id, $restaurantId);
    }
    $stmt->execute();
    header("Location: reservations.php");
    exit;
}

// Get all tables
$stmt = $conn->prepare("SELECT * FROM tables WHERE restaurant_id=?");
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$tables = $stmt->get_result();

include("../includes/header.php");
include("sidebar.php");
?>

<div class="main-content">
    <h2>🍽️ Manage Table Reservations</h2>

    <form method="POST">
        <input type="text" name="table_number" placeholder="Table No." required>
        <input type="number" name="seats" placeholder="Seats" required min="1">
        <button type="submit" name="add_table">Add Table</button>
    </form>

    <table border="1" cellpadding="10" style="margin-top: 20px; width: 100%;">
        <thead>
            <tr>
                <th>Table No</th>
                <th>Seats</th>
                <th>Status</th>
                <th>Reserved By</th>
                <th>Reserved At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $tables->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['table_number']) ?></td>
                    <td><?= $row['seats'] ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['reserved_by']) ?></td>
                    <td><?= $row['reserved_at'] ?></td>
                    <td>
                        <?php if ($row['status'] == 'available'): ?>
                            <a href="?action=reserve&id=<?= $row['id'] ?>">Reserve</a>
                        <?php elseif ($row['status'] == 'reserved'): ?>
                            <a href="?action=occupy&id=<?= $row['id'] ?>">Mark Occupied</a> |
                            <a href="?action=free&id=<?= $row['id'] ?>">Free</a>
                        <?php elseif ($row['status'] == 'occupied'): ?>
                            <a href="?action=free&id=<?= $row['id'] ?>">Free</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include("../includes/footer.php"); ?>
