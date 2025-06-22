<?php
include 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid payment ID.");
}

$stmt = $conn->prepare("SELECT * FROM paid WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

if (!$payment) {
    die("Payment record not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $source_name = $_POST['source_name'];
    $payment_date = $_POST['payment_date'];
    $invoice_no = $_POST['invoice_no'];
    $transaction_id = $_POST['transaction_id'];
    $payment_method = $_POST['payment_method'];
    $amount = $_POST['amount'];
    $remarks = $_POST['remarks'];
    $receipt_name = $payment['receipt'];

    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $target_dir = "uploads/receipts/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $receipt_name = uniqid('receipt_') . "." . $ext;
        $target_file = $target_dir . $receipt_name;

        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
            die("Failed to upload new receipt image.");
        }
    }

    $stmt = $conn->prepare("UPDATE paid SET source = ?, payment_date = ?, invoice_no = ?, transaction_id = ?, payment_method = ?, receipt = ?, amount = ?, remarks = ? WHERE id = ?");
    $stmt->bind_param("ssssssdsi", $source_name, $payment_date, $invoice_no, $transaction_id, $payment_method, $receipt_name, $amount, $remarks, $id);
    $stmt->execute();
    $stmt->close();

    echo "<div class='success'>✅ Payment updated successfully.</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Payment</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: url('https://media.tenor.com/7vN2r1Oa1UQAAAAd/money-payment.gif') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: rgba(255, 239, 182,0.6);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(239, 239, 239, 0.5);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #f1f1f1;
        }

        form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color:rgb(0, 0, 0);
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        select,
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: rgba(128, 207, 247, 0.05);
            color: #000;
            font-size: 14px;
        }

        input::placeholder,
        textarea::placeholder {
            color: #000;
        }

        textarea {
            resize: vertical;
            min-height: 60px;
            grid-column: span 3;
        }

        .submit-container {
            grid-column: span 4;
            text-align: center;
        }

        input[type="submit"] {
            background-color: #ffc107;
            border: none;
            color: #000;
            padding: 14px 30px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #e0a800;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-left: 5px solid #28a745;
            margin: 30px auto;
            max-width: 900px;
            border-radius: 6px;
        }

        select option {
            color: #000;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
    <h2>Edit Payment</h2>
    <form action="edit_paid.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
        <div>
            <label>Source:</label>
            <select name="source_name" required>
                <option value="">Select Source</option>
                <?php
                $res = mysqli_query($conn, "SELECT agency_name FROM Sources");
                while ($row = mysqli_fetch_assoc($res)) {
                    $selected = $payment['source'] == $row['agency_name'] ? "selected" : "";
                    echo "<option value='{$row['agency_name']}' $selected>{$row['agency_name']}</option>";
                }
                ?>
            </select>
        </div>

        <div>
            <label>Payment Date:</label>
            <input type="date" name="payment_date" value="<?= $payment['payment_date'] ?>" required>
        </div>

        <div>
            <label>Invoice No:</label>
            <input type="text" name="invoice_no" value="<?= $payment['invoice_no'] ?>">
        </div>

        <div>
            <label>Transaction ID:</label>
            <input type="text" name="transaction_id" value="<?= $payment['transaction_id'] ?>">
        </div>

        <div>
            <label>Payment Method:</label>
            <select name="payment_method" required>
                <?php
                $methods = ['Cash', 'Bank Transfer', 'Cheque', 'Clearing Cheque', 'MFS'];
                foreach ($methods as $method) {
                    $selected = $payment['payment_method'] == $method ? "selected" : "";
                    echo "<option value='$method' $selected>$method</option>";
                }
                ?>
            </select>
        </div>

        <div>
            <label>Amount:</label>
            <input type="number" name="amount" step="0.01" value="<?= $payment['amount'] ?>" required>
        </div>

        <div>
            <label>Upload New Receipt (Optional):</label>
            <input type="file" name="receipt" accept="image/*">
        </div>

        <div>
            <label>Remarks:</label>
            <textarea name="remarks" placeholder="Optional remarks..."><?= $payment['remarks'] ?></textarea>
        </div>

        <div class="submit-container">
            <input type="submit" value="Update Payment">
        </div>
    </form>
</div>
</body>
</html>
