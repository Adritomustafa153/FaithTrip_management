<?php
ob_clean();
ob_start();

require_once __DIR__ . '/vendor/autoload.php';
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

    $digits = ['', 'Hundred', 'Thousand', 'Lac', 'Crore'];
    $result = '';

    if ($number == 0) return 'Zero';

    $crore = floor($number / 10000000);
    $number %= 10000000;
    $lac = floor($number / 100000);
    $number %= 100000;
    $thousand = floor($number / 1000);
    $number %= 1000;
    $hundred = floor($number / 100);
    $number %= 100;
    $ten = $number;

    if ($crore) $result .= convertTwoDigits($crore, $words) . ' Crore ';
    if ($lac) $result .= convertTwoDigits($lac, $words) . ' Lac ';
    if ($thousand) $result .= convertTwoDigits($thousand, $words) . ' Thousand ';
    if ($hundred) $result .= $words[$hundred] . ' Hundred ';
    if ($ten) {
        if ($result != '') $result .= 'and ';
        $result .= convertTwoDigits($ten, $words);
    }

    return trim($result);
}

function convertTwoDigits($number, $words) {
    if ($number < 21) return $words[$number];
    $tens = floor($number / 10) * 10;
    $units = $number % 10;
    return $words[$tens] . ($units ? ' ' . $words[$units] : '');
}

// Generate refund invoice number
$invoiceNumber = 'RFD-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=faithtrip_accounts", "root", "");
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$refunds = [];
$total_refund_charge = 0;
$total_refund_amount = 0;
$ait = 0;

$pnr = '';
$partyName = '';
$issueDate = '';
$flightDate = '';
$returnDate = '';
$sellingPrice = 0;
$section = 'refund';

// Get client info from form
$client_name = '';
if (isset($_POST['ClientNameManual']) && trim($_POST['ClientNameManual']) !== '') {
    $client_name = trim($_POST['ClientNameManual']);
} elseif (isset($_POST['ClientNameDropdown']) && trim($_POST['ClientNameDropdown']) !== '') {
    $client_name = trim($_POST['ClientNameDropdown']);
} else {
    $client_name = 'Unknown Client';
}

$client_address = $_POST['address'] ?? 'Unknown Address';
$client_email = $_POST['client_email'] ?? 'No Email';
// $client_type = $_POST['clientType'] ?? 'Unknown';

if (!empty($_SESSION['refund_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['refund_cart']));
    $query = "SELECT *, BillAmount AS refund_charge, refundtc AS refund_amount FROM sales WHERE SaleID IN ($id_list)";
    $result = $pdo->query($query);
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if (empty($pnr)) {
            $pnr = $row['PNR'];
            $partyName = $row['PartyName'];
            $issueDate = $row['IssueDate'];
            $flightDate = $row['FlightDate'];
            $returnDate = $row['ReturnDate'];
            $sellingPrice = $row['refund_amount'];
        }
        $refunds[] = $row;
        $total_refund_charge += $row['refund_charge'];
        $total_refund_amount += $row['refund_amount'];
    }

    $ait = $total_refund_amount * 0.003;
    
    // Insert into invoices table with correct structure
    $stmt = $pdo->prepare("INSERT INTO invoices 
        (Invoice_number, date, PNR, PartyName, IssueDate, FlightDate, ReturnDate, SellingPrice, Section) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $invoiceNumber,
        $pnr,
        $client_name,
        $issueDate,
        $flightDate,
        $returnDate,
        $sellingPrice,
        $section
    ]);
}

// Create PDF
$pdf = new TCPDF();
$pdf->SetPrintHeader(false);
$pdf->AddPage();
$pdf->Image('logo.jpg', 10, 14, 30);
$pdf->SetY(10);
$pdf->SetX(80);

// Barcode style
$barcodeStyle = [
    'position' => '', 'align' => 'C', 'stretch' => false, 'fitwidth' => true,
    'cellfitalign' => '', 'border' => false, 'hpadding' => 'auto', 'vpadding' => 'auto',
    'fgcolor' => [0, 0, 0], 'bgcolor' => false, 'text' => true,
    'font' => 'helvetica', 'fontsize' => 10, 'stretchtext' => 4
];
$pdf->write1DBarcode($invoiceNumber, 'C128', '70.0', '16.0', '45', 18, 0.4, $barcodeStyle, 'N');

// Invoice title
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(220, 53, 69); // Red color for refund
$pdf->Text(75, 40, 'REFUND INVOICE');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Company Info
$companyInfo = <<<EOF
<table style="font-family: helvetica; font-size: 10pt; text-align: right;" border="0" cellpadding="2">
  <tr><td colspan="2" style="font-size: 14pt;"><h3>Faith Travels and Tours LTD</h3></td></tr>
  <tr><td colspan="2">Abedin Tower (Level 5), Road 17,<br>35 Kamal Ataturk Avenue, Banani, Dhaka 1213</td></tr>
  <tr><td colspan="2">info@faithtrip.net, director@faithtrip.net</td></tr>
  <tr><td colspan="2">+8801896459490, +8801896459495</td></tr>
