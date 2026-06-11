<?php
// ledger.php - Perfect PDF export using html2pdf (same design, no navbar)
require 'db.php';
require 'auth_check.php';
include 'nav.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ================== Number to Words (Indian) ==================
function convertNumberToWordsIndian($number) {
    $number = round($number);
    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    ];
    if ($number == 0) return 'Zero';
    $result = '';
    $crore = floor($number / 10000000); $number %= 10000000;
    $lac = floor($number / 100000); $number %= 100000;
    $thousand = floor($number / 1000); $number %= 1000;
    $hundred = floor($number / 100); $number %= 100;
    $ten = $number;
    if ($crore) $result .= convertTwoDigits($crore, $words) . ' Crore ';
    if ($lac) $result .= convertTwoDigits($lac, $words) . ' Lac ';
    if ($thousand) $result .= convertTwoDigits($thousand, $words) . ' Thousand ';
    if ($hundred) $result .= $words[$hundred] . ' Hundred ';
    if ($ten) { if ($result != '') $result .= 'and '; $result .= convertTwoDigits($ten, $words); }
    return trim($result);
}

function convertTwoDigits($number, $words) {
    if ($number < 21) return $words[$number];
    $tens = floor($number / 10) * 10;
    $units = $number % 10;
    return $words[$tens] . ($units ? ' ' . $words[$units] : '');
}
// ================================================================

// Handle AJAX for parties
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_parties') {
    $section = isset($_GET['section']) ? trim($_GET['section']) : '';
    $options = '<option value="">Select Party</option>';
    if ($section) {
        $sectionLower = strtolower($section);
        if ($sectionLower == 'counter sell') {
            $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
        } else {
            $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
        }
        $query = "SELECT DISTINCT TRIM(PartyName) as PartyName FROM sales 
                  WHERE $sectionCond AND PartyName IS NOT NULL AND TRIM(PartyName)!='' 
                  ORDER BY PartyName";
        $res = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_assoc($res)) {
            $options .= '<option value="' . htmlspecialchars($row['PartyName'] ?? '') . '">' . htmlspecialchars($row['PartyName'] ?? '') . '</option>';
        }
    }
    echo $options;
    exit;
}

// Get filters
$selectedSection = isset($_GET['section']) ? trim($_GET['section']) : '';
$selectedParty = isset($_GET['party']) ? trim($_GET['party']) : '';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$txnTypeFilter = isset($_GET['txn_type']) ? $_GET['txn_type'] : 'all';

// Get distinct sections
$sectionsRes = mysqli_query($conn, "SELECT DISTINCT TRIM(section) as section FROM sales WHERE section IS NOT NULL AND TRIM(section)!='' ORDER BY section");
$sections = [];
while ($row = mysqli_fetch_assoc($sectionsRes)) $sections[] = $row['section'];

// Get parties for initial load
$parties = [];
if ($selectedSection) {
    $sectionLower = strtolower($selectedSection);
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    $partiesRes = mysqli_query($conn, "SELECT DISTINCT TRIM(PartyName) as PartyName FROM sales WHERE $sectionCond AND PartyName IS NOT NULL AND TRIM(PartyName)!='' ORDER BY PartyName");
    while ($row = mysqli_fetch_assoc($partiesRes)) $parties[] = $row['PartyName'];
}

