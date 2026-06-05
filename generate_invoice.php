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
    if ($number == 0) return 'Zero';
    $result = '';
    $crore = floor($number / 10000000); $number %= 10000000;
    $lac = floor($number / 100000); $number %= 100000;
    $thousand = floor($number / 1000); $number %= 1000;
    $hundred = floor($number / 100); $number %= 100;
    $ten = $number;
    if ($crore) $result .= convertTwoDigits($crore, $words) . ' Crore ';
    if ($lac) $result .= convertTwoDigits($lac, $words) . ' Lac ';
    if ($thousand) $result .= convertTwoDigits($thousand, $words) . ' Thousand ';
    if ($hundred) $result .= $words[$hundred] . ' Hundred ';
    if ($ten) { if ($result != '') $result .= 'and '; $result .= convertTwoDigits($ten, $words); }
    return trim($result);
}

function convertTwoDigits($number, $words) {
    if ($number < 21) return $words[$number];
    $tens = floor($number / 10) * 10;
    $units = $number % 10;
    return $words[$tens] . ($units ? ' ' . $words[$units] : '');
}

// DB connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=faithtrip_accounts", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Generate unique invoice number
do {
    $invoiceNumber = 'INV-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE Invoice_number = ?");
    $stmtCheck->execute([$invoiceNumber]);
    $exists = $stmtCheck->fetchColumn();
} while ($exists > 0);

// Client info from POST
$client_name = '';
if (isset($_POST['ClientNameManual']) && trim($_POST['ClientNameManual']) !== '') {
    $client_name = trim($_POST['ClientNameManual']);
} elseif (isset($_POST['ClientNameDropdown']) && trim($_POST['ClientNameDropdown']) !== '') {
    $client_name = trim($_POST['ClientNameDropdown']);
} else {
    $client_name = 'Unknown Client';
}

$Sales_section = $_POST['clientType'] ?? 'Unknown Client';
// Map client type to section value used in sales table
$section_map = [
    'company' => 'corporate',
    'agent' => 'agent',
    'passenger' => 'counter'
];
$sales_section_value = $section_map[$Sales_section] ?? $Sales_section;

$client_address = $_POST['address'] ?? 'Unknown Address';
$client_email = $_POST['client_email'] ?? 'No Email';
$cc_emails = $_POST['cc_emails'] ?? '';
$bcc_emails = $_POST['bcc_emails'] ?? '';
$addAIT = isset($_POST['addAIT']) && $_POST['addAIT'] == '1';

// Fetch sales from cart
$sales = [];
$subtotal = 0;
$line_items = [];

