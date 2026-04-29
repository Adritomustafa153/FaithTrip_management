<?php
// generate_visa_invoice.php – final version: supports single visa (GET) and multiple (POST from cart)
require_once __DIR__ . '/vendor/autoload.php';
require 'db.php';
require 'auth_check.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------
// Helper functions
// --------------------------------------------------------------
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

// --------------------------------------------------------------
// 1. Process cart submission (POST from visa_cart.php)
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_generated'])) {
    
    // Get client data from POST
    $client_name   = trim($_POST['ClientNameManual'] ?? '');
    if (empty($client_name) && isset($_POST['ClientNameDropdown'])) {
        $client_name = trim($_POST['ClientNameDropdown']);
    }
    $client_address = trim($_POST['address'] ?? '');
    $client_email   = trim($_POST['client_email'] ?? '');
    $cc_emails      = trim($_POST['cc_emails'] ?? '');
    $bcc_emails     = trim($_POST['bcc_emails'] ?? '');
    $addAIT         = isset($_POST['addAIT']) && $_POST['addAIT'] == '1';
    $client_type    = trim($_POST['clientType'] ?? '');
    
    // Get selected visa IDs
    if (!isset($_POST['visa_ids']) || !is_array($_POST['visa_ids']) || empty($_POST['visa_ids'])) {
        die("No visa records selected.");
    }
    $visa_ids = array_map('intval', $_POST['visa_ids']);
    
    // Fetch visa records
    $placeholders = implode(',', array_fill(0, count($visa_ids), '?'));
    $stmt = $conn->prepare("SELECT * FROM visa WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($visa_ids)), ...$visa_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    $visas = $result->fetch_all(MYSQLI_ASSOC);
    
    if (empty($visas)) die("Visa records not found.");
    
    $total_selling = array_sum(array_column($visas, 'selling price'));
    $ait = $addAIT ? $total_selling * 0.003 : 0;
    $grand_total = $total_selling + $ait;
    
    // Generate unique invoice number
    do {
        $invoiceNumber = 'VINV-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM visa_invoices WHERE invoice_number = ?");
        if (!$checkStmt) break;
        $checkStmt->bind_param("s", $invoiceNumber);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();
    } while ($count > 0);
    
    // --------------------------------------------------------------
    // PDF Generation
    // --------------------------------------------------------------
    $pdf = new TCPDF();
    $pdf->SetPrintHeader(false);
    $pdf->AddPage();
    
    // Logo
    if (file_exists('logo.jpg')) {
        $pdf->Image('logo.jpg', 10, 14, 30);
    }
    $pdf->SetY(10);
    $pdf->SetX(80);
    
    // Barcode
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
    
    // Items table (no borders, original design)
    $html = '<style>tr {border-bottom: 1px solid #ccc;} th {background-color:rgb(0, 98, 202); color: white;}</style>';
    $html .= '<table cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;">';
    $html .= '<thead><tr>';
    $html .= '<th width="5%">SL</th>';
    $html .= '<th width="40%">Client Info</th>';
    $html .= '<th width="40%">Visa Details</th>';
    $html .= '<th width="15%">Amount (BDT)</th>';
    $html .= '</tr></thead><tbody>';
    
    $serial = 1;
    foreach ($visas as $visa) {
        $description = "Visa Fee for " . htmlspecialchars($visa['name']) . " – " . htmlspecialchars($visa['country']);
        $details = "Type: {$visa['Type']}<br>Entry: {$visa['NoOfEntry']}<br>Duration: {$visa['Duration']}<br>Visa #: " . ($visa['visano'] ?: 'N/A');
        $html .= '<tr>';
        $html .= '<td align="center" width="5%">' . $serial++ . '</td>';
        $html .= '<td align="left" width="40%">' . $description . '</td>';
        $html .= '<td align="left" width="40%">' . $details . '</td>';
        $html .= '<td align="right" width="15%">' . number_format($visa['selling price'], 2) . '</td>';
        $html .= '</tr>';
    }
    
    if ($ait > 0) {
        $html .= '<tr><td colspan="3" align="right"><strong>AIT (0.3%)</strong></td>';
        $html .= '<td align="right">' . number_format($ait, 2) . '</td></tr>';
    }
    $html .= '<tr><td colspan="3" align="right"><strong>Grand Total</strong></td>';
    $html .= '<td align="right"><strong>' . number_format($grand_total, 2) . '</strong></td></tr>';
    $html .= '</tbody></table>';
    
    $pdf->Ln(10);
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Amount in words
    $amountWords = convertNumberToWordsIndian($grand_total) . ' Bangladeshi Taka Only';
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Write(0, "Amount in Words: $amountWords", '', 0, 'L', true);
    
    // Notes
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 9);
    $notes = <<<EOD
