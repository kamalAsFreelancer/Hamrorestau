<?php
include("../includes/auth.php");
requireLogin();

if (!checkRole('manager')) exit("Access Denied");

include("../includes/db.php");

$restaurantId = $_SESSION['restaurant_id'] ?? 0;
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$tableId = $_GET['table_id'] ?? '';

$where = "o.restaurant_id = ?";
$params = [$restaurantId];
$types = "i";

if (!empty($from)) {
    $where .= " AND o.created_at >= ?";
    $params[] = $from . " 00:00:00";
    $types .= "s";
}

if (!empty($to)) {
    $where .= " AND o.created_at <= ?";
    $params[] = $to . " 23:59:59";
    $types .= "s";
}

if (!empty($tableId)) {
    $where .= " AND o.table_id = ?";
    $params[] = $tableId;
    $types .= "i";
}

// Get table list for dropdown
$tables = [];
$tableStmt = $conn->prepare("SELECT id, table_number FROM tables WHERE restaurant_id = ?");
$tableStmt->bind_param("i", $restaurantId);
$tableStmt->execute();
$res = $tableStmt->get_result();
while ($row = $res->fetch_assoc()) {
    $tables[] = $row;
}

// Fetch orders
$sql = "SELECT o.*, t.table_number FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.id 
        WHERE $where ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$totalSales = 0;
$totalOrders = $result->num_rows;

include('header/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h1>Sales Report</h1>

    <form method="GET" class="filter-form" style="margin-bottom: 20px;">
        <label>From: <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
        <label>To: <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
        <label>Table:
            <select name="table_id">
                <option value="">-- All --</option>
                <?php foreach ($tables as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $tableId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['table_number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Filter</button>
    </form>

    <button onclick="exportTableToCSV('sales_report.csv')">Export CSV</button>
    <button onclick="exportTableToPDF()">Export PDF</button>

    <h3>Total Orders: <?= $totalOrders ?></h3>

    <table id="sales-table" border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Table</th>
                <th>Status</th>
                <th>Total Amount (Rs.)</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $totalSales += $row['total_amount']; ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= htmlspecialchars($row['table_number'] ?? 'N/A') ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td><?= number_format($row['total_amount'], 2) ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3>Total Sales: Rs. <?= number_format($totalSales, 2) ?></h3>
</div>

<!-- JavaScript for Export -->
<script>
function exportTableToCSV(filename) {
    let csv = [];
    const rows = document.querySelectorAll("table tr");

    for (let row of rows) {
        const cols = row.querySelectorAll("td, th");
        const rowData = Array.from(cols).map(col => `"${col.innerText}"`);
        csv.push(rowData.join(","));
    }

    const csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    const link = document.createElement("a");
    link.download = filename;
    link.href = URL.createObjectURL(csvFile);
    link.click();
}

function exportTableToPDF() {
    const printContents = document.getElementById("sales-table").outerHTML;
    const w = window.open();
    w.document.write("<html><head><title>Sales Report</title></head><body>");
    w.document.write("<h1>Sales Report</h1>");
    w.document.write(printContents);
    w.document.write("</body></html>");
    w.print();
    w.close();
}
</script>

<?php include("../includes/footer.php"); ?>