if (!empty($_SESSION['invoice_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['invoice_cart']));
    
    // ========== UPDATE PARTY INFORMATION FOR ALL SALES IN CART ==========
    // Set PartyName and section for every sale in the cart
    $updateParty = $pdo->prepare("UPDATE sales SET PartyName = ?, section = ? WHERE SaleID IN ($id_list)");
    $updateParty->execute([$client_name, $sales_section_value]);
    // ====================================================================
    
    // Update sales table with invoice number
    $pdo->exec("UPDATE sales SET invoice_number = '$invoiceNumber' WHERE SaleID IN ($id_list)");
    
    $query = "SELECT * FROM sales WHERE SaleID IN ($id_list)";
    $result = $pdo->query($query);
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $remarks = trim($row['Remarks'] ?? '');
        $airlines = trim($row['airlines'] ?? '');
        $section = trim($row['section'] ?? '');
        
        // Bulletproof extra service detection
        $isExtraService = false;
        $extraKeywords = ['Extra Baggage', 'Seat Purchase', 'Meal Purchase', 'Name Correction', 'Extra Leg Room'];
        if (in_array($remarks, $extraKeywords)) $isExtraService = true;
        if ($airlines == 'Extra Service') $isExtraService = true;
        if ($section == 'extra_service') $isExtraService = true;
        foreach ($extraKeywords as $kw) {
            if (stripos($remarks, $kw) !== false) $isExtraService = true;
        }
        
        $amount = 0;
        $type_label = '';
        
        if ($remarks == 'Air Ticket Sale' || $remarks == '' || $remarks === null) {
            $amount = floatval($row['BillAmount']);
            $type_label = 'Air Ticket Sale';
        } elseif ($remarks == 'Void Transaction') {
            $amount = floatval($row['BillAmount']);
            $type_label = 'Void Charge';
        } elseif ($remarks == 'Refund') {
            $amount = -floatval($row['refundtc']);
            $type_label = 'Ticket Refund';
        } elseif ($remarks == 'Reissue') {
            $amount = floatval($row['BillAmount']);
            $type_label = 'Ticket Reissue';
        } elseif ($remarks == 'Cancellation Charge') {
            $amount = floatval($row['BillAmount']);
            $type_label = 'Ticket Cancellation Charge';
        } elseif ($isExtraService) {
            $amount = floatval($row['BillAmount']);
            $type_label = $remarks ?: 'Extra Service';
        } else {
            $amount = floatval($row['BillAmount']);
            $type_label = 'Sale';
        }
        
        $line_items[] = [
            'data'   => $row,
            'amount' => $amount,
            'type_label' => $type_label,
            'is_extra' => $isExtraService
        ];
        $subtotal += $amount;
        $sales[] = $row;
    }
}

if (empty($sales)) {
    die("No items in cart.");
}

// AIT and Grand Total
$ait = $addAIT ? $subtotal * 0.003 : 0;
$gt = $subtotal + $ait;

// First sale data for header (fallbacks if first is service)
$firstSale = $sales[0];
$pnr = $firstSale['PNR'] ?? 'N/A';
$issueDate = $firstSale['IssueDate'] ?? date('Y-m-d');
$flightDate = $firstSale['FlightDate'] ?? 'N/A';
$returnDate = $firstSale['ReturnDate'] ?? 'N/A';

// Update AIT in sales
$pdo->exec("UPDATE sales SET AIT = '$ait' WHERE SaleID IN ($id_list)");

// Insert invoice header
$created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$stmt = $pdo->prepare("INSERT INTO invoices (Invoice_number, date, PNR, PartyName, IssueDate, FlightDate, ReturnDate, SellingPrice, Section, created_by_user_id) 
                       VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$invoiceNumber, $pnr, $client_name, $issueDate, $flightDate, $returnDate, $gt, $Sales_section, $created_by]);

// ------------------- PDF CREATION (same as before) -------------------
$pdf = new TCPDF();
$pdf->SetPrintHeader(false);
$pdf->AddPage();
$pdf->Image('logo.jpg', 10, 14, 30);
$pdf->SetY(10);
$pdf->SetX(80);
$barcodeStyle = [
    'position' => '', 'align' => 'C', 'stretch' => false, 'fitwidth' => true,
    'cellfitalign' => '', 'border' => false, 'hpadding' => 'auto', 'vpadding' => 'auto',
    'fgcolor' => [0,0,0], 'bgcolor' => false, 'text' => true,
    'font' => 'helvetica', 'fontsize' => 10, 'stretchtext' => 4
];
$pdf->write1DBarcode($invoiceNumber, 'C128', '70.0', '16.0', '45', 18, 0.4, $barcodeStyle, 'N');
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(0,102,204);
$pdf->Text(80,40,'INVOICE');
$pdf->SetFont('helvetica','',10);
$pdf->SetTextColor(0,0,0);

$companyInfo = <<<EOF
<table style="font-family: helvetica; font-size: 10pt; text-align: right;" border="0" cellpadding="2">
    <tr><td colspan="2" style="font-size: 14pt;"><h3>Faith Travels and Tours LTD</h3></td></tr>
    <tr><td colspan="2">Abedin Tower (Level 5), Road 17,<br>35 Kamal Ataturk Avenue, Banani, Dhaka 1213</td></tr>
    <tr><td colspan="2">info@faithtrip.net, director@faithtrip.net</td></tr>
    <tr><td colspan="2">+8801896459490, +8801896459495</td></tr>
