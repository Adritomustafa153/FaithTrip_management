<?php
// supplier_ledger.php - Payable Ledger for Suppliers/Vendors
require 'db.php';
require 'auth_check.php';
include 'nav.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle AJAX for parties (suppliers)
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_parties') {
    $section = isset($_GET['section']) ? trim($_GET['section']) : '';
    $options = '<option value="">Select Supplier</option>';
    if ($section) {
        $sectionLower = strtolower($section);
        if ($sectionLower == 'counter sell') {
            $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
        } else {
            $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
        }
        // Get distinct PartyNames from sales table as suppliers (you can extend this to other tables)
        $query = "SELECT DISTINCT TRIM(PartyName) as PartyName FROM sales 
                  WHERE $sectionCond AND PartyName IS NOT NULL AND TRIM(PartyName)!='' 
                  ORDER BY PartyName";
        $res = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_assoc($res)) {
            $options .= '<option value="' . htmlspecialchars($row['PartyName']) . '">' . htmlspecialchars($row['PartyName']) . '</option>';
        }
    }
    echo $options;
    exit;
}

// Get filters
$selectedSection = isset($_GET['section']) ? trim($_GET['section']) : '';
$selectedSupplier = isset($_GET['party']) ? trim($_GET['party']) : '';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$txnTypeFilter = isset($_GET['txn_type']) ? $_GET['txn_type'] : 'all';

// Get distinct sections
$sectionsRes = mysqli_query($conn, "SELECT DISTINCT TRIM(section) as section FROM sales WHERE section IS NOT NULL AND TRIM(section)!='' ORDER BY section");
$sections = [];
while ($row = mysqli_fetch_assoc($sectionsRes)) $sections[] = $row['section'];

// Get parties for initial load
$suppliers = [];
if ($selectedSection) {
    $sectionLower = strtolower($selectedSection);
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    $suppliersRes = mysqli_query($conn, "SELECT DISTINCT TRIM(PartyName) as PartyName FROM sales WHERE $sectionCond AND PartyName IS NOT NULL AND TRIM(PartyName)!='' ORDER BY PartyName");
    while ($row = mysqli_fetch_assoc($suppliersRes)) $suppliers[] = $row['PartyName'];
}

// ------------------------------------------------------------
// Supplier Ledger Functions (Payable Logic)
// ------------------------------------------------------------

