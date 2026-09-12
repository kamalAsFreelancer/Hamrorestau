<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) exit("Access Denied");

$restaurantId = $_SESSION['restaurant_id'];

// Fetch categories
$catStmt = $conn->prepare("SELECT id, name FROM categories WHERE restaurant_id=? ORDER BY name");
$catStmt->bind_param("i", $restaurantId);
$catStmt->execute();
$catResult = $catStmt->get_result();

// Initialize edit variables
$editId = $editName = $editDesc = $editCategory = $editPrice = $editDestination = "";

// Handle Edit Mode
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM menus WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("ii", $editId, $restaurantId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    if ($editData) {
        $editName = $editData['name'];
        $editDesc = $editData['description'];
        $editCategory = $editData['category_id'];
        $editPrice = $editData['price'];
        $editDestination = $editData['destination'];
    }
}

// Handle Add/Update Menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['price'], $_POST['destination'])) {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?: null;
    $price = $_POST['price'];
    $destination = $_POST['destination'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE menus SET name=?, description=?, category_id=?, price=?, destination=? WHERE id=? AND restaurant_id=?");
        $stmt->bind_param("ssdsisi", $name, $description, $category_id, $price, $destination, $id, $restaurantId);
    } else {
        $stmt = $conn->prepare("INSERT INTO menus (name, description, category_id, price, destination, restaurant_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsisi", $name, $description, $category_id, $price, $destination, $restaurantId, $_SESSION['user_id']);
    }
    $stmt->execute();
    header("Location: menu.php");
    exit();
}

// Handle Delete Menu
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM menus WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("ii", $delId, $restaurantId);
    $stmt->execute();
    header("Location: menu.php");
    exit();
}

// Handle Category Add/Delete
if (isset($_POST['add_category'])) {
    $catName = trim($_POST['cat_name']);
    if (!empty($catName)) {
        $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $restaurantId, $catName);
        $stmt->execute();
    }
    header("Location: menu.php");
    exit;
}
if (isset($_GET['delete_cat'])) {
    $catId = (int)$_GET['delete_cat'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("ii", $catId, $restaurantId);
    $stmt->execute();
    header("Location: menu.php");
    exit;
}

// Fetch Menus
$search = $_GET['search'] ?? '';
$searchParam = "%$search%";
$sql = "SELECT m.id, m.name, m.description, c.id AS category_id, c.name AS category_name, m.price, m.destination 
        FROM menus m 
        LEFT JOIN categories c ON m.category_id = c.id 
        WHERE m.restaurant_id = ? AND (m.name LIKE ? OR c.name LIKE ?)
        ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $restaurantId, $searchParam, $searchParam);
$stmt->execute();
$menuResult = $stmt->get_result();

include('header/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h1>Menu Management</h1>

    <!-- Search -->
    <form method="GET" style="margin-bottom:20px;">
        <input type="text" name="search" placeholder="Search items or categories" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <a href="menu.php"><button type="button">Clear</button></a>
    </form>

    <!-- Category Management -->
    <h2>🍽️ Manage Categories</h2>
    <form method="POST" style="margin-bottom: 20px;">
        <input type="text" name="cat_name" placeholder="New Category Name" required>
        <button type="submit" name="add_category">Add Category</button>
    </form>

    <table border="1" cellpadding="8" style="margin-bottom: 30px; width: 100%;">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php
            $catResult->data_seek(0);
            while ($cat = $catResult->fetch_assoc()):
            ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td>
                    <a href="?delete_cat=<?= $cat['id'] ?>" onclick="return confirm('Delete this category?')"><button type="button">Delete</button></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Menu Form -->
    <h2>🍔 <?= $editId ? "Edit" : "Add" ?> Menu Item</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $editId ?>">
        <input type="text" name="name" placeholder="Item Name" value="<?= htmlspecialchars($editName) ?>" required><br><br>
        <textarea name="description" placeholder="Description"><?= htmlspecialchars($editDesc) ?></textarea><br><br>
        <select name="category_id" required>
            <option value="">Select Category</option>
            <?php
            $catResult->data_seek(0);
            while ($cat = $catResult->fetch_assoc()):
            ?>
                <option value="<?= $cat['id'] ?>" <?= $editCategory == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>
        <input type="number" name="price" placeholder="Price" step="0.01" value="<?= htmlspecialchars($editPrice) ?>" required><br><br>
        <select name="destination" required>
            <option value="">Select Destination</option>
            <option value="Kitchen" <?= $editDestination == "Kitchen" ? 'selected' : '' ?>>Kitchen</option>
            <option value="Bar" <?= $editDestination == "Bar" ? 'selected' : '' ?>>Bar</option>
            <option value="Dessert" <?= $editDestination == "Dessert" ? 'selected' : '' ?>>Dessert</option>
        </select><br><br>
        <button type="submit">Save Menu Item</button>
        <a href="menu.php"><button type="button">Cancel</button></a>
    </form>

    <!-- Menu List -->
    <table border="1" cellpadding="8" style="margin-top:20px; width:100%;">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Category</th><th>Description</th><th>Price</th><th>Destination</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while ($menu = $menuResult->fetch_assoc()): ?>
            <tr>
                <td><?= $menu['id'] ?></td>
                <td><?= htmlspecialchars($menu['name']) ?></td>
                <td><?= htmlspecialchars($menu['category_name']) ?></td>
                <td><?= htmlspecialchars($menu['description']) ?></td>
                <td>Rs.<?= number_format($menu['price'], 2) ?></td>
                <td><?= htmlspecialchars($menu['destination']) ?></td>
                <td>
                    <a href="?edit=<?= $menu['id'] ?>"><button>Edit</button></a>
                    <a href="?delete=<?= $menu['id'] ?>" onclick="return confirm('Delete this menu item?')"><button>Delete</button></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include('../includes/footer.php'); ?>