<b>Notes:</b><br>
1. Visa processing fees are non-refundable once the application is submitted.<br>
2. Please make all payments to "Faith Travels and Tours LTD."<br>
3. For POS payment, an additional 2.5% charge will be added for Visa/MasterCard and 3.5% for AMEX.
EOD;
    $pdf->writeHTMLCell(0, 0, '', '', $notes, 0, 1, 0, true, 'L', true);
    
    // Bank Details
    $pdf->Ln(8);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Write(0, "Bank Account Details for Payment:", '', 0, 'L', true);
    $pdf->Ln(4);
    $pdf->SetFont('helvetica', '', 9);
    $bankDetailsHTML = '
    <style>.bank-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    .bank-table td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }</style>
    <table class="bank-table">
        <tr><td width="50%"><strong>City Bank Limited</strong><br>A/C Title: FAITH TRAVELS & TOURS LTD.<br>A/C No.: 1254079547001<br>Branch: Gulshan Avenue<br>Routing No.: 225261732</td>
        <td width="50%"><strong>BRAC Bank Limited</strong><br>A/C Title: FAITH TRAVELS & TOURS LTD.<br>A/C No.: 2068855480001<br>Branch: Banani<br>Routing No.: 060260435</td>
    </tr>
    <tr><td width="50%"><strong>Dutch Bangla Bank Limited</strong><br>A/C Title: FAITH TRAVELS AND TOURS LTD.<br>A/C No.: 1031100056392<br>Branch: Banani<br>Routing No.: 090260434</td>
        <td width="50%"><strong>Islami Bank Bangladesh Limited</strong><br>A/C Title: FAITH TRAVELS AND TOURS LTD<br>A/C No.: 20503910100069217<br>Branch: Banani<br>Routing No.: 125260433</td>
    </tr>
    </table>';
    $pdf->writeHTML($bankDetailsHTML, true, false, true, false, '');
    
    // Payment logos
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Write(0, "We Accept:", '', 0, 'L', true);
    $logos = ['visa.png', 'master.png', 'amex.png', 'unionpay.png', 'diners.jpg', 'npsb.jpeg', 'discover.jpg', 'tkpay.jpeg'];
    $x = 25;
    foreach ($logos as $logo) {
        $logoPath = __DIR__ . "/payment_icons/$logo";
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, $x, $pdf->GetY() + 2, 15);
            $x += 20;
        }
    }
    
    // Save PDF – filename uses visa IDs (underscore separated)
    $ids_part = implode('_', $visa_ids);
    $fileName = "VISA_{$ids_part}_{$invoiceNumber}.pdf";
    if (!is_dir('invoices')) mkdir('invoices', 0777, true);
    $filePath = __DIR__ . "/invoices/" . $fileName;
    $pdf->Output($filePath, 'F');
    
    // Insert into visa_invoices
    $created_by = $_SESSION['user_id'] ?? null;
    $visa_ids_str = implode(',', $visa_ids);
    $conn->query("ALTER TABLE visa_invoices ADD COLUMN IF NOT EXISTS created_by_user_id INT(11) NULL");
    $conn->query("ALTER TABLE visa_invoices ADD COLUMN IF NOT EXISTS visa_ids TEXT AFTER id");
    $insertStmt = $conn->prepare("INSERT INTO visa_invoices (invoice_number, visa_ids, client_name, client_email, amount, ait, grand_total, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("ssssdddi", $invoiceNumber, $visa_ids_str, $client_name, $client_email, $total_selling, $ait, $grand_total, $created_by);
    $insertStmt->execute();
    $insertStmt->close();
    
    // Send email
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'faithtrip.net@gmail.com';
        $mail->Password = 'hhbz fwis jioi fhpr';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('info@faithtrip.net', 'Faith Travels and Tours LTD');
        $mail->addAddress($client_email);
        if (!empty($cc_emails)) {
            foreach (explode(',', $cc_emails) as $cc) {
                $cc = trim($cc);
                if (filter_var($cc, FILTER_VALIDATE_EMAIL)) $mail->addCC($cc);
            }
        }
        if (!empty($bcc_emails)) {
            foreach (explode(',', $bcc_emails) as $bcc) {
                $bcc = trim($bcc);
                if (filter_var($bcc, FILTER_VALIDATE_EMAIL)) $mail->addBCC($bcc);
            }
        }
        $mail->Subject = "Visa Invoice - $invoiceNumber";
        $mail->Body = "Dear $client_name,\n\nGreetings from Faith Travels and Tours LTD.\n\nPlease find attached your visa processing invoice for " . count($visas) . " visa application(s).\n\nThank you for choosing us.";
        $mail->addAttachment($filePath);
        $mail->send();
        
        // Clear cart
        unset($_SESSION['visa_cart']);
        
        $_SESSION['invoice_sent'] = true;
        $_SESSION['invoice_file'] = $fileName;
        $_SESSION['invoice_email'] = $client_email;
        header("Location: mail_success.php");
        exit;
    } catch (Exception $e) {
        $error = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        echo $error;
        exit;
    }
}

