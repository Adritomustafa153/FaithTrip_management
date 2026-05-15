<?php
// ============================================================
// NO OUTPUT BEFORE THIS LINE – prevents "headers already sent"
// ============================================================
ob_start();
include 'auth_check.php';
include 'db.php';

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $source_name      = trim($_POST['source_name'] ?? '');
    $payment_date     = trim($_POST['payment_date'] ?? '');
    $invoice_no       = trim($_POST['invoice_no'] ?? '');
    $transaction_id   = trim($_POST['transaction_id'] ?? '');
    $payment_method   = trim($_POST['payment_method'] ?? '');
    $amount           = floatval($_POST['amount'] ?? 0);
    $remarks          = trim($_POST['remarks'] ?? '');
    $receipt_name     = "";
    $errors = [];

    if (empty($source_name))     $errors[] = "Source is required.";
    if (empty($payment_date))    $errors[] = "Payment date is required.";
    if (empty($payment_method))  $errors[] = "Payment method is required.";
    if ($amount <= 0)            $errors[] = "Valid positive amount is required.";

    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $target_dir = "uploads/receipts/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['receipt']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = "Invalid file type. Use JPEG, PNG, or WEBP.";
        } elseif ($_FILES['receipt']['size'] > 5 * 1024 * 1024) {
            $errors[] = "File size exceeds 5MB.";
        } else {
            $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $receipt_name = uniqid('receipt_') . "." . $ext;
            $target_file = $target_dir . $receipt_name;
            if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
                $errors[] = "Failed to upload receipt image.";
                $receipt_name = "";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO paid (source, payment_date, invoice_no, transaction_id, payment_method, receipt, amount, remarks)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssds", $source_name, $payment_date, $invoice_no, $transaction_id, $payment_method, $receipt_name, $amount, $remarks);
        if ($stmt->execute()) {
            $success_message = "✅ Payment recorded successfully.";
        } else {
            $error_message = "Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = implode(' · ', array_map('htmlspecialchars', $errors));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Payment | Invoice Manager</title>
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f0f9ff 0%, #e6f0fa 100%);
            min-height: 100vh;
            /* padding: 2rem 1.5rem; */
            color: #1e293b;
        }

        /* main container - clean white card with soft shadow */
        .payment-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 2rem;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            transition: transform 0.2s ease;
            margin-top: 25px;
        }

        /* header */
        .form-header {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #5f6c68;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .form-header h2 i {
            color: #46544f;
            font-size: 1.8rem;
        }
        .form-header p {
            color: #5b6e8c;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        /* form body */
        .payment-form {
            padding: 2rem 2rem 2.2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem 2rem;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .input-group label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #6d6e6e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-group label i {
            font-size: 0.85rem;
            color: #3b8b6e;
        }

        input, select, textarea {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 1rem;
            padding: 0.8rem 1rem;
            font-family: 'Inter', monospace;
            font-size: 0.9rem;
            color: #0f172a;
            transition: all 0.2s ease;
            outline: none;
            width: 100%;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #2c7a5e;
            box-shadow: 0 0 0 3px rgba(44, 122, 94, 0.15);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%232c7a5e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
            background-repeat: no-repeat;
            background-position: right 1rem center;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .file-wrapper input[type="file"] {
            padding: 0.7rem 0.8rem;
            background: #f9fafb;
        }

        .file-wrapper input[type="file"]::file-selector-button {
            background: #eef2ff;
            border: 1px solid #cbd5e1;
            border-radius: 2rem;
            padding: 0.4rem 1rem;
            margin-right: 1rem;
            color: #5153c4;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }

        .file-wrapper input[type="file"]::file-selector-button:hover {
            background: #d9e6ff;
        }

        .hint-text {
            font-size: 0.7rem;
            color: #6c86a3;
            margin-top: 4px;
        }

        .submit-area {
            margin-top: 2rem;
            text-align: center;
        }

        .submit-btn {
            background: linear-gradient(105deg, #2c7a5e, #1f5e48);
            border: none;
            padding: 0.9rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 3rem;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: 0.25s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(44, 122, 94, 0.3);
        }

        .submit-btn:hover {
            background: linear-gradient(105deg, #3b8b6e, #286e54);
            transform: translateY(-2px);
            box-shadow: 0 10px 18px -8px rgba(44, 122, 94, 0.4);
        }

        .message {
            border-radius: 1rem;
            padding: 1rem 1.8rem;
            margin: 0 2rem 1.5rem 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .message.success {
            background: #e6f7ec;
            border-left: 5px solid #2c7a5e;
            color: #14532d;
        }

        .message.error {
            background: #fee9e6;
            border-left: 5px solid #dc2626;
            color: #7f1d1d;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 680px) {
            body { padding: 1rem; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .payment-form { padding: 1.5rem; }
            .form-header h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="payment-container">
    <div class="form-header">
        <h2><i class="fas fa-coins"></i> Record Payment</h2>
        <p>Create a detailed entry · track transactions with ease</p>
    </div>

    <?php if ($success_message): ?>
        <div class="message success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php elseif ($error_message): ?>
        <div class="message error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form class="payment-form" action="" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="input-group">
                <label><i class="fas fa-building"></i> Source *</label>
                <select name="source_name" required>
                    <option value="" disabled selected>— Select source —</option>
                    <?php
                    $res = mysqli_query($conn, "SELECT id, agency_name FROM Sources ORDER BY agency_name");
                    if ($res && mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            echo "<option value='" . htmlspecialchars($row['agency_name']) . "'>" . htmlspecialchars($row['agency_name']) . "</option>";
                        }
                    } else {
                        echo "<option disabled>No sources available</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="input-group">
                <label><i class="fas fa-calendar-alt"></i> Payment Date *</label>
                <input type="date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="input-group">
                <label><i class="fas fa-file-invoice"></i> Invoice No.</label>
                <input type="text" name="invoice_no" placeholder="e.g., INV-2409-01">
            </div>

            <div class="input-group">
                <label><i class="fas fa-hashtag"></i> Transaction ID</label>
                <input type="text" name="transaction_id" placeholder="Bank Ref / Trx ID">
            </div>

            <div class="input-group">
                <label><i class="fas fa-credit-card"></i> Payment Method *</label>
                <select name="payment_method" required>
                    <option value="" disabled selected>— Select method —</option>
                    <option value="Cash">💵 Cash</option>
                    <option value="Bank Transfer">🏦 Bank Transfer</option>
                    <option value="Cheque">📄 Cheque</option>
                    <option value="Clearing Cheque">📑 Clearing Cheque</option>
                    <option value="MFS">📱 MFS (bKash/Nagad)</option>
                </select>
            </div>

            <div class="input-group">
                <label><i class="fas fa-dollar-sign"></i> Amount (BDT/USD) *</label>
                <input type="number" name="amount" step="0.01" placeholder="0.00" required>
            </div>

            <div class="input-group full-width">
                <label><i class="fas fa-receipt"></i> Payment Receipt (Image)</label>
                <div class="file-wrapper">
                    <input type="file" name="receipt" accept="image/jpeg, image/png, image/webp">
                </div>
                <div class="hint-text"><i class="fas fa-info-circle"></i> JPEG, PNG, WEBP up to 5MB (optional).</div>
            </div>

            <div class="input-group full-width">
                <label><i class="fas fa-pen-alt"></i> Remarks / Notes</label>
                <textarea name="remarks" placeholder="Additional details (purpose, reference, etc.)"></textarea>
            </div>
        </div>

        <div class="submit-area">
            <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Submit Payment</button>
        </div>
    </form>
</div>

<script>
    // Optional: show selected file name
    (function() {
        const fileInput = document.querySelector('input[name="receipt"]');
        if(fileInput) {
            fileInput.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name;
                if(fileName) {
                    let hint = this.closest('.input-group')?.querySelector('.file-hint');
                    if(!hint) {
                        hint = document.createElement('div');
                        hint.className = 'hint-text file-hint';
                        hint.style.marginTop = '6px';
                        this.closest('.input-group')?.appendChild(hint);
                    }
                    hint.innerHTML = `<i class="fas fa-paperclip"></i> selected: ${fileName.substring(0, 45)}${fileName.length > 45 ? '…' : ''}`;
                }
            });
        }
    })();
</script>
</body>
</html>