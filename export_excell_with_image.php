<?php
require 'vendor/autoload.php';
include 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Get party name from GET
$party_name = isset($_GET['party_name']) ? urldecode($_GET['party_name']) : '';

// Query
$sql = "SELECT * FROM sales WHERE PaymentStatus != 'Paid'";
if (!empty($party_name) && strtolower($party_name) !== 'all') {
    $sql .= " AND PartyName = '" . mysqli_real_escape_string($conn, $party_name) . "'";
}
$result = mysqli_query($conn, $sql);

// Spreadsheet setup
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Insert Logo
$logoPath = 'logo.jpg';
if (file_exists($logoPath)) {
    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Company Logo');
    $drawing->setPath($logoPath);
    $drawing->setHeight(70);
    $drawing->setCoordinates('A1');
    $drawing->setWorksheet($sheet);
}

// Header info
$sheet->mergeCells('B1:H1');
$sheet->setCellValue('B1', 'FAITH TRIP INTERNATIONAL');
$sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('B2:H2');
$sheet->setCellValue('B2', 'Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue, Banani, Dhaka 1213');

$sheet->mergeCells('B3:H3');
$sheet->setCellValue('B3', 'Email: info@faithtrip.net | Phone: +8801896459590');

// Report title
$sheet->mergeCells('A5:J5');
$sheet->setCellValue('A5', 'Outstanding Report for ' . ($party_name ? $party_name : 'All Parties'));
$sheet->getStyle('A5')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Table headers
$headers = ['SL', 'Date', 'Party Name', 'Passenger Name', 'Ticket No', 'PNR', 'Bill Amount', 'Paid Amount', 'Due Amount', 'Payment Status'];
$sheet->fromArray($headers, null, 'A7');

$rowIndex = 8;
$sl = 1;
$total_due = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $rowIndex, $sl++);
    $sheet->setCellValue('B' . $rowIndex, $row['IssueDate']);
    $sheet->setCellValue('C' . $rowIndex, $row['PartyName']);
    $sheet->setCellValue('D' . $rowIndex, $row['PassengerName']);
    $sheet->setCellValue('E' . $rowIndex, $row['TicketNumber']);
    $sheet->setCellValue('F' . $rowIndex, $row['PNR']);
    $sheet->setCellValue('G' . $rowIndex, $row['BillAmount']);
    $sheet->setCellValue('H' . $rowIndex, $row['PaidAmount']);
    $sheet->setCellValue('I' . $rowIndex, $row['DueAmount']);
    $sheet->setCellValue('J' . $rowIndex, $row['PaymentStatus']);
    $total_due += $row['DueAmount'];
    $rowIndex++;
}

// Total row
$sheet->setCellValue('F' . $rowIndex, 'Total');
$sheet->setCellValue('I' . $rowIndex, $total_due);
$sheet->getStyle('F' . $rowIndex . ':I' . $rowIndex)->getFont()->setBold(true);

// Amount in words
$rowIndex++;
$sheet->mergeCells('A' . $rowIndex . ':J' . $rowIndex);
$sheet->setCellValue('A' . $rowIndex, 'In Words: ' . numberToWords($total_due));
$sheet->getStyle('A' . $rowIndex)->getFont()->setItalic(true);

// Auto width
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output to browser
$filename = ($party_name ? $party_name . '_' : '') . 'Outstanding_Report_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

// Number to words function
function numberToWords($num) {
    $ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine"];
    $tens = ["", "Ten", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
    $teens = ["Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];

    if ($num == 0) return "Zero Taka Only";

    $words = "";

    if ($num >= 10000000) {
        $crores = floor($num / 10000000);
        $words .= numberToWords($crores) . " Crore ";
        $num %= 10000000;
    }

    if ($num >= 100000) {
        $lakhs = floor($num / 100000);
        $words .= numberToWords($lakhs) . " Lakh ";
        $num %= 100000;
    }

    if ($num >= 1000) {
        $thousands = floor($num / 1000);
        $words .= numberToWords($thousands) . " Thousand ";
        $num %= 1000;
    }

    if ($num >= 100) {
        $hundreds = floor($num / 100);
        $words .= $ones[$hundreds] . " Hundred ";
        $num %= 100;
    }

    if ($num >= 20) {
        $words .= $tens[floor($num / 10)] . " ";
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
