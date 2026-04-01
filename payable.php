<?php
require 'vendor/autoload.php';
include 'db.php';
include 'auth_check.php';

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
$load_ledger = isset($_GET['load_ledger']) && $_GET['load_ledger'] == '1';

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

// ========== CALCULATE FORWARD BALANCE (Balance before from_date) ==========
$forward_balance = 0;

if ($load_ledger && !empty($from_date)) {
    // Query to get all transactions BEFORE the from_date for the selected source
    $forward_query = "
        SELECT SUM(credit - debit) as forward_balance
        FROM (
            -- Regular Sales
            SELECT 
                s.Source,
                s.IssueDate AS trans_date,
                s.NetPayment AS credit,
                0 AS debit
            FROM sales s
            WHERE s.Remarks NOT IN ('Refund', 'Void Transaction', 'Voided', 'Reissue', 'Reissued') 
                $source_condition
                AND s.IssueDate < '$from_date'

            UNION ALL

            -- Refunds
            SELECT 
                s.Source,
                s.IssueDate AS trans_date,
                0 AS credit,
                s.refundtc AS debit
            FROM sales s
            WHERE s.Remarks = 'Refund' 
                $source_condition
                AND s.refundtc > 0
                AND s.IssueDate < '$from_date'

            UNION ALL

            -- Reissue Charges
            SELECT 
                s.Source,
                s.IssueDate AS trans_date,
                s.NetPayment AS credit,
                0 AS debit
            FROM sales s
            WHERE s.Remarks = 'Reissue' 
                $source_condition
                AND s.IssueDate < '$from_date'

            UNION ALL

            -- Reissue Reversals
            SELECT 
                s.Source,
                s.IssueDate AS trans_date,
                0 AS credit,
                s.NetPayment AS debit
            FROM sales s
            WHERE s.Remarks = 'Reissued' 
                $source_condition
                AND s.TicketNumber NOT LIKE '%REISSUE%'
                AND s.IssueDate < '$from_date'

            UNION ALL

            -- Void Transactions - FIXED: Now debit is the NetPayment (amount to deduct)
            SELECT 
                s.Source,
                s.IssueDate AS trans_date,
                0 AS credit,
                s.NetPayment AS debit
            FROM sales s
            WHERE s.Remarks = 'Void Transaction' 
                $source_condition
                AND s.IssueDate < '$from_date'

            UNION ALL

            -- Void Reversals
            SELECT 
                s.Source,
                s.IssueDate AS trans_date,
                s.NetPayment AS credit,
                s.BillAmount AS debit
            FROM sales s
            WHERE s.Remarks = 'Voided' 
                $source_condition
                AND s.TicketNumber NOT LIKE '%VOID%'
                AND s.IssueDate < '$from_date'

            UNION ALL

            -- Payment Records (if ledger is loaded)
            SELECT 
                p.Source,
                p.payment_date AS trans_date,
                0 AS credit,
                p.amount AS debit
            FROM paid p
            WHERE 1=1 $source_condition
                AND p.payment_date < '$from_date'
        ) AS forward_transactions
    ";
    
    $forward_result = mysqli_query($conn, $forward_query);
    if ($forward_result && $forward_row = mysqli_fetch_assoc($forward_result)) {
        $forward_balance = floatval($forward_row['forward_balance']);
    }
}

