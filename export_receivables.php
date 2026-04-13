<?php
require 'vendor/autoload.php';
include 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Get parameters
$party_name = isset($_GET['party']) ? urldecode($_GET['party']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$section_filter = isset($_GET['section']) ? $_GET['section'] : '';
$pnr_search = isset($_GET['pnr']) ? $_GET['pnr'] : '';
$load_ledger = isset($_GET['load_ledger']) && $_GET['load_ledger'] == '1';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add Logo (if exists)
$logoPath = 'logo.jpg';
if (file_exists($logoPath)) {
    $drawing = new Drawing();
    $drawing->setPath($logoPath);
    $drawing->setHeight(100);
    $drawing->setCoordinates('D1');
    $drawing->setWorksheet($sheet);
}

// Company Info (centered)
$sheet->mergeCells('B1:N1')->setCellValue('B1', 'FAITH TRIP INTERNATIONAL');
$sheet->mergeCells('B2:N2')->setCellValue('B2', 'Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue, Banani C/A, Dhaka 1213');
$sheet->mergeCells('B3:N3')->setCellValue('B3', 'Email: info@faithtrip.net | Phone: +8801896459590, +8801896459495');
$sheet->getStyle('B1:B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('B1:B3')->getFont()->setBold(true);

// Filter Info
$row = 5;
$filter_label = ($load_ledger ? 'Ledger Report' : 'Receivable Report') . " for " . ($party_name ?: 'All Parties');
if ($from_date && $to_date) {
    $filter_label .= " from $from_date to $to_date";
}
if ($section_filter) {
    $filter_label .= " | Section: $section_filter";
}
if ($pnr_search) {
    $filter_label .= " | PNR: $pnr_search";
}
$sheet->mergeCells("A{$row}:N{$row}")->setCellValue("A{$row}", $filter_label);
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$row += 2;

// ---------- LEDGER EXPORT ----------
if ($load_ledger) {
    // Build the same ledger query as in receiveable.php
    $date_condition = "";
    if (!empty($from_date) && !empty($to_date)) {
        $date_condition = "AND trans_date BETWEEN '" . mysqli_real_escape_string($conn, $from_date) . "' AND '" . mysqli_real_escape_string($conn, $to_date) . "'";
    }
    
    $party_condition_sales = "";
    $party_condition_payments = "";
    if (!empty($party_name) && strtolower($party_name) !== 'all') {
        $party_condition_sales = "AND s.PartyName = '" . mysqli_real_escape_string($conn, $party_name) . "'";
        $party_condition_payments = "AND (p.PartyName = '" . mysqli_real_escape_string($conn, $party_name) . "' OR (p.SaleID IS NOT NULL AND p.SaleID IN (SELECT SaleID FROM sales WHERE PartyName = '" . mysqli_real_escape_string($conn, $party_name) . "')))";
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
    
    $result = mysqli_query($conn, $ledger_query);
    if (!$result) {
        die("Query Error: " . mysqli_error($conn));
    }
    
    // Headers for ledger
    $headers = ['Date', 'Type', 'Party', 'Ticket No', 'PNR', 'Credit (Paid)', 'Debit (Owed)', 'Balance', 'Notes'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue("{$col}{$row}", $header);
        $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EDF7');
        $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $col++;
    }
    
    // Adjust column widths
    $sheet->getColumnDimension('A')->setWidth(12);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(15);
    $sheet->getColumnDimension('I')->setWidth(30);
    
    $row++;
    $balance = 0;
    $total_debit = 0;
    $total_credit = 0;
    
    while ($data = mysqli_fetch_assoc($result)) {
        $debit = floatval($data['debit']);
        $credit = floatval($data['credit']);
        $balance += $debit - $credit;
        $total_debit += $debit;
        $total_credit += $credit;
        
        $col = 'A';
        $sheet->setCellValue($col++ . $row, $data['trans_date']);
        $sheet->setCellValue($col++ . $row, $data['trans_type']);
        $sheet->setCellValue($col++ . $row, $data['party']);
        $sheet->setCellValueExplicit($col++ . $row, $data['TicketNumber'], DataType::TYPE_STRING);
        $sheet->setCellValue($col++ . $row, $data['PNR']);
        $sheet->setCellValue($col++ . $row, number_format($debit, 2));
        $sheet->setCellValue($col++ . $row, number_format($credit, 2));
        $sheet->setCellValue($col++ . $row, number_format($balance, 2));
        $sheet->setCellValue($col++ . $row, $data['notes']);
        $row++;
    }
    
    // Totals row for ledger
    $sheet->setCellValue("E{$row}", 'Total:');
    $sheet->setCellValue("F{$row}", number_format($total_debit, 2));
    $sheet->setCellValue("G{$row}", number_format($total_credit, 2));
    $sheet->setCellValue("H{$row}", number_format($total_debit - $total_credit, 2));
    $sheet->getStyle("E{$row}:H{$row}")->getFont()->setBold(true);
    $sheet->getStyle("E{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
    
    // Add borders
    $lastRow = $row - 1;
    $sheet->getStyle("A8:I{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
} else {
    // ---------- ORIGINAL RECEIVABLE REPORT (Outstanding Sales) ----------
    $sql = "SELECT s.SaleID, s.section, s.PartyName, s.PassengerName, s.airlines, s.TicketRoute, s.TicketNumber, 
                   s.IssueDate, s.PNR, s.BillAmount, s.PaymentStatus, 
                   COALESCE(SUM(p.Amount), 0) as PaidAmount, 
                   (s.BillAmount - COALESCE(SUM(p.Amount), 0)) as DueAmount,
                   s.SalesPersonName, DATEDIFF(CURDATE(), s.IssueDate) AS DaysPassed 
            FROM sales s
            LEFT JOIN payments p ON s.SaleID = p.SaleID
            WHERE (s.PaymentStatus = 'Due' OR s.PaymentStatus = 'Partially Paid')
            GROUP BY s.SaleID
            HAVING DueAmount > 0";
    
    if (!empty($section_filter)) {
        $sql .= " AND s.section = '" . mysqli_real_escape_string($conn, $section_filter) . "'";
    }
    if (!empty($party_name) && strtolower($party_name) !== 'all') {
        $sql .= " AND s.PartyName = '" . mysqli_real_escape_string($conn, $party_name) . "'";
    }
    if (!empty($from_date) && !empty($to_date)) {
        $sql .= " AND s.IssueDate BETWEEN '" . mysqli_real_escape_string($conn, $from_date) . "' AND '" . mysqli_real_escape_string($conn, $to_date) . "'";
    }
    if (!empty($pnr_search)) {
        $sql .= " AND s.PNR LIKE '%" . mysqli_real_escape_string($conn, $pnr_search) . "%'";
    }
    $sql .= " ORDER BY s.IssueDate DESC";
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query Error: " . mysqli_error($conn));
    }
    
    // Headers for receivable report
    $headers = ['SL', 'Section', 'Party Name', 'Passenger', 'Airline', 'Route', 'Ticket No', 'Issue Date', 'Days Passed', 'PNR', 'Bill Amount', 'Status', 'Paid', 'Due', 'Sales Person'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue("{$col}{$row}", $header);
        $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EDF7');
        $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set column widths
        if ($header == 'Party Name' || $header == 'Passenger') {
            $sheet->getColumnDimension($col)->setWidth(15);
        } else {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $col++;
    }
    
    // Set Ticket No column to text format
    $sheet->getStyle('G:G')->getNumberFormat()->setFormatCode('@');
    
    $row++;
    $sl = 1;
    $total_bill = $total_due = $total_paid = 0;
    
    while ($data = mysqli_fetch_assoc($result)) {
        $issue_date = new DateTime($data['IssueDate']);
        $days_passed = $issue_date->diff(new DateTime())->days;
        
        $col = 'A';
        $sheet->setCellValue($col++ . $row, $sl++);
        $sheet->setCellValue($col++ . $row, $data['section']);
        $sheet->setCellValue($col++ . $row, $data['PartyName']);
        $sheet->setCellValue($col++ . $row, $data['PassengerName']);
        $sheet->setCellValue($col++ . $row, $data['airlines']);
        $sheet->setCellValue($col++ . $row, $data['TicketRoute']);
        $sheet->setCellValueExplicit($col++ . $row, $data['TicketNumber'], DataType::TYPE_STRING);
        $sheet->setCellValue($col++ . $row, $data['IssueDate']);
        $sheet->setCellValue($col++ . $row, $days_passed . " days");
        $sheet->setCellValue($col++ . $row, $data['PNR']);
        $sheet->setCellValue($col++ . $row, $data['BillAmount']);
        $sheet->setCellValue($col++ . $row, $data['PaymentStatus']);
        $sheet->setCellValue($col++ . $row, $data['PaidAmount']);
        $sheet->setCellValue($col++ . $row, $data['DueAmount']);
        $sheet->setCellValue($col++ . $row, $data['SalesPersonName']);
        
        $total_bill += $data['BillAmount'];
        $total_due += $data['DueAmount'];
        $total_paid += $data['PaidAmount'];
        $row++;
    }
    
    // Totals row
    $sheet->setCellValue("J{$row}", 'Total:');
    $sheet->setCellValue("K{$row}", $total_bill);
    $sheet->setCellValue("L{$row}", '');
    $sheet->setCellValue("M{$row}", $total_paid);
    $sheet->setCellValue("N{$row}", $total_due);
    $sheet->getStyle("J{$row}:N{$row}")->getFont()->setBold(true);
    $sheet->getStyle("J{$row}:N{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
    
    // Amount in words (for total due)
    $row++;
    $amount_words = "In Words: " . numberToWords($total_due);
    $sheet->mergeCells("A{$row}:N{$row}")->setCellValue("A{$row}", $amount_words);
    $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setBold(true);
    
    // Borders
    $lastRow = $row - 1;
    $sheet->getStyle("A8:N{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
}

// Output file
$filename = ($party_name ? $party_name . '_' : '') . ($load_ledger ? 'Ledger_Report' : 'Receivable_Report') . '_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
mysqli_close($conn);
exit();

// Helper function for number to words (same as original)
function numberToWords($num) {
    $ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine"];
    $tens = ["", "Ten", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
    $teens = ["Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];

    if ($num == 0) return "Zero";

    $words = "";
    if ($num >= 10000000) {
        $words .= numberToWords((int)($num / 10000000)) . " Crore ";
        $num %= 10000000;
    }
    if ($num >= 100000) {
        $words .= numberToWords((int)($num / 100000)) . " Lakh ";
        $num %= 100000;
    }
    if ($num >= 1000) {
        $words .= numberToWords((int)($num / 1000)) . " Thousand ";
        $num %= 1000;
    }
    if ($num >= 100) {
        $words .= $ones[(int)($num / 100)] . " Hundred ";
        $num %= 100;
    }
    if ($num >= 20) {
        $words .= $tens[(int)($num / 10)] . " ";
        $num %= 10;
    } elseif ($num >= 10) {
        $words .= $teens[$num - 10] . " ";
        $num = 0;
    }
    if ($num > 0) {
        $words .= $ones[$num] . " ";
    }

    return trim($words) . " Taka Only";
}
?>