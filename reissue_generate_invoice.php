<?php
// file_put_contents('debug_log.txt', 
//     "⚠️ Blocked request at " . date('Y-m-d H:i:s') . "\n" .
//     "Method: " . $_SERVER['REQUEST_METHOD'] . "\n" .
//     "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n" .
//     "Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'N/A') . "\n" .
//     "----------------------\n", FILE_APPEND);
file_put_contents('debug.txt', print_r($_POST, true));
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

// START PROCESS
$invoiceNumber = 'INV-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=faithtrip_accounts", "root", "");
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$sales = [];
$total = 0;
$ait = 0;
$gt = 0;

$pnr = '';
$partyName = '';
$issueDate = '';
$flightDate = '';
$returnDate = '';
$sellingPrice = 0;
$section = '';

$client_name = $_POST['ClientName'] ?? $_POST['ClientName'] ?? 'Unknown Client';

$client_address = $_POST['client_address'] ?? $_POST['ClientAddress'] ?? 'Unknown Address';


if (!empty($_SESSION['invoice_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['invoice_cart']));
    $query = "SELECT * FROM sales WHERE SaleID IN ($id_list)";
    $result = $pdo->query($query);
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if (empty($pnr)) {
            $pnr = $row['PNR'];
            $partyName = $row['PartyName'];
            $issueDate = $row['IssueDate'];
            $flightDate = $row['FlightDate'];
            $returnDate = $row['ReturnDate'];
        }
        $sales[] = $row;
        $total += $row['BillAmount'];
    }

    // $ait = $total * 0.003;
    $ait = 0;
    $gt = $total + $ait;
        $sellingPrice = $gt;
$client_name = '';

if (isset($_POST['ClientNameManual']) && trim($_POST['ClientNameManual']) !== '') {
    $client_name = trim($_POST['ClientNameManual']);
} elseif (isset($_POST['ClientNameDropdown']) && trim($_POST['ClientNameDropdown']) !== '') {
    $client_name = trim($_POST['ClientNameDropdown']);
} else {
    $client_name = 'Unknown Client';
}

$Sales_section = $_POST['clientType'] ?? 'Unknown Client';


$client_address = $_POST['address'] ?? 'Unknown Address';
$client_email = $_POST['client_email'] ?? 'No Email';
    // Insert into invoices table with additional ticket fields
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
        $Sales_section
    ]);
}

// PDF and email logic (same as before)

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

$clientInfo = <<<EOD
<div style="padding: 0px;margin-top: 0px;margin-bottom: 2px; background-color:rgb(248, 246, 246);">
    <p><b>Client Name: </b>{$client_name}</p>
    <p><b>Client Address: </b>{$client_address}</p>
</div>
EOD;
$pdf->SetY(40);
$pdf->Ln(20);
$pdf->writeHTML($clientInfo, true, false, true, false, '');

$html = '<style>tr {border-bottom: 1px solid #ccc;} th {background-color:rgb(0, 98, 202); color: white;}</style>';
$html .= '<table cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;">
<thead>
<tr>
    <th width="5%">SL</th>
    <th width="20%">Travelers</th>
    <th width="25%">Flight Info</th>
    <th width="25%">Ticket Info</th>
    <th width="12%">Remarks</th>
    <th width="13%">Price</th>
</tr>
</thead><tbody>';

$serial = 1;
foreach ($sales as $row) {
    $html .= '<tr>';
    $html .= '<td>' . $serial++ . '</td>';
    $html .= '<td>' . htmlspecialchars($row['PassengerName']) . '</td>';
    $html .= '<td>Route: <b>' . htmlspecialchars($row['TicketRoute']) . '</b><br>Airlines: <b>' . htmlspecialchars($row['airlines']) . '</b><br>Departure: <b>' . htmlspecialchars($row['FlightDate']) . '</b><br>Return: <b>' . htmlspecialchars($row['ReturnDate']) . '</b></td>';
    $html .= '<td>Ticket No: <b>' . htmlspecialchars($row['TicketNumber']) . '</b><br>PNR: <b>' . htmlspecialchars($row['PNR']) . '</b><br>Issued: <b>' . htmlspecialchars($row['IssueDate']) . '</b><br>Seat Class: <b>' . htmlspecialchars($row['Class']) . '</b></td>';
    $html .= '<td>Reissue</td>';
    $html .= '<td>' . number_format($row['BillAmount'], 2) . '</td>';
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
$logos = ['visa.png', 'master.png', 'amex.png', 'unionpay.png', 'diners.jpg', 'npsb.jpeg', 'discover.jpg', 'tkpay.jpeg'];
$x = 25;
foreach ($logos as $logo) {
    $pdf->Image(__DIR__ . "/payment_icons/$logo", $x, $pdf->GetY() + 2, 15);
    $x += 20;
}
$fileName = "{$pnr}_{$invoiceNumber}.pdf";
$filePath = __DIR__ . "/invoices/" . $fileName;

ob_end_clean();
$pdf->Output($filePath, 'F');

// Send email
$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'info@faithtrip.net';
$mail->Password = 'kbjtsnmotgbwhwvw';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('info@faithtrip.net', 'Faith Travels and Tours LTD');
$mail->addAddress($client_email);
$mail->Subject = 'Your Invoice - ' . $invoiceNumber;
$mail->Body = "Dear Sir/Mam,\n\nGreetings From Faith Travels and Tours LTD. Thank You for being with us.\n\nIf you have any confusion please feel free to reach us. Please find your invoice attached.";

$mail->addAttachment($filePath);

if (!$mail->send()) {
    echo 'Mailer Error: ' . $mail->ErrorInfo;
    exit;
}

$_SESSION['invoice_sent'] = true;
$_SESSION['invoice_file'] = $fileName;
$_SESSION['invoice_email'] = $client_email;

header("Location: mail_success.php");
exit;