</table>
EOF;
$pdf->SetXY(110,10);
$pdf->writeHTMLCell(0,0,'','',$companyInfo,0,1,0,true,'R',true);
$pdf->SetXY(150,45);
$pdf->MultiCell(50,0,"Date: ".date('d M Y')."\nInvoice: $invoiceNumber",0,'R');

$clientInfo = "<div style='padding:0;margin-top:0;margin-bottom:2px;background-color:rgb(248,246,246);'><p><b>Client Name: </b>{$client_name}</p><p><b>Client Address: </b>{$client_address}</p></div>";
$pdf->SetY(40);
$pdf->Ln(20);
$pdf->writeHTML($clientInfo, true, false, true, false, '');

// Build items table
$html = '<style>tr{border-bottom:1px solid #ccc;} th{background-color:rgb(0,98,202);color:white;}</style>';
$html .= '<table cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;"><thead><tr>
    <th width="5%">SL</th><th width="20%">Travelers</th><th width="22%">Flight Info / Service</th>
    <th width="22%">Ticket Info</th><th width="15%">Type</th><th width="16%">Amount (BDT)</th>
</tr></thead><tbody>';

$serial=1;
foreach($line_items as $item){
    $row = $item['data'];
    $amount = $item['amount'];
    $type_label = $item['type_label'];
    $isExtra = $item['is_extra'];
    $display = number_format(abs($amount),2);
    $sign = $amount>=0?'+':'-';
    $color = $amount>=0?'':'color:#dc3545;';
    
    $html .= '<tr>';
    $html .= '<td width="5%">'.$serial++.'</td>';
    $html .= '<td width="20%">'.htmlspecialchars($row['PassengerName']).'</td>';
    
    if($isExtra){
        $service_desc = !empty($row['TicketRoute']) ? $row['TicketRoute'] : ($row['Remarks'] ?? 'Extra Service');
        $html .= '<td width="22%">Service: <b>'.htmlspecialchars($service_desc).'</b></td>';
    } else {
        $html .= '<td width="22%">Route: <b>'.htmlspecialchars($row['TicketRoute']).'</b><br>Airlines: <b>'.htmlspecialchars($row['airlines']).'</b><br>Departure: <b>'.htmlspecialchars($row['FlightDate']).'</b><br>Return: <b>'.htmlspecialchars($row['ReturnDate']).'</b></td>';
    }
    
    if($isExtra){
        $html .= '<td width="22%">---</td>';
    } else {
        $html .= '<td width="22%">Ticket No: <b>'.htmlspecialchars($row['TicketNumber']).'</b><br>PNR: <b>'.htmlspecialchars($row['PNR']).'</b><br>Issued: <b>'.htmlspecialchars($row['IssueDate']).'</b><br>Seat Class: <b>'.htmlspecialchars($row['Class']).'</b></td>';
    }
    
    $html .= '<td width="15%"><b>'.htmlspecialchars($type_label).'</b></td>';
    $html .= '<td width="16%" '.$color.'>'.$sign.' '.$display.'</td>';
    $html .= '</tr>';
}

// Totals
$html .= '<tr><td colspan="5" align="right"><strong>Subtotal (Net Amount)</strong></td><td><strong>'.number_format($subtotal,2).'</strong></td></tr>';
$html .= '<tr><td colspan="5" align="right">Advance Income Tax (AIT 0.3%)</td><td>'.number_format($ait,2).'</td></tr>';
$html .= '<tr><td colspan="5" align="right"><strong>Grand Total</strong></td><td><strong>'.number_format($gt,2).'</strong></td></tr>';
$html .= '</tbody></table>';

$pdf->Ln(10);
$pdf->writeHTML($html, true, false, true, false, '');

$amountWords = convertNumberToWordsIndian($gt).' Bangladeshi Taka Only';
$pdf->Ln(10);
$pdf->SetFont('helvetica','B',10);
$pdf->Write(0,"Amount in Words: $amountWords",'',0,'L',true);

