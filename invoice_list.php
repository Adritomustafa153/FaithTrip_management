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

// Fetch sales records - Include all records
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

$salesQuery = "SELECT * FROM sales" . $where . " ORDER BY SaleID DESC";
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

// Process void request - FIXED SYNTAX ERROR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['void_ticket'])) {
    $sale_id = intval($_POST['sale_id']);
    $void_charge = floatval($_POST['void_charge']);
    $notes = $conn->real_escape_string($_POST['notes']);
    
    // Fetch original sale data
    $originalQuery = "SELECT * FROM sales WHERE SaleID = $sale_id";
    $originalResult = $conn->query($originalQuery);
    
    if ($originalResult && $originalData = $originalResult->fetch_assoc()) {
        
        // Check if already has a void transaction
        $checkVoidQuery = "SELECT * FROM sales WHERE TicketNumber = '" . $conn->real_escape_string($originalData['TicketNumber']) . " VOID' AND Remarks = 'Void Transaction'";
        $checkResult = $conn->query($checkVoidQuery);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            echo "<script>alert('This ticket already has a void transaction!'); window.location='invoice_list.php';</script>";
            exit();
        }
        
        $original_bill = floatval($originalData['BillAmount']);
        $original_net = floatval($originalData['NetPayment']);
        
        // CORRECTED: Debit amount = Original Net - Void Charge
        $debit_amount = $original_net - $void_charge;
        
        // Calculate profit for the void transaction
        $void_profit = 0;
        
        $currentDate = date('Y-m-d');
        
        // Create void transaction entry - FIXED SYNTAX
        $voidSQL = "INSERT INTO sales (
            section, 
            PartyName, 
            PassengerName, 
            airlines, 
            TicketRoute, 
            TicketNumber, 
            Class, 
            IssueDate, 
            FlightDate, 
            ReturnDate, 
            PNR, 
            BillAmount, 
            NetPayment, 
            Profit, 
            Source, 
            system, 
            PaymentStatus, 
            PaidAmount, 
            DueAmount, 
            PaymentMethod, 
            BankName, 
            SalesPersonName, 
            invoice_number, 
            Remarks, 
            Notes, 
            ReceivedDate,
            VoidCharge,
            VoidNetPrice,
            OriginalBillAmount,
            OriginalNetAmount
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
            'Void reversal with " . number_format($void_charge, 2) . " tk charge | Original Net: " . number_format($original_net, 2) . " | New Debit: " . number_format($debit_amount, 2) . " | Reason: $notes',
            '$currentDate',
            $void_charge,
            $void_charge,
            $original_bill,
            $original_net
        )";
        
        // Execute query
        $voidResult = $conn->query($voidSQL);
        
        if ($voidResult) {
            $new_id = $conn->insert_id;
            echo "<script>
                alert('Void transaction created successfully!\\n\\n' +
                      'Original Ticket: " . $originalData['TicketNumber'] . "\\n' +
                      'Original Net: " . number_format($original_net, 2) . "\\n\\n' +
                      'Void Charge: " . number_format($void_charge, 2) . "\\n' +
                      'New Debit Amount: " . number_format($debit_amount, 2) . "\\n\\n' +
                      'Ledger Effect:\\n' +
                      '• Original Ticket: CREDIT " . number_format($original_bill, 2) . "\\n' +
                      '• Original Net: " . number_format($original_net, 2) . "\\n' +
                      '• Void Transaction: DEBIT " . number_format($debit_amount, 2) . "\\n' +
                      '• Balance Reduction: " . number_format($void_charge, 2) . "\\n\\n' +
                      'Void transaction record created with ID: $new_id\\n' +
                      'Original ticket remains unchanged.');
                window.location='invoice_list.php';
            </script>";
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

// Function to safely escape HTML
function safeHtml($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="logo.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png">
    <title>Sales Records</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        th, td { 
            padding: 12px; 
            text-align: left; 
            font-size: 13px;
        }
        
        th { 
            background-color: #4a71ff; 
            color: white; 
            font-weight: 600;
        }
        
        .search-container { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 20px; 
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .search-container select, .search-container input { 
            padding: 10px; 
            width: 200px; 
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-container button {
            padding: 10px 20px;
            background: #4a71ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .search-container button:hover {
            background: #3a5fd9;
        }
        
        .export-btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .export-btn:hover {
            background: #218838;
        }
        
        .hide-options {
            display: inline-flex;
            gap: 15px;
            margin-left: 20px;
            align-items: center;
        }
        
        .hide-options label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-size: 14px;
            color: #555;
        }
        
        .hide-options input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
        
        .btn { 
            padding: 6px 12px; 
            border: none; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 12px; 
            border-radius: 4px;
            display: inline-block;
            margin: 2px 0;
            text-align: center;
        }
        
        .edit-btn { 
            background-color: #079320; 
            color: white; 
        }
        
        .delete-btn { 
            background-color: #d9534f; 
            color: white; 
        }
        
        .void-btn {
            background-color: #ff6b6b;
            color: white;
        }
        
        .btn-primary {
            background-color: #4a71ff;
            color: white;
        }
        
        .btn:hover { 
            opacity: 0.9; 
        }

        tr:nth-child(odd) {
            background-color: #f8f9ff; 
        }
        
        tr:nth-child(even) {
            background-color: #ffffff; 
        }
        
        tr.void-transaction-row {
            background-color: #fff3e0 !important;
        }

        tr {
            border-bottom: 1px solid #eaeaea;
        }

        tr:last-child {
            border-bottom: none;
        }
        
        .small-text {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
        }
        
        .highlight {
            color: #088910;
            font-weight: bold;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .success { background-color: #28a745; color: white; }
        .danger { background-color: #dc3545; color: white; }
        .warning { background-color: #ffc107; color: #212529; }
        .secondary { background-color: #6c757d; color: white; }
        
        .action-cell {
            min-width: 120px;
        }
        
        .export-container {
            text-align: center;
            margin: 15px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
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
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            color: #333;
            margin: 0;
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .ticket-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4a71ff;
        }
        
        .ticket-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .calculation-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #90caf9;
        }
        
        .calculation-info p {
            margin: 5px 0;
        }
        
        .modal-buttons {
            text-align: right;
            margin-top: 20px;
        }
        
        .modal-buttons button {
            padding: 10px 20px;
            margin-left: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-confirm {
            background-color: #28a745;
            color: white;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        
        .void-indicator {
            color: #ff6b6b;
            font-weight: bold;
            font-size: 11px;
            display: block;
            margin-top: 3px;
        }
        
        .debit-text {
            color: #dc3545;
            font-weight: bold;
        }
        
        .credit-text {
            color: #28a745;
            font-weight: bold;
        }
        
        @media (max-width: 1200px) {
            .container {
                overflow-x: auto;
            }
            
            .search-container {
                flex-direction: column;
                align-items: center;
            }
            
            .search-container select, .search-container input {
                width: 100%;
                max-width: 300px;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .export-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<?php include 'nav.php'  ?>

<div class="container">
    <h2>Sales Records</h2>
    
    <!-- Search Form -->
    <form method="GET" class="search-container">
        <select name="company">
            <option value="">Select Company</option>
            <?php while ($row = $companyResult->fetch_assoc()) : ?>
                <option value="<?= safeHtml($row['PartyName']) ?>" 
                    <?= (isset($_GET['company']) && $_GET['company'] == $row['PartyName']) ? 'selected' : '' ?>>
                    <?= safeHtml($row['PartyName']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        
        <input type="text" name="invoice" placeholder="Search Invoice Number" 
            value="<?= isset($_GET['invoice']) ? safeHtml($_GET['invoice']) : '' ?>">

        <input type="text" name="pnr" placeholder="Search PNR" 
            value="<?= isset($_GET['pnr']) ? safeHtml($_GET['pnr']) : '' ?>">
            
        <input type="date" name="from_date" placeholder="From Date" 
            value="<?= isset($_GET['from_date']) ? safeHtml($_GET['from_date']) : '' ?>">
            
        <input type="date" name="to_date" placeholder="To Date" 
            value="<?= isset($_GET['to_date']) ? safeHtml($_GET['to_date']) : '' ?>">
            
        <button type="submit">Search</button>
    </form>

    <!-- Export Button with Hide Options -->
    <div class="export-container">
        <?php
        $export_params = [];
        if (isset($_GET['company']) && !empty($_GET['company'])) {
            $export_params[] = "company=" . urlencode($_GET['company']);
        }
        if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
            $export_params[] = "from_date=" . urlencode($_GET['from_date']);
        }
        if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
            $export_params[] = "to_date=" . urlencode($_GET['to_date']);
        }
        if ($hide_net) {
            $export_params[] = "hide_net=1";
        }
        if ($hide_profit) {
            $export_params[] = "hide_profit=1";
        }
        $export_url = "export_invoice_excel.php";
        if (!empty($export_params)) {
            $export_url .= "?" . implode("&", $export_params);
        }
        ?>
        <a href="<?= $export_url ?>" class="export-btn">Export to Excel</a>
        
        <div class="hide-options">
            <label>
                <input type="checkbox" id="hide_net_checkbox" <?= $hide_net ? 'checked' : '' ?> onchange="toggleHideOption('hide_net', this.checked)">
                Hide Net Fare
            </label>
            <label>
                <input type="checkbox" id="hide_profit_checkbox" <?= $hide_profit ? 'checked' : '' ?> onchange="toggleHideOption('hide_profit', this.checked)">
                Hide Profit
            </label>
        </div>
    </div>

    <!-- Void Modal -->
    <div id="voidModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Void Ticket</h3>
                <span class="close">&times;</span>
            </div>
            <form id="voidForm" method="POST">
                <input type="hidden" name="sale_id" id="void_sale_id">
                <input type="hidden" name="void_ticket" value="1">
                
                <div class="ticket-info" id="ticketDetails">
                    <!-- Ticket details will be loaded here -->
                </div>
                
                <div class="form-group">
                    <label for="void_charge">Void Charge (Amount to Deduct from Net):</label>
                    <input type="number" step="0.01" id="void_charge" name="void_charge" required 
                           placeholder="Enter void charge amount">
                    <small style="color: #666;">This amount will be deducted from the Net Price</small>
                </div>
                
                <div class="calculation-info" id="calculationInfo" style="display: none;">
                    <p><strong>Void Transaction Calculation:</strong></p>
                    <p>Original Net: <span id="orig_net" class="credit-text">0.00</span></p>
                    <p>Void Charge: <span id="void_charge_display">0.00</span></p>
                    <p><strong class="debit-text">New Debit Amount: <span id="debit_amount">0.00</span></strong></p>
                    <p><small>This amount will appear in the DEBIT (Paid) column</small></p>
                    <hr>
                    <p><strong>Balance Reduction: <span id="net_balance">0.00</span></strong></p>
                </div>
                
                <div class="form-group">
                    <label for="notes">Void Remarks:</label>
                    <textarea id="notes" name="notes" placeholder="Enter reason for void..." required></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" id="cancelVoid">Cancel</button>
                    <button type="submit" class="btn-confirm">Confirm Void</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Records Table -->
    <div class="result">
         <table>
            <thead>
                 <tr>
                    <th>Company Name</th>
                    <th>Passenger Name</th>
                    <th>Invoice Number</th>
                    <th>Route</th>
                    <th>Airlines</th>
                    <th>PNR</th>
                    <th>Ticket Number</th>
                    <th>Issue Date</th>
                    <th>Day Passes</th>
                    <th>Payment Status</th>
                    <th>Pricing</th>
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
                $isVoidTransaction = ($row['Remarks'] ?? '') == 'Void Transaction';
                
                if ($isVoidTransaction) {
                    $rowClass = 'void-transaction-row';
                } else {
                    $rowClass = '';
                }
                ?>
                <tr class="<?= $rowClass ?>">
                     <td><?= safeHtml($row['PartyName']) ?></td>
                     <td><?= safeHtml($row['PassengerName']) ?></td>
                     <td>
                        <?= safeHtml($row['invoice_number']) ?>
                        <?php if (!$isVoidTransaction && !empty($row['invoice_number'])): ?>
                            <div>
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
                     </td>
                     <td><?= safeHtml($row['TicketRoute']) ?></td>
                     <td>
                        <?= safeHtml($row['airlines']) ?><br>
                        <span class="small-text">
                            <b>Issued From:</b> <span class="highlight"><?= safeHtml($row['Source']) ?></span><br>
                            <b>System:</b> <span class="highlight"><?= safeHtml($row['system']) ?></span>
                        </span>
                     </td>
                     <td><?= safeHtml($row['PNR']) ?></td>
                     <td>
                        <?= safeHtml($row['TicketNumber']) ?>
                        <?php if ($isVoidTransaction): ?>
                            <span class="void-indicator">VOID TRANSACTION (DEBIT)</span>
                        <?php endif; ?>
                     </td>
                     <td><b>Issue Date : </b><?= safeHtml($row['IssueDate']) ?> <br><b>Departure : </b><?= safeHtml($row['FlightDate']) ?><br><b>Return Date : </b><?= safeHtml($row['ReturnDate']) ?></td>
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
                        <span class="badge <?= $statusClass ?>"><?= substr($row['PaymentStatus'] ?? '', 0, 1) ?></span><br>
                        <span class="small-text">
                            <b> Method:</b> <span class="highlight"><?= safeHtml($row['PaymentMethod']) ?></span><br>
                            <b>Received in:</b> <span class="highlight"><?= safeHtml($row['BankName']) ?></span><br>
                            <b>Received :</b> <span class="highlight"><?= number_format($row['PaidAmount'] ?? 0, 2) ?></span><br>
                            <b>Receive Date:</b> <span class="highlight"><?= safeHtml($row['ReceivedDate']) ?></span>
                        </span>
                     </td>
                     <td>
                        <span class="small-text">
                            <?php if (!$isVoidTransaction): ?>
                                <b class="credit-text">Selling (CREDIT):</b> <?= number_format($row['BillAmount'] ?? 0, 2) ?><br>
                                <b>Net:</b> <?= number_format($row['NetPayment'] ?? 0, 2) ?><br>
                                <b>Profit:</b> <?= number_format($row['Profit'] ?? 0, 2) ?>
                            <?php else: ?>
                                <b class="debit-text">Debit (Paid):</b> <?= number_format($row['NetPayment'] ?? 0, 2) ?><br>
                                <b>Void Charge:</b> <?= number_format($row['VoidCharge'] ?? 0, 2) ?><br>
                                <b>Original Net:</b> <?= number_format($row['OriginalNetAmount'] ?? 0, 2) ?>
                            <?php endif; ?>
                        </span>
                     </td>
                     <td><?= safeHtml($row['SalesPersonName']) ?></td>
                    <td class="action-cell">
                        <?php if (!$isVoidTransaction && isset($row['SaleID'])): ?>
                            <a href="redirect_edit.php?id=<?= $row['SaleID'] ?>" class="btn edit-btn">
                                Edit
                            </a><br>
                            <a href="invoice_list.php?delete=<?= $row['SaleID'] ?>" class="btn delete-btn" 
                               onclick="return confirm('Are you sure you want to delete this record?')">
                                Delete
                            </a>
                            <form action="invoice_cart2.php" method="POST" style="margin-top: 5px;">
                                <input type="hidden" name="sell_id" value="<?= $row['SaleID'] ?>">
                                <button type="submit" class="btn btn-primary">Add to Invoice</button>
                            </form>
                        <?php elseif ($isVoidTransaction): ?>
                            <span class="void-indicator">Void Transaction (Debit Entry)</span>
                        <?php endif; ?>
                     </td>
                 </tr>
            <?php endwhile; ?>
            <?php if (!$hasResults): ?>
                 <tr>
                    <td colspan="13" style="text-align: center; padding: 40px;">
                        No records found. Please adjust your search criteria.
                     </td>
                 </tr>
            <?php endif; ?>
            </tbody>
         </table>
    </div>
</div>

<script>
    // Toggle hide options and reload page
    function toggleHideOption(option, isChecked) {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (isChecked) {
            urlParams.set(option, '1');
        } else {
            urlParams.delete(option);
        }
        
        window.location.search = urlParams.toString();
    }
    
    // Void Modal functionality
    const modal = document.getElementById('voidModal');
    const closeBtn = document.getElementsByClassName('close')[0];
    const cancelBtn = document.getElementById('cancelVoid');
    const voidForm = document.getElementById('voidForm');
    const voidChargeInput = document.getElementById('void_charge');
    const calculationInfo = document.getElementById('calculationInfo');
    
    let originalBill = 0;
    let originalNet = 0;

    // Open modal when void button is clicked
    document.querySelectorAll('.void-ticket-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const saleId = this.getAttribute('data-sale-id');
            const passenger = this.getAttribute('data-passenger');
            const ticket = this.getAttribute('data-ticket');
            const pnr = this.getAttribute('data-pnr');
            const route = this.getAttribute('data-route');
            const airline = this.getAttribute('data-airline');
            const selling = parseFloat(this.getAttribute('data-selling'));
            const net = parseFloat(this.getAttribute('data-net'));
            
            // Store original values for calculation
            originalBill = selling;
            originalNet = net;
            
            // Set hidden sale ID
            document.getElementById('void_sale_id').value = saleId;
            
            // Set default values
            voidChargeInput.value = 0;
            
            // Trigger calculation
            calculatePreview();
            
            // Display ticket details
            document.getElementById('ticketDetails').innerHTML = `
                <p><strong>Passenger:</strong> ${passenger}</p>
                <p><strong>Ticket Number:</strong> ${ticket}</p>
                <p><strong>PNR:</strong> ${pnr}</p>
                <p><strong>Route:</strong> ${route}</p>
                <p><strong>Airline:</strong> ${airline}</p>
                <p><strong>Original Selling:</strong> ${selling.toFixed(2)}</p>
                <p><strong>Original Net:</strong> ${net.toFixed(2)}</p>
                <p><strong>Original Profit:</strong> ${(selling - net).toFixed(2)}</p>
            `;
            
            // Show modal
            modal.style.display = 'block';
        });
    });

    // Close modal
    closeBtn.onclick = function() {
        modal.style.display = 'none';
        voidForm.reset();
        calculationInfo.style.display = 'none';
    }

    cancelBtn.onclick = function() {
        modal.style.display = 'none';
        voidForm.reset();
        calculationInfo.style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            voidForm.reset();
            calculationInfo.style.display = 'none';
        }
    }

    // Calculate preview when void charge changes
    voidChargeInput.addEventListener('input', calculatePreview);

    function calculatePreview() {
        const voidCharge = parseFloat(voidChargeInput.value) || 0;
        
        // Calculate debit amount = Original Net - Void Charge
        const debitAmount = originalNet - voidCharge;
        
        // Calculate balance reduction (void charge)
        const balanceReduction = voidCharge;
        
        document.getElementById('orig_net').textContent = originalNet.toFixed(2);
        document.getElementById('void_charge_display').textContent = voidCharge.toFixed(2);
        document.getElementById('debit_amount').textContent = debitAmount.toFixed(2);
        document.getElementById('net_balance').textContent = balanceReduction.toFixed(2);
        
        if (voidCharge > 0) {
            calculationInfo.style.display = 'block';
        } else {
            calculationInfo.style.display = 'none';
        }
    }

    // Confirm void before submitting
    voidForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const voidCharge = parseFloat(voidChargeInput.value);
        const debitAmount = originalNet - voidCharge;
        
        if (voidCharge <= 0) {
            alert('Please enter valid void charge amount');
            return;
        }
        
        const notes = document.getElementById('notes').value.trim();
        if (!notes) {
            alert('Please enter void remarks/reason');
            return;
        }
        
        const confirmMsg = `Confirm Void Transaction\n\n` +
                          `Original Net: ${originalNet.toFixed(2)}\n` +
                          `Void Charge: ${voidCharge.toFixed(2)}\n\n` +
                          `New Void Entry:\n` +
                          `• Debit Amount: ${debitAmount.toFixed(2)}\n` +
                          `• Remarks: ${notes}\n\n` +
                          `Balance Reduction: ${voidCharge.toFixed(2)}\n\n` +
                          `This will create a new VOID transaction record.\n` +
                          `Original ticket remains unchanged.\n\n` +
                          `This action cannot be undone!`;
        
        if (confirm(confirmMsg)) {
            this.submit();
        }
    });
</script>

</body>
</html>

<?php $conn->close(); ?>