// Helper functions (updated for refund handling)
function getLedgerTransactions($conn, $party, $section, $startDate, $endDate, $searchTerm, $txnTypeFilter, &$debugSql = '') {
    $transactions = [];
    $party = trim($party);
    $sectionLower = strtolower(trim($section));
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(s.section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(s.section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    
    $dateCondition = "";
    if (!empty($startDate) && !empty($endDate)) {
        $dateCondition = " AND s.IssueDate BETWEEN '" . mysqli_real_escape_string($conn, $startDate) . "' AND '" . mysqli_real_escape_string($conn, $endDate) . "'";
    }
    
    // Include normal sales and refunds, exclude Source Refund (internal)
    $salesQuery = "SELECT s.SaleID, s.IssueDate as transaction_date, s.Remarks, s.BillAmount, s.PaidAmount, s.DueAmount,
                          s.Invoice_number, s.PNR, s.TicketNumber, s.PassengerName, s.airlines, s.TicketRoute,
                          s.refundtc, s.VoidCharge, s.PaymentMethod, s.PaymentStatus, s.refund_date, s.section
                   FROM sales s
                   WHERE TRIM(s.PartyName) = '" . mysqli_real_escape_string($conn, $party) . "'
                   AND $sectionCond $dateCondition
                   AND s.Remarks NOT IN ('Source Refund')";
    $debugSql = $salesQuery;
    $salesRes = mysqli_query($conn, $salesQuery);
    if ($salesRes) {
        while ($row = mysqli_fetch_assoc($salesRes)) {
            $amount = floatval($row['BillAmount']);
            $remarks = strtolower(trim($row['Remarks']));
            $airlines = strtolower(trim($row['airlines'] ?? ''));
            $sectionVal = strtolower(trim($row['section'] ?? ''));
            
            // Detect extra services
            $extraKeywords = ['extra baggage', 'seat purchase', 'meal purchase', 'name correction', 'extra leg room'];
            $isExtraService = in_array($remarks, $extraKeywords) 
                              || $airlines == 'extra service' 
                              || $sectionVal == 'extra_service';
            
            if ($isExtraService) {
                $debit = $amount; $credit = 0; $txnType = 'EMD';
                $remarkText = "EMD Issued";
            } elseif ($remarks == 'refund') {
                $refundAmount = floatval($row['refundtc']);
                if ($refundAmount == 0) $refundAmount = abs($amount);
                $debit = 0; 
                $credit = $refundAmount; 
                $txnType = 'Refund';
                $remarkText = "TKT refund";
                if (!empty($row['refund_date']) && $row['refund_date'] != '0000-00-00') {
                    $remarkText .= " | Date: " . date('d-m-Y', strtotime($row['refund_date']));
                }
            } elseif ($remarks == 'void transaction') {
                $voidAmount = floatval($row['VoidCharge']);
                $debit = 0; $credit = $voidAmount; $txnType = 'Void';
                $remarkText = "Void charge: " . number_format($voidAmount,2);
            } elseif ($remarks == 'reissue') {
                $debit = $amount; $credit = 0; $txnType = 'Reissue';
                $remarkText = "Reissued | Date: " . date('d-m-Y', strtotime($row['transaction_date']));
            } elseif ($remarks == 'cancellation charge') {
                $debit = $amount; $credit = 0; $txnType = 'Cancellation';
                $remarkText = "Cancellation Charge";
            } else {
                // Normal sale (including 'Air Ticket Sale' or NULL)
                $debit = $amount; $credit = 0; $txnType = 'Sale';
                $remarkText = "";
                if ($row['PaymentStatus'] == 'Paid' || $row['PaymentStatus'] == 'Partially Paid') {
                    $remarkText = "Status: " . $row['PaymentStatus'];
                    if (!empty($row['PaymentMethod'])) $remarkText .= " | " . $row['PaymentMethod'];
                }
            }
            $transactions[] = [
                'date' => $row['transaction_date'],
                'type' => $txnType,
                'route' => $row['TicketRoute'],
                'airline' => $row['airlines'],
                'pnr' => $row['PNR'],
                'ticket_no' => $row['TicketNumber'],
                'invoice' => $row['Invoice_number'],
                'debit' => $debit,
                'credit' => $credit,
                'remarks' => $remarkText
            ];
        }
    }
    
    // Payments query
    $paymentsDateCond = "";
    if (!empty($startDate) && !empty($endDate)) {
        $paymentsDateCond = " AND p.PaymentDate BETWEEN '" . mysqli_real_escape_string($conn, $startDate) . "' AND '" . mysqli_real_escape_string($conn, $endDate) . "'";
    }
    $payQuery = "SELECT p.PaymentDate, p.Amount, p.PaymentMethod, p.Notes, s.Invoice_number, s.PNR, s.TicketNumber, s.airlines, s.TicketRoute
                 FROM payments p JOIN sales s ON p.SaleID = s.SaleID
                 WHERE TRIM(s.PartyName) = '" . mysqli_real_escape_string($conn, $party) . "'
                 AND $sectionCond $paymentsDateCond";
    $payRes = mysqli_query($conn, $payQuery);
    if ($payRes) {
        while ($row = mysqli_fetch_assoc($payRes)) {
            $remarkText = "Payment: " . ($row['PaymentMethod'] ?? 'N/A');
            if (!empty($row['Notes'])) $remarkText .= " - " . $row['Notes'];
            $remarkText .= " | Date: " . date('d-m-Y', strtotime($row['PaymentDate']));
            $transactions[] = [
                'date' => $row['PaymentDate'],
                'type' => 'Payment',
                'route' => $row['TicketRoute'],
                'airline' => $row['airlines'],
                'pnr' => $row['PNR'],
                'ticket_no' => $row['TicketNumber'],
                'invoice' => $row['Invoice_number'],
                'debit' => 0,
                'credit' => floatval($row['Amount']),
                'remarks' => $remarkText
            ];
        }
    }
    
    usort($transactions, fn($a,$b) => strtotime($a['date']) - strtotime($b['date']));
    if ($txnTypeFilter != 'all') {
        $transactions = array_filter($transactions, fn($t) => strtolower($t['type']) == strtolower($txnTypeFilter));
    }
    if (!empty($searchTerm)) {
        $transactions = array_filter($transactions, fn($t) =>
            stripos($t['invoice'], $searchTerm)!==false ||
            stripos($t['ticket_no'], $searchTerm)!==false ||
            stripos($t['pnr'], $searchTerm)!==false
        );
    }
    return array_values($transactions);
}

function calculateTotals(&$transactions, $openingBalance = 0) {
    $totalDebit = $totalCredit = 0;
    $runningBalance = $openingBalance;
    foreach ($transactions as &$txn) {
        $runningBalance += $txn['debit'] - $txn['credit'];
        $txn['balance'] = $runningBalance;
        $totalDebit += $txn['debit'];
        $totalCredit += $txn['credit'];
    }
    return ['total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'current_balance' => $runningBalance];
}

function getOpeningBalance($conn, $party, $section, $startDate) {
    if (empty($startDate)) return 0;
    $sectionLower = strtolower(trim($section));
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    $query = "SELECT SUM(CASE WHEN LOWER(TRIM(Remarks)) NOT IN ('refund','void transaction','cancellation charge') THEN BillAmount ELSE 0 END) as total_sales,
                     SUM(PaidAmount) as total_paid
              FROM sales 
              WHERE TRIM(PartyName) = '" . mysqli_real_escape_string($conn, trim($party)) . "'
              AND $sectionCond AND IssueDate < '" . mysqli_real_escape_string($conn, $startDate) . "'";
    $res = mysqli_query($conn, $query);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return floatval($row['total_sales']) - floatval($row['total_paid']);
    }
    return 0;
}

function getPartySummary($conn, $party, $section) {
    $sectionLower = strtolower(trim($section));
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    $query = "SELECT 
                SUM(CASE WHEN LOWER(TRIM(Remarks)) NOT IN ('refund','void transaction','cancellation charge') THEN BillAmount ELSE 0 END) as total_sales,
                SUM(PaidAmount) as total_paid,
                SUM(DueAmount) as total_due,
                SUM(Profit) as total_profit,
                COUNT(*) as total_transactions
              FROM sales 
              WHERE TRIM(PartyName) = '" . mysqli_real_escape_string($conn, trim($party)) . "' AND $sectionCond";
    $res = mysqli_query($conn, $query);
    if ($res && $row = mysqli_fetch_assoc($res)) return $row;
    return ['total_sales'=>0,'total_paid'=>0,'total_due'=>0,'total_profit'=>0,'total_transactions'=>0];
}

function getMonthlySalesData($conn, $party, $section, $year) {
    $sectionLower = strtolower(trim($section));
    if ($sectionLower == 'counter sell') $sectionCond = "LOWER(section) IN ('counter','counter sell','countersell')";
    else $sectionCond = "LOWER(section) = '" . mysqli_real_escape_string($conn, $sectionLower) . "'";
    $monthly = array_fill(1,12,0);
    $query = "SELECT MONTH(IssueDate) as month, SUM(BillAmount) as total 
              FROM sales 
              WHERE TRIM(PartyName) = '" . mysqli_real_escape_string($conn, trim($party)) . "'
              AND $sectionCond AND YEAR(IssueDate) = $year
              AND LOWER(TRIM(Remarks)) NOT IN ('refund','void transaction','cancellation charge')
              GROUP BY MONTH(IssueDate)";
    $res = mysqli_query($conn, $query);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $monthly[(int)$row['month']] = floatval($row['total']);
    }
    return $monthly;
}

$transactions = [];
$openingBalance = 0;
$totals = [];
$summary = [];
$monthlySales = array_fill(1,12,0);
$debugInfo = '';
$currentYear = date('Y');
$closingBalance = 0;

if ($selectedParty && $selectedSection) {
    $openingBalance = getOpeningBalance($conn, $selectedParty, $selectedSection, $startDate);
    $transactions = getLedgerTransactions($conn, $selectedParty, $selectedSection, $startDate, $endDate, $searchTerm, $txnTypeFilter, $debugSql);
    $totals = calculateTotals($transactions, $openingBalance);
    $summary = getPartySummary($conn, $selectedParty, $selectedSection);
    $monthlySales = getMonthlySalesData($conn, $selectedParty, $selectedSection, $currentYear);
    $closingBalance = $totals['current_balance'];
    if (empty($transactions)) {
        $debugInfo = '<div class="alert alert-warning">No transactions found for the selected criteria.</div>';
    }
}

$closingBalanceWords = convertNumberToWordsIndian(abs($closingBalance)) . ' Bangladeshi Taka Only';
if (empty(trim($closingBalanceWords))) $closingBalanceWords = 'Zero Bangladeshi Taka Only';
$balanceSign = $closingBalance >= 0 ? 'Receivable' : 'Payable';

$companyName = "Faith Travels and Tours LTD";
$companyAddress = "Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue, Banani, Dhaka 1213";
$companyPhone = "+8801896459490, +8801896459495";
$companyEmail = "info@faithtrip.net, director@faithtrip.net";
$companyLogo = "logo.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger - <?php echo $companyName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="css/mdb.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        /* ----- GLOBAL STYLES (Screen & Print) ----- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #f0f8ff;
            font-family: 'Roboto', sans-serif;
        }
        .ledger-container {
            max-width: 1400px;
            margin: 15px auto;
            padding: 0 15px;
        }
        /* Filter card & stats (screen only) */
        .filter-card, .chart-container {
            background: white;
            border-radius: 10px;
            padding: 12px 15px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            margin-bottom: 15px;
        }
        .stats-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .stats-card {
            flex: 1;
            min-width: 130px;
            border-radius: 8px;
            padding: 6px 10px;
            color: white;
            transition: transform 0.2s;
        }
        .stats-card.primary { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .stats-card.success { background: linear-gradient(135deg, #0f9b0f 0%, #1e7e1e 100%); }
        .stats-card.warning { background: linear-gradient(135deg, #d35400 0%, #e67e22 100%); }
        .stats-card.info { background: linear-gradient(135deg, #2980b9 0%, #3498db 100%); }
        .stats-number { font-size: 18px; font-weight: bold; }
        .stats-label { font-size: 11px; opacity: 0.9; }
        .btn-export { border-radius: 20px; padding: 4px 12px; margin: 2px; font-size: 11px; }
        
        /* ----- HEADER (screen & pdf) ----- */
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 2px solid #2a5298;
            padding-bottom: 8px;
            flex-wrap: wrap;
        }
        .header-left { flex: 0 0 auto; }
        .header-center { flex: 1; text-align: center; padding: 0 15px; }
        .header-right { flex: 0 0 auto; text-align: right; font-size: 11px; line-height: 1.3; margin-right: 20px; }
        .header-center h3 { margin: 0; font-size: 18px; color: #1e3c72; }
        .header-center p { margin: 2px 0; font-size: 12px; }
        .header-right p { margin: 1px 0; }
        .logo-img { max-height: 45px; width: auto; }
        
        /* ----- TABLE STYLES with fixed layout ----- */
        .ledger-table-container {
            overflow-x: auto;
            margin: 10px 0;
        }
        #ledgerTable {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
            font-family: 'Roboto', sans-serif;
            font-size: 12px;
        }
        /* Column widths (percentages) */
        #ledgerTable th:nth-child(1), #ledgerTable td:nth-child(1) { width: 5%; }   /* SL */
        #ledgerTable th:nth-child(2), #ledgerTable td:nth-child(2) { width: 10%; }  /* Issue Date */
        #ledgerTable th:nth-child(3), #ledgerTable td:nth-child(3) { width: 25%; }  /* Flight Info */
        #ledgerTable th:nth-child(4), #ledgerTable td:nth-child(4) { width: 20%; }  /* Ticket Info */
        #ledgerTable th:nth-child(5), #ledgerTable td:nth-child(5) { width: 8%; }   /* Debit */
        #ledgerTable th:nth-child(6), #ledgerTable td:nth-child(6) { width: 8%; }   /* Credit */
        #ledgerTable th:nth-child(7), #ledgerTable td:nth-child(7) { width: 10%; }  /* Balance */
        #ledgerTable th:nth-child(8), #ledgerTable td:nth-child(8) { width: 14%; }  /* Remarks */
        
        #ledgerTable thead th {
            background: #2a5298;
            color: white;
            padding: 8px 5px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #1e3c72;
        }
        #ledgerTable tbody td {
            padding: 6px 4px;
            vertical-align: top;
            border-bottom: 1px solid #cfe2ff;
            border-right: 1px solid #cfe2ff;
            word-wrap: break-word;
        }
        #ledgerTable tbody td:first-child { border-left: 1px solid #cfe2ff; }
        #ledgerTable tbody tr { background-color: #eef2ff; }
        #ledgerTable tbody tr:nth-child(even) { background-color: #e6edf9; }
        #ledgerTable tbody tr:hover { background-color: #d4e2fc !important; }
        
        /* Transaction type colors */
        .sale-text { color: #0f5c0f; font-weight: 500; }
        .payment-text { color: #0a4d8c; font-weight: 500; }
        .refund-text { color: #b33c1f; font-weight: 500; }
        .reissue-text { color: #b86f0c; font-weight: 500; }
        .void-text { color: #6f42c1; font-weight: 500; }
        .cancellation-text { color: #dc3545; font-weight: 500; }
        
        /* Hide DataTables UI elements */
        .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate { display: none !important; }
        
        .amount-words {
            text-align: center;
            font-weight: 500;
            margin: 15px 0 5px;
            padding: 8px;
            background: #eef2ff;
            border-radius: 6px;
            font-size: 12px;
            color: #1e3c72;
        }
        
        /* Print styles (only for fallback) */
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .no-print, .filter-card, .stats-row, .chart-container, .btn-export { display: none !important; }
            .ledger-container { max-width: 100%; margin: 0; padding: 0; }
            .report-header { margin-top: 0; margin-bottom: 10px; }
            .header-center h3 { font-size: 16pt; }
            .header-center p { font-size: 11pt; }
            .header-right p { font-size: 9pt; }
            .logo-img { max-height: 40px; }
            #ledgerTable { font-size: 10pt !important; }
            #ledgerTable th, #ledgerTable td { padding: 4px 3px !important; }
            #ledgerTable thead th { background: #2a5298 !important; color: white !important; }
            .amount-words { font-size: 10pt !important; margin-top: 10px; }
            #ledgerTable tbody tr { background-color: #eef2ff !important; }
            #ledgerTable tbody tr:nth-child(even) { background-color: #e6edf9 !important; }
        }
        .emd-text { color: #8b5cf6; font-weight: 500; }
    </style>
</head>
<body>
<div class="ledger-container">
    <!-- Filter Card (hidden in print) -->
    <div class="filter-card no-print">
        <h4 class="mb-2"><i class="fas fa-book me-2"></i>Professional Ledger</h4>
        <form method="GET" action="" id="ledgerForm">
            <div class="row g-1">
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <select name="section" id="sectionSelect" class="form-select form-select-sm" required>
                        <option value="">Select Section</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo htmlspecialchars($sec ?? ''); ?>" <?php echo $selectedSection == $sec ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($sec ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Party</label>
                    <select name="party" id="partySelect" class="form-select form-select-sm" required>
                        <option value="">Select Party</option>
                        <?php foreach ($parties as $party): ?>
                            <option value="<?php echo htmlspecialchars($party ?? ''); ?>" <?php echo $selectedParty == $party ? 'selected' : ''; ?>><?php echo htmlspecialchars($party ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($startDate ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($endDate ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Transaction Type</label>
                    <select name="txn_type" class="form-select form-select-sm">
                        <option value="all" <?php echo $txnTypeFilter=='all'?'selected':''; ?>>All</option>
                        <option value="sale" <?php echo $txnTypeFilter=='sale'?'selected':''; ?>>Sale</option>
                        <option value="payment" <?php echo $txnTypeFilter=='payment'?'selected':''; ?>>Payment</option>
                        <option value="refund" <?php echo $txnTypeFilter=='refund'?'selected':''; ?>>Refund</option>
                        <option value="reissue" <?php echo $txnTypeFilter=='reissue'?'selected':''; ?>>Reissue</option>
                        <option value="void" <?php echo $txnTypeFilter=='void'?'selected':''; ?>>Void</option>
                    </select>
                </div>
            </div>
            <div class="row g-1 mt-2">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Invoice, PNR, Ticket..." value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>">
                </div>
                <div class="col-md-9 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm btn-export"><i class="fas fa-search me-1"></i>Load</button>
                    <button type="button" class="btn btn-success btn-sm btn-export" onclick="exportToExcel()"><i class="fas fa-file-excel me-1"></i>Excel</button>
                    <button type="button" class="btn btn-danger btn-sm btn-export" onclick="downloadPDF()"><i class="fas fa-file-pdf me-1"></i>Download PDF</button>
                    <button type="button" class="btn btn-info btn-sm btn-export" onclick="exportToCSV()"><i class="fas fa-file-csv me-1"></i>CSV</button>
                </div>
            </div>
        </form>
    </div>

    <?php echo $debugInfo; ?>

    <?php if ($selectedParty && $selectedSection && !empty($transactions)): ?>
    <!-- Stats Cards (compact, screen only) -->
    <div class="stats-row no-print">
        <div class="stats-card primary"><div class="stats-number"><?php echo number_format($summary['total_sales'],2); ?></div><div class="stats-label">Total Sales</div><small><?php echo $summary['total_transactions']; ?> txns</small></div>
        <div class="stats-card success"><div class="stats-number"><?php echo number_format($summary['total_paid'],2); ?></div><div class="stats-label">Total Received</div></div>
        <div class="stats-card warning"><div class="stats-number"><?php echo number_format($summary['total_due'],2); ?></div><div class="stats-label">Total Due</div></div>
        <div class="stats-card info"><div class="stats-number"><?php echo number_format($summary['total_profit'],2); ?></div><div class="stats-label">Total Profit</div></div>
    </div>
    <div class="stats-row no-print">
        <div class="stats-card primary"><div class="stats-number"><?php echo number_format(abs($totals['current_balance']),2); ?></div><div class="stats-label"><?php echo $balanceSign; ?> Balance</div></div>
        <div class="stats-card primary"><div class="stats-number"><?php echo number_format($openingBalance,2); ?></div><div class="stats-label">Opening Balance</div></div>
    </div>

    <!-- Charts (screen only) -->
    <div class="row g-2 no-print">
        <div class="col-md-6"><div class="chart-container"><h6>Monthly Sales - <?php echo $currentYear; ?></h6><canvas id="monthlySalesChart" style="max-height:180px; width:100%"></canvas></div></div>
        <div class="col-md-6"><div class="chart-container"><h6>Payment Status</h6><canvas id="paymentStatusChart" style="max-height:180px; width:100%"></canvas></div></div>
    </div>
    <?php endif; ?>

    <!-- Report Content (this is what will be included in the PDF) -->
    <div id="reportContent">
        <div class="report-header">
            <div class="header-left">
                <?php if(file_exists($companyLogo)) echo '<img src="'.$companyLogo.'" class="logo-img">'; ?>
            </div>
            <div class="header-center">
                <h3>Ledger Statement of <?php echo htmlspecialchars($selectedParty ?? '[Select Party]'); ?></h3>
                <?php if (!empty($startDate) && !empty($endDate)): ?>
                    <p><strong>Period:</strong> <?php echo date('d M Y',strtotime($startDate)); ?> to <?php echo date('d M Y',strtotime($endDate)); ?></p>
                <?php else: ?>
                    <p><strong>Period:</strong> All transactions (no date filter)</p>
                <?php endif; ?>
            </div>
            <div class="header-right">
                <h4><strong><?php echo $companyName; ?></strong></h4>
                <p><?php echo $companyAddress; ?></p>
                <p>Phone: <?php echo $companyPhone; ?></p>
                <p>Email: <?php echo $companyEmail; ?></p>
            </div>
        </div>

        <div class="ledger-table-container">
            <table id="ledgerTable" class="ledger-table" width="100%">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>Date of Issue</th>
                        <th>Flight Info</th>
                        <th>Ticket Info</th>
                        <th>Debit (BDT)</th>
                        <th>Credit (BDT)</th>
                        <th>Balance (BDT)</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($transactions)):
                        $serial = 1;
                        $balance = $openingBalance;
                        foreach ($transactions as $txn): 
                            $balance += $txn['debit'] - $txn['credit'];
                            $route = htmlspecialchars($txn['route'] ?? '');
                            $airline = htmlspecialchars($txn['airline'] ?? '');
                            $pnr = htmlspecialchars($txn['pnr'] ?? '');
                            $flightInfo = $route . '<br><small>' . $airline;
                            if (!empty($pnr)) $flightInfo .= ' | PNR: ' . $pnr;
                            $flightInfo .= '</small>';
                            if (empty($route) && empty($airline)) $flightInfo = '—';
                            
                            $ticketNo = htmlspecialchars($txn['ticket_no'] ?? '');
                            $invoice = htmlspecialchars($txn['invoice'] ?? '');
                            $ticketInfo = '';
                            if (!empty($ticketNo)) $ticketInfo .= 'Ticket: ' . $ticketNo;
                            if (!empty($invoice)) $ticketInfo .= ($ticketInfo ? '<br>' : '') . 'Invoice: ' . $invoice;
                            if (empty($ticketInfo)) $ticketInfo = '—';
                            
                            $remarkClass = '';
                            switch(strtolower($txn['type'] ?? '')) {
                                case 'sale': $remarkClass = 'sale-text'; break;
                                case 'payment': $remarkClass = 'payment-text'; break;
                                case 'refund': $remarkClass = 'refund-text'; break;
                                case 'reissue': $remarkClass = 'reissue-text'; break;
                                case 'void': $remarkClass = 'void-text'; break;
                                case 'emd': $remarkClass = 'emd-text'; break;
                                case 'cancellation': $remarkClass = 'cancellation-text'; break;
                            }
                    ?>
                    <tr>
                        <td><?php echo $serial++; ?></td>
                        <td><?php echo date('d-m-Y',strtotime($txn['date'])); ?></td>
                        <td><?php echo $flightInfo; ?></td>
                        <td><?php echo $ticketInfo; ?></td>
                        <td class="text-end"><?php echo $txn['debit']>0?number_format($txn['debit'],2):'-'; ?></td>
                        <td class="text-end"><?php echo $txn['credit']>0?number_format($txn['credit'],2):'-'; ?></td>
                        <td class="text-end fw-bold"><?php echo number_format($balance,2); ?></td>
                        <td class="<?php echo $remarkClass; ?>"><?php echo htmlspecialchars($txn['remarks'] ?? ''); ?></td>
                    </tr>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </tbody>
                <?php if (!empty($transactions)): ?>
                <tfoot>
                    <tr style="background:#e9ecef; font-weight:bold;">
                        <td colspan="4" class="text-end">Totals / Closing Balance</td>
                        <td class="text-end"><?php echo number_format($totals['total_debit'],2); ?></td>
                        <td class="text-end"><?php echo number_format($totals['total_credit'],2); ?></td>
                        <td class="text-end"><?php echo number_format($balance,2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Amount in Words -->
        <?php if (!empty($transactions)): ?>
        <div class="amount-words">
            <strong>Amount in Words:</strong> <?php echo $closingBalanceWords; ?> (<?php echo $balanceSign; ?>)
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function(){
    var tableElement = $('#ledgerTable');
    if ($.fn.DataTable.isDataTable(tableElement)) {
        tableElement.DataTable().destroy();
    }
    if (tableElement.length) {
        tableElement.DataTable({ 
            pageLength: -1,
            ordering: true,
            order: [],
            scrollX: false,
            responsive: true,
            destroy: true,
            autoWidth: false,
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            bPaginate: false,
            language: { emptyTable: "No records found for the selected criteria." }
        });
    }
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
                error: function(){ alert('Error loading parties.'); }
            });
        } else { $('#partySelect').html('<option value="">Select Party</option>'); }
    });
});

<?php if($selectedParty && $selectedSection && !empty($transactions)): ?>
const monthlyCtx = document.getElementById('monthlySalesChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: { labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], 
            datasets: [{ label: 'Sales (BDT)', data: [<?php echo implode(',',$monthlySales); ?>], backgroundColor: '#2a5298', borderColor: '#1e3c72', borderWidth: 1 }] },
    options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true, ticks: { callback: v => 'BDT '+v.toLocaleString() } } } }
});
const statusCtx = document.getElementById('paymentStatusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: { labels: ['Paid','Due'], datasets: [{ data: [<?php echo $summary['total_paid']; ?>, <?php echo $summary['total_due']; ?>], backgroundColor: ['#2ecc71','#e74c3c'] }] },
    options: { responsive: true, maintainAspectRatio: true, plugins: { tooltip: { callbacks: { label: ctx => ctx.label+': BDT '+ctx.raw.toLocaleString()+' ('+((ctx.raw/<?php echo $summary['total_sales']?:1; ?>)*100).toFixed(1)+'%)' } } } }
});
<?php endif; ?>

function getDynamicFileName(baseName = 'Ledger') {
    let party = '<?php echo htmlspecialchars($selectedParty ?? ''); ?>';
    let startDate = '<?php echo htmlspecialchars($startDate ?? ''); ?>';
    let endDate = '<?php echo htmlspecialchars($endDate ?? ''); ?>';
    let fileName = `${baseName}_of_${party.replace(/[^a-z0-9]/gi, '_')}`;
    if (startDate && endDate) fileName += `_from_${startDate}_to_${endDate}`;
    else fileName += `_all_records`;
    return fileName;
}

function exportToExcel() {
    var table = document.getElementById('ledgerTable');
    if(!table) return;
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.table_to_sheet(table, { raw: true });
    XLSX.utils.book_append_sheet(wb, ws, 'Ledger');
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

function downloadPDF() {
    var element = document.getElementById('reportContent');
    if (!element) return;
    var opt = {
        margin:        [0.5, 0.5, 0.5, 0.5],
        filename:      getDynamicFileName() + '.pdf',
        image:         { type: 'jpeg', quality: 0.98 },
        html2canvas:   { scale: 2, letterRendering: true, useCORS: true, logging: false },
        jsPDF:         { unit: 'in', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>