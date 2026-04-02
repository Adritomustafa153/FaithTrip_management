<?php
include 'auth_check.php';
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch company names for dropdown
$companyQuery = "SELECT DISTINCT PartyName FROM sales WHERE PartyName IS NOT NULL AND PartyName != ''";
$companyResult = $conn->query($companyQuery);

// Get hide options from GET parameters
$hide_net = isset($_GET['hide_net']) && $_GET['hide_net'] == '1';
$hide_profit = isset($_GET['hide_profit']) && $_GET['hide_profit'] == '1';

// Get sorting option
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'IssueDate';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Fetch sales records - EXCLUDE voided and void transaction records from main list
$where = "";
if (isset($_GET['company']) && !empty($_GET['company'])) {
    $company = $conn->real_escape_string($_GET['company']);
    $where .= " WHERE PartyName = '$company'";
}
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = $conn->real_escape_string($_GET['invoice']);
    $where .= ($where ? " AND" : " WHERE") . " invoice_number LIKE '%$invoice%'";
}
if (isset($_GET['pnr']) && !empty($_GET['pnr'])) {
    $pnr_ = $conn->real_escape_string($_GET['pnr']);
    $where .= ($where ? " AND" : " WHERE") . " PNR LIKE '%$pnr_%'";
}
if (isset($_GET['from_date']) && !empty($_GET['from_date']) && isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $from_date = $conn->real_escape_string($_GET['from_date']);
    $to_date = $conn->real_escape_string($_GET['to_date']);
    $where .= ($where ? " AND" : " WHERE") . " IssueDate BETWEEN '$from_date' AND '$to_date'";
}

// Exclude voided and void transaction records from main list
if (!empty($where)) {
    $where .= " AND (Remarks = 'Air Ticket Sale' OR Remarks IS NULL OR Remarks = '')";
    $where .= " AND (Remarks != 'Voided' OR Remarks IS NULL)";
    $where .= " AND (Remarks != 'Void Transaction' OR Remarks IS NULL)";
} else {
    $where = " WHERE (Remarks = 'Air Ticket Sale' OR Remarks IS NULL OR Remarks = '')";
    $where .= " AND (Remarks != 'Voided' OR Remarks IS NULL)";
    $where .= " AND (Remarks != 'Void Transaction' OR Remarks IS NULL)";
}

// Validate sort_by
$allowed_sort_columns = ['IssueDate', 'PartyName', 'TicketNumber', 'BillAmount', 'NetPayment'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'IssueDate';
}
$sort_order = strtoupper($sort_order);
if ($sort_order != 'ASC' && $sort_order != 'DESC') {
    $sort_order = 'DESC';
}

$salesQuery = "SELECT * FROM sales" . $where . " ORDER BY $sort_by $sort_order";
$salesResult = $conn->query($salesQuery);

// Delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM sales WHERE SaleID=$id";
    if ($conn->query($deleteQuery) === TRUE) {
        echo "<script>alert('Record deleted successfully!'); window.location='invoice_list.php';</script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Process void request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['void_ticket'])) {
    $sale_id = intval($_POST['sale_id']);
    $void_charge = floatval($_POST['void_charge']);
    $notes = $conn->real_escape_string($_POST['notes']);
    
    $originalQuery = "SELECT * FROM sales WHERE SaleID = $sale_id";
    $originalResult = $conn->query($originalQuery);
    
    if ($originalResult && $originalData = $originalResult->fetch_assoc()) {
        $checkVoidQuery = "SELECT * FROM sales WHERE TicketNumber = '" . $conn->real_escape_string($originalData['TicketNumber']) . " VOID' AND Remarks = 'Void Transaction'";
        $checkResult = $conn->query($checkVoidQuery);
        if ($checkResult && $checkResult->num_rows > 0) {
            echo "<script>alert('This ticket already has a void transaction!'); window.location='invoice_list.php';</script>";
            exit();
        }
        
        $original_bill = floatval($originalData['BillAmount']);
        $original_net = floatval($originalData['NetPayment']);
        $debit_amount = $original_net - $void_charge;
        $void_profit = 0;
        $currentDate = date('Y-m-d');
        
        $voidSQL = "INSERT INTO sales (
            section, PartyName, PassengerName, airlines, TicketRoute, TicketNumber, Class, IssueDate, FlightDate, ReturnDate, PNR, BillAmount, NetPayment, Profit, Source, system, PaymentStatus, PaidAmount, DueAmount, PaymentMethod, BankName, SalesPersonName, invoice_number, Remarks, Notes, ReceivedDate, VoidCharge, VoidNetPrice, OriginalBillAmount, OriginalNetAmount
        ) VALUES (
            '" . $conn->real_escape_string($originalData['section'] ?? '') . "',
            '" . $conn->real_escape_string($originalData['PartyName'] ?? '') . "',
            '" . $conn->real_escape_string($originalData['PassengerName'] ?? '') . "',
            '" . $conn->real_escape_string($originalData['airlines'] ?? '') . "',
            '" . $conn->real_escape_string($originalData['TicketRoute'] ?? '') . "',
            '" . $conn->real_escape_string($originalData['TicketNumber'] ?? '') . " VOID',
            '" . $conn->real_escape_string($originalData['Class'] ?? '') . "',
            '$currentDate',
            '" . ($originalData['FlightDate'] ?? '') . "',
            '" . ($originalData['ReturnDate'] ?? '') . "',
            '" . $conn->real_escape_string($originalData['PNR'] ?? '') . "',
            0,
            $debit_amount,
            $void_profit,
            '" . $conn->real_escape_string($originalData['Source'] ?? '') . "',
            '" . $conn->real_escape_string($originalData['system'] ?? '') . "',
            'Due',
            0,
            $debit_amount,
            'Cash Payment',
            'Void Processing',
            '" . $conn->real_escape_string($originalData['SalesPersonName'] ?? '') . "',
            'VOID-" . ($originalData['invoice_number'] ?? '') . "',
            'Void Transaction',
            'Void reversal with " . number_format($void_charge, 2) . " tk charge | Reason: $notes',
            '$currentDate',
            $void_charge,
            $void_charge,
            $original_bill,
            $original_net
        )";
        
        $voidResult = $conn->query($voidSQL);
        if ($voidResult) {
            echo "<script>alert('Void transaction created successfully!'); window.location='invoice_list.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error creating void transaction: " . $conn->error . "'); window.location='invoice_list.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Original ticket not found!'); window.location='invoice_list.php';</script>";
        exit();
    }
}

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
    <title>Sales Records</title>
    <style>
        * { box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px;
            margin: 10px;
            overflow-x: auto;
        }
        
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
            font-size: 1.3rem;
        }
        
        table { 
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            min-width: 1100px;
        }
        
        th, td { 
            padding: 6px 5px; 
            text-align: left; 
            border-bottom: 1px solid #dee2e6;
        }
        
        th { 
            background-color: #4a71ff; 
            color: white; 
            font-weight: 600;
            font-size: 10px;
            white-space: nowrap;
        }
        
        th a {
            color: white;
            text-decoration: none;
        }
        
        th a:hover {
            text-decoration: underline;
        }
        
        .search-container { 
            display: flex; 
            gap: 8px; 
            margin-bottom: 15px; 
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .search-container select, .search-container input { 
            padding: 6px 8px; 
            width: 150px; 
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .search-container button {
            padding: 6px 12px;
            background: #4a71ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .export-btn {
            padding: 6px 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .hide-options {
            display: inline-flex;
            gap: 10px;
            margin-left: 10px;
            align-items: center;
        }
        
        .hide-options label {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            cursor: pointer;
            font-size: 11px;
            color: #555;
        }
        
        .btn { 
            padding: 2px 5px; 
            border: none; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 9px; 
            border-radius: 3px;
            display: inline-block;
            margin: 1px 0;
            text-align: center;
        }
        
        .edit-btn { background-color: #079320; color: white; }
        .delete-btn { background-color: #d9534f; color: white; }
        .void-btn { background-color: #ff6b6b; color: white; }
        .btn-primary { background-color: #4a71ff; color: white; }
        .btn-cart { background-color: #ff9800; color: white; }
        .btn:hover { opacity: 0.9; }

        tr:nth-child(odd) { background-color: #f8f9ff; }
        tr:nth-child(even) { background-color: #ffffff; }
        
        tr.voided-row { background-color: #ffe6e6 !important; opacity: 0.8; }
        tr.voided-row td { text-decoration: line-through; color: #999; }
        tr.void-transaction-row { background-color: #fff3e0 !important; }

        .small-text { font-size: 9px; color: #666; line-height: 1.2; }
        .highlight { color: #088910; font-weight: bold; }
        
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: bold;
        }
        .success { background-color: #28a745; color: white; }
        .danger { background-color: #dc3545; color: white; }
        .warning { background-color: #ffc107; color: #212529; }
        .secondary { background-color: #6c757d; color: white; }
        
        .action-cell { white-space: nowrap; }
        .export-container { text-align: center; margin: 15px 0; display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap; }
        .sort-info { text-align: right; font-size: 10px; color: #666; margin-bottom: 8px; }
        
        .void-indicator { color: #ff6b6b; font-weight: bold; font-size: 9px; display: block; margin-top: 2px; }
        .debit-text { color: #dc3545; font-weight: bold; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 450px;
        }
        .modal-header { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .modal-header h3 { margin: 0; font-size: 16px; }
        .close { float: right; font-size: 22px; font-weight: bold; color: #aaa; cursor: pointer; }
        .close:hover { color: #333; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 600; font-size: 12px; }
        .form-group input, .form-group textarea { width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; }
        .form-group textarea { height: 60px; }
        .ticket-info { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 3px solid #4a71ff; font-size: 11px; }
        .ticket-info p { margin: 3px 0; }
        .calculation-info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 12px 0; font-size: 11px; }
        .calculation-info p { margin: 3px 0; }
        .modal-buttons { text-align: right; margin-top: 15px; }
        .modal-buttons button { padding: 6px 12px; margin-left: 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-confirm { background-color: #28a745; color: white; }
        .btn-cancel { background-color: #6c757d; color: white; }
        
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
    <h2>Sales Records</h2>
    
    <form method="GET" class="search-container">
        <select name="company">
            <option value="">Select Company</option>
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
        $export_url = "export_invoice_excel.php";
        if (!empty($export_params)) $export_url .= "?" . implode("&", $export_params);
        ?>
        <a href="<?= $export_url ?>" class="export-btn">Export to Excel</a>
        
        <div class="hide-options">
            <label><input type="checkbox" id="hide_net_checkbox" <?= $hide_net ? 'checked' : '' ?> onchange="toggleHideOption('hide_net', this.checked)"> Hide Net</label>
            <label><input type="checkbox" id="hide_profit_checkbox" <?= $hide_profit ? 'checked' : '' ?> onchange="toggleHideOption('hide_profit', this.checked)"> Hide Profit</label>
        </div>
    </div>

    <div class="sort-info">
        Sort: <strong><?= htmlspecialchars($sort_by) ?></strong> (<?= $sort_order == 'DESC' ? 'Newest' : 'Oldest' ?>)
    </div>

    <div id="voidModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Void Ticket</h3>
                <span class="close">&times;</span>
            </div>
            <form id="voidForm" method="POST">
                <input type="hidden" name="sale_id" id="void_sale_id">
                <input type="hidden" name="void_ticket" value="1">
                <div class="ticket-info" id="ticketDetails"></div>
                <div class="form-group">
                    <label for="void_charge">Void Charge:</label>
                    <input type="number" step="0.01" id="void_charge" name="void_charge" required placeholder="Enter void charge amount">
                </div>
                <div class="calculation-info" id="calculationInfo" style="display: none;">
                    <p>Original Net: <span id="orig_net">0.00</span></p>
                    <p>Void Charge: <span id="void_charge_display">0.00</span></p>
                    <p><strong>New Debit: <span id="debit_amount">0.00</span></strong></p>
                    <p><strong>Balance Reduction: <span id="net_balance">0.00</span></strong></p>
                </div>
                <div class="form-group">
                    <label for="notes">Remarks:</label>
                    <textarea id="notes" name="notes" placeholder="Enter reason for void..." required></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" id="cancelVoid">Cancel</button>
                    <button type="submit" class="btn-confirm">Confirm Void</button>
                </div>
            </form>
        </div>
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
            $isVoided = ($row['Remarks'] ?? '') == 'Voided';
            $isVoidTransaction = ($row['Remarks'] ?? '') == 'Void Transaction';
            
            $rowClass = '';
            if ($isVoided) $rowClass = 'voided-row';
            elseif ($isVoidTransaction) $rowClass = 'void-transaction-row';
        ?>
            <tr class="<?= $rowClass ?>">
                <td><?= safeHtml($row['PartyName']) ?></td>
                <td><?= safeHtml($row['PassengerName']) ?></td>
                <td>
                    <?= safeHtml($row['invoice_number']) ?>
                    <?php if (!$isVoidTransaction && !$isVoided && !empty($row['invoice_number'])): ?>
                        <div style="margin-top: 3px;">
                            <a href="redirect_reissue.php?id=<?= $row['SaleID'] ?>" class="btn btn-primary">Reissue</a>
                            <a href="redirect_refund.php?id=<?= $row['SaleID'] ?>" class="btn btn-primary">Refund</a>
                            <a href="#" class="btn void-btn void-ticket-btn" 
                               data-sale-id="<?= $row['SaleID'] ?>"
                               data-passenger="<?= safeHtml($row['PassengerName']) ?>"
                               data-ticket="<?= safeHtml($row['TicketNumber']) ?>"
                               data-pnr="<?= safeHtml($row['PNR']) ?>"
                               data-route="<?= safeHtml($row['TicketRoute']) ?>"
                               data-airline="<?= safeHtml($row['airlines']) ?>"
                               data-selling="<?= $row['BillAmount'] ?>"
                               data-net="<?= $row['NetPayment'] ?>">Void</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($isVoidTransaction): ?>
                        <div style="margin-top: 3px;">
                            <form action="invoice_cart2.php" method="POST">
                                <input type="hidden" name="sell_id" value="<?= $row['SaleID'] ?>">
                                <button type="submit" class="btn btn-cart">Add to Cart</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </td>
                <td><?= safeHtml($row['TicketRoute']) ?></td>
                <td>
                    <?= safeHtml($row['airlines']) ?><br>
                    <span class="small-text">Src: <?= safeHtml($row['Source']) ?> | Sys: <?= safeHtml($row['system']) ?></span>
                </td>
                <td><?= safeHtml($row['PNR']) ?></td>
                <td>
                    <?= safeHtml($row['TicketNumber']) ?>
                    <?php if ($isVoided): ?><span class="void-indicator">VOIDED</span><?php endif; ?>
                    <?php if ($isVoidTransaction): ?><span class="void-indicator">VOID</span><?php endif; ?>
                </td>
                <td>
                    <span class="small-text">Issue: <?= safeHtml($row['IssueDate']) ?></span><br>
                    <span class="small-text">Dep: <?= safeHtml($row['FlightDate']) ?></span><br>
                    <span class="small-text">Ret: <?= safeHtml($row['ReturnDate']) ?></span>
                </td>
                <td><?= $day_passes ?></td>
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
                    <?php if (!$hide_net && !$isVoided): ?>
                        <br><span class="small-text">Net: <?= number_format($row['NetPayment'] ?? 0, 2) ?></span>
                    <?php endif; ?>
                    <?php if (!$hide_profit && !$isVoided): ?>
                        <br><span class="small-text">Pr: <?= number_format($row['Profit'] ?? 0, 2) ?></span>
                    <?php endif; ?>
                    <?php if ($isVoidTransaction && isset($row['VoidCharge'])): ?>
                        <br><span class="small-text debit-text">Chg: <?= number_format($row['VoidCharge'] ?? 0, 2) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= safeHtml($row['SalesPersonName']) ?></td>
                <td class="action-cell">
                    <?php if (!$isVoided && !$isVoidTransaction && isset($row['SaleID'])): ?>
                        <a href="redirect_edit.php?id=<?= $row['SaleID'] ?>" class="btn edit-btn">Edit</a>
                        <a href="invoice_list.php?delete=<?= $row['SaleID'] ?>" class="btn delete-btn" onclick="return confirm('Delete this record?')">Del</a>
                        <form action="invoice_cart2.php" method="POST" style="margin-top: 2px;">
                            <input type="hidden" name="sell_id" value="<?= $row['SaleID'] ?>">
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        </form>
                    <?php elseif ($isVoidTransaction): ?>
                        <span class="void-indicator">Void Record</span>
                    <?php elseif ($isVoided): ?>
                        <span class="void-indicator">VOIDED</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php if (!$hasResults): ?>
            <tr><td colspan="13" style="text-align: center; padding: 25px;">No records found.</td></tr>
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
    
    // Void Modal
    const modal = document.getElementById('voidModal');
    const closeBtn = document.getElementsByClassName('close')[0];
    const cancelBtn = document.getElementById('cancelVoid');
    const voidForm = document.getElementById('voidForm');
    const voidChargeInput = document.getElementById('void_charge');
    const calculationInfo = document.getElementById('calculationInfo');
    let originalNet = 0;

    document.querySelectorAll('.void-ticket-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const saleId = this.getAttribute('data-sale-id');
            const passenger = this.getAttribute('data-passenger');
            const ticket = this.getAttribute('data-ticket');
            const pnr = this.getAttribute('data-pnr');
            const route = this.getAttribute('data-route');
            const airline = this.getAttribute('data-airline');
            const net = parseFloat(this.getAttribute('data-net'));
            
            originalNet = net;
            document.getElementById('void_sale_id').value = saleId;
            voidChargeInput.value = 0;
            calculatePreview();
            
            document.getElementById('ticketDetails').innerHTML = `
                <p><strong>Passenger:</strong> ${passenger}</p>
                <p><strong>Ticket:</strong> ${ticket}</p>
                <p><strong>PNR:</strong> ${pnr}</p>
                <p><strong>Route:</strong> ${route}</p>
                <p><strong>Airline:</strong> ${airline}</p>
                <p><strong>Original Net:</strong> ${net.toFixed(2)}</p>
            `;
            modal.style.display = 'block';
        });
    });

    closeBtn.onclick = cancelBtn.onclick = function() {
        modal.style.display = 'none';
        voidForm.reset();
        calculationInfo.style.display = 'none';
    };
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            voidForm.reset();
            calculationInfo.style.display = 'none';
        }
    };
    
    voidChargeInput.addEventListener('input', calculatePreview);
    
    function calculatePreview() {
        const voidCharge = parseFloat(voidChargeInput.value) || 0;
        const debitAmount = originalNet - voidCharge;
        document.getElementById('orig_net').textContent = originalNet.toFixed(2);
        document.getElementById('void_charge_display').textContent = voidCharge.toFixed(2);
        document.getElementById('debit_amount').textContent = debitAmount.toFixed(2);
        document.getElementById('net_balance').textContent = voidCharge.toFixed(2);
        calculationInfo.style.display = voidCharge > 0 ? 'block' : 'none';
    }
    
    voidForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const voidCharge = parseFloat(voidChargeInput.value);
        if (voidCharge <= 0) { alert('Please enter valid void charge'); return; }
        const notes = document.getElementById('notes').value.trim();
        if (!notes) { alert('Please enter remarks'); return; }
        if (confirm(`Confirm Void Transaction\n\nOriginal Net: ${originalNet.toFixed(2)}\nVoid Charge: ${voidCharge.toFixed(2)}\nNew Debit: ${(originalNet - voidCharge).toFixed(2)}\nBalance Reduction: ${voidCharge.toFixed(2)}\n\nThis cannot be undone!`)) {
            this.submit();
        }
    });
</script>

</body>
</html>