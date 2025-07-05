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

$party_name = isset($_GET['party']) ? urldecode($_GET['party']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

$sql = "SELECT * FROM sales WHERE (PaymentStatus = 'Due' OR PaymentStatus = 'Partially Paid')";
if (!empty($party_name) && strtolower($party_name) !== 'all') {
    $sql .= " AND PartyName = '" . mysqli_real_escape_string($conn, $party_name) . "'";
}
if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND IssueDate BETWEEN '" . mysqli_real_escape_string($conn, $from_date) . "' AND '" . mysqli_real_escape_string($conn, $to_date) . "'";
}
$sql .= " ORDER BY IssueDate DESC";

$result = mysqli_query($conn, $sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Receivables');

// Add Logo (Bigger)
$logoPath = 'logo.jpg';
if (file_exists($logoPath)) {
    $drawing = new Drawing();
    $drawing->setPath($logoPath);
    $drawing->setHeight(100); // increased height
    $drawing->setCoordinates('D1');
    $drawing->setWorksheet($sheet);
}

// Centered Company Info with Merged Rows
$sheet->mergeCells('B1:N1')->setCellValue('B1', 'FAITH TRIP INTERNATIONAL');
$sheet->mergeCells('B2:N2')->setCellValue('B2', 'Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue, Banani C/A, Dhaka 1213');
$sheet->mergeCells('B3:N3')->setCellValue('B3', 'Email: info@faithtrip.net | Phone: +8801896459590, +8801896459495');

$sheet->getStyle('B1:B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('B1:B3')->getFont()->setBold(true);

// Filter Info
$row = 5;
$filter_label = "Receivable report for " . ($party_name ?: 'All Parties');
if ($from_date && $to_date) {
    $filter_label .= " from $from_date to $to_date";
}
$sheet->mergeCells("A{$row}:N{$row}")->setCellValue("A{$row}", $filter_label);
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header Row
$row += 2;
$headers = ['SL', 'Section', 'Party Name', 'Passenger', 'Airline', 'Route', 'Ticket No', 'Issue Date', 'Days Passed', 'PNR', 'Bill Amount', 'Status', 'Paid', 'Due', 'Sales Person'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue("{$col}{$row}", $header);
    $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
    $sheet->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EDF7');
    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Set smaller widths for Party Name and Passenger
    if ($header == 'Party Name' || $header == 'Passenger') {
        $sheet->getColumnDimension($col)->setWidth(15);
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $col++;
}

// Set Ticket No column (G) to text format
$sheet->getStyle('G:G')->getNumberFormat()->setFormatCode('@');

// Data Rows
$row++;
$sl = 1;
$total_bill = $total_due = $total_paid = 0;

while ($data = mysqli_fetch_assoc($result)) {
    $col = 'A';
    $issue_date = new DateTime($data['IssueDate']);
    $days_passed = $issue_date->diff(new DateTime())->days;

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

// Totals Row
$sheet->setCellValue("J{$row}", 'Total:');
$sheet->setCellValue("K{$row}", $total_bill);
$sheet->setCellValue("L{$row}", '');
$sheet->setCellValue("M{$row}", $total_paid);
$sheet->setCellValue("N{$row}", $total_due);
$sheet->getStyle("J{$row}:N{$row}")->getFont()->setBold(true);
$sheet->getStyle("J{$row}:N{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');

// Amount in Words (Bold)
$row++;
$amount_words = "In Words: " . numberToWords($total_due);
$sheet->mergeCells("A{$row}:N{$row}")->setCellValue("A{$row}", $amount_words);
$sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setBold(true);

// Borders
$sheet->getStyle("A8:N" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Output
$filename = ($party_name ? $party_name . '_' : '') . 'Receivable_Report_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
mysqli_close($conn);
exit();

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