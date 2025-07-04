<?php
include("../includes/auth.php");
requireLogin();
if (!checkRole('super_admin')) exit("Access Denied");
include("../includes/db.php");
include("../includes/header.php");
include('sidebar.php');

$message = "";

// Handle add restaurant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_restaurant'])) {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);

    $stmt = $conn->prepare("INSERT INTO restaurants (name, location) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $location);

    if ($stmt->execute()) {
        $message = "Restaurant added successfully.";
    } else {
        $message = "Error: " . $conn->error;
    }
    $stmt->close();
}

// Fetch restaurants
$restaurants = $conn->query("SELECT * FROM restaurants ORDER BY id DESC");
?>

<div class="main-content">
    <h1>Manage Restaurants</h1>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Add Restaurant Form -->
    <h2>Add New Restaurant</h2>
    <form method="POST" class="add-form">
        <input type="hidden" name="add_restaurant" value="1">
        <input type="text" name="name" placeholder="Restaurant Name" required>
        <input type="text" name="location" placeholder="Location">
        <button type="submit">Add Restaurant</button>
    </form>

    <!-- Restaurant List Table -->
    <h2>All Restaurants</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($r = $restaurants->fetch_assoc()): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= htmlspecialchars($r['location']) ?></td>
                    <td>
                        <a href="edit_restaurant.php?id=<?= $r['id'] ?>">Edit</a> |
                        <a href="delete_restaurant.php?id=<?= $r['id'] ?>" onclick="return confirm('Are you sure to delete this restaurant?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if ($restaurants->num_rows === 0): ?>
                <tr><td colspan="4" style="text-align:center;">No restaurants found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include("../includes/footer.php"); ?>
