<?php
include("../includes/auth.php");
requireLogin();

if (!checkRole('manager')) {
    exit("Access Denied");
}

include("../includes/db.php");

$restaurantId = $_SESSION['restaurant_id'] ?? 0;

// Receive and sanitize POST data
$fromDate = $_POST['from'] ?? '';
$toDate = $_POST['to'] ?? '';
$tableFilter = isset($_POST['table']) && is_numeric($_POST['table']) ? (int)$_POST['table'] : 0;

// Validate dates format
if ($fromDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
    $fromDate = '';
}
if ($toDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    $toDate = '';
}

// Build query with filters
$where = "WHERE o.restaurant_id = ?";
$params = [$restaurantId];
$types = "i";

if ($fromDate) {
    $where .= " AND DATE(o.created_at) >= ?";
    $params[] = $fromDate;
    $types .= "s";
}
if ($toDate) {
    $where .= " AND DATE(o.created_at) <= ?";
    $params[] = $toDate;
    $types .= "s";
}
if ($tableFilter) {
    $where .= " AND o.table_id = ?";
    $params[] = $tableFilter;
    $types .= "i";
}

// Fetch orders for export
$query = "
    SELECT o.id, o.customer_name, o.total_amount, o.status, o.created_at, t.table_number
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    $where
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Check export type
if (isset($_POST['export_csv'])) {
    // Export CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report.csv"');

    $output = fopen('php://output', 'w');
    // CSV header row
    fputcsv($output, ['Order ID', 'Customer Name', 'Table Number', 'Status', 'Total Amount', 'Created At']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['customer_name'],
            $row['table_number'] ?? 'N/A',
            ucfirst($row['status']),
            number_format($row['total_amount'], 2),
            $row['created_at']
        ]);
    }
    fclose($output);
    exit();
} elseif (isset($_POST['export_pdf'])) {
    // Export PDF
    require_once '../vendor/autoload.php'; // Assuming you have installed TCPDF or similar via Composer

    $pdf = new \TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Title
    $pdf->Write(0, 'Sales Report', '', 0, 'C', true, 0, false, false, 0);
    $pdf->Ln(5);

    // Table header
    $tblHeader = '
    <table border="1" cellpadding="4">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th><b>Order ID</b></th>
                <th><b>Customer Name</b></th>
                <th><b>Table Number</b></th>
                <th><b>Status</b></th>
                <th><b>Total Amount</b></th>
                <th><b>Created At</b></th>
            </tr>
        </thead>
        <tbody>
    ';

    $tblBody = '';
    while ($row = $result->fetch_assoc()) {
        $tblBody .= '<tr>
            <td>' . $row['id'] . '</td>
            <td>' . htmlspecialchars($row['customer_name']) . '</td>
            <td>' . htmlspecialchars($row['table_number'] ?? 'N/A') . '</td>
            <td>' . ucfirst($row['status']) . '</td>
            <td>Rs. ' . number_format($row['total_amount'], 2) . '</td>
            <td>' . $row['created_at'] . '</td>
        </tr>';
    }

    $tblFooter = '</tbody></table>';

    $pdf->writeHTML($tblHeader . $tblBody . $tblFooter, true, false, false, false, '');

    $pdf->Output('sales_report.pdf', 'D'); // Download PDF
    exit();
} else {
    echo "Invalid export request.";
}
