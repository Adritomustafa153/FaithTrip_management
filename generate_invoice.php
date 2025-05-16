<?php
ob_clean();
ob_start();

require_once __DIR__ . '/vendor/autoload.php'; // Adjust path as needed

// require_once('invoice_cart2.php'); // invoice_cart2.php must return $invoiceHTML
include('invoice_cart2.php');
$invoiceHTML = ob_get_clean();
$formatter = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);


// Generate unique 6-digit invoice number
$invoiceNumber = 'INV-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// ✅ Use in PDF


// Assume PNR from somewhere, e.g., $_GET['pnr']
$pnr = 'PNR123456'; // Replace with actual logic

// Save to database (basic example using PDO)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=faithtrip_accounts", "root", "");
    $stmt = $pdo->prepare("INSERT INTO invoices (Invoice_number, date) VALUES (?, NOW())");
    $stmt->execute([$invoiceNumber]);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Create new PDF document
$pdf = new TCPDF();
$pdf->AddPage();
// $pdf->write1DBarcode($invoiceNumber);

// Company logo
$pdf->Image('logo.jpg', 10, 10, 30);

// Barcode (center)
$pdf->SetY(10);
$pdf->SetX(80);


$barcodeStyle = [
    'position' => '',
    'align' => 'C',
    'stretch' => false,
    'fitwidth' => true,
    'cellfitalign' => '',
    'border' => false,
    'hpadding' => 'auto',
    'vpadding' => 'auto',
    'fgcolor' => [0, 0, 0],
    'bgcolor' => false,
    'text' => true,
    'font' => 'helvetica',
    'fontsize' => 10,
    'stretchtext' => 4
];

$pdf->write1DBarcode($invoiceNumber, 'C128', '70.0', '10.0', '50', 18, 0.4, $barcodeStyle, 'N');


$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(0, 102, 204);
$pdf->Text(85, 30, 'INVOICE');

// Company info (right)
$pdf->SetFont('helvetica', '', 10);
$companyInfo = <<<EOD
<b>Faith Travels and Tours LTD.</b>
📍 Abedin Tower (Level 5), Road 17, <br> 35 Kamal Ataturk Avenue, Banani Dhaka 1213
✉️ info@faithtrip.net, director@faithtrip.net
📞 +8810896459490, +8801896459495
EOD;
$pdf->SetXY(150, 10);
$pdf->MultiCell(50, 0, $companyInfo, 0, 'R', 0, 1, '', '', true);

// Invoice date and number
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(150, 40);
$today = date('d M Y');
$pdf->MultiCell(50, 0, "Date: $today\nInvoice: $invoiceNumber", 0, 'R');

// Client Name & Address
$clientInfo = <<<EOD
<b>Client Name:</b> John Doe
<b>Client Address:</b> 123 Client Road, City, Country
EOD;
$pdf->SetY(50);
$pdf->Ln(20);
$pdf->MultiCell(0, 0, $clientInfo, 0, 'L');

// Inject Invoice Cart HTML
ob_start();
include('invoice_cart2.php'); // $invoiceHTML must be set
$invoiceHTML = ob_get_clean();
$pdf->Ln(10);
$pdf->writeHTML($invoiceHTML, true, false, true, false, '');

// Total in words (you must calculate total)
$totalAmount = $gt; // Replace with real total from cart
$formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
$amountWords = ucwords($formatter->format($totalAmount)) . ' Bangladeshi Taka Only';

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Write(0, "Amount in Words: $amountWords", '', 0, 'L', true);

// Notes
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 9);
$notes = <<<EOD
<b>Notes:</b>
1. Please make all payments for "Faith Travels and Tours LTD."
2. For POS payment, an additional 2.5% charge will be added for Visa/MasterCard and 3.5% for AMEX.
3. For MFS Banking, an additional 1.75% charge will be added.
EOD;
$pdf->MultiCell(0, 0, $notes, 0, 'L');

// Output PDF
ob_end_clean();
$fileName = "{$pnr}_{$invoiceNumber}.pdf";
$pdf->Output($fileName, 'I'); // D = download, I = inline