// Main query - FIXED: Void Transactions now show as DEBIT (NetPayment in debit column)
$query = "
    -- Regular Sales (excluding refunds, void, and reissue transactions)
    SELECT 
        s.Source,
        s.IssueDate AS trans_date,
        s.TicketRoute,
        s.Airlines,
        s.PNR,
        s.TicketNumber,
        s.NetPayment AS credit,
        0 AS debit,
        s.Remarks AS remarks,
        'sale' AS type
    FROM sales s
    WHERE s.Remarks NOT IN ('Refund', 'Void Transaction', 'Voided', 'Reissue', 'Reissued') $source_condition $date_condition

    UNION ALL

    -- Refunds (deduct refund amount)
    SELECT 
        s.Source,
        s.IssueDate AS trans_date,
        s.TicketRoute,
        s.Airlines,
        s.PNR,
        s.TicketNumber,
        0 AS credit,
        s.refundtc AS debit,
        CONCAT('REFUND: ', IFNULL(s.Remarks, '')) AS remarks,
        'refund' AS type
    FROM sales s
    WHERE s.Remarks = 'Refund' $source_condition $date_condition
    AND s.refundtc > 0

    UNION ALL

    -- Reissue Charges (add reissue charge as credit)
    SELECT 
        s.Source,
        s.IssueDate AS trans_date,
        s.TicketRoute,
        s.Airlines,
        s.PNR,
        CONCAT(s.TicketNumber, ' REISSUE') AS TicketNumber,
        s.NetPayment AS credit,
        0 AS debit,
        CONCAT('REISSUE CHARGE: ', IFNULL(s.Remarks, '')) AS remarks,
        'reissue' AS type
    FROM sales s
    WHERE s.Remarks = 'Reissue' $source_condition $date_condition

    UNION ALL

    -- Reissue Reversals (deduct original sale)
    SELECT 
        s.Source,
        s.IssueDate AS trans_date,
        s.TicketRoute,
        s.Airlines,
        s.PNR,
        s.TicketNumber,
        0 AS credit,
        s.NetPayment AS debit,
        'REISSUE REVERSAL: Original sale reversed for reissue' AS remarks,
        'reissue_reversal' AS type
    FROM sales s
    WHERE s.Remarks = 'Reissued' $source_condition $date_condition
    AND s.TicketNumber NOT LIKE '%REISSUE%'

    UNION ALL

    -- Void Transactions - FIXED: Now shows as DEBIT (NetPayment goes to debit column)
    SELECT 
        s.Source,
        s.IssueDate AS trans_date,
        s.TicketRoute,
        s.Airlines,
        s.PNR,
        s.TicketNumber,
        0 AS credit,
        s.NetPayment AS debit,
        CONCAT('Void reversal with ', IFNULL(s.VoidCharge, 0), ' tk charge: ', IFNULL(s.Notes, '')) AS remarks,
        'void' AS type
    FROM sales s
    WHERE s.Remarks = 'Void Transaction' $source_condition $date_condition

    UNION ALL

    -- Void Reversals (deduct original sale amount as debit AND original net as credit)
    SELECT 
        s.Source,
        s.IssueDate AS trans_date,
        s.TicketRoute,
        s.Airlines,
        s.PNR,
        s.TicketNumber,
        s.NetPayment AS credit,
        s.BillAmount AS debit,
        'VOID REVERSAL: Original sale & net payment reversed' AS remarks,
        'void_reversal' AS type
    FROM sales s
    WHERE s.Remarks = 'Voided' $source_condition $date_condition
    AND s.TicketNumber NOT LIKE '%VOID%'
";

// Only include payment records if ledger is requested
if ($load_ledger) {
    $query .= "
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
            IFNULL(p.remarks, '') AS remarks,
            'paid' AS type
        FROM paid p
        WHERE 1=1 $source_condition
        " . (!empty($from_date) && !empty($to_date) ? "AND p.payment_date BETWEEN '$from_date' AND '$to_date'" : "") . "
    ";
}

$query .= " ORDER BY trans_date ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

// Calculate total credit and debit for the period
$total_credit = 0;
$total_debit = 0;

if ($result && mysqli_num_rows($result) > 0) {
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_credit += floatval($row['credit']);
        $total_debit += floatval($row['debit']);
    }
    mysqli_data_seek($result, 0); // Reset pointer for later use
}

$period_balance = $total_credit - $total_debit;
$closing_balance = $forward_balance + $period_balance;

