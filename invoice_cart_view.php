<?php
ob_clean();
ob_start();

require_once __DIR__ . '/vendor/autoload.php';
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$formatter = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);

// Generate unique 6-digit invoice number
$invoiceNumber = 'INV-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
// $pnr = 'PNR123456'; // Replace with actual dynamic PNR

// Save to database
try {
    $pdo = new PDO("mysql:host=localhost;dbname=faithtrip_accounts", "root", "");
    $stmt = $pdo->prepare("INSERT INTO invoices (Invoice_number, date) VALUES (?, NOW())");
    $stmt->execute([$invoiceNumber]);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Get cart data
$sales = [];
$total = 0;
$ait = 0;
$gt = 0;

if (!empty($_SESSION['invoice_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['invoice_cart']));
    $query = "SELECT * FROM sales WHERE SaleID IN ($id_list)";
    $result = $pdo->query($query);
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $sales[] = $row;
        $total += $row['BillAmount'];
    }
    $ait = $total * 0.003;
    $gt = $total + $ait;
}

// Create PDF
$pdf = new TCPDF();
$pdf->SetPrintHeader(false); // Remove black bar header
$pdf->AddPage();

// Logo
$pdf->Image('logo.jpg', 10, 14, 30);

// Barcode
$pdf->SetY(10);
$pdf->SetX(80);
$barcodeStyle = [
    'position' => '', 'align' => 'C', 'stretch' => false, 'fitwidth' => true,
    'cellfitalign' => '', 'border' => false, 'hpadding' => 'auto', 'vpadding' => 'auto',
    'fgcolor' => [0, 0, 0], 'bgcolor' => false, 'text' => true,
    'font' => 'helvetica', 'fontsize' => 10, 'stretchtext' => 4
];
$pdf->write1DBarcode($invoiceNumber, 'C128', '70.0', '16.0', '45', 18, 0.4, $barcodeStyle, 'N');

// Title
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(0, 102, 204);
$pdf->Text(80, 40, 'INVOICE');

// Company info
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

// Invoice info
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(150, 45);
$today = date('d M Y');
$pdf->MultiCell(50, 0, "Date: $today\nInvoice: $invoiceNumber", 0, 'R');

// Client Info
$clientInfo = <<<EOD
<b>Client Name:</b> John Doe<br>
<b>Client Address:</b> 123 Client Road, City, Country
EOD;
$pdf->SetY(40);
$pdf->Ln(20);
$pdf->MultiCell(0, 0, $clientInfo, 1, 'L');

// Table header
$html = '<table border="1" cellpadding="4" cellspacing="0" width="100%">
<thead style="background-color:rgb(21, 99, 255); color: white;">
<tr>
    <th>SL</th>
    <th>Travelers</th>
    <th>Flight Info</th>
    <th>Ticket Info</th>
    <th>Price</th>
</tr>
</thead><tbody>';

$serial = 1;
foreach ($sales as $row) {
    $html .= '<tr>';
    $html .= '<td>' . $serial++ . '</td>';
    $html .= '<td>' . htmlspecialchars($row['PassengerName']) . '</td>';
    $html .= '<td>Route: <b>' . htmlspecialchars($row['TicketRoute']) . '</b><br>Airlines: <b>' . htmlspecialchars($row['airlines']) . '</b><br>Departure: <b>' . htmlspecialchars($row['FlightDate']) . '</b><br>Return: <b>' . htmlspecialchars($row['ReturnDate']) . '</b></td>';
    $html .= '<td>Ticket No: <b>' . htmlspecialchars($row['TicketNumber']) . '</b><br>PNR: <b>' . htmlspecialchars($row['PNR']) . '</b><br>Issued: <b>' . htmlspecialchars($row['IssueDate']) . '</b><br>Seat Class: <b>' . htmlspecialchars($row['PNR']) . '</b></td>';
    $html .= '<td>' . number_format($row['BillAmount'], 2) . '</td>';
    $html .= '</tr>';
}

$html .= '<tr><td colspan="4" align="right">Selling</td><td>' . number_format($total, 2) . '</td></tr>';
$html .= '<tr><td colspan="4" align="right">AIT</td><td>' . number_format($ait, 2) . '</td></tr>';
$html .= '<tr><td colspan="4" align="right"><b>Total</b></td><td><b>' . number_format($gt, 2) . '</b></td></tr>';
$html .= '</tbody></table>';

$pdf->Ln(10);
$pdf->writeHTML($html, true, false, true, false, '');
$pnr = $row['PNR'];
// Amount in words
$amountWords = ucwords($formatter->format($gt)) . ' Bangladeshi Taka Only';
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Write(0, "Amount in Words: $amountWords", '', 0, 'L', true);

// Notes
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 9);
$notes = <<<EOD
<b>Notes:</b><br>
1. Please make all payments for "Faith Travels and Tours LTD."<br>
2. For POS payment, an additional 2.5% charge will be added for Visa/MasterCard and 3.5% for AMEX.<br>
3. For MFS Banking, an additional 1.75% charge will be added.
EOD;
$pdf->writeHTMLCell(0, 0, '', '', $notes, 0, 1, 0, true, 'L', true);

// Output PDF
ob_end_clean();
$fileName = "{$pnr}_{$invoiceNumber}.pdf";
$pdf->Output($fileName, 'D');
