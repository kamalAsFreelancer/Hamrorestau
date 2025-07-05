<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) exit("Access Denied");

$restaurantId = $_SESSION['restaurant_id'];

// Handle Add Category
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

// Handle Delete Category
if (isset($_GET['delete_cat'])) {
    $catId = (int)$_GET['delete_cat'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("ii", $catId, $restaurantId);
    $stmt->execute();
    header("Location: menu.php");
    exit;
}

// Fetch categories
$catStmt = $conn->prepare("SELECT id, name FROM categories WHERE restaurant_id=? ORDER BY name");
$catStmt->bind_param("i", $restaurantId);
$catStmt->execute();
$catResult = $catStmt->get_result();

// Handle Add/Update Menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['price'])) {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?: null;
    $price = $_POST['price'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE menus SET name=?, description=?, category_id=?, price=? WHERE id=? AND restaurant_id=?");
        $stmt->bind_param("ssdsii", $name, $description, $category_id, $price, $id, $restaurantId);
    } else {
        $stmt = $conn->prepare("INSERT INTO menus (name, description, category_id, price, restaurant_id, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsii", $name, $description, $category_id, $price, $restaurantId, $_SESSION['user_id']);
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

// Fetch Menus (with Search)
$search = $_GET['search'] ?? '';
$searchParam = "%$search%";

$sql = "SELECT m.id, m.name, m.description, c.id AS category_id, c.name AS category_name, m.price 
        FROM menus m 
        LEFT JOIN categories c ON m.category_id = c.id 
        WHERE m.restaurant_id = ? AND (m.name LIKE ? OR c.name LIKE ?)
        ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $restaurantId, $searchParam, $searchParam);
$stmt->execute();
$menuResult = $stmt->get_result();

include('../includes/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h1>Menu Management</h1>

    <!-- Search -->
    <form method="GET" style="margin-bottom:20px;">
        <input type="text" name="search" placeholder="Search items or categories" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <a href="menu.php"><button type="button" onclick="resetForm()">Clear</button></a>
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
            // Use the already fetched $catResult
            $catResult->data_seek(0); // Reset pointer
            while ($cat = $catResult->fetch_assoc()):
            ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td>
                    <a href="?delete_cat=<?= $cat['id'] ?>" onclick="return confirm('Delete this category?')"><button type="button" class="delete_btn" onclick="resetForm()">Delete</button></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Menu Form -->
    <h2>🍔 Add / Edit Menu Item</h2>
    <form method="POST">
        <input type="hidden" name="id" id="menu_id" value="">
        <input type="text" name="name" id="menu_name" placeholder="Item Name" required><br><br>
        <textarea name="description" id="menu_desc" placeholder="Description"></textarea><br><br>
        <select name="category_id" id="menu_cat" required>
            <option value="">Select Category</option>
            <?php
            $catResult->data_seek(0); // Reset again for dropdown
            while ($cat = $catResult->fetch_assoc()):
            ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>
        <input type="number" name="price" id="menu_price" placeholder="Price" step="0.01" required><br><br>
        <button type="submit">Save Menu Item</button>
        <button type="button" class="delete_btn" onclick="resetForm()">Cancel</button>
    </form>

    <!-- Menu List -->
    <table border="1" cellpadding="8" style="margin-top:20px; width:100%;">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Category</th><th>Description</th><th>Price</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while ($menu = $menuResult->fetch_assoc()): ?>
            <tr>
                <td><?= $menu['id'] ?></td>
                <td><?= htmlspecialchars($menu['name']) ?></td>
                <td><?= htmlspecialchars($menu['category_name']) ?></td>
                <td><?= htmlspecialchars($menu['description']) ?></td>
                <td>Rs.<?= number_format($menu['price'], 2) ?></td>
                <td>
                    <button class="edit_btn" onclick="editMenu(
                        <?= $menu['id'] ?>,
                        '<?= htmlspecialchars(addslashes($menu['name'])) ?>',
                        '<?= htmlspecialchars(addslashes($menu['description'])) ?>',
                        <?= $menu['category_id'] ?>,
                        <?= $menu['price'] ?>
                    )">Edit</button>
                    <a href="?delete=<?= $menu['id'] ?>" onclick="return confirm('Delete this menu item?')"><button type="button" class="delete_btn" onclick="resetForm()">Delete</button></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function editMenu(id, name, desc, catId, price) {
    document.getElementById('menu_id').value = id;
    document.getElementById('menu_name').value = name;
    document.getElementById('menu_desc').value = desc;
    document.getElementById('menu_cat').value = catId;
    document.getElementById('menu_price').value = price;
}

function resetForm() {
    document.getElementById('menu_id').value = '';
    document.getElementById('menu_name').value = '';
    document.getElementById('menu_desc').value = '';
    document.getElementById('menu_cat').value = '';
    document.getElementById('menu_price').value = '';
}
</script>

<?php include('../includes/footer.php'); ?>
