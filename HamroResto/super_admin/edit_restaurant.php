<?php
include("../includes/auth.php");
requireLogin();
if (!checkRole('super_admin')) exit("Access Denied");
include("../includes/db.php");
include("../includes/header.php");
include('sidebar.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid restaurant ID.");
}

$id = intval($_GET['id']);
$message = "";

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Restaurant not found.");
}

$restaurant = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);

    $update = $conn->prepare("UPDATE restaurants SET name = ?, location = ? WHERE id = ?");
    $update->bind_param("ssi", $name, $location, $id);

    if ($update->execute()) {
        $message = "Restaurant updated successfully.";
        // Refresh restaurant data
        $restaurant['name'] = $name;
        $restaurant['location'] = $location;
    } else {
        $message = "Error updating restaurant: " . $conn->error;
    }
    $update->close();
}

$stmt->close();
?>

<div class="main-content">
    <h1>Edit Restaurant</h1>
    <?php if ($message): ?>
        <p style="color:green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Restaurant Name</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($restaurant['name']) ?>" required><br><br>

        <label>Location</label><br>
        <input type="text" name="location" value="<?= htmlspecialchars($restaurant['location']) ?>"><br><br>

        <button type="submit">Update Restaurant</button>
        <a href="restaurants.php" style="margin-left:10px;">Back</a>
    </form>
</div>

<?php include("../includes/footer.php"); ?>