</table>
EOF;

$pdf->SetXY(110, 10);
$pdf->writeHTMLCell(0, 0, '', '', $companyInfo, 0, 1, 0, true, 'R', true);
$pdf->SetXY(150, 45);
$pdf->MultiCell(50, 0, "Date: " . date('d M Y') . "\nInvoice: $invoiceNumber", 0, 'R');

// Client Info
$clientInfo = <<<EOD
<div style="padding: 0px;margin-top: 0px;margin-bottom: 2px; background-color:rgb(248, 246, 246);">
    <p><b>Client Name: </b>{$client_name}</p>
    <p><b>Client Address: </b>{$client_address}</p>

</div>
EOD;
$pdf->SetY(40);
$pdf->Ln(20);
$pdf->writeHTML($clientInfo, true, false, true, false, '');

// Refund items table
$html = '<style>
    tr {border-bottom: 1px solid #ccc;} 
    th {background-color:rgb(220, 53, 69); color: white;}
    .refund-amount {color: #000000ff; font-weight: bold;}
</style>';
$html .= '<table cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;">
<thead>
<tr>
    <th width="5%">SL</th>
    <th width="20%">Travelers</th>
    <th width="25%">Flight Info</th>
    <th width="25%">Ticket Info</th>
    <th width="12%">Refund</th>
    <th width="13%">Refund</th>
</tr>
</thead><tbody>';

$serial = 1;
foreach ($refunds as $row) {
    $html .= '<tr>';
    $html .= '<td width="5%">' . $serial++ . '</td>';
    $html .= '<td width="20%">' . htmlspecialchars($row['PassengerName']) . '</td>';
    $html .= '<td width="25%">Route: <b>' . htmlspecialchars($row['TicketRoute']) . '</b><br>Airlines: <b>' . htmlspecialchars($row['airlines']) . '</b><br>Departure: <b>' . htmlspecialchars($row['FlightDate']) . '</b><br>Return: <b>' . htmlspecialchars($row['ReturnDate']) . '</b></td>';
    $html .= '<td width="25%">Ticket No: <b>' . htmlspecialchars($row['TicketNumber']) . '</b><br>PNR: <b>' . htmlspecialchars($row['PNR']) . '</b><br>Issued: <b>' . htmlspecialchars($row['IssueDate']) . '</b><br>Seat Class: <b>' . htmlspecialchars($row['Class']) . '</b></td>';
    $html .= '<td width="12%"><b>' . htmlspecialchars($row['Remarks']) . '</b></td>';
    $html .= '<td width="13%" class="refund-amount">' . number_format($row['refund_amount'], 2) . '</td>';
    $html .= '</tr>';
}

// $html .= '<tr><td colspan="5" align="right">Total Refund Charge</td><td class="refund-amount">' . number_format($total_refund_charge, 2) . '</td></tr>';
$html .= '<tr><td colspan="5" align="right">Total Refund Amount</td><td class="refund-amount">' . number_format($total_refund_amount, 2) . '</td></tr>';
// $html .= '<tr><td colspan="5" align="right">AIT (0.3%)</td><td class="refund-amount">' . number_format($ait, 2) . '</td></tr>';
$html .= '</tbody></table>';

$pdf->Ln(10);
$pdf->writeHTML($html, true, false, true, false, '');

// Amount in words
$amountWords = convertNumberToWordsIndian($total_refund_amount) . ' Bangladeshi Taka Only';
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Write(0, "Amount in Words: $amountWords", '', 0, 'L', true);

// Notes
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 9);
$notes = <<<EOD
<b>Notes:</b><br>
1. This is a refund invoice for previously issued tickets.<br>
2. Refund amount will be processed within 7-10 business days.<br>
3. For any queries regarding this refund, please contact our accounts department.
EOD;
$pdf->writeHTMLCell(0, 0, '', '', $notes, 0, 1, 0, true, 'L', true);

// Save PDF file
$fileName = "REFUND_{$pnr}_{$invoiceNumber}.pdf";
$filePath = __DIR__ . "/invoices/" . $fileName;

ob_end_clean();
$pdf->Output($filePath, 'F');

// Send email
$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'faithtrip.net@gmail.com';
$mail->Password = 'hprnbfnzkywrymqw';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('info@faithtrip.net', 'Faith Travels and Tours LTD');
$mail->addAddress($client_email);
$mail->Subject = 'Your Refund Invoice - ' . $invoiceNumber;
$mail->Body = "Dear Sir/Madam,\n\nThis email contains your refund invoice from Faith Travels and Tours LTD.\n\nRefund amount: BDT " . number_format($total_refund_amount, 2) . "\n\nPlease find your refund invoice attached.";

$mail->addAttachment($filePath);

if (!$mail->send()) {
    echo 'Mailer Error: ' . $mail->ErrorInfo;
    exit;
}

// Clear refund cart after successful generation
unset($_SESSION['refund_cart']);

$_SESSION['invoice_sent'] = true;
$_SESSION['invoice_file'] = $fileName;
$_SESSION['invoice_email'] = $client_email;

header("Location: mail_success.php");
exit;