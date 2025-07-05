<?php
include 'db.php';

// Get the selected party name from the request
$party_name = isset($_GET['party_name']) ? urldecode($_GET['party_name']) : '';

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"".($party_name ? $party_name.'_' : '')."Outstanding_Report_".date('Y-m-d').".xls\"");
header("Pragma: no-cache");
header("Expires: 0");

// Query to get data for the selected party only
$sql = "SELECT * FROM sales WHERE PaymentStatus != 'Paid'";
if (!empty($party_name) && strtolower($party_name) !== 'all') {
    $sql .= " AND PartyName = '".mysqli_real_escape_string($conn, $party_name)."'";
}

$result = mysqli_query($conn, $sql);

// Calculate total amount
$total_amount = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $total_amount += $row['DueAmount'];
}
mysqli_data_seek($result, 0); // Reset result pointer

// Function to convert number to words
function numberToWords($num) {
    $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine");
    $tens = array("", "Ten", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");
    $teens = array("Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen");
    
    if ($num == 0) return "Zero";
    
    $words = "";
    
    if ($num >= 10000000) {
        $crores = (int)($num / 10000000);
        $words .= numberToWords($crores) . " Crore ";
        $num %= 10000000;
    }
    
    if ($num >= 100000) {
        $lakhs = (int)($num / 100000);
        $words .= numberToWords($lakhs) . " Lakh ";
        $num %= 100000;
    }
    
    if ($num >= 1000) {
        $thousands = (int)($num / 1000);
        $words .= numberToWords($thousands) . " Thousand ";
        $num %= 1000;
    }
    
    if ($num >= 100) {
        $hundreds = (int)($num / 100);
        $words .= $ones[$hundreds] . " Hundred ";
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

// Path to your logo file (replace with actual path)
$logo_path = 'logo.jpg';

// Check if logo exists and get base64 encoded version
$logo_data = '';
if (file_exists($logo_path)) {
    $logo_type = pathinfo($logo_path, PATHINFO_EXTENSION);
    $logo_data = 'data:image/' . $logo_type . ';base64,' . base64_encode(file_get_contents($logo_path));
}
?>

<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Outstanding Report</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        .company-header {
            font-size: 14px;
            margin-bottom: 20px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .amount-in-words {
            font-style: italic;
            margin-top: 5px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .numeric {
            text-align: right;
        }
        .logo-cell {
            height: 80px;
            vertical-align: top;
        }
    </style>
</head>
<body>
    <!-- Company Header with Logo -->
    <table width="100%" class="company-header">
        <tr>
            <td width="20%" class="logo-cell">
                <?php if (!empty($logo_data)): ?>
                    <img src="<?php echo $logo_data; ?>" alt="Company Logo" style="height: 60px;">
                <?php else: ?>
                    [LOGO]
                <?php endif; ?>
            </td>
            <td width="80%">
                <strong>FAITH TRIP INTERNATIONAL</strong><br>
                Abedin Tower (Level 5), Road 17, 35 Kamal Ataturk Avenue,<br>
                Banani C/A, Banani, Dhaka 1213<br>
                Email: info@faithtrip.net, director@faithtrip.net<br>
                Phone: +8801896459590, +8801896459495
            </td>
        </tr>
    </table>
    
    <!-- Report Title -->
    <div class="report-title">
        Outstanding report for <?php echo htmlspecialchars($party_name ? $party_name : 'All Parties'); ?>
    </div>
    
    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th>SL</th>
                <th>Date</th>
                <th>Party Name</th>
                <th>Passenger Name</th>
                <th>Ticket No</th>
                <th>PNR</th>
                <th>Bill Amount</th>
                <th>Paid Amount</th>
                <th>Due Amount</th>
                <th>Payment Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sl = 1;
            while ($row = mysqli_fetch_assoc($result)): 
            ?>
                <tr>
                    <td><?php echo $sl++; ?></td>
                    <td><?php echo $row['IssueDate']; ?></td>
                    <td><?php echo $row['PartyName']; ?></td>
                    <td><?php echo $row['PassengerName']; ?></td>
                    <td><?php echo $row['TicketNumber']; ?></td>
                    <td><?php echo $row['PNR']; ?></td>
                    <td class="numeric"><?php echo number_format($row['BillAmount'], 2); ?></td>
                    <td class="numeric"><?php echo number_format($row['PaidAmount'], 2); ?></td>
                    <td class="numeric"><?php echo number_format($row['DueAmount'], 2); ?></td>
                    <td><?php echo $row['PaymentStatus']; ?></td>
                </tr>
            <?php endwhile; ?>
            <tr class="total-row">
                <td colspan="6" align="right"><strong>Total:</strong></td>
                <td class="numeric"><?php echo number_format($total_amount, 2); ?></td>
                <td class="numeric"></td>
                <td class="numeric"><?php echo number_format($total_amount, 2); ?></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="10" class="amount-in-words">
                    <strong>In Words:</strong> <?php echo numberToWords($total_amount); ?>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php
mysqli_close($conn);
?>