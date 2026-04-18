<?php
include 'auth_check.php';
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch distinct company names for filter dropdown
$companyQuery = "SELECT DISTINCT PartyName FROM sales WHERE PartyName IS NOT NULL AND PartyName != ''";
$companyResult = $conn->query($companyQuery);

// Get hide options from GET
$hide_net = isset($_GET['hide_net']) && $_GET['hide_net'] == '1';
$hide_profit = isset($_GET['hide_profit']) && $_GET['hide_profit'] == '1';

// Sorting
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'IssueDate';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Build WHERE clause – only Reissue records
$where = " WHERE Remarks = 'Reissue'";

if (isset($_GET['company']) && !empty($_GET['company'])) {
    $company = $conn->real_escape_string($_GET['company']);
    $where .= " AND PartyName = '$company'";
}
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = $conn->real_escape_string($_GET['invoice']);
    $where .= " AND invoice_number LIKE '%$invoice%'";
}
if (isset($_GET['pnr']) && !empty($_GET['pnr'])) {
    $pnr_ = $conn->real_escape_string($_GET['pnr']);
    $where .= " AND PNR LIKE '%$pnr_%'";
}
if (isset($_GET['from_date']) && !empty($_GET['from_date']) && isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $from_date = $conn->real_escape_string($_GET['from_date']);
    $to_date = $conn->real_escape_string($_GET['to_date']);
    $where .= " AND IssueDate BETWEEN '$from_date' AND '$to_date'";
}

// Exclude voided records (optional, but safe)
$where .= " AND (Remarks != 'Voided' OR Remarks IS NULL)";
$where .= " AND (Remarks != 'Void Transaction' OR Remarks IS NULL)";

// Allowed sort columns
$allowed_sort_columns = ['IssueDate', 'PartyName', 'TicketNumber', 'BillAmount', 'NetPayment'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'IssueDate';
}
$sort_order = strtoupper($sort_order);
if ($sort_order != 'ASC' && $sort_order != 'DESC') {
    $sort_order = 'DESC';
}

// Query with user join
$salesQuery = "SELECT sales.*, user.UserName AS created_by_name 
               FROM sales 
               LEFT JOIN user ON sales.created_by_user_id = user.UserId 
               $where 
               ORDER BY $sort_by $sort_order";
$salesResult = $conn->query($salesQuery);

// Delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM sales WHERE SaleID=$id";
    if ($conn->query($deleteQuery) === TRUE) {
        echo "<script>alert('Record deleted successfully!'); window.location='reissue.php';</script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Helper functions
function safeHtml($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function sortLink($column, $label, $current_sort, $current_order) {
    $new_order = ($current_sort == $column && $current_order == 'DESC') ? 'ASC' : 'DESC';
    $icon = '';
    if ($current_sort == $column) {
        $icon = $current_order == 'DESC' ? ' ↓' : ' ↑';
    }
    $params = array_filter($_GET, function($key) {
        return !in_array($key, ['sort_by', 'sort_order']);
    }, ARRAY_FILTER_USE_KEY);
    $query = http_build_query($params);
    return "<a href='?sort_by=$column&sort_order=$new_order&$query' style='color: white; text-decoration: none;'>$label$icon</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="logo.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reissue Records</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa; color: #333; margin: 0; padding: 0; }
        .container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 15px; margin: 10px; overflow-x: auto; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eaeaea; font-size: 1.3rem; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; min-width: 1200px; }
        th, td { padding: 6px 5px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background-color: #4a71ff; color: white; font-weight: 600; font-size: 10px; white-space: nowrap; }
        th a { color: white; text-decoration: none; }
        th a:hover { text-decoration: underline; }
        .search-container { display: flex; gap: 8px; margin-bottom: 15px; justify-content: center; flex-wrap: wrap; }
        .search-container select, .search-container input { padding: 6px 8px; width: 150px; border: 1px solid #ddd; border-radius: 5px; font-size: 12px; }
        .search-container button { padding: 6px 12px; background: #4a71ff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .export-btn { padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .hide-options { display: inline-flex; gap: 10px; margin-left: 10px; align-items: center; }
        .btn { padding: 2px 5px; border: none; cursor: pointer; text-decoration: none; font-size: 9px; border-radius: 3px; display: inline-block; margin: 1px 0; text-align: center; }
        .edit-btn { background-color: #079320; color: white; }
        .delete-btn { background-color: #d9534f; color: white; }
        .btn-primary { background-color: #4a71ff; color: white; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-success { background-color: #28a745; color: white; }
        .btn:hover { opacity: 0.9; }
        tr:nth-child(odd) { background-color: #f8f9ff; }
        tr:nth-child(even) { background-color: #ffffff; }
        .small-text { font-size: 9px; color: #666; line-height: 1.2; }
        .badge { display: inline-block; padding: 2px 4px; border-radius: 8px; font-size: 9px; font-weight: bold; }
        .success { background-color: #28a745; color: white; }
        .danger { background-color: #dc3545; color: white; }
        .warning { background-color: #ffc107; color: #212529; }
        .secondary { background-color: #6c757d; color: white; }
        .action-cell { white-space: nowrap; }
        .export-container { text-align: center; margin: 15px 0; display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap; }
        .sort-info { text-align: right; font-size: 10px; color: #666; margin-bottom: 8px; }
        @media (max-width: 768px) {
            .container { padding: 10px; margin: 5px; }
            .search-container select, .search-container input { width: 100%; }
            .search-container { flex-direction: column; align-items: stretch; }
            .export-container { flex-direction: column; }
            .hide-options { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <h2>Reissue Records</h2>
    
    <form method="GET" class="search-container">
        <select name="company">
            <option value="">All Companies</option>
            <?php while ($row = $companyResult->fetch_assoc()) : ?>
                <option value="<?= safeHtml($row['PartyName']) ?>" <?= (isset($_GET['company']) && $_GET['company'] == $row['PartyName']) ? 'selected' : '' ?>>
                    <?= safeHtml($row['PartyName']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="invoice" placeholder="Invoice No" value="<?= isset($_GET['invoice']) ? safeHtml($_GET['invoice']) : '' ?>">
        <input type="text" name="pnr" placeholder="PNR" value="<?= isset($_GET['pnr']) ? safeHtml($_GET['pnr']) : '' ?>">
        <input type="date" name="from_date" value="<?= isset($_GET['from_date']) ? safeHtml($_GET['from_date']) : '' ?>">
        <input type="date" name="to_date" value="<?= isset($_GET['to_date']) ? safeHtml($_GET['to_date']) : '' ?>">
        <button type="submit">Search</button>
    </form>

    <div class="export-container">
        <?php
        $export_params = [];
        if (isset($_GET['company']) && !empty($_GET['company'])) $export_params[] = "company=" . urlencode($_GET['company']);
        if (isset($_GET['from_date']) && !empty($_GET['from_date'])) $export_params[] = "from_date=" . urlencode($_GET['from_date']);
        if (isset($_GET['to_date']) && !empty($_GET['to_date'])) $export_params[] = "to_date=" . urlencode($_GET['to_date']);
        if (isset($_GET['sort_by']) && !empty($_GET['sort_by'])) $export_params[] = "sort_by=" . urlencode($_GET['sort_by']);
        if (isset($_GET['sort_order']) && !empty($_GET['sort_order'])) $export_params[] = "sort_order=" . urlencode($_GET['sort_order']);
        if ($hide_net) $export_params[] = "hide_net=1";
        if ($hide_profit) $export_params[] = "hide_profit=1";
        $export_url = "export_reissue_excel.php"; // You may create this file later
        if (!empty($export_params)) $export_url .= "?" . implode("&", $export_params);
        ?>
        <a href="<?= $export_url ?>" class="export-btn">Export to Excel</a>
        
        <div class="hide-options">
            <label><input type="checkbox" id="hide_net_checkbox" <?= $hide_net ? 'checked' : '' ?> onchange="toggleHideOption('hide_net', this.checked)"> Hide Net</label>
            <label><input type="checkbox" id="hide_profit_checkbox" <?= $hide_profit ? 'checked' : '' ?> onchange="toggleHideOption('hide_profit', this.checked)"> Hide Profit</label>
        </div>
    </div>

    <div class="sort-info">
        Sorting by: <strong><?= htmlspecialchars($sort_by) ?></strong> (<?= $sort_order == 'DESC' ? 'Newest first' : 'Oldest first' ?>)
    </div>

    <table>
        <thead>
            <tr>
                <th><?= sortLink('PartyName', 'Company', $sort_by, $sort_order) ?></th>
                <th>Passenger</th>
                <th>Invoice / Actions</th>
                <th>Route</th>
                <th>Airlines / Source</th>
                <th>PNR</th>
                <th><?= sortLink('TicketNumber', 'Ticket No', $sort_by, $sort_order) ?></th>
                <th><?= sortLink('IssueDate', 'Dates', $sort_by, $sort_order) ?></th>
                <th>Days</th>
                <th>Status</th>
                <th><?= sortLink('BillAmount', 'Amount', $sort_by, $sort_order) ?></th>
                <th>Sales Person</th>
                <th>Entry By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $hasResults = false;
        while ($row = $salesResult->fetch_assoc()) : 
            $hasResults = true;
            $issue_date = new DateTime($row['IssueDate']);
            $today = new DateTime();
            $interval = $issue_date->diff($today);
            $day_passes = $interval->days;
        ?>
            <tr>
                <td><?= safeHtml($row['PartyName']) ?></td>
                <td><?= safeHtml($row['PassengerName']) ?></td>
                <td>
                    <?= safeHtml($row['invoice_number']) ?>
                    <div style="margin-top: 3px;">
                        <a href="redirect_reissue.php?id=<?= $row['SaleID'] ?>" class="btn btn-success">Reissue</a>
                        <a href="redirect_refund.php?id=<?= $row['SaleID'] ?>" class="btn btn-warning">Refund</a>
                    </div>
                </td>
                <td><?= safeHtml($row['TicketRoute']) ?></td>
                <td>
                    <?= safeHtml($row['airlines']) ?><br>
                    <span class="small-text">Src: <?= safeHtml($row['Source']) ?> | Sys: <?= safeHtml($row['system']) ?></span>
                </td>
                <td><?= safeHtml($row['PNR']) ?></td>
                <td><?= safeHtml($row['TicketNumber']) ?></td>
                <td>
                    <span class="small-text">Issue: <?= safeHtml($row['IssueDate']) ?></span><br>
                    <span class="small-text">Dep: <?= safeHtml($row['FlightDate']) ?></span><br>
                    <span class="small-text">Ret: <?= safeHtml($row['ReturnDate']) ?></span>
                </td>
                <td><?= $day_passes ?> days</td>
                <td>
                    <?php 
                    $statusClass = '';
                    switch($row['PaymentStatus'] ?? '') {
                        case 'Paid': $statusClass = 'success'; break;
                        case 'Due': $statusClass = 'danger'; break;
                        case 'Partially Paid': $statusClass = 'warning'; break;
                        default: $statusClass = 'secondary';
                    }
                    ?>
                    <span class="badge <?= $statusClass ?>"><?= substr($row['PaymentStatus'] ?? '', 0, 1) ?></span>
                    <span class="small-text"><br>M: <?= safeHtml($row['PaymentMethod']) ?></span>
                </td>
                <td>
                    <?= number_format($row['BillAmount'] ?? 0, 2) ?>
                    <?php if (!$hide_net): ?>
                        <br><span class="small-text">Net: <?= number_format($row['NetPayment'] ?? 0, 2) ?></span>
                    <?php endif; ?>
                    <?php if (!$hide_profit): ?>
                        <br><span class="small-text">Pr: <?= number_format($row['Profit'] ?? 0, 2) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= safeHtml($row['SalesPersonName']) ?></td>
                <td><?= safeHtml($row['created_by_name'] ?? 'Unknown') ?></td>
                <td class="action-cell">
                    <a href="edit.php?id=<?= $row['SaleID'] ?>" class="btn edit-btn">Edit</a>
                    <a href="reissue.php?delete=<?= $row['SaleID'] ?>" class="btn delete-btn" onclick="return confirm('Delete this record?')">Del</a>
                    <form action="invoice_cart2.php" method="POST" style="margin-top: 2px;">
                        <input type="hidden" name="sell_id" value="<?= $row['SaleID'] ?>">
                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php if (!$hasResults): ?>
            <tr><td colspan="14" style="text-align: center; padding: 25px;">No reissue records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    function toggleHideOption(option, isChecked) {
        const urlParams = new URLSearchParams(window.location.search);
        if (isChecked) urlParams.set(option, '1');
        else urlParams.delete(option);
        window.location.search = urlParams.toString();
    }
</script>

</body>
</html>

<?php $conn->close(); ?>