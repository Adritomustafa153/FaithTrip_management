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



$invoiceNumber = 'INV-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=faithtrip_accounts", "root", "");
    $stmt = $pdo->prepare("INSERT INTO invoices (Invoice_number, date) VALUES (?, NOW())");
    $stmt->execute([$invoiceNumber]);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$sales = [];
$total = 0;
$ait = 0;
$gt = 0;
$pnr = '';

if (!empty($_SESSION['invoice_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['invoice_cart']));
    $query = "SELECT * FROM sales WHERE SaleID IN ($id_list)";
    $result = $pdo->query($query);
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if (empty($pnr)) {
            $pnr = $row['PNR'];
        }
        $sales[] = $row;
        $total += $row['BillAmount'];
    }
    $ait = $total * 0.003;
    $gt = $total + $ait;
}

$clientName = $_SESSION['invoice_cart']['clientName'] ;
$clientAddress = $_SESSION['invoice_cart']['address'] ;

$pdf = new TCPDF();
$pdf->SetPrintHeader(false);
$pdf->AddPage();

$pdf->Image('logo.jpg', 10, 14, 30);
$pdf->SetY(10);
$pdf->SetX(80);
$barcodeStyle = [
    'position' => '', 'align' => 'C', 'stretch' => false, 'fitwidth' => true,
    'cellfitalign' => '', 'border' => false, 'hpadding' => 'auto', 'vpadding' => 'auto',
    'fgcolor' => [0, 0, 0], 'bgcolor' => false, 'text' => true,
    'font' => 'helvetica', 'fontsize' => 10, 'stretchtext' => 4
];
$pdf->write1DBarcode($invoiceNumber, 'C128', '70.0', '16.0', '45', 18, 0.4, $barcodeStyle, 'N');

$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(0, 102, 204);
$pdf->Text(80, 40, 'INVOICE');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
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

$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(150, 45);
$today = date('d M Y');
$pdf->MultiCell(50, 0, "Date: $today\nInvoice: $invoiceNumber", 0, 'R');

// Modify the clientInfo section to include box shadow styling:
$clientInfo = <<<EOD
<div style="border: 1px solid #ddd; padding: 10px; box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);">
    <b>Client Name:</b> $clientName<br>
    <b>Client Address:</b> $clientAddress
</div>
EOD;

$pdf->SetY(40);
$pdf->Ln(20);
$pdf->writeHTML($clientInfo, true, false, true, false, '');

$html = '<style>
tr {border-bottom: 1px solid #ccc;}
th {background-color:rgb(0, 98, 202); color: white;}
</style>';
$html .= '<table cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;">
<thead>
<tr>
    <th width="5%">SL</th>
    <th width="20%">Travelers</th>
    <th width="25%">Flight Info</th>
    <th width="25%">Ticket Info</th>
    <th width="15%">Remarks</th>
    <th width="10%">Price</th>
</tr>
</thead><tbody>';

$serial = 1;
foreach ($sales as $row) {
    $html .= '<tr>';
    $html .= '<td width="5%">' . $serial++ . '</td>';
    $html .= '<td width="20%">' . htmlspecialchars($row['PassengerName']) . '</td>';
    $html .= '<td width="25%">Route: <b>' . htmlspecialchars($row['TicketRoute']) . '</b><br>Airlines: <b>' . htmlspecialchars($row['airlines']) . '</b><br>Departure: <b>' . htmlspecialchars($row['FlightDate']) . '</b><br>Return: <b>' . htmlspecialchars($row['ReturnDate']) . '</b></td>';
    $html .= '<td width="25%">Ticket No: <b>' . htmlspecialchars($row['TicketNumber']) . '</b><br>PNR: <b>' . htmlspecialchars($row['PNR']) . '</b><br>Issued: <b>' . htmlspecialchars($row['IssueDate']) . '</b><br>Seat Class: <b>' . htmlspecialchars($row['PNR']) . '</b></td>';
    $html .= '<td width="15%"> </td>';
    $html .= '<td width="10%">' . number_format($row['BillAmount'], 2) . '</td>';
    $html .= '</tr>';
}

$html .= '<tr><td colspan="5" align="right">Selling</td><td>' . number_format($total, 2) . '</td></tr>';
$html .= '<tr><td colspan="5" align="right">AIT</td><td>' . number_format($ait, 2) . '</td></tr>';
$html .= '<tr><td colspan="5" align="right"><b>Total</b></td><td><b>' . number_format($gt, 2) . '</b></td></tr>';
$html .= '</tbody></table>';

$pdf->Ln(10);
$pdf->writeHTML($html, true, false, true, false, '');

$amountWords = convertNumberToWordsIndian($gt) . ' Bangladeshi Taka Only';
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Write(0, "Amount in Words: $amountWords", '', 0, 'L', true);

$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 9);
$notes = <<<EOD
<b>Notes:</b><br>
1. Please make all payments for "Faith Travels and Tours LTD."<br>
2. For POS payment, an additional 2.5% charge will be added for Visa/MasterCard and 3.5% for AMEX.<br>
3. For MFS Banking, an additional 1.75% charge will be added.
EOD;
$pdf->writeHTMLCell(0, 0, '', '', $notes, 0, 1, 0, true, 'L', true);

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Write(0, "We Accept:", '', 0, 'L', true);
$logos = ['visa.png', 'master.png', 'amex.jpg', 'unionpay.png', 'diners', 'npsb.jpeg'];
$x = 25;
foreach ($logos as $logo) {
    $pdf->Image(__DIR__ . "/payment_icons/$logo", $x, $pdf->GetY() + 2, 15);
    $x += 20;
}

ob_end_clean();
$fileName = "{$pnr}_{$invoiceNumber}.pdf";
$filePath = __DIR__ . "/invoices/" . $fileName;
$pdf->Output($filePath, 'I');

// $mail = new PHPMailer\PHPMailer\PHPMailer();
// $mail->isSMTP();
// $mail->Host = 'smtp.gmail.com';
// $mail->SMTPAuth = true;
// $mail->Username = 'info@faithtrip.net';
// $mail->Password = 'kbjtsnmotgbwhwvw';
// $mail->SMTPSecure = 'tls';
// $mail->Port = 587;
// $mail->setFrom('info@faithtrip.net', 'Faith Travels and Tours LTD');
// $mail->addAddress('director@faithtrip.net');
// $mail->Subject = 'Your Invoice - ' . $invoiceNumber;
// $mail->Body = 'Dear Sir/Mam, Please find your invoice attached.';
// $mail->addAttachment($filePath);

// if (!$mail->send()) {
//     echo 'Mailer Error: ' . $mail->ErrorInfo;
//     exit;
// }

header("Location: invoice_list.php");
exit;