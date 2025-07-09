<?php
require 'vendor/autoload.php';
include 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Filters
$source_filter = $_GET['source'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$source_condition = "";
$date_condition = "";

if (!empty($source_filter)) {
    $source_condition = "AND Source = '$source_filter'";
}
if (!empty($from_date) && !empty($to_date)) {
    $date_condition = "AND IssueDate BETWEEN '$from_date' AND '$to_date'";
}

// Get list of sources
$source_query = mysqli_query($conn, "SELECT DISTINCT agency_name FROM sources");
$sources = [];
while ($row = mysqli_fetch_assoc($source_query)) {
    $sources[] = $row['agency_name'];
}

// Main query
$query = "
    SELECT 
        s.Source,
        s.IssueDate AS trans_date,
        s.TicketRoute,
        s.Airlines,
        s.PNR,
        s.TicketNumber,
        s.NetPayment AS credit,
        0 AS debit,
        '' AS remarks,
        'sale' AS type
    FROM sales s
    WHERE 1=1 $source_condition $date_condition

    UNION ALL

    SELECT 
        p.Source,
        p.payment_date AS trans_date,
        '' AS TicketRoute,
        '' AS Airlines,
        '' AS PNR,
        '' AS TicketNumber,
        0 AS credit,
        p.amount AS debit,
        p.remarks,
        'paid' AS type
    FROM paid p
    WHERE 1=1 $source_condition
    " . (!empty($from_date) && !empty($to_date) ? "AND p.payment_date BETWEEN '$from_date' AND '$to_date'" : "") . "

    ORDER BY trans_date ASC
";

$result = mysqli_query($conn, $query);

// Handle Excel export
if (isset($_GET['export'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add logo from URL (replace with your actual logo URL)
    $logoUrl = 'https://portal.faithtrip.net/companyLogo/gZdfl1728121001.jpg';
    $tempLogoPath = tempnam(sys_get_temp_dir(), 'logo') . '.jpg';
    file_put_contents($tempLogoPath, file_get_contents($logoUrl));

    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Company Logo');
    $drawing->setPath($tempLogoPath);
    $drawing->setHeight(80);
    $drawing->setCoordinates('E1');
    $drawing->setWorksheet($sheet);

    // Set company information (starting from column C to leave space for logo)
    $sheet->setCellValue('C1', 'FAITH TRAVELS AND TOURS LTD');
    $sheet->setCellValue('C2', 'Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue, Banani C/A, Banani Dhaka 1213');
    $sheet->setCellValue('C3', 'Email: info@faithtrip.net, director@faithtrip.net');
    $sheet->setCellValue('C4', 'Phone: +8801896459490, +8801896459495');
    $sheet->setCellValue('C5', 'PAYABLE PARTY LIST');
    $sheet->setCellValue('C6', 'Period: ' . ($from_date ? $from_date : 'Start Date') . ' to ' . ($to_date ? $to_date : 'End Date'));

    // Merge cells for company info
    $sheet->mergeCells('C1:L1');
    $sheet->mergeCells('C2:L2');
    $sheet->mergeCells('C3:L3');
    $sheet->mergeCells('C4:L4');
    $sheet->mergeCells('C5:L5');
    $sheet->mergeCells('C6:L6');

    // Style company information
    $companyStyle = [
        'font' => [
            'size' => 11,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ]
    ];
    $sheet->getStyle('C1:L6')->applyFromArray($companyStyle);
    
    // Make company name and title bold
    $sheet->getStyle('C1')->getFont()->setBold(true);
    $sheet->getStyle('C5')->getFont()->setBold(true)->setSize(14);

    // Set headers (row 8)
    $headers = ['SL', 'Date', 'Type', 'Source', 'Route', 'Airlines', 'PNR', 'Ticket No', 'Debit (Paid)', 'Credit (Bill)', 'Balance', 'Remarks'];
    $sheet->fromArray($headers, null, 'A8');

    // Style headers
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2a5885']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $sheet->getStyle('A8:L8')->applyFromArray($headerStyle);

    // Add data rows
    $sl = 1;
    $balance = 0;
    $rowNum = 9;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $debit = floatval($row['debit']);
        $credit = floatval($row['credit']);
        $balance += $credit - $debit;

        $sheet->setCellValue('A'.$rowNum, $sl++);
        $sheet->setCellValue('B'.$rowNum, $row['trans_date']);
        $sheet->setCellValue('C'.$rowNum, ucfirst($row['type']));
        $sheet->setCellValue('D'.$rowNum, $row['Source']);
        $sheet->setCellValue('E'.$rowNum, $row['TicketRoute']);
        $sheet->setCellValue('F'.$rowNum, $row['Airlines']);
        $sheet->setCellValue('G'.$rowNum, $row['PNR']);
        
        // Format TicketNumber as text to prevent scientific notation
        $ticketNumber = $row['TicketNumber'];
        $sheet->setCellValueExplicit('H'.$rowNum, $ticketNumber, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $sheet->setCellValue('I'.$rowNum, number_format($debit, 2));
        $sheet->setCellValue('J'.$rowNum, number_format($credit, 2));
        $sheet->setCellValue('K'.$rowNum, number_format($balance, 2));
        $sheet->setCellValue('L'.$rowNum, $row['remarks']);

        // Apply borders to data rows
        $sheet->getStyle('A'.$rowNum.':L'.$rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $rowNum++;
    }

    // Add total row
    $sheet->setCellValue('I'.$rowNum, 'Total Payable Balance:');
    $sheet->setCellValue('K'.$rowNum, number_format($balance, 2));
    $sheet->mergeCells('I'.$rowNum.':J'.$rowNum);
    
    // Style total row
    $totalStyle = [
        'font' => [
            'bold' => true,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'f2f2f2']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $sheet->getStyle('I'.$rowNum.':L'.$rowNum)->applyFromArray($totalStyle);
    $sheet->getStyle('I'.$rowNum.':K'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);  // SL
    $sheet->getColumnDimension('B')->setWidth(12); // Date
    $sheet->getColumnDimension('C')->setWidth(8);  // Type
    $sheet->getColumnDimension('D')->setWidth(15); // Source
    $sheet->getColumnDimension('E')->setWidth(20); // Route
    $sheet->getColumnDimension('F')->setWidth(25); // Airlines
    $sheet->getColumnDimension('G')->setWidth(15); // PNR
    $sheet->getColumnDimension('H')->setWidth(15); // Ticket No
    $sheet->getColumnDimension('I')->setWidth(12); // Debit
    $sheet->getColumnDimension('J')->setWidth(12); // Credit
    $sheet->getColumnDimension('K')->setWidth(12); // Balance
    $sheet->getColumnDimension('L')->setWidth(20); // Remarks

    // Set number formatting for currency columns
    $sheet->getStyle('I9:K'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');

    // Set row heights
    $sheet->getRowDimension(1)->setRowHeight(25);
    $sheet->getRowDimension(8)->setRowHeight(20);

    // Output Excel file
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="payable_report_'.date('Y-m-d').'.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    
    // Clean up temporary file
    unlink($tempLogoPath);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payable</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #2a5885; text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .filter-section { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .filter-group { display: flex; align-items: center; gap: 8px; }
        label { font-weight: 600; color: #495057; min-width: 60px; }
        select, input[type="date"] { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; background-color: white; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-search { background-color: #2a5885; color: white; }
        .btn-search:hover { background-color: #1e3c6d; }
        .btn-export { background-color: #28a745; color: white; }
        .btn-export:hover { background-color: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border: 1px solid #dee2e6; }
        th { background-color: #2a5885; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .total-section { margin-top: 20px; padding: 10px; background-color: #e9ecef; border-radius: 4px; text-align: right; font-weight: 600; }
        @media (max-width: 768px) {
            .filter-form { flex-direction: column; align-items: stretch; }
            .filter-group { width: 100%; }
            select, input[type="date"] { width: 100%; }
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <h2>Payable Party List</h2>
    
    <div class="filter-section">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label for="source">Source:</label>
                <select name="source" id="source">
                    <option value="">All Sources</option>
                    <?php foreach ($sources as $src): ?>
                        <option value="<?= htmlspecialchars($src) ?>" <?= $src == $source_filter ? 'selected' : '' ?>>
                            <?= htmlspecialchars($src) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="from_date">From:</label>
                <input type="date" name="from_date" id="from_date" value="<?= htmlspecialchars($from_date) ?>">
            </div>
            
            <div class="filter-group">
                <label for="to_date">To:</label>
                <input type="date" name="to_date" id="to_date" value="<?= htmlspecialchars($to_date) ?>">
            </div>
            
            <button type="submit" class="btn btn-search">Search</button>
            <button type="submit" name="export" value="1" class="btn btn-export">Export to Excel</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>SL</th>
                <th>Date</th>
                <th>Type</th>
                <th>Source</th>
                <th>Route</th>
                <th>Airlines</th>
                <th>PNR</th>
                <th>Ticket No</th>
                <th class="text-right">Debit (Paid)</th>
                <th class="text-right">Credit (Bill)</th>
                <th class="text-right">Balance</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sl = 1;
            $balance = 0;
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)):
                $debit = floatval($row['debit']);
                $credit = floatval($row['credit']);
                $balance += $credit - $debit;
            ?>
            <tr>
                <td><?= $sl++ ?></td>
                <td><?= htmlspecialchars($row['trans_date']) ?></td>
                <td><?= htmlspecialchars(ucfirst($row['type'])) ?></td>
                <td><?= htmlspecialchars($row['Source']) ?></td>
                <td><?= htmlspecialchars($row['TicketRoute']) ?></td>
                <td><?= htmlspecialchars($row['Airlines']) ?></td>
                <td><?= htmlspecialchars($row['PNR']) ?></td>
                <td><?= htmlspecialchars($row['TicketNumber']) ?></td>
                <td class="text-right"><?= number_format($debit, 2) ?></td>
                <td class="text-right"><?= number_format($credit, 2) ?></td>
                <td class="text-right"><?= number_format($balance, 2) ?></td>
                <td><?= htmlspecialchars($row['remarks']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="total-section">
        Total Payable Balance: <?= number_format($balance, 2) ?> Taka
    </div>
</div>

</body>
</html>