// Handle Excel export
if (isset($_GET['export'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add logo from URL
    $logoUrl = 'https://portal.faithtrip.net/companyLogo/gZdfl1728121001.jpg';
    $tempLogoPath = tempnam(sys_get_temp_dir(), 'logo') . '.jpg';
    $logoContent = @file_get_contents($logoUrl);
    if ($logoContent !== false) {
        file_put_contents($tempLogoPath, $logoContent);
        
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Company Logo');
        $drawing->setPath($tempLogoPath);
        $drawing->setHeight(80);
        $drawing->setCoordinates('E1');
        $drawing->setWorksheet($sheet);
    }

    // Set company information
    $sheet->setCellValue('C1', 'FAITH TRAVELS AND TOURS LTD');
    $sheet->setCellValue('C2', 'Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue, Banani C/A, Banani Dhaka 1213');
    $sheet->setCellValue('C3', 'Email: info@faithtrip.net, director@faithtrip.net');
    $sheet->setCellValue('C4', 'Phone: +8801896459490, +8801896459495');
    $sheet->setCellValue('C5', $load_ledger ? 'PAYABLE PARTY LIST' : 'SALES RECORD');
    $sheet->setCellValue('C6', 'Period: ' . ($from_date ? $from_date : 'Start Date') . ' to ' . ($to_date ? $to_date : 'End Date'));
    
    // Add forward balance info if ledger is loaded
    if ($load_ledger && !empty($from_date)) {
        $sheet->setCellValue('C7', 'Forward Balance (as of ' . $from_date . '): ' . number_format($forward_balance, 2));
    }

    // Merge cells for company info
    $sheet->mergeCells('C1:L1');
    $sheet->mergeCells('C2:L2');
    $sheet->mergeCells('C3:L3');
    $sheet->mergeCells('C4:L4');
    $sheet->mergeCells('C5:L5');
    $sheet->mergeCells('C6:L6');
    if ($load_ledger && !empty($from_date)) {
        $sheet->mergeCells('C7:L7');
    }

    // Style company information
    $companyStyle = [
        'font' => ['size' => 11],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ]
    ];
    $startRow = $load_ledger && !empty($from_date) ? 7 : 6;
    $sheet->getStyle('C1:L' . $startRow)->applyFromArray($companyStyle);
    
    // Make company name and title bold
    $sheet->getStyle('C1')->getFont()->setBold(true);
    $sheet->getStyle('C5')->getFont()->setBold(true)->setSize(14);
    if ($load_ledger && !empty($from_date)) {
        $sheet->getStyle('C7')->getFont()->setBold(true);
    }

    // Set headers
    $headerRow = $load_ledger && !empty($from_date) ? 9 : 8;
    $headers = ['SL', 'Date', 'Type', 'Source', 'Route', 'Airlines', 'PNR', 'Ticket No', 'Debit (Paid)', 'Credit (Bill)', 'Balance', 'Remarks'];
    $sheet->fromArray($headers, null, 'A' . $headerRow);

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
    $sheet->getStyle('A' . $headerRow . ':L' . $headerRow)->applyFromArray($headerStyle);

    // Add forward balance row if ledger is loaded
    $currentRow = $headerRow + 1;
    if ($load_ledger && !empty($from_date)) {
        $sheet->setCellValue('A' . $currentRow, '');
        $sheet->setCellValue('B' . $currentRow, $from_date);
        $sheet->setCellValue('C' . $currentRow, 'Forward Balance');
        $sheet->setCellValue('D' . $currentRow, $source_filter ?: 'ALL');
        $sheet->setCellValue('E' . $currentRow, '');
        $sheet->setCellValue('F' . $currentRow, '');
        $sheet->setCellValue('G' . $currentRow, '');
        $sheet->setCellValue('H' . $currentRow, '');
        $sheet->setCellValue('I' . $currentRow, '');
        $sheet->setCellValue('J' . $currentRow, '');
        $sheet->setCellValue('K' . $currentRow, number_format($forward_balance, 2));
        $sheet->setCellValue('L' . $currentRow, 'Opening Balance');
        
        // Style forward balance row
        $forwardStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '2a5885']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F0F8']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];
        $sheet->getStyle('A' . $currentRow . ':L' . $currentRow)->applyFromArray($forwardStyle);
        $currentRow++;
    }

    // Add data rows
    $sl = 1;
    $balance = $forward_balance;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $debit = floatval($row['debit']);
        $credit = floatval($row['credit']);
        
        if ($load_ledger) {
            $balance += $credit - $debit;
        }

        $sheet->setCellValue('A'.$currentRow, $sl++);
        $sheet->setCellValue('B'.$currentRow, $row['trans_date']);
        
        $displayType = ucfirst($row['type']);
        if ($row['type'] == 'void_reversal') {
            $displayType = 'Void Reversal';
        } elseif ($row['type'] == 'reissue') {
            $displayType = 'Reissue';
        } elseif ($row['type'] == 'reissue_reversal') {
            $displayType = 'Reissue Reversal';
        } elseif ($row['type'] == 'void') {
            $displayType = 'Void';
        }
        $sheet->setCellValue('C'.$currentRow, $displayType);
        
        $sheet->setCellValue('D'.$currentRow, $row['Source']);
        $sheet->setCellValue('E'.$currentRow, $row['TicketRoute']);
        $sheet->setCellValue('F'.$currentRow, $row['Airlines']);
        $sheet->setCellValue('G'.$currentRow, $row['PNR']);
        
        $ticketNumber = $row['TicketNumber'];
        $sheet->setCellValueExplicit('H'.$currentRow, $ticketNumber, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $sheet->setCellValue('I'.$currentRow, number_format($debit, 2));
        $sheet->setCellValue('J'.$currentRow, number_format($credit, 2));
        $sheet->setCellValue('K'.$currentRow, $load_ledger ? number_format($balance, 2) : '');
        $sheet->setCellValue('L'.$currentRow, $row['remarks']);

        $sheet->getStyle('A'.$currentRow.':L'.$currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        if (in_array($row['type'], ['void', 'void_reversal'])) {
            $sheet->getStyle('A'.$currentRow.':L'.$currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2F2');
        } elseif (in_array($row['type'], ['reissue', 'reissue_reversal'])) {
            $sheet->getStyle('A'.$currentRow.':L'.$currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0FFF0');
        } elseif ($row['type'] == 'refund') {
            $sheet->getStyle('A'.$currentRow.':L'.$currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF0F0');
        }
        
        $currentRow++;
    }

    // Add summary rows
    $sheet->setCellValue('I'.$currentRow, 'Total for Period:');
    $sheet->setCellValue('J'.$currentRow, number_format($period_balance, 2));
    $sheet->mergeCells('I'.$currentRow.':J'.$currentRow);
    $currentRow++;
    
    if ($load_ledger && !empty($from_date)) {
        $sheet->setCellValue('I'.$currentRow, 'Forward Balance:');
        $sheet->setCellValue('J'.$currentRow, number_format($forward_balance, 2));
        $sheet->mergeCells('I'.$currentRow.':J'.$currentRow);
        $currentRow++;
    }
    
    $sheet->setCellValue('I'.$currentRow, 'Closing Balance:');
    $sheet->setCellValue('K'.$currentRow, number_format($closing_balance, 2));
    $sheet->mergeCells('I'.$currentRow.':J'.$currentRow);
    
    $totalStyle = [
        'font' => ['bold' => true],
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
    $sheet->getStyle('I'.($currentRow-2).':L'.$currentRow)->applyFromArray($totalStyle);
    $sheet->getStyle('I'.($currentRow-2).':K'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(12);
    $sheet->getColumnDimension('C')->setWidth(12);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(25);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(15);
    $sheet->getColumnDimension('I')->setWidth(12);
    $sheet->getColumnDimension('J')->setWidth(12);
    $sheet->getColumnDimension('K')->setWidth(12);
    $sheet->getColumnDimension('L')->setWidth(20);

    $sheet->getStyle('I' . ($headerRow + 1) . ':K'.$currentRow)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getRowDimension(1)->setRowHeight(25);
    $sheet->getRowDimension($headerRow)->setRowHeight(20);

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'.($load_ledger ? 'payable_report' : 'sales_record').'_'.date('Y-m-d').'.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    
    if (file_exists($tempLogoPath)) {
        unlink($tempLogoPath);
    }
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payable</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
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
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { padding: 10px 12px; text-align: left; border: 1px solid #dee2e6; }
        th { background-color: #2a5885; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .total-section { margin-top: 20px; padding: 10px; background-color: #e9ecef; border-radius: 4px; text-align: right; font-weight: 600; }
        .forward-balance-section { margin-top: 15px; padding: 10px; background-color: #e8f0f8; border-radius: 4px; border-left: 4px solid #2a5885; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .void-row { background-color: #fff2f2 !important; }
        .void-reversal-row { background-color: #f0f8ff !important; }
        .reissue-row { background-color: #f0fff0 !important; }
        .reissue-reversal-row { background-color: #f8f0ff !important; }
        .refund-row { background-color: #fff0f0 !important; }
        .forward-row { background-color: #e8f0f8 !important; font-weight: bold; }
        .type-void { color: #dc3545; font-weight: bold; }
        .type-void-reversal { color: #007bff; font-weight: bold; }
        .type-reissue { color: #28a745; font-weight: bold; }
        .type-reissue-reversal { color: #6f42c1; font-weight: bold; }
        .type-refund { color: #fd7e14; font-weight: bold; }
        .balance-positive { color: #28a745; font-weight: bold; }
        .balance-negative { color: #dc3545; font-weight: bold; }
        @media (max-width: 1200px) {
            .container { max-width: 100%; padding: 10px; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
        }
        @media (max-width: 768px) {
            .filter-form { flex-direction: column; align-items: stretch; }
            .filter-group { width: 100%; }
            select, input[type="date"] { width: 100%; }
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
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
            
            <div class="checkbox-group">
                <input type="checkbox" name="load_ledger" id="load_ledger" value="1" <?= $load_ledger ? 'checked' : '' ?>>
                <label for="load_ledger" style="min-width: auto;">Load Ledger</label>
            </div>
            
            <button type="submit" class="btn btn-search">Search</button>
            <button type="submit" name="export" value="1" class="btn btn-export">Export to Excel</button>
        </form>
    </div>

    <?php if ($load_ledger && !empty($from_date) && $forward_balance != 0): ?>
    <div class="forward-balance-section">
        <strong>Forward Balance (as of <?= htmlspecialchars($from_date) ?>):</strong> 
        <span class="<?= $forward_balance >= 0 ? 'balance-positive' : 'balance-negative' ?>">
            <?= number_format($forward_balance, 2) ?> Taka
        </span>
        <br>
        <small style="color: #666;">* This is the balance from before the selected start date.</small>
    </div>
    <?php endif; ?>

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
                <?php if ($load_ledger): ?>
                <th class="text-right">Balance</th>
                <?php endif; ?>
                <th>Remarks</th>
             </tr>
        </thead>
        <tbody>
            <?php
            $sl = 1;
            $balance = $forward_balance;
            $has_forward_displayed = false;
            
            if ($load_ledger && !empty($from_date) && $forward_balance != 0) {
                $has_forward_displayed = true;
                ?>
                <tr class="forward-row">
                     <td></td>
                     <td><?= htmlspecialchars($from_date) ?></td>
                     <td><strong>Forward Balance</strong></td>
                     <td><?= htmlspecialchars($source_filter ?: 'ALL') ?></td>
                     <td></td>
                     <td></td>
                     <td></td>
                     <td></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <?php if ($load_ledger): ?>
                    <td class="text-right <?= $forward_balance >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                        <?= number_format($forward_balance, 2) ?>
                     </td>
                    <?php endif; ?>
                     <td>Opening Balance</td>
                 </tr>
                <?php
            }
            
            mysqli_data_seek($result, 0);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)):
                    $debit = floatval($row['debit']);
                    $credit = floatval($row['credit']);
                    
                    if ($load_ledger) {
                        $balance += $credit - $debit;
                    }
                    
                    $rowClass = '';
                    $typeClass = '';
                    switch ($row['type']) {
                        case 'void':
                            $rowClass = 'void-row';
                            $typeClass = 'type-void';
                            $displayType = 'Void';
                            break;
                        case 'void_reversal':
                            $rowClass = 'void-reversal-row';
                            $typeClass = 'type-void-reversal';
                            $displayType = 'Void Reversal';
                            break;
                        case 'reissue':
                            $rowClass = 'reissue-row';
                            $typeClass = 'type-reissue';
                            $displayType = 'Reissue';
                            break;
                        case 'reissue_reversal':
                            $rowClass = 'reissue-reversal-row';
                            $typeClass = 'type-reissue-reversal';
                            $displayType = 'Reissue Reversal';
                            break;
                        case 'refund':
                            $rowClass = 'refund-row';
                            $typeClass = 'type-refund';
                            $displayType = 'Refund';
                            break;
                        default:
                            $displayType = ucfirst($row['type']);
                    }
            ?>
            <tr class="<?= $rowClass ?>">
                <td><?= $sl++ ?></td>
                <td><?= htmlspecialchars($row['trans_date']) ?></td>
                <td class="<?= $typeClass ?>"><?= htmlspecialchars($displayType) ?></td>
                <td><?= htmlspecialchars($row['Source']) ?></td>
                <td><?= htmlspecialchars($row['TicketRoute']) ?></td>
                <td><?= htmlspecialchars($row['Airlines']) ?></td>
                <td><?= htmlspecialchars($row['PNR']) ?></td>
                <td><?= htmlspecialchars($row['TicketNumber']) ?></td>
                <td class="text-right"><?= number_format($debit, 2) ?></td>
                <td class="text-right"><?= number_format($credit, 2) ?></td>
                <?php if ($load_ledger): ?>
                <td class="text-right <?= $balance >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                    <?= number_format($balance, 2) ?>
                </td>
                <?php endif; ?>
                <td><?= htmlspecialchars($row['remarks']) ?></td>
            </tr>
            <?php endwhile; 
            } else if (!$has_forward_displayed) {
                echo '<tr><td colspan="'.($load_ledger ? '12' : '11').'" style="text-align: center;">No records found</td></tr>';
            }
            ?>
        </tbody>
     </table>
    
    <?php if ($result && mysqli_num_rows($result) > 0 || ($load_ledger && !empty($from_date))): ?>
    <div class="total-section">
        <div><strong>Period Balance:</strong> <?= number_format($period_balance, 2) ?> Taka</div>
        <?php if ($load_ledger && !empty($from_date)): ?>
        <div><strong>Forward Balance:</strong> <?= number_format($forward_balance, 2) ?> Taka</div>
        <div><strong>Closing Balance:</strong> <?= number_format($closing_balance, 2) ?> Taka</div>
        <?php else: ?>
        <div><strong>Total Payable Balance:</strong> <?= number_format($period_balance, 2) ?> Taka</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>