$pdf->Ln(5);
$pdf->SetFont('helvetica','',9);
$notes = "<b>Notes:</b><br>1. Please make all payments for \"Faith Travels and Tours LTD.\"<br>2. For POS payment, an additional 2.5% charge will be added for Visa/MasterCard and 3.5% for AMEX.<br>3. For MFS Banking, an additional 1.75% charge will be added.";
$pdf->writeHTMLCell(0,0,'','',$notes,0,1,0,true,'L',true);

$pdf->Ln(8);
$pdf->SetFont('helvetica','B',11);
$pdf->Write(0,"Bank Account Details for Payment:",'',0,'L',true);
$pdf->Ln(4);
$pdf->SetFont('helvetica','',9);

$bankDetailsHTML = '
<style>.bank-table{width:100%;border-collapse:collapse;font-size:9pt;}.bank-table td{border:1px solid #ddd;padding:6px;vertical-align:top;}</style>
<table class="bank-table">
<tr><td width="50%"><strong>City Bank Limited</strong><br>A/C Title: FAITH TRAVELS & TOURS LTD.<br>A/C No.: 1254079547001<br>Branch: Gulshan Avenue<br>Routing No.: 225261732</td>
<td width="50%"><strong>BRAC Bank Limited</strong><br>A/C Title: FAITH TRAVELS & TOURS LTD.<br>A/C No.: 2068855480001<br>Branch: Banani<br>Routing No.: 060260435</td></tr>
<tr><td width="50%"><strong>Dutch Bangla Bank Limited</strong><br>A/C Title: FAITH TRAVELS AND TOURS LTD.<br>A/C No.: 1031100056392<br>Branch: Banani<br>Routing No.: 090260434</td>
<td width="50%"><strong>Islami Bank Bangladesh Limited</strong><br>A/C Title: FAITH TRAVELS AND TOURS LTD<br>A/C No.: 20503910100069217<br>Branch: Banani<br>Routing No.: 125260433</td></tr>
</table>';
$pdf->writeHTML($bankDetailsHTML, true, false, true, false, '');

$pdf->Ln(5);
$pdf->SetFont('helvetica','B',10);
$pdf->Write(0,"We Accept:",'',0,'L',true);
$logos = ['visa.png','master.png','amex.png','unionpay.png','diners.jpg','npsb.jpeg','discover.jpg','tkpay.jpeg'];
$x=25;
foreach($logos as $logo){
    $pdf->Image(__DIR__."/payment_icons/$logo", $x, $pdf->GetY()+2, 15);
    $x+=20;
}

$fileName = "{$pnr}_{$invoiceNumber}.pdf";
$filePath = __DIR__."/invoices/".$fileName;
ob_end_clean();
$pdf->Output($filePath, 'F');

// Email Sending
$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'faithtrip.net@gmail.com';
$mail->Password = 'hhbz fwis jioi fhpr';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('info@faithtrip.net', 'Faith Travels and Tours LTD');
$mail->addAddress($client_email);
if(!empty($cc_emails)){
    foreach(explode(',',$cc_emails) as $cc){
        $cc=trim($cc);
        if(filter_var($cc, FILTER_VALIDATE_EMAIL)) $mail->addCC($cc);
    }
}
if(!empty($bcc_emails)){
    foreach(explode(',',$bcc_emails) as $bcc){
        $bcc=trim($bcc);
        if(filter_var($bcc, FILTER_VALIDATE_EMAIL)) $mail->addBCC($bcc);
    }
}
$mail->Subject = 'Your Invoice - '.$invoiceNumber;
$mail->Body = "Dear Sir/Mam,\n\nGreetings From Faith Travels and Tours LTD. Thank You for being with us.\n\nIf you have any confusion please feel free to reach us. Please find your invoice attached.";
$mail->addAttachment($filePath);
if(!$mail->send()){
    echo 'Mailer Error: '.$mail->ErrorInfo;
    exit;
}

$_SESSION['invoice_sent'] = true;
$_SESSION['invoice_file'] = $fileName;
$_SESSION['invoice_email'] = $client_email;
$_SESSION['cc_emails'] = $cc_emails;
$_SESSION['bcc_emails'] = $bcc_emails;

header("Location: mail_success.php");
exit;
?>