// --------------------------------------------------------------
// 2. Single visa invoice via GET (show client form)
// --------------------------------------------------------------
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id <= 0) die("Invalid visa record.");
    
    $stmt = $conn->prepare("SELECT * FROM visa WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) die("Visa record not found.");
    $visa = $result->fetch_assoc();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Generate Visa Invoice</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            #loadingOverlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.9); display:none; z-index:9999; justify-content:center; align-items:center; flex-direction:column; }
            .cc-bcc-fields { display:none; margin-top:10px; }
        </style>
    </head>
    <body>
        <?php include 'nav.php'; ?>
        <div id="loadingOverlay"><div class="text-center"><img src="gif/inv_loading.gif" alt="Loading..." style="width:100px;"><div>Generating Invoice, Please wait...</div></div></div>
        <div class="container mt-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white"><h4><i class="fas fa-passport me-2"></i> Generate Visa Invoice (Single)</h4></div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Visa Applicant:</strong> <?php echo htmlspecialchars($visa['name']); ?><br>
                        <strong>Country:</strong> <?php echo htmlspecialchars($visa['country']); ?> | 
                        <strong>Selling Price:</strong> ৳ <?php echo number_format($visa['selling price'], 2); ?>
                    </div>
                    <?php if (isset($error)) echo '<div class="alert alert-danger">'.$error.'</div>'; ?>
                    <form method="POST" onsubmit="showLoading()">
                        <input type="hidden" name="single_visa_id" value="<?php echo $id; ?>">
                        <!-- Copy client fields from visa_cart.php (dropdowns, etc.) – same as in visa_cart.php -->
                        <div class="row">
                            <div class="col-md-4 mb-3"><label>Type</label><select id="clientType" class="form-select" name="clientType" required><option value="">Select</option><option value="company">Company</option><option value="agent">Agent</option><option value="passenger">Counter Sell</option></select></div>
                            <div class="col-md-4 mb-3"><label>Name <input type="checkbox" id="manualName" onchange="toggleManualName()"> Add Manually</label><select id="clientName" class="form-select" name="ClientNameDropdown" required></select><input type="text" id="manualClientName" name="ClientNameManual" class="form-control mt-2" placeholder="Enter client name" style="display:none;"></div>
                            <div class="col-md-4 mb-3"><label>Address <input type="checkbox" id="manualAddress" onchange="toggleManualAddress()"> Add Manually</label><input type="text" id="address" name="address" class="form-control" required></div>
                            <div class="col-md-4 mb-3"><label>Email:</label><input type="text" id="email" name="client_email" class="form-control" required></div>
                            <div class="col-md-4 mb-3"><div class="form-check"><input type="checkbox" class="form-check-input" id="addAIT" name="addAIT" value="1"><label class="form-check-label">Add AIT (0.3%) – ৳ <?php echo number_format($visa['selling price'] * 0.003, 2); ?></label></div></div>
                            <div class="col-md-12 mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="showCCBCC" onchange="toggleCCBCCFields()"><label class="form-check-label">Add CC/BCC Recipients</label></div><div id="ccBCCFields" class="cc-bcc-fields"><div class="row"><div class="col-md-6"><label>CC (comma separated):</label><input type="text" name="cc_emails" class="form-control"></div><div class="col-md-6"><label>BCC (comma separated):</label><input type="text" name="bcc_emails" class="form-control"></div></div></div></div>
                        </div>
                        <div class="text-center"><button type="submit" class="btn btn-success">Generate & Send Invoice</button></div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            function toggleManualAddress() { document.getElementById('address').readOnly = !document.getElementById('manualAddress').checked; }
            function toggleManualName() { const isManual = document.getElementById('manualName').checked; document.getElementById('clientName').style.display = isManual ? 'none' : 'block'; document.getElementById('manualClientName').style.display = isManual ? 'block' : 'none'; document.getElementById('clientName').required = !isManual; document.getElementById('manualClientName').required = isManual; }
            function toggleCCBCCFields() { document.getElementById('ccBCCFields').style.display = document.getElementById('showCCBCC').checked ? 'block' : 'none'; }
            document.getElementById('clientType').addEventListener('change', function () { let type = this.value; fetch(`fetch_names.php?type=${type}`).then(res => res.json()).then(data => { let options = '<option value="">Select</option>'; data.forEach(item => { options += `<option value="${item.name}" data-address="${item.address}" data-email="${item.email}">${item.name}</option>`; }); document.getElementById('clientName').innerHTML = options; }); });
            document.getElementById('clientName').addEventListener('change', function () { if (!document.getElementById('manualName').checked) { let selected = this.options[this.selectedIndex]; let address = selected.getAttribute('data-address'); let email = selected.getAttribute('data-email'); if (!document.getElementById('manualAddress').checked) { document.getElementById('address').value = address; } document.getElementById('email').value = email || ''; } });
            function showLoading() { document.getElementById('loadingOverlay').style.display = 'flex'; }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// If neither GET nor POST, redirect
header("Location: visa_list.php");
exit;
?>