<?php
include 'db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=invoice_export.xls");

$company_filter = $_GET['company'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$where = "WHERE Remarks = 'Air Ticket Sale'";
if (!empty($company_filter)) {
    $where .= " AND PartyName = '$company_filter'";
}
if (!empty($from_date) && !empty($to_date)) {
    $where .= " AND IssueDate BETWEEN '$from_date' AND '$to_date'";
}

$query = "
    SELECT s.PartyName, s.PassengerName, s.IssueDate, s.PaidAmount as debit, 
           s.BillAmount as credit, (s.BillAmount - IFNULL(s.PaidAmount, 0)) as balance,
           s.airlines, s.PNR, s.TicketRoute, s.TicketNumber, s.invoice_number
    FROM sales s
    $where
    ORDER BY s.IssueDate DESC
";

$result = mysqli_query($conn, $query);

// Function to convert number to words
function numberToWords($number) {
    $ones = array(
        0 => "Zero",
        1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five",
        6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine", 10 => "Ten",
        11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 14 => "Fourteen",
        15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen", 18 => "Eighteen",
        19 => "Nineteen"
    );
    
    $tens = array(
        2 => "Twenty", 3 => "Thirty", 4 => "Forty", 5 => "Fifty",
        6 => "Sixty", 7 => "Seventy", 8 => "Eighty", 9 => "Ninety"
    );
    
    if ($number < 20) {
        return $ones[$number];
    } elseif ($number < 100) {
        return $tens[floor($number / 10)] . (($number % 10 != 0) ? " " . $ones[$number % 10] : "");
    } elseif ($number < 1000) {
        return $ones[floor($number / 100)] . " Hundred" . (($number % 100 != 0) ? " " . numberToWords($number % 100) : "");
    } elseif ($number < 100000) {
        return numberToWords(floor($number / 1000)) . " Thousand" . (($number % 1000 != 0) ? " " . numberToWords($number % 1000) : "");
    } elseif ($number < 10000000) {
        return numberToWords(floor($number / 100000)) . " Lakh" . (($number % 100000 != 0) ? " " . numberToWords($number % 100000) : "");
    } else {
        return numberToWords(floor($number / 10000000)) . " Crore" . (($number % 10000000 != 0) ? " " . numberToWords($number % 10000000) : "");
    }
}

function convertToMoneyWords($amount) {
    $whole = floor($amount);
    $fraction = round(($amount - $whole) * 100);
    
    $words = numberToWords($whole) . " Taka";
    
    if ($fraction > 0) {
        $words .= " and " . numberToWords($fraction) . " Poisha";
    }
    
    return $words;
}

// Calculate totals
$total_debit = 0;
$total_credit = 0;
$running_balance = 0;
$records = array();

while ($row = mysqli_fetch_assoc($result)) {
    $debit = floatval($row['debit'] ?? 0);
    $credit = floatval($row['credit'] ?? 0);
    $balance = $credit - $debit;
    
    $total_debit += $debit;
    $total_credit += $credit;
    $running_balance = $running_balance - $debit + $credit;
    
    $row['calculated_balance'] = $running_balance;
    $records[] = $row;
}
?>
<html>
<head>
    <title>Invoice Export</title>
    <meta charset="UTF-8">
</head>
<body>
    <table border="1" cellpadding="12" cellspacing="0" width="100%">
        <!-- Header Section -->
        <tr>
            <td colspan="12" style="text-align: center; padding: 15px;">
                <table width="100%">
                    <tr>
                        <td style="text-align: center;">
                            <img src="logo.JPG" alt="Faith Travels and Tours LTD" style="height: 80px;">
                            <div style="font-size: 24px; font-weight: bold; margin-top: 10px;">Faith Travels and Tours LTD</div>
                            <div style="font-size: 14px; margin-top: 8px;">Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue, Banani C/A, Banani Dhaka 1213</div>
                            <div style="font-size: 14px; margin-top: 5px;">Email: info@faithtrip.net; director@faithtrip.net</div>
                            <div style="font-size: 14px; margin-top: 5px;">Phone: 01717649044, 01896459495</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- Title -->
        <tr>
            <td colspan="12" style="text-align: center; font-size: 18px; font-weight: bold; padding: 12px; border: 1px solid #000;">
                SALES INVOICE REPORT
            </td>
        </tr>
        
        <!-- Filter Information -->
        <tr>
            <td colspan="12" style="padding: 10px; border: 1px solid #000;">
                <strong>Filters Applied:</strong> 
                Company: <?php echo !empty($company_filter) ? $company_filter : 'All'; ?> | 
                Date Range: <?php echo !empty($from_date) && !empty($to_date) ? $from_date . ' to ' . $to_date : 'All Dates'; ?>
            </td>
        </tr>
        
        <!-- Column Headers -->
        <tr style="font-weight: bold; border: 1px solid #000;">
            <th style="border: 1px solid #000; padding: 8px;">SL</th>
            <th style="border: 1px solid #000; padding: 8px;">Party Name</th>
            <th style="border: 1px solid #000; padding: 8px;">Passenger Name</th>
            <th style="border: 1px solid #000; padding: 8px;">Invoice No</th>
            <th style="border: 1px solid #000; padding: 8px;">Issue Date</th>
            <th style="border: 1px solid #000; padding: 8px;">Airlines</th>
            <th style="border: 1px solid #000; padding: 8px;">PNR</th>
            <th style="border: 1px solid #000; padding: 8px;">Route</th>
            <th style="border: 1px solid #000; padding: 8px;">Ticket Number</th>
            <th style="border: 1px solid #000; padding: 8px;">Debit</th>
            <th style="border: 1px solid #000; padding: 8px;">Credit</th>
            <th style="border: 1px solid #000; padding: 8px;">Balance</th>
        </tr>
        
        <!-- Data Rows -->
        <?php 
        $sl = 1;
        $running_balance = 0;
        foreach ($records as $row): 
            $debit = floatval($row['debit'] ?? 0);
            $credit = floatval($row['credit'] ?? 0);
            $running_balance = $running_balance - $debit + $credit;
        ?>
        <tr>
            <td style="border: 1px solid #000; text-align: center; padding: 6px;"><?php echo $sl; ?></td>
            <td style="border: 1px solid #000; padding: 6px;"><?php echo htmlspecialchars($row['PartyName']); ?></td>
            <td style="border: 1px solid #000; padding: 6px;"><?php echo htmlspecialchars($row['PassengerName']); ?></td>
            <td style="border: 1px solid #000; padding: 6px;"><?php echo htmlspecialchars($row['invoice_number']); ?></td>
            <td style="border: 1px solid #000; padding: 6px;"><?php echo htmlspecialchars($row['IssueDate']); ?></td>
            <td style="border: 1px solid #000; padding: 6px;"><?php echo htmlspecialchars($row['airlines']); ?></td>
            <td style="border: 1px solid #000; padding: 6px;"><?php echo htmlspecialchars($row['PNR']); ?></td>
            <td style="border: 1px solid #000; padding: 6px;"><?php echo htmlspecialchars($row['TicketRoute']); ?></td>
            <td style="border: 1px solid #000; mso-number-format:'\@'; text-align: left; padding: 6px;"><?php echo htmlspecialchars($row['TicketNumber']); ?></td>
            <td style="border: 1px solid #000; text-align: right; padding: 6px;"><?php echo number_format($debit, 2); ?></td>
            <td style="border: 1px solid #000; text-align: right; padding: 6px;"><?php echo number_format($credit, 2); ?></td>
            <td style="border: 1px solid #000; text-align: right; padding: 6px;"><?php echo number_format($running_balance, 2); ?></td>
        </tr>
        <?php $sl++; endforeach; ?>
        
        <!-- Total Row -->
        <tr style="font-weight: bold;">
            <td colspan="9" style="border: 1px solid #000; text-align: right; padding: 10px;">TOTAL:</td>
            <td style="border: 1px solid #000; text-align: right; padding: 10px;"><?php echo number_format($total_debit, 2); ?></td>
            <td style="border: 1px solid #000; text-align: right; padding: 10px;"><?php echo number_format($total_credit, 2); ?></td>
            <td style="border: 1px solid #000; text-align: right; padding: 10px;"><?php echo number_format($running_balance, 2); ?></td>
        </tr>
        
        <!-- Amount in Words -->
        <tr>
            <td colspan="12" style="border: 1px solid #000; padding: 12px;">
                <strong>Total Amount in Words:</strong> 
                <?php echo convertToMoneyWords($total_credit); ?> Only
            </td>
        </tr>
        
        <!-- Footer -->
        <tr>
            <td colspan="12" style="border: 1px solid #000; text-align: center; padding: 12px;">
                <strong>Generated on:</strong> <?php echo date('Y-m-d H:i:s'); ?> | 
                <strong>Total Records:</strong> <?php echo count($records); ?>
            </td>
        </tr>
    </table>
</body>
</html>