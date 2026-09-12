<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

$restaurantId = $_SESSION['restaurant_id'];
// For orders related to a table, set your table_id here. For now, 0 means no table selected.
$tableId = $_GET['table_id'] ?? 0;
$tableId = intval($tableId);

// Fetch categories for filter dropdown
$catStmt = $conn->prepare("SELECT DISTINCT category FROM menus WHERE restaurant_id = ? ORDER BY category ASC");
$catStmt->bind_param("i", $restaurantId);
$catStmt->execute();
$catResult = $catStmt->get_result();
$categories = [];
while ($catRow = $catResult->fetch_assoc()) {
    $categories[] = $catRow['category'];
}

// Fetch all menus for restaurant
$menuStmt = $conn->prepare("SELECT id, name, category, price FROM menus WHERE restaurant_id = ? ORDER BY name ASC");
$menuStmt->bind_param("i", $restaurantId);
$menuStmt->execute();
$menuResult = $menuStmt->get_result();

include('../includes/header.php');
include('sidebar.php');
?>

<style>
  h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #2c3e50;
    font-weight: 700;
  }

  .filters {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
  }

  .filters input[type="search"],
  .filters select {
    padding: 10px 15px;
    font-size: 1.1rem;
    border-radius: 8px;
    border: 2px solid #2980b9;
    outline-color: #2980b9;
    min-width: 220px;
    max-width: 300px;
    transition: border-color 0.3s;
  }
  .filters input[type="search"]:focus,
  .filters select:focus {
    border-color: #1c5980;
  }

  .menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
  }

  .menu-card {
    background: #f0f4f8;
    border-radius: 15px;
    box-shadow: 0 10px 18px rgba(41, 128, 185, 0.15);
    padding: 20px 25px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: box-shadow 0.3s, transform 0.3s;
  }
  .menu-card:hover {
    box-shadow: 0 18px 38px rgba(41, 128, 185, 0.35);
    transform: translateY(-5px);
  }

  .menu-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: #34495e;
    margin-bottom: 5px;
  }
  .menu-category {
    font-size: 0.95rem;
    font-style: italic;
    color: #7f8c8d;
    margin-bottom: 10px;
  }
  .menu-price {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2980b9;
    margin-bottom: 18px;
  }

  .qty-controls {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .qty-controls button {
    width: 36px;
    height: 36px;
    border: none;
    background-color: #2980b9;
    color: white;
    font-size: 1.8rem;
    line-height: 1;
    border-radius: 6px;
    cursor: pointer;
    user-select: none;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0;
    transition: background-color 0.3s;
  }
  .qty-controls button:hover {
    background-color: #1c5980;
  }
  .qty-controls input[type="number"] {
    width: 50px;
    text-align: center;
    font-size: 1.2rem;
    padding: 6px 8px;
    border-radius: 6px;
    border: 1.5px solid #ccc;
    -moz-appearance: textfield;
  }
  .qty-controls input[type=number]::-webkit-inner-spin-button, 
  .qty-controls input[type=number]::-webkit-outer-spin-button { 
      -webkit-appearance: none;
      margin: 0;
  }

  .add-order-btn {
    margin-top: 15px;
    padding: 12px;
    background-color: #27ae60;
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s;
  }
  .add-order-btn:hover {
    background-color: #1e8449;
  }

  .no-menus {
    text-align: center;
    font-size: 1.2rem;
    color: #777;
  }

  a button{
       background: whitesmoke;
    color: black;
    padding: 10px;
    font-size: 25px;
    margin: -35px;
    border: none;
    border-radius: 25px;

  }
</style>

<div class="main-content" role="main">
<br>
    <a href="table_management.php"><button><</button></a>
  <br><br>
  <h1>Take Order<?= $tableId > 0 ? " for Table #$tableId" : "" ?></h1>

  <div class="filters" role="search">
    <input type="search" id="searchInput" placeholder="Search menu by name..." aria-label="Search menu by name" />
    <select id="categoryFilter" aria-label="Filter menu by category">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div id="menuGrid" class="menu-grid" aria-live="polite" aria-atomic="true" aria-relevant="additions removals">
    <?php if ($menuResult->num_rows === 0): ?>
      <p class="no-menus">No menu items found.</p>
    <?php else: ?>
      <?php while ($menu = $menuResult->fetch_assoc()): ?>
        <div class="menu-card" 
             data-menu-id="<?= $menu['id'] ?>" 
             data-name="<?= htmlspecialchars(strtolower($menu['name'])) ?>" 
             data-category="<?= htmlspecialchars($menu['category']) ?>">
          <div class="menu-name"><?= htmlspecialchars($menu['name']) ?></div>
          <div class="menu-category"><?= htmlspecialchars($menu['category']) ?></div>
          <div class="menu-price">Rs. <?= number_format($menu['price'], 2) ?></div>

          <div class="qty-controls" role="group" aria-label="Quantity controls">
            <button type="button" class="qty-decrease" aria-label="Decrease quantity">−</button>
            <input type="number" name="quantity" value="1" min="1" aria-live="polite" aria-label="Quantity" />
            <button type="button" class="qty-increase" aria-label="Increase quantity">+</button>
          </div>

          <button class="add-order-btn" type="button" aria-label="Add <?= htmlspecialchars($menu['name']) ?> to order">
            Add to Order
          </button>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<script>
  const searchInput = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const menuGrid = document.getElementById('menuGrid');
  const menuCards = Array.from(menuGrid.querySelectorAll('.menu-card'));

  function filterMenus() {
    const searchValue = searchInput.value.toLowerCase();
    const categoryValue = categoryFilter.value;

    menuCards.forEach(card => {
      const name = card.getAttribute('data-name');
      const category = card.getAttribute('data-category');

      const matchesSearch = name.includes(searchValue);
      const matchesCategory = categoryValue === '' || category === categoryValue;

      card.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
    });
  }

  searchInput.addEventListener('input', filterMenus);
  categoryFilter.addEventListener('change', filterMenus);

  menuGrid.addEventListener('click', async (e) => {
    if (e.target.classList.contains('qty-increase') || e.target.classList.contains('qty-decrease')) {
      const card = e.target.closest('.menu-card');
      const qtyInput = card.querySelector('input[type="number"]');
      let currentVal = parseInt(qtyInput.value) || 1;

      if (e.target.classList.contains('qty-increase')) {
        qtyInput.value = currentVal + 1;
      } else if (e.target.classList.contains('qty-decrease')) {
        if (currentVal > 1) qtyInput.value = currentVal - 1;
      }
    }

    if (e.target.classList.contains('add-order-btn')) {
      const btn = e.target;
      const card = btn.closest('.menu-card');
      const menuId = card.getAttribute('data-menu-id');
      const qtyInput = card.querySelector('input[type="number"]');
      const quantity = parseInt(qtyInput.value);

      if (!menuId) {
        alert('Menu item not found.');
        return;
      }
      if (isNaN(quantity) || quantity < 1) {
        alert('Please enter a valid quantity (minimum 1).');
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Adding...';

      try {
        const response = await fetch('add_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            menu_id: menuId,
            quantity: quantity,
            table_id: <?= json_encode($tableId) ?>// pass table_id here if relevant, or 0
          }),
        });

        const data = await response.json();

        if (data.success) {
          alert('Added to order successfully!');
          qtyInput.value = 1;
        } else {
          alert('Failed to add to order: ' + (data.message || 'Unknown error'));
        }
      } catch (error) {
        alert('Network error. Please try again.');
      }

      btn.disabled = false;
      btn.textContent = 'Add to Order';
    }
  });
</script>

<?php include('../includes/footer.php'); ?>