function getSupplierTransactions($conn, $supplier, $section, $startDate, $endDate, $searchTerm, $txnTypeFilter, &$debugSql = '') {
    $transactions = [];
    $supplier = trim($supplier);
    $sectionLower = strtolower(trim($section));
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(s.section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(s.section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    
    $dateCondition = "";
    if (!empty($startDate) && !empty($endDate)) {
        $dateCondition = " AND s.IssueDate BETWEEN '" . mysqli_real_escape_string($conn, $startDate) . "' AND '" . mysqli_real_escape_string($conn, $endDate) . "'";
    }
    
    // 1. Purchase invoices from supplier (increase payable)
    $purchaseQuery = "SELECT 
                        s.SaleID, s.IssueDate as transaction_date,
                        'Purchase' as txn_type,
                        s.BillAmount as amount,
                        s.Invoice_number, s.PNR, s.TicketNumber, s.airlines, s.TicketRoute,
                        s.PaymentMethod, s.PaymentStatus
                      FROM sales s
                      WHERE TRIM(s.PartyName) = '" . mysqli_real_escape_string($conn, $supplier) . "'
                      AND $sectionCond $dateCondition
                      AND LOWER(TRIM(s.Remarks)) NOT IN ('refund','void transaction')";
    $debugSql = $purchaseQuery;
    $purchaseRes = mysqli_query($conn, $purchaseQuery);
    if ($purchaseRes) {
        while ($row = mysqli_fetch_assoc($purchaseRes)) {
            $amount = floatval($row['amount']);
            $transactions[] = [
                'date' => $row['transaction_date'],
                'type' => 'Purchase',
                'route' => $row['TicketRoute'],
                'airline' => $row['airlines'],
                'pnr' => $row['PNR'],
                'ticket_no' => $row['TicketNumber'],
                'invoice' => $row['Invoice_number'],
                'debit' => 0,          // purchase increases payable -> credit
                'credit' => $amount,
                'remarks' => "Purchase Invoice – " . ($row['PaymentMethod'] ? "Method: " . $row['PaymentMethod'] : "")
            ];
        }
    }
    
    // 2. Payments made TO supplier (reduce payable)
    // Using the 'paid' table where 'source' matches supplier name
    $paymentQuery = "SELECT 
                        p.payment_date as transaction_date,
                        'Payment' as txn_type,
                        p.amount,
                        p.payment_method,
                        p.remarks as payment_notes,
                        p.invoice_no
                      FROM paid p
                      WHERE TRIM(p.source) = '" . mysqli_real_escape_string($conn, $supplier) . "'";
    if (!empty($startDate) && !empty($endDate)) {
        $paymentQuery .= " AND p.payment_date BETWEEN '" . mysqli_real_escape_string($conn, $startDate) . "' AND '" . mysqli_real_escape_string($conn, $endDate) . "'";
    }
    $paymentRes = mysqli_query($conn, $paymentQuery);
    if ($paymentRes) {
        while ($row = mysqli_fetch_assoc($paymentRes)) {
            $transactions[] = [
                'date' => $row['transaction_date'],
                'type' => 'Payment',
                'route' => '',
                'airline' => '',
                'pnr' => '',
                'ticket_no' => '',
                'invoice' => $row['invoice_no'],
                'debit' => floatval($row['amount']),   // payment reduces payable
                'credit' => 0,
                'remarks' => "Payment made – " . $row['payment_method'] . ($row['payment_notes'] ? " | " . $row['payment_notes'] : "")
            ];
        }
    }
    
    // Sort by date
    usort($transactions, fn($a,$b) => strtotime($a['date']) - strtotime($b['date']));
    
    // Apply transaction type filter
    if ($txnTypeFilter != 'all') {
        $transactions = array_filter($transactions, fn($t) => strtolower($t['type']) == strtolower($txnTypeFilter));
    }
    // Search filter
    if (!empty($searchTerm)) {
        $transactions = array_filter($transactions, fn($t) =>
            stripos($t['invoice'], $searchTerm) !== false ||
            stripos($t['ticket_no'], $searchTerm) !== false ||
            stripos($t['pnr'], $searchTerm) !== false
        );
    }
    return array_values($transactions);
}

function calculatePayableTotals(&$transactions, $openingBalance = 0) {
    $totalDebit = $totalCredit = 0;
    $runningBalance = $openingBalance;
    foreach ($transactions as &$txn) {
        // For payable: balance = opening + credits - debits
        $runningBalance += $txn['credit'] - $txn['debit'];
        $txn['balance'] = $runningBalance;
        $totalDebit += $txn['debit'];
        $totalCredit += $txn['credit'];
    }
    return ['total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'current_balance' => $runningBalance];
}

function getOpeningPayable($conn, $supplier, $section, $startDate) {
    if (empty($startDate)) return 0;
    $sectionLower = strtolower(trim($section));
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    
    // Purchases before start date
    $purchaseQuery = "SELECT SUM(BillAmount) as total_purchases
                      FROM sales 
                      WHERE TRIM(PartyName) = '" . mysqli_real_escape_string($conn, trim($supplier)) . "'
                      AND $sectionCond AND IssueDate < '" . mysqli_real_escape_string($conn, $startDate) . "'
                      AND LOWER(TRIM(Remarks)) NOT IN ('refund','void transaction')";
    $purchaseRes = mysqli_query($conn, $purchaseQuery);
    $totalPurchases = 0;
    if ($purchaseRes && $row = mysqli_fetch_assoc($purchaseRes)) $totalPurchases = floatval($row['total_purchases']);
    
    // Payments before start date
    $paymentQuery = "SELECT SUM(amount) as total_payments
                     FROM paid 
                     WHERE TRIM(source) = '" . mysqli_real_escape_string($conn, trim($supplier)) . "'
                     AND payment_date < '" . mysqli_real_escape_string($conn, $startDate) . "'";
    $paymentRes = mysqli_query($conn, $paymentQuery);
    $totalPayments = 0;
    if ($paymentRes && $row = mysqli_fetch_assoc($paymentRes)) $totalPayments = floatval($row['total_payments']);
    
    return $totalPurchases - $totalPayments;
}

function getSupplierSummary($conn, $supplier, $section) {
    $sectionLower = strtolower(trim($section));
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    
    // Total purchases
    $purchaseQuery = "SELECT SUM(BillAmount) as total_purchases, COUNT(*) as purchase_count
                      FROM sales 
                      WHERE TRIM(PartyName) = '" . mysqli_real_escape_string($conn, trim($supplier)) . "'
                      AND $sectionCond AND LOWER(TRIM(Remarks)) NOT IN ('refund','void transaction')";
    $purchaseRes = mysqli_query($conn, $purchaseQuery);
    $totalPurchases = 0;
    $purchaseCount = 0;
    if ($purchaseRes && $row = mysqli_fetch_assoc($purchaseRes)) {
        $totalPurchases = floatval($row['total_purchases']);
        $purchaseCount = intval($row['purchase_count']);
    }
    
    // Total payments to supplier
    $paymentQuery = "SELECT SUM(amount) as total_payments
                     FROM paid 
                     WHERE TRIM(source) = '" . mysqli_real_escape_string($conn, trim($supplier)) . "'";
    $paymentRes = mysqli_query($conn, $paymentQuery);
    $totalPayments = 0;
    if ($paymentRes && $row = mysqli_fetch_assoc($paymentRes)) $totalPayments = floatval($row['total_payments']);
    
    // Profit from sales related to this supplier? optional
    $profitQuery = "SELECT SUM(Profit) as total_profit
                    FROM sales 
                    WHERE TRIM(PartyName) = '" . mysqli_real_escape_string($conn, trim($supplier)) . "'
                    AND $sectionCond AND Profit IS NOT NULL";
    $profitRes = mysqli_query($conn, $profitQuery);
    $totalProfit = 0;
    if ($profitRes && $row = mysqli_fetch_assoc($profitRes)) $totalProfit = floatval($row['total_profit']);
    
    return [
        'total_purchases' => $totalPurchases,
        'total_payments' => $totalPayments,
        'total_payable' => $totalPurchases - $totalPayments,
        'total_profit' => $totalProfit,
        'transaction_count' => $purchaseCount
    ];
}

// Process data
$transactions = [];
$openingBalance = 0;
$totals = [];
$summary = [];
$debugInfo = '';

if ($selectedSupplier && $selectedSection) {
    $openingBalance = getOpeningPayable($conn, $selectedSupplier, $selectedSection, $startDate);
    $transactions = getSupplierTransactions($conn, $selectedSupplier, $selectedSection, $startDate, $endDate, $searchTerm, $txnTypeFilter, $debugSql);
    $totals = calculatePayableTotals($transactions, $openingBalance);
    $summary = getSupplierSummary($conn, $selectedSupplier, $selectedSection);
    if (empty($transactions)) {
        $debugInfo = '<div class="alert alert-warning">No supplier transactions found.</div>';
    }
}

$companyName = "Faith Trip Accounts";
$companyAddress = "Banani, Dhaka-1213, Bangladesh";
$companyPhone = "+880 1234 567890";
$companyEmail = "info@faithtrip.com";
$companyLogo = "logo.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Ledger - Faith Trip Accounts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/mdb.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { background-color: #f8f9fa; font-family: 'Roboto', sans-serif; }
        .filter-card, .ledger-table-container, .stats-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card.success { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); color: #333; }
        .stats-card.warning { background: linear-gradient(135deg, #fccb90 0%, #d57eeb 100%); }
        .stats-number { font-size: 32px; font-weight: bold; }
        .btn-export { border-radius: 25px; padding: 8px 20px; margin: 5px; }
        .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #667eea; padding-bottom: 20px; }
        .ledger-table th, .ledger-table td { vertical-align: middle; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="ledger-container">
    <!-- Filter Card -->
    <div class="filter-card no-print">
        <h4 class="mb-4"><i class="fas fa-hand-holding-usd me-2"></i>Supplier Ledger (Payable)</h4>
        <form method="GET" action="" id="ledgerForm">
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="form-label">Section</label>
                    <select name="section" id="sectionSelect" class="form-select" required>
                        <option value="">Select Section</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo htmlspecialchars($sec); ?>" <?php echo $selectedSection == $sec ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($sec)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Supplier</label>
                    <select name="party" id="partySelect" class="form-select" required>
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?php echo htmlspecialchars($sup); ?>" <?php echo $selectedSupplier == $sup ? 'selected' : ''; ?>><?php echo htmlspecialchars($sup); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Start Date (opt)</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">End Date (opt)</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Transaction Type</label>
                    <select name="txn_type" class="form-select">
                        <option value="all" <?php echo $txnTypeFilter=='all'?'selected':''; ?>>All</option>
                        <option value="purchase" <?php echo $txnTypeFilter=='purchase'?'selected':''; ?>>Purchase</option>
                        <option value="payment" <?php echo $txnTypeFilter=='payment'?'selected':''; ?>>Payment</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Invoice, PNR, Ticket..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-9 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-export"><i class="fas fa-search me-2"></i>Load Ledger</button>
                    <button type="button" class="btn btn-success btn-export" onclick="exportToExcel()"><i class="fas fa-file-excel me-2"></i>Excel</button>
                    <button type="button" class="btn btn-danger btn-export" onclick="exportToPDF()"><i class="fas fa-file-pdf me-2"></i>PDF</button>
                    <button type="button" class="btn btn-secondary btn-export" onclick="window.print()"><i class="fas fa-print me-2"></i>Print</button>
                    <button type="button" class="btn btn-info btn-export" onclick="exportToCSV()"><i class="fas fa-file-csv me-2"></i>CSV</button>
                </div>
            </div>
        </form>
    </div>

    <?php echo $debugInfo; ?>

    <?php if ($selectedSupplier && $selectedSection && !empty($transactions)): ?>
    <!-- Stats Cards -->
    <div class="row no-print">
        <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo number_format($summary['total_purchases'],2); ?></div><div>Total Purchases</div><small><?php echo $summary['transaction_count']; ?> invoices</small></div></div>
        <div class="col-md-3"><div class="stats-card success"><div class="stats-number"><?php echo number_format($summary['total_payments'],2); ?></div><div>Total Paid</div></div></div>
        <div class="col-md-3"><div class="stats-card warning"><div class="stats-number"><?php echo number_format($summary['total_payable'],2); ?></div><div>Current Payable</div></div></div>
        <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo number_format($summary['total_profit'],2); ?></div><div>Est. Profit</div></div></div>
    </div>

    <!-- Report Content -->
    <div id="reportContent">
        <div class="report-header">
            <?php if(file_exists($companyLogo)) echo '<img src="'.$companyLogo.'" height="60">'; ?>
            <h3><?php echo $companyName; ?></h3>
            <p><?php echo $companyAddress; ?> | Phone: <?php echo $companyPhone; ?> | Email: <?php echo $companyEmail; ?></p>
            <h5>Supplier Ledger – <?php echo htmlspecialchars($selectedSupplier); ?></h5>
            <?php if (!empty($startDate) && !empty($endDate)): ?>
                <p><strong>Period:</strong> <?php echo date('d M Y',strtotime($startDate)); ?> to <?php echo date('d M Y',strtotime($endDate)); ?></p>
            <?php else: ?>
                <p><strong>Period:</strong> All transactions</p>
            <?php endif; ?>
            <p><em>Debit = Payment to Supplier (reduces payable) | Credit = Purchase from Supplier (increases payable)</em></p>
        </div>

        <div class="ledger-table-container">
            <table id="ledgerTable" class="table table-striped table-hover ledger-table" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Route / Airlines / PNR</th>
                        <th>Ticket No / Invoice No</th>
                        <th>Debit (BDT)</th>
                        <th>Credit (BDT)</th>
                        <th>Balance (BDT)</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $serial = 1;
                    $balance = $openingBalance;
                    foreach ($transactions as $txn): 
                        $balance += $txn['credit'] - $txn['debit'];
                    ?>
                    <tr>
                        <td><?php echo $serial++; ?></td>
                        <td><?php echo date('d-m-Y',strtotime($txn['date'])); ?></td>
                        <td>
                            <?php echo htmlspecialchars($txn['route']); ?><br>
                            <small><?php echo htmlspecialchars($txn['airline']); ?> | PNR: <?php echo htmlspecialchars($txn['pnr']); ?></small>
                        </td>
                        <td>
                            <?php if($txn['ticket_no']): ?>Ticket: <?php echo htmlspecialchars($txn['ticket_no']); ?><br><?php endif; ?>
                            Invoice: <?php echo htmlspecialchars($txn['invoice']); ?>
                        </td>
                        <td class="text-end"><?php echo $txn['debit']>0?number_format($txn['debit'],2):'-'; ?></td>
                        <td class="text-end"><?php echo $txn['credit']>0?number_format($txn['credit'],2):'-'; ?></td>
                        <td class="text-end fw-bold"><?php echo number_format($balance,2); ?></td>
                        <td><?php echo htmlspecialchars($txn['remarks']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:#e9ecef; font-weight:bold;">
                        <td colspan="4" class="text-end">Totals / Closing Payable</td>
                        <td class="text-end"><?php echo number_format($totals['total_debit'],2); ?></td>
                        <td class="text-end"><?php echo number_format($totals['total_credit'],2); ?></td>
                        <td class="text-end"><?php echo number_format($balance,2); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif($selectedSupplier && $selectedSection): ?>
        <div class="alert alert-warning">No supplier transactions found.</div>
    <?php elseif($selectedSection && !$selectedSupplier): ?>
        <div class="alert alert-info">Please select a supplier to view the ledger.</div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function(){
    var tableElement = $('#ledgerTable');
    if ($.fn.DataTable.isDataTable(tableElement)) {
        tableElement.DataTable().destroy();
        tableElement.find('tbody').empty();
    }
    if (tableElement.length && tableElement.find('tbody tr').length > 0) {
        tableElement.DataTable({ 
            pageLength: 25, 
            order: [[1,'asc']], 
            scrollX: true, 
            responsive: true,
            destroy: true,
            autoWidth: false
        });
    }
    
    // Fix MDB dropdowns
    if (typeof mdb !== 'undefined') {
        document.querySelectorAll('[data-mdb-dropdown-init]').forEach(el => {
            if (el && !el.__mdbDropdown) new mdb.Dropdown(el);
        });
    }
    
    $('#sectionSelect').change(function(){
        var section = $(this).val();
        if(section){
            $.ajax({
                url: window.location.href.split('?')[0] + '?ajax=get_parties&section=' + encodeURIComponent(section),
                type: 'GET',
                success: function(response){ $('#partySelect').html(response); },
                error: function(){ alert('Error loading suppliers.'); }
            });
        } else {
            $('#partySelect').html('<option value="">Select Supplier</option>');
        }
    });
});

function getDynamicFileName(baseName = 'Supplier_Ledger') {
    let party = '<?php echo htmlspecialchars($selectedSupplier); ?>';
    let startDate = '<?php echo htmlspecialchars($startDate); ?>';
    let endDate = '<?php echo htmlspecialchars($endDate); ?>';
    let fileName = `${baseName}_${party.replace(/[^a-z0-9]/gi, '_')}`;
    if (startDate && endDate) fileName += `_${startDate}_to_${endDate}`;
    else fileName += `_all_records`;
    return fileName;
}

function exportToExcel() {
    var table = document.getElementById('ledgerTable');
    if(!table) return;
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.table_to_sheet(table, { raw: true });
    XLSX.utils.book_append_sheet(wb, ws, 'Supplier_Ledger');
    XLSX.writeFile(wb, getDynamicFileName() + '.xlsx');
}
function exportToCSV() {
    var table = document.getElementById('ledgerTable');
    if(!table) return;
    var rows = table.querySelectorAll('tr');
    var csv = [];
    for(var i=0; i<rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td,th');
        for(var j=0; j<cols.length; j++) row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        csv.push(row.join(','));
    }
    var blob = new Blob([csv.join('\n')], {type: 'text/csv'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = getDynamicFileName() + '.csv';
    link.click();
}
function exportToPDF() {
    var element = document.getElementById('reportContent');
    if(!element) return;
    html2pdf().set({ margin: 0.5, filename: getDynamicFileName() + '.pdf', html2canvas: { scale: 2 }, jsPDF: { unit: 'in', format: 'a2', orientation: 'landscape' } }).from(element).save();
}
</script>
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="js/mdb.umd.min.js"></script> -->
</body>
</html>