```php
<?php

include("../includes/auth.php");

requireLogin();

include("../includes/db.php");

if (!checkRole('manager')) {
    exit("Access Denied");
}

if (!isset($_SESSION['restaurant_id'])) {
    exit("Restaurant ID missing in session");
}

$restaurantId = (int) $_SESSION['restaurant_id'];


/*
|--------------------------------------------------------------------------
| Helper: Execute Prepared Statement
|--------------------------------------------------------------------------
*/
function prepareStatement($conn, $sql)
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die(
            "SQL Prepare Error: " .
            htmlspecialchars($conn->error) .
            "<br><br>Query:<br>" .
            htmlspecialchars($sql)
        );
    }

    return $stmt;
}


/*
|--------------------------------------------------------------------------
| Helper: Count Query
|--------------------------------------------------------------------------
*/
function getCount($conn, $query, $restaurantId)
{
    $stmt = prepareStatement($conn, $query);

    $stmt->bind_param("i", $restaurantId);

    if (!$stmt->execute()) {
        die(
            "SQL Execute Error: " .
            htmlspecialchars($stmt->error)
        );
    }

    $result = $stmt->get_result();

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    $stmt->close();

    return $row['total'] ?? 0;
}


/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

// Total Orders
$totalOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE restaurant_id = ?",
    $restaurantId
);


// Pending Orders
$pendingOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE restaurant_id = ?
     AND status = 'pending'",
    $restaurantId
);


// Completed Orders
$completedOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE restaurant_id = ?
     AND status = 'completed'",
    $restaurantId
);


// Total Staff
$totalStaff = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'staff'
     AND restaurant_id = ?",
    $restaurantId
);


// Total Menu Items
$totalMenu = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM menus
     WHERE restaurant_id = ?",
    $restaurantId
);


/*
|--------------------------------------------------------------------------
| Recent Orders
|--------------------------------------------------------------------------
*/

$recentStmt = prepareStatement(
    $conn,
    "SELECT
        id,
        status,
        total_amount,
        created_at,
        customer_name
     FROM orders
     WHERE restaurant_id = ?
     ORDER BY created_at DESC
     LIMIT 5"
);

$recentStmt->bind_param("i", $restaurantId);
$recentStmt->execute();

$recentOrders = $recentStmt->get_result();


/*
|--------------------------------------------------------------------------
| Daily Orders - Last 7 Days
|--------------------------------------------------------------------------
*/

$orderDataStmt = prepareStatement(
    $conn,
    "SELECT
        DATE(created_at) AS day,
        COUNT(*) AS total
     FROM orders
     WHERE restaurant_id = ?
     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(created_at)
     ORDER BY DATE(created_at) ASC"
);

$orderDataStmt->bind_param("i", $restaurantId);
$orderDataStmt->execute();

$orderDataResult = $orderDataStmt->get_result();

$dailyLabels = [];
$dailyCounts = [];

while ($row = $orderDataResult->fetch_assoc()) {

    $dailyLabels[] = $row['day'];
    $dailyCounts[] = (int) $row['total'];
}

$orderDataStmt->close();


/*
|--------------------------------------------------------------------------
| Revenue Chart - Last 7 Days
|--------------------------------------------------------------------------
|
| IMPORTANT:
| This assumes order_items contains:
|     quantity
|     unit_price
|
*/

$revenueStmt = prepareStatement(
    $conn,
    "SELECT
        DATE(o.created_at) AS day,
        IFNULL(SUM(oi.unit_price * oi.quantity), 0) AS revenue
     FROM orders o
     LEFT JOIN order_items oi
        ON o.id = oi.order_id
     WHERE o.restaurant_id = ?
     AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(o.created_at)
     ORDER BY DATE(o.created_at) ASC"
);

$revenueStmt->bind_param("i", $restaurantId);
$revenueStmt->execute();

$revenueData = $revenueStmt->get_result();

$revenueLabels = [];
$revenueTotals = [];

while ($row = $revenueData->fetch_assoc()) {

    $revenueLabels[] = $row['day'];
    $revenueTotals[] = (float) $row['revenue'];
}

$revenueStmt->close();


/*
|--------------------------------------------------------------------------
| Top Selling Items - Last 30 Days
|--------------------------------------------------------------------------
*/

$topStmt = prepareStatement(
    $conn,
    "SELECT
        m.name,
        SUM(oi.quantity) AS sold
     FROM order_items oi

     INNER JOIN menus m
        ON oi.menu_id = m.id

     INNER JOIN orders o
        ON oi.order_id = o.id

     WHERE o.restaurant_id = ?
     AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)

     GROUP BY m.id, m.name

     ORDER BY sold DESC

     LIMIT 5"
);

$topStmt->bind_param("i", $restaurantId);
$topStmt->execute();

$topData = $topStmt->get_result();

$topLabels = [];
$topSold = [];

while ($row = $topData->fetch_assoc()) {

    $topLabels[] = $row['name'];
    $topSold[] = (int) $row['sold'];
}

$topStmt->close();

?>

<?php include('header/header.php'); ?>

<?php include('sidebar.php'); ?>


<div class="main-content">

    <h1>Manager Dashboard</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['username'] ?? 'Manager') ?>
    </p>


    <!-- Dashboard Cards -->

    <div class="dashboard-cards">

        <div class="card">
            <h3>Total Orders</h3>
            <p><?= $totalOrders ?></p>
        </div>

        <div class="card">
            <h3>Pending Orders</h3>
            <p><?= $pendingOrders ?></p>
        </div>

        <div class="card">
            <h3>Completed Orders</h3>
            <p><?= $completedOrders ?></p>
        </div>

        <div class="card">
            <h3>Total Staff</h3>
            <p><?= $totalStaff ?></p>
        </div>

        <div class="card">
            <h3>Total Menu Items</h3>
            <p><?= $totalMenu ?></p>
        </div>

    </div>


    <!-- Recent Orders -->

    <h2>Recent Orders</h2>

    <table
        border="1"
        cellpadding="5"
        cellspacing="0"
        style="width:100%; margin-bottom:40px;"
    >

        <thead>

            <tr>

                <th>Order ID</th>

                <th>Customer</th>

                <th>Status</th>

                <th>Total</th>

                <th>Created At</th>

            </tr>

        </thead>


        <tbody>

            <?php while ($order = $recentOrders->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= (int) $order['id'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $order['customer_name'] ?? 'Walk-in Customer'
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            ucfirst($order['status'])
                        ) ?>
                    </td>

                    <td>
                        Rs.
                        <?= number_format(
                            (float) $order['total_amount'],
                            2
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($order['created_at']) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </tbody>

    </table>


    <!-- Daily Orders -->

    <h2>📈 Daily Orders (Last 7 Days)</h2>

    <canvas
        id="ordersChart"
        height="100"
    ></canvas>


    <!-- Revenue -->

    <h2>💵 Revenue Report (Last 7 Days)</h2>

    <canvas
        id="revenueChart"
        height="100"
    ></canvas>


    <!-- Top Selling -->

    <h2>🔥 Top Selling Items (Last 30 Days)</h2>

    <canvas
        id="topItemsChart"
        height="100"
    ></canvas>

</div>


<!-- Chart.js -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

const dailyLabels =
    <?= json_encode($dailyLabels) ?>;

const dailyCounts =
    <?= json_encode($dailyCounts) ?>;

const revenueLabels =
    <?= json_encode($revenueLabels) ?>;

const revenueTotals =
    <?= json_encode($revenueTotals) ?>;

const topLabels =
    <?= json_encode($topLabels) ?>;

const topSold =
    <?= json_encode($topSold) ?>;


/*
|--------------------------------------------------------------------------
| Daily Orders Chart
|--------------------------------------------------------------------------
*/

new Chart(
    document.getElementById("ordersChart"),
    {

        type: "line",

        data: {

            labels: dailyLabels,

            datasets: [{

                label: "Orders",

                data: dailyCounts,

                borderColor: "blue",

                backgroundColor: "rgba(0, 0, 255, 0.2)",

                fill: true,

                tension: 0.3

            }]

        },

        options: {

            responsive: true,

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {

                        stepSize: 1

                    }

                }

            }

        }

    }
);


/*
|--------------------------------------------------------------------------
| Revenue Chart
|--------------------------------------------------------------------------
*/

new Chart(
    document.getElementById("revenueChart"),
    {

        type: "bar",

        data: {

            labels: revenueLabels,

            datasets: [{

                label: "Revenue (Rs.)",

                data: revenueTotals,

                backgroundColor: "green"

            }]

        },

        options: {

            responsive: true,

            scales: {

                y: {

                    beginAtZero: true

                }

            }

        }

    }
);


/*
|--------------------------------------------------------------------------
| Top Selling Items Chart
|--------------------------------------------------------------------------
*/

new Chart(
    document.getElementById("topItemsChart"),
    {

        type: "pie",

        data: {

            labels: topLabels,

            datasets: [{

                label: "Sold",

                data: topSold,

                backgroundColor: [

                    "#e74c3c",
                    "#f39c12",
                    "#f1c40f",
                    "#3498db",
                    "#9b59b6"

                ]

            }]

        },

        options: {

            responsive: true

        }

    }
);

</script>


<script src="/js/poll_orders.js"></script>

<div id="live-orders"></div>


<?php include('../includes/footer.php'); ?>
```

### One important thing

I changed this:

```php
SUM(oi.price * oi.quantity)
```

to:

```php
SUM(oi.unit_price * oi.quantity)
```

because the POS database structure we have been building uses `unit_price` in `order_items`.

Your database should therefore have something like:

```sql
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status ENUM('pending','preparing','ready','served','cancelled')
        DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### If you still get an error

The new code will now tell you the **actual SQL error** instead of the confusing:

```text
Call to a member function bind_param() on bool
```

For example, if you get:

```text
Unknown column 'oi.unit_price'
```

then we know your `order_items` table has a different column name.

If you get:

```text
Table 'restaurant.order_items' doesn't exist
```

then your database is missing that table.

If you get:

```text
Unknown column 'restaurant_id'
```

then one of your tables has a different structure.

**Run this version first and send me the new error if one appears.** That will let us fix the database/query mismatch directly.
