<?php
// ledger_pdf.php - Generate PDF using TCPDF with perfect alignment
require_once __DIR__ . '/vendor/autoload.php'; // TCPDF autoload
require 'db.php';

// ========== Copy all helper functions from ledger.php ==========
// (They must be exactly the same to get the same data)
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
    
    $salesQuery = "SELECT s.SaleID, s.IssueDate as transaction_date, s.Remarks, s.BillAmount, s.PaidAmount, s.DueAmount,
                          s.Invoice_number, s.PNR, s.TicketNumber, s.PassengerName, s.airlines, s.TicketRoute,
                          s.refundtc, s.VoidCharge, s.PaymentMethod, s.PaymentStatus, s.refund_date
                   FROM sales s
                   WHERE TRIM(s.PartyName) = '" . mysqli_real_escape_string($conn, $party) . "'
                   AND $sectionCond $dateCondition";
    $debugSql = $salesQuery;
    $salesRes = mysqli_query($conn, $salesQuery);
    if ($salesRes) {
        while ($row = mysqli_fetch_assoc($salesRes)) {
            $amount = floatval($row['BillAmount']);
            $remarks = strtolower(trim($row['Remarks']));
            if ($remarks == 'refund') {
                $refundAmount = floatval($row['refundtc']);
                if ($refundAmount == 0) $refundAmount = abs($amount);
                $debit = 0; $credit = $refundAmount; $txnType = 'Refund';
                $remarkText = "Refund: " . number_format($refundAmount,2);
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
            } else {
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
            $remarkText = "Payment: " . $row['PaymentMethod'];
            if ($row['Notes']) $remarkText .= " - " . $row['Notes'];
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
    $query = "SELECT SUM(CASE WHEN LOWER(TRIM(Remarks)) NOT IN ('refund','void transaction') THEN BillAmount ELSE 0 END) as total_sales,
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
                SUM(CASE WHEN LOWER(TRIM(Remarks)) NOT IN ('refund','void transaction') THEN BillAmount ELSE 0 END) as total_sales,
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
// ========== End of helper functions ==========

// Get filters (from GET or POST)
$selectedSection = isset($_REQUEST['section']) ? trim($_REQUEST['section']) : '';
$selectedParty = isset($_REQUEST['party']) ? trim($_REQUEST['party']) : '';
$startDate = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : '';
$endDate = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : '';
$searchTerm = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
$txnTypeFilter = isset($_REQUEST['txn_type']) ? $_REQUEST['txn_type'] : 'all';

// Fetch data
$transactions = [];
$openingBalance = 0;
$totals = [];
$closingBalance = 0;

if ($selectedParty && $selectedSection) {
    $openingBalance = getOpeningBalance($conn, $selectedParty, $selectedSection, $startDate);
    $transactions = getLedgerTransactions($conn, $selectedParty, $selectedSection, $startDate, $endDate, $searchTerm, $txnTypeFilter, $debugSql);
    $totals = calculateTotals($transactions, $openingBalance);
    $closingBalance = $totals['current_balance'];
}

if (empty($transactions)) {
    die("No transactions found for the selected criteria.");
}

$closingBalanceWords = convertNumberToWordsIndian(abs($closingBalance)) . ' Bangladeshi Taka Only';
if (empty(trim($closingBalanceWords))) $closingBalanceWords = 'Zero Bangladeshi Taka Only';
$balanceSign = $closingBalance >= 0 ? 'Receivable' : 'Payable';

$companyName = "Faith Travels and Tours LTD";
$companyAddress = "Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue, Banani, Dhaka 1213";
$companyPhone = "+8801896459490, +8801896459495";
$companyEmail = "info@faithtrip.net, director@faithtrip.net";
$logoPath = __DIR__ . '/logo.jpg';

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator($companyName);
$pdf->SetAuthor($companyName);
$pdf->SetTitle('Ledger Statement');
$pdf->SetSubject('Ledger');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage('L', 'A4'); // Landscape

// Set font
$pdf->SetFont('helvetica', '', 9);

// ---- HEADER (Logo left, Title center, Company right) ----
$pdf->SetY(10);
$pdf->SetX(10);
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 10, 30, 0, 'JPG', '', '', false, 300);
}
// Title
$pdf->SetXY(60, 15);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(80, 6, 'Ledger Statement of ' . $selectedParty, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(60, 22);
$period = (!empty($startDate) && !empty($endDate)) ? date('d M Y',strtotime($startDate)) . ' to ' . date('d M Y',strtotime($endDate)) : 'All transactions (no date filter)';
$pdf->Cell(80, 5, 'Period: ' . $period, 0, 1, 'C');

// Company info right
$pdf->SetXY(150, 10);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->MultiCell(50, 4, $companyName, 0, 'R');
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY(150, 18);
$pdf->MultiCell(50, 3, $companyAddress, 0, 'R');
$pdf->SetXY(150, 28);
$pdf->MultiCell(50, 3, 'Phone: ' . $companyPhone, 0, 'R');
$pdf->SetXY(150, 34);
$pdf->MultiCell(50, 3, 'Email: ' . $companyEmail, 0, 'R');

$pdf->Ln(20);

// ---- TABLE HEADER ----
$widths = [8, 20, 45, 40, 20, 20, 25, 40]; // in mm (total ~218mm, fits A4 landscape)
$headers = ['SL', 'Date of Issue', 'Flight Info', 'Ticket Info', 'Debit (BDT)', 'Credit (BDT)', 'Balance (BDT)', 'Remarks'];

$pdf->SetFillColor(42, 82, 152); // dark blue
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 9);
for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// ---- TABLE BODY ----
$pdf->SetFillColor(238, 242, 255); // light blue
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);
$fill = false;
$balance = $openingBalance;
$serial = 1;
foreach ($transactions as $txn) {
    $balance += $txn['debit'] - $txn['credit'];
    // Flight info
    $flightInfo = $txn['route'] . "\n" . $txn['airline'] . ' | PNR: ' . $txn['pnr'];
    // Ticket info
    $ticketInfo = 'Ticket: ' . $txn['ticket_no'] . "\n" . 'Invoice: ' . $txn['invoice'];
    // Debit, Credit, Balance
    $debit = $txn['debit'] > 0 ? number_format($txn['debit'], 2) : '-';
    $credit = $txn['credit'] > 0 ? number_format($txn['credit'], 2) : '-';
    $bal = number_format($balance, 2);
    $remarks = $txn['remarks'];
    
    // Set row background alternating
    $pdf->SetFillColor($fill ? 238 : 246, $fill ? 242 : 249, $fill ? 255 : 255);
    $pdf->Cell($widths[0], 8, $serial++, 1, 0, 'C', 1);
    $pdf->Cell($widths[1], 8, date('d-m-Y', strtotime($txn['date'])), 1, 0, 'C', 1);
    // Flight Info (MultiCell)
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->MultiCell($widths[2], 8, $flightInfo, 1, 'L', 1);
    $pdf->SetXY($x + $widths[2], $y);
    // Ticket Info
    $pdf->MultiCell($widths[3], 8, $ticketInfo, 1, 'L', 1);
    $pdf->SetXY($x + $widths[2] + $widths[3], $y);
    // Debit
    $pdf->Cell($widths[4], 8, $debit, 1, 0, 'R', 1);
    // Credit
    $pdf->Cell($widths[5], 8, $credit, 1, 0, 'R', 1);
    // Balance
    $pdf->Cell($widths[6], 8, $bal, 1, 0, 'R', 1);
    // Remarks (MultiCell)
    $pdf->MultiCell($widths[7], 8, $remarks, 1, 'L', 1);
    $pdf->Ln();
    $fill = !$fill;
}

