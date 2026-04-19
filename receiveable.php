<?php
// Database connection
include 'db.php';
include 'auth_check.php';

// Ensure payments table has PartyName column and SaleID allows NULL
$check_party_column = $conn->query("SHOW COLUMNS FROM payments LIKE 'PartyName'");
if ($check_party_column->num_rows == 0) {
    $conn->query("ALTER TABLE payments ADD COLUMN PartyName VARCHAR(255) NULL AFTER SaleID");
}

$check_saleid_null = $conn->query("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS 
                                   WHERE TABLE_SCHEMA = DATABASE() 
                                   AND TABLE_NAME = 'payments' 
                                   AND COLUMN_NAME = 'SaleID'");
$row = $check_saleid_null->fetch_assoc();
if ($row && $row['IS_NULLABLE'] == 'NO') {
    $conn->query("ALTER TABLE payments MODIFY COLUMN SaleID INT NULL");
}

// ===================== AJAX HANDLERS =====================
if (isset($_GET['action']) && $_GET['action'] == 'get_parties_by_section') {
    $section = isset($_GET['section']) ? $conn->real_escape_string($_GET['section']) : '';
    $parties = [];
    if ($section) {
        $query = "SELECT DISTINCT PartyName FROM sales WHERE section = '$section' AND PartyName != '' ORDER BY PartyName";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $parties[] = $row['PartyName'];
        }
    }
    echo json_encode(['success' => true, 'parties' => $parties]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'get_sale_details') {
    $sale_id = isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0;
    if ($sale_id) {
        $query = "SELECT section, PartyName FROM sales WHERE SaleID = $sale_id";
        $result = $conn->query($query);
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'section' => $row['section'], 'party_name' => $row['PartyName']]);
        } else {
            echo json_encode(['success' => false]);
        }
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Handle Add Payment AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_payment') {
    $sale_id = !empty($_POST['sale_id']) ? intval($_POST['sale_id']) : null;
    $section = $conn->real_escape_string($_POST['section']);
    $party_name = $conn->real_escape_string($_POST['party_name']);
    $amount = floatval($_POST['amount']);
    $payment_date = $conn->real_escape_string($_POST['payment_date']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $bank_id = isset($_POST['bank_id']) ? intval($_POST['bank_id']) : null;
    $remarks = $conn->real_escape_string($_POST['remarks']);
    
    mysqli_begin_transaction($conn);
    try {
        $insert_payment = "INSERT INTO payments (SaleID, PartyName, PaymentDate, Amount, PaymentMethod, BankName, Notes, PaymentType) 
                           VALUES (" . ($sale_id ? $sale_id : "NULL") . ", '$party_name', '$payment_date', $amount, '$payment_method', " . ($bank_id ? "(SELECT Bank_Name FROM banks WHERE id = $bank_id)" : "NULL") . ", '$remarks', 'Partial')";
        if (!$conn->query($insert_payment)) {
            throw new Exception("Failed to insert payment: " . $conn->error);
        }
        
        if ($sale_id) {
            $sale_query = $conn->query("SELECT BillAmount, PaymentStatus, COALESCE(SUM(p.Amount), 0) as PaidAmount 
                                        FROM sales s 
                                        LEFT JOIN payments p ON s.SaleID = p.SaleID 
                                        WHERE s.SaleID = $sale_id 
                                        GROUP BY s.SaleID");
            $sale = $sale_query->fetch_assoc();
            if ($sale) {
                $current_paid = $sale['PaidAmount'];
                $new_paid = $current_paid + $amount;
                $bill_amount = $sale['BillAmount'];
                $payment_status = ($new_paid >= $bill_amount) ? 'Paid' : 'Partially Paid';
                $update_sale = "UPDATE sales SET PaymentStatus = '$payment_status' WHERE SaleID = $sale_id";
                if (!$conn->query($update_sale)) {
                    throw new Exception("Failed to update sale status: " . $conn->error);
                }
            }
        }
        
        if (($payment_method == 'Bank Transfer' || $payment_method == 'Clearing Cheque') && $bank_id) {
            $update_bank = "UPDATE banks SET Balance = Balance + $amount WHERE id = $bank_id";
            if (!$conn->query($update_bank)) {
                throw new Exception("Failed to update bank balance: " . $conn->error);
            }
        }
        
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully!']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Display success message
if (isset($_GET['payment']) && $_GET['payment'] == 'success') {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>Payment recorded successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Filters
$section_filter = isset($_GET['section']) ? $_GET['section'] : '';
$party_filter = isset($_GET['party']) ? $_GET['party'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$pnr_search = isset($_GET['pnr']) ? $_GET['pnr'] : '';
$load_ledger = isset($_GET['load_ledger']) && $_GET['load_ledger'] == '1';

// Fetch sections and parties
$sections_sql = "SELECT DISTINCT section FROM sales WHERE section != '' AND section != 'Counter Sell' ORDER BY section";
$sections_result = $conn->query($sections_sql);
$parties_sql = "SELECT DISTINCT PartyName FROM sales WHERE PartyName != ''";
if (!empty($section_filter)) {
    $parties_sql .= " AND section = '" . $conn->real_escape_string($section_filter) . "'";
}
$parties_sql .= " ORDER BY PartyName";
$parties_result = $conn->query($parties_sql);

// Banks
$banks_query = mysqli_query($conn, "SELECT id, Bank_Name FROM banks ORDER BY Bank_Name");
$banks = [];
while ($row = mysqli_fetch_assoc($banks_query)) {
    $banks[] = $row;
}

$modal_sections = ['Corporate', 'Counter Sell', 'Agent'];

// ------------------------------------------------------------
// LEDGER VIEW (includes all sales and payments) - unchanged
// ------------------------------------------------------------
if ($load_ledger) {
    $date_condition = "";
    if (!empty($from_date) && !empty($to_date)) {
        $date_condition = "AND trans_date BETWEEN '$from_date' AND '$to_date'";
    }
    
    $party_condition_sales = "";
    $party_condition_payments = "";
    if (!empty($party_filter)) {
        $party_condition_sales = "AND s.PartyName = '" . $conn->real_escape_string($party_filter) . "'";
        $party_condition_payments = "AND (p.PartyName = '" . $conn->real_escape_string($party_filter) . "' OR (p.SaleID IS NOT NULL AND p.SaleID IN (SELECT SaleID FROM sales WHERE PartyName = '" . $conn->real_escape_string($party_filter) . "')))";
    }

    $ledger_query = "
        SELECT 
            s.SaleID as ref_id,
            s.IssueDate AS trans_date,
            'Sale' AS trans_type,
            s.PartyName AS party,
            s.TicketNumber,
            s.PNR,
            s.BillAmount AS debit,
            0 AS credit,
            COALESCE(s.Remarks, '') AS notes
        FROM sales s
        WHERE s.Remarks NOT IN ('Refund', 'Void Transaction', 'Voided', 'Reissue', 'Reissued')
          $party_condition_sales $date_condition

        UNION ALL

        SELECT 
            p.PaymentID as ref_id,
            p.PaymentDate AS trans_date,
            'Payment' AS trans_type,
            COALESCE(p.PartyName, (SELECT PartyName FROM sales WHERE SaleID = p.SaleID)) AS party,
            '' AS TicketNumber,
            '' AS PNR,
            0 AS debit,
            p.Amount AS credit,
            CONCAT('Payment #', p.PaymentID, ' - ', COALESCE(p.Notes, '')) AS notes
        FROM payments p
        WHERE 1=1 
          $party_condition_payments
          " . (!empty($from_date) && !empty($to_date) ? "AND p.PaymentDate BETWEEN '$from_date' AND '$to_date'" : "") . "
        
        UNION ALL

        SELECT 
            s.SaleID as ref_id,
            s.IssueDate AS trans_date,
            'Refund' AS trans_type,
            s.PartyName AS party,
            s.TicketNumber,
            s.PNR,
            0 AS debit,
            s.refundtc AS credit,
            CONCAT('REFUND: ', COALESCE(s.Notes, '')) AS notes
        FROM sales s
        WHERE s.Remarks = 'Refund' AND s.refundtc > 0
          $party_condition_sales $date_condition

        UNION ALL

        SELECT 
            s.SaleID as ref_id,
            s.IssueDate AS trans_date,
            'Reissue Charge' AS trans_type,
            s.PartyName AS party,
            CONCAT(s.TicketNumber, ' REISSUE') AS TicketNumber,
            s.PNR,
            s.NetPayment AS debit,
            0 AS credit,
            CONCAT('REISSUE CHARGE: ', COALESCE(s.Notes, '')) AS notes
        FROM sales s
        WHERE s.Remarks = 'Reissue'
          $party_condition_sales $date_condition

        UNION ALL

        SELECT 
            s.SaleID as ref_id,
            s.IssueDate AS trans_date,
            'Reissue Reversal' AS trans_type,
            s.PartyName AS party,
            s.TicketNumber,
            s.PNR,
            0 AS debit,
            s.NetPayment AS credit,
            'REISSUE REVERSAL: Original sale reversed' AS notes
        FROM sales s
        WHERE s.Remarks = 'Reissued'
          AND s.TicketNumber NOT LIKE '%REISSUE%'
          $party_condition_sales $date_condition

        UNION ALL

        SELECT 
            s.SaleID as ref_id,
            s.IssueDate AS trans_date,
            'Void Charge' AS trans_type,
            s.PartyName AS party,
            CONCAT(s.TicketNumber, ' VOID') AS TicketNumber,
            s.PNR,
            s.NetPayment AS debit,
            0 AS credit,
            CONCAT('VOID CHARGE: ', COALESCE(s.Notes, '')) AS notes
        FROM sales s
        WHERE s.Remarks = 'Void Transaction'
          $party_condition_sales $date_condition

        UNION ALL

        SELECT 
            s.SaleID as ref_id,
            s.IssueDate AS trans_date,
            'Void Reversal' AS trans_type,
            s.PartyName AS party,
            s.TicketNumber,
            s.PNR,
            0 AS debit,
            s.BillAmount AS credit,
            'VOID REVERSAL: Original sale cancelled' AS notes
        FROM sales s
        WHERE s.Remarks = 'Voided'
          AND s.TicketNumber NOT LIKE '%VOID%'
          $party_condition_sales $date_condition

        ORDER BY trans_date ASC
    ";

    $result = $conn->query($ledger_query);
    if (!$result) {
        die("Query Error: " . $conn->error);
    }
    
    $ledger_data = [];
    $running_balance = 0;
    $total_debit = 0;
    $total_credit = 0;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $running_balance += $row['debit'] - $row['credit'];
            $row['balance'] = $running_balance;
            $ledger_data[] = $row;
            $total_debit += $row['debit'];
            $total_credit += $row['credit'];
        }
    }
    $total_outstanding = $total_debit - $total_credit;
} else {
    // ========== OUTSTANDING VIEW: Exclude refunded original sales, show refund entries with BillAmount ==========
    
    // Part 1: Normal sales that are NOT refunded, voided, or reissued, and still have due amount > 0
    $normal_sales_sql = "
        SELECT 
            s.SaleID, s.section, s.PartyName, s.PassengerName, s.airlines, s.TicketRoute, 
            s.TicketNumber, s.IssueDate, s.PNR, s.BillAmount, s.Source, 
            COALESCE(SUM(p.Amount), 0) as PaidAmount,
            (s.BillAmount - COALESCE(SUM(p.Amount), 0)) as DueAmount,
            s.SalesPersonName, DATEDIFF(CURDATE(), s.IssueDate) AS DaysPassed,
            '' as RefundChargeFlag
        FROM sales s
        LEFT JOIN payments p ON s.SaleID = p.SaleID
        WHERE (s.Remarks IS NULL OR s.Remarks NOT IN ('Refund', 'Void Transaction', 'Voided', 'Reissue', 'Reissued'))
          -- Exclude sales that have been refunded (by checking existence of a refund record with same PNR and TicketNumber)
          AND NOT EXISTS (
              SELECT 1 FROM sales r 
              WHERE r.Remarks = 'Refund' 
                AND r.PNR = s.PNR 
                AND r.TicketNumber = s.TicketNumber
          )
        GROUP BY s.SaleID
        HAVING DueAmount > 0
    ";

    // Part 2: Refund entries (Remarks = 'Refund') - show BillAmount instead of refundtc
    $refund_sales_sql = "
        SELECT 
            s.SaleID, s.section, s.PartyName, 
            CONCAT('REFUND - ', s.TicketNumber) AS PassengerName,
            s.airlines, s.TicketRoute, s.TicketNumber, s.IssueDate, s.PNR, 
            s.BillAmount AS BillAmount,   -- Changed from s.refundtc to s.BillAmount
            s.Source, 
            COALESCE(SUM(p.Amount), 0) as PaidAmount,
            (s.BillAmount - COALESCE(SUM(p.Amount), 0)) as DueAmount,
            s.SalesPersonName, DATEDIFF(CURDATE(), s.IssueDate) AS DaysPassed,
            'RefundCharge' as RefundChargeFlag
        FROM sales s
        LEFT JOIN payments p ON s.SaleID = p.SaleID
        WHERE s.Remarks = 'Refund' 
          AND s.BillAmount > 0   -- Only if BillAmount is positive (amount owed)
        GROUP BY s.SaleID
        HAVING DueAmount > 0
    ";

    // Combine both parts with UNION
    $sql = "($normal_sales_sql) UNION ALL ($refund_sales_sql)";
    
    // Apply filters
    $filter_sql = "";
    if (!empty($section_filter)) {
        $filter_sql .= " AND section = '" . $conn->real_escape_string($section_filter) . "'";
    }
    if (!empty($party_filter)) {
        $filter_sql .= " AND PartyName = '" . $conn->real_escape_string($party_filter) . "'";
    }
    if (!empty($from_date) && !empty($to_date)) {
        $filter_sql .= " AND IssueDate BETWEEN '" . $conn->real_escape_string($from_date) . "' AND '" . $conn->real_escape_string($to_date) . "'";
    }
    if (!empty($pnr_search)) {
        $filter_sql .= " AND PNR LIKE '%" . $conn->real_escape_string($pnr_search) . "%'";
    }
    
    // Wrap the union query to apply filters on the combined result
    if (!empty($filter_sql)) {
        $sql = "SELECT * FROM ($sql) AS combined WHERE 1=1 $filter_sql";
    }
    $sql .= " ORDER BY IssueDate DESC";
    
    $result = $conn->query($sql);
    if (!$result) {
        die("Query Error: " . $conn->error);
    }

    $total_bill = 0;
    $total_due = 0;
    $total_paid = 0;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $total_bill += $row['BillAmount'];
            $total_due += $row['DueAmount'];
            $total_paid += $row['PaidAmount'];
        }
        $result->data_seek(0);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receivable Payments Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same CSS as before – omitted for brevity, include your existing styles */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-header {
            color: var(--secondary-color);
            margin-bottom: 25px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            text-align: center;
            margin-top: 20px;
        }
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
        }
        .filter-group select, .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-actions .btn {
            white-space: nowrap;
        }
        .ledger-checkbox {
            display: flex;
            align-items: center;
            margin-left: auto;
            gap: 8px;
            white-space: nowrap;
        }
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            overflow-x: auto;
            margin-bottom: 20px;
        }
        .table {
            margin-bottom: 0;
            font-size: 14px;
        }
        .table th {
            background-color: var(--secondary-color);
            color: white;
            position: sticky;
            top: 0;
            font-weight: 500;
        }
        .table td {
            vertical-align: middle;
        }
        .total-row {
            font-weight: bold;
            background-color: rgba(52, 152, 219, 0.1);
        }
        .status-due { color: var(--accent-color); font-weight: bold; }
        .status-partial { color: #f39c12; font-weight: bold; }
        .action-buttons { min-width: 120px; }
        .btn-sm { font-size: 12px; padding: 4px 8px; margin: 2px 0; }
        .btn-success { background-color: #28a745; border-color: #28a745; color: white !important; }
        .btn-info { background-color: #17a2b8; border-color: #17a2b8; color: white !important; }
        .btn-primary { background-color: #007bff; border-color: #007bff; color: white !important; }
        .btn-payment { background-color: #ffc107; color: #212529; border-color: #ffc107; }
        .btn-payment:hover { background-color: #e0a800; }
        .balance-positive { color: #28a745; font-weight: bold; }
        .balance-negative { color: #dc3545; font-weight: bold; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 550px; border-radius: 8px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .modal-header h3 { margin: 0; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; }
        @media (max-width: 768px) {
            .filter-row { flex-direction: column; align-items: stretch; }
            .ledger-checkbox { margin-left: 0; justify-content: flex-start; margin-top: 10px; }
            .modal-content { width: 95%; margin: 10% auto; }
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: white; font-size: 12pt; }
            .table th { background-color: #343a40 !important; color: white !important; }
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container-fluid">
    <h2 class="page-header">Receivable Payments Management</h2>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="" id="filterForm">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="section">Section</label>
                    <select class="form-select" id="section" name="section">
                        <option value="">All Sections</option>
                        <?php
                        if ($sections_result->num_rows > 0) {
                            $sections_result->data_seek(0);
                            while($row = $sections_result->fetch_assoc()) {
                                $selected = ($section_filter == $row['section']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($row['section']) . '" ' . $selected . '>' 
                                     . htmlspecialchars($row['section']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="party">Party Name</label>
                    <select class="form-select" id="party" name="party">
                        <option value="">All Parties</option>
                        <?php
                        if ($parties_result->num_rows > 0) {
                            $parties_result->data_seek(0);
                            while($row = $parties_result->fetch_assoc()) {
                                $selected = ($party_filter == $row['PartyName']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($row['PartyName']) . '" ' . $selected . '>' 
                                     . htmlspecialchars($row['PartyName']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="from_date">From Date</label>
                    <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="to_date">To Date</label>
                    <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="pnr">PNR Search</label>
                    <input type="text" class="form-control" id="pnr" name="pnr" placeholder="Enter PNR" value="<?php echo htmlspecialchars($pnr_search); ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Search</button>
                    <button type="button" class="btn btn-payment" id="addPaymentBtn"><i class="fas fa-plus-circle me-2"></i>Add Payment</button>
                    <a href="export_receivables.php?section=<?= urlencode($section_filter) ?>&party=<?= urlencode($party_filter) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&pnr=<?= urlencode($pnr_search) ?>&load_ledger=<?= $load_ledger ? '1' : '0' ?>" class="btn btn-success">Excel <i class="fas fa-file-excel"></i></a>
                    <div class="ledger-checkbox">
                        <input class="form-check-input" type="checkbox" name="load_ledger" id="load_ledger" value="1" <?php echo $load_ledger ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <label class="form-check-label" for="load_ledger">Load Ledger</label>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Section -->
    <div class="table-container">
        <div class="table-responsive">
            <?php if ($load_ledger): ?>
                <table class="table table-hover">
                    <thead>
                        <tr><th>Date</th><th>Type</th><th>Party</th><th>Ticket No</th><th>PNR</th><th>Debit (Owed)</th><th>Credit (Paid)</th><th>Balance</th><th>Notes</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ledger_data)): ?>
                            <?php foreach ($ledger_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['trans_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['trans_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['party']); ?></td>
                                <td><?php echo htmlspecialchars($row['TicketNumber']); ?></td>
                                <td><?php echo htmlspecialchars($row['PNR']); ?></td>
                                <td class="text-end"><?php echo number_format($row['debit'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($row['credit'], 2); ?></td>
                                <td class="text-end <?php echo $row['balance'] >= 0 ? 'balance-positive' : 'balance-negative'; ?>"><?php echo number_format($row['balance'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['notes']); ?></td>
                                <td>
                                    <?php if ($row['trans_type'] == 'Sale'): ?>
                                        <button class="btn btn-success btn-sm pay-btn" data-id="<?php echo $row['ref_id']; ?>" data-amount="<?php echo $row['debit']; ?>"><i class="fas fa-money-bill-wave"></i> Pay</button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row"><td colspan="5" class="text-end"><strong>Totals:</strong></td>
                            <td class="text-end"><strong><?php echo number_format($total_debit, 2); ?></strong></td>
                            <td class="text-end"><strong><?php echo number_format($total_credit, 2); ?></strong></td>
                            <td class="text-end"><strong><?php echo number_format($total_outstanding, 2); ?></strong></td>
                            <td colspan="2"></td>
                            </tr>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center">No ledger transactions found.</div>
                            <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr><th>Section</th><th>Party Name</th><th>Passenger</th><th>Airline</th><th>Route</th><th>Ticket No</th><th>Issue Date</th><th>Days</th><th>PNR</th><th>Bill Amt</th><th>Status</th><th>Paid</th><th>Due</th><th>Sales Person</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $days = (new DateTime($row['IssueDate']))->diff(new DateTime())->days;
                                $status_class = ($row['DueAmount'] == $row['BillAmount']) ? 'status-due' : 'status-partial';
                                $status_text = ($row['DueAmount'] == $row['BillAmount']) ? 'Due' : 'Partially Paid';
                                // For refund rows, show a badge
                                $passenger_display = $row['PassengerName'];
                                if (isset($row['RefundChargeFlag']) && $row['RefundChargeFlag'] == 'RefundCharge') {
                                    $passenger_display = '<span class="badge bg-warning text-dark">Refund Entry</span> ' . htmlspecialchars($row['PassengerName']);
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['section']); ?></td>
                                <td><?php echo htmlspecialchars($row['PartyName']); ?></td>
                                <td><?php echo $passenger_display; ?></div>
                                <td><?php echo htmlspecialchars($row['airlines']); ?></div>
                                <td><?php echo htmlspecialchars($row['TicketRoute']); ?></div>
                                <td><?php echo htmlspecialchars($row['TicketNumber']); ?></div>
                                <td><?php echo htmlspecialchars($row['IssueDate']); ?></div>
                                <td><?php echo $days; ?> days</div>
                                <td><?php echo htmlspecialchars($row['PNR']); ?></div>
                                <td class="text-end"><?php echo number_format($row['BillAmount'], 2); ?></div>
                                <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></div>
                                <td class="text-end"><?php echo number_format($row['PaidAmount'], 2); ?></div>
                                <td class="text-end"><?php echo number_format($row['DueAmount'], 2); ?></div>
                                <td><?php echo htmlspecialchars($row['SalesPersonName']); ?></div>
                                <td>
                                    <button class="btn btn-success btn-sm pay-btn" data-id="<?php echo $row['SaleID']; ?>" data-amount="<?php echo $row['DueAmount']; ?>"><i class="fas fa-money-bill-wave"></i> Pay</button>
                                    <a href="payment_history.php?id=<?php echo $row['SaleID']; ?>" class="btn btn-info btn-sm"><i class="fas fa-history"></i> History</a>
                                 </div>
                             </tr>
                            <?php endwhile; ?>
                            <tr class="total-row"><td colspan="9" class="text-end">Total:</div>
                            <td class="text-end"><?php echo number_format($total_bill, 2); ?></div><td></div>
                            <td class="text-end"><?php echo number_format($total_paid, 2); ?></div>
                            <td class="text-end"><?php echo number_format($total_due, 2); ?></div><td colspan="2"></div></tr>
                        <?php else: ?>
                            <tr><td colspan="15" class="text-center">No records found.</div></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal" id="paymentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-money-bill-wave"></i> Record Payment</h3>
            <span class="close">&times;</span>
        </div>
        <form id="paymentForm">
            <input type="hidden" id="sale_id" name="sale_id">
            <div class="form-group">
                <label for="modal_section">Section <span class="text-danger">*</span></label>
                <select id="modal_section" name="section" required>
                    <option value="">Select Section</option>
                    <?php foreach ($modal_sections as $sec): ?>
                        <option value="<?= $sec ?>"><?= $sec ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="party_name">Party/Agent Name <span class="text-danger">*</span></label>
                <select id="party_name" name="party_name" required>
                    <option value="">Select Party/Agent</option>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Amount (Taka) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="amount" name="amount" required>
            </div>
            <div class="form-group">
                <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                <input type="date" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">Select Method</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Clearing Cheque">Clearing Cheque</option>
                    <option value="Mobile Banking">Mobile Banking</option>
                </select>
            </div>
            <div class="form-group" id="bank_group" style="display:none;">
                <label for="bank_id">Bank Name</label>
                <select id="bank_id" name="bank_id">
                    <option value="">Select Bank</option>
                    <?php foreach ($banks as $bank): ?>
                        <option value="<?= $bank['id'] ?>"><?= htmlspecialchars($bank['Bank_Name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" rows="2"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary" style="width:100%;">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    var modal = $('#paymentModal');
    var btn = $('#addPaymentBtn');
    var span = $('.close');
    
    btn.click(function(){
        $('#sale_id').val('');
        $('#modal_section').val('');
        $('#party_name').html('<option value="">Select Party/Agent</option>');
        $('#amount').val('');
        $('#payment_date').val(new Date().toISOString().slice(0,10));
        $('#payment_method').val('');
        $('#bank_id').val('');
        $('#remarks').val('');
        $('#bank_group').hide();
        modal.show();
    });
    
    $('.pay-btn').click(function(){
        var saleId = $(this).data('id');
        var dueAmount = $(this).data('amount');
        $('#sale_id').val(saleId);
        $('#amount').val(dueAmount);
        $('#payment_date').val(new Date().toISOString().slice(0,10));
        $('#payment_method').val('');
        $('#bank_id').val('');
        $('#remarks').val('');
        $('#bank_group').hide();
        
        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: { action: 'get_sale_details', sale_id: saleId },
            dataType: 'json',
            success: function(data){
                if (data.success) {
                    $('#modal_section').val(data.section);
                    loadPartyDropdown(data.section, data.party_name);
                } else {
                    alert('Could not load sale details');
                }
            },
            error: function(){
                alert('Error loading sale details');
            }
        });
        
        modal.show();
    });
    
    function loadPartyDropdown(section, selectedParty = null) {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: { action: 'get_parties_by_section', section: section },
            dataType: 'json',
            success: function(data){
                var options = '<option value="">Select Party/Agent</option>';
                if (data.success && data.parties.length > 0) {
                    $.each(data.parties, function(i, party){
                        var selected = (selectedParty && party == selectedParty) ? 'selected' : '';
                        options += '<option value="' + party + '" ' + selected + '>' + party + '</option>';
                    });
                }
                $('#party_name').html(options);
            },
            error: function(){
                $('#party_name').html('<option value="">Error loading parties</option>');
            }
        });
    }
    
    $('#modal_section').change(function(){
        var section = $(this).val();
        if (section) {
            loadPartyDropdown(section);
        } else {
            $('#party_name').html('<option value="">Select Party/Agent</option>');
        }
    });
    
    span.click(function(){
        modal.hide();
    });
    
    $(window).click(function(event){
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });
    
    $('#payment_method').change(function(){
        var val = $(this).val();
        if (val === 'Bank Transfer' || val === 'Clearing Cheque') {
            $('#bank_group').show();
        } else {
            $('#bank_group').hide();
            $('#bank_id').val('');
        }
    });
    
    $('#paymentForm').submit(function(e){
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=add_payment';
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response){
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(){
                alert('Server error. Please try again.');
            }
        });
    });
});
</script>
</body>
</html>