// ---- FOOTER (Totals) ----
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(233, 236, 239);
$totalWidth = array_sum($widths);
$totalWidthMinusThree = $totalWidth - $widths[4] - $widths[5] - $widths[6];
$pdf->Cell($totalWidthMinusThree, 8, 'Totals / Closing Balance', 1, 0, 'R', 1);
$pdf->Cell($widths[4], 8, number_format($totals['total_debit'], 2), 1, 0, 'R', 1);
$pdf->Cell($widths[5], 8, number_format($totals['total_credit'], 2), 1, 0, 'R', 1);
$pdf->Cell($widths[6], 8, number_format($balance, 2), 1, 0, 'R', 1);
$pdf->Cell($widths[7], 8, '', 1, 0, 'R', 1);
$pdf->Ln();

// ---- Amount in Words ----
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(238, 242, 255);
$pdf->Cell(0, 8, 'Amount in Words: ' . $closingBalanceWords . ' (' . $balanceSign . ')', 0, 1, 'C', 1);

// ---- Watermark ----
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(170, 170, 170);
$pdf->SetY(-10);
$pdf->Cell(0, 5, 'Copyrights © ' . date('Y') . ' ' . $companyName . '. All rights reserved.', 0, 0, 'C');

// Output PDF
$pdf->Output('Ledger_' . date('Y-m-d') . '.pdf', 'I');
?>