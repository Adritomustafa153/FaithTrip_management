<?php
include 'db.php';
$banks = [];
$bank_query = mysqli_query($conn, "SELECT id, Bank_Name FROM banks");
while ($row = mysqli_fetch_assoc($bank_query)) {
    $banks[] = $row;
}
// Fetch banks from database
$banks_sql = "SELECT * FROM banks ORDER BY bank_name";
$banks_result = $conn->query($banks_sql);

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $payment_status = $_POST['payment_status'];
    $paid_amount = $_POST['paid_amount'];
    $payment_date = $_POST['payment_date'];
    $receive_date = $_POST['receive_date'];
    $payment_method = $_POST['payment_method'];
    $bank_id = $_POST['bank_id'];
    
    // Calculate due amount
$bill_amount = str_replace(',', '', $_POST['bill_amount']); // Remove commas
$bill_amount = floatval($bill_amount); // Convert to float
$due_amount = $bill_amount - $paid_amount;
    
    // Update payment status if fully paid
    if ($due_amount <= 0) {
        $payment_status = 'Paid';
    } elseif ($paid_amount > 0) {
        $payment_status = 'Partially Paid';
    }
    
    // Update record
    $sql = "UPDATE sales SET 
            PaymentStatus = ?,
            PaidAmount = ?,
            DueAmount = ?,
            DepositDate = ?,
            ReceivedDate = ?,
            PaymentMethod = ?,
            BankName = ?
            WHERE SaleID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sddssssi", $payment_status, $paid_amount, $due_amount, $payment_date, $receive_date, $payment_method, $bank_id, $id);
    
if ($stmt->execute()) {
    $update_success = true;
}
 else {
        $error = "Error updating record: " . $conn->error;
    }
}

// Fetch the record to edit
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT s.*, b.Bank_Name 
            FROM sales s
            LEFT JOIN banks b ON s.SaleID  = b.id
            WHERE s.SaleID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        die("Record not found");
    }
} else {
    die("Invalid request");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Receivable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            /* padding: 20px; */
        }
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 500;
        }
        .read-only-field {
            background-color: #e9ecef;
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container">
        <div class="edit-container">
            <h2 class="text-center mb-4">Edit Receivable Entry</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
<?php if (isset($update_success) && $update_success): ?>
    <div class="text-center mt-5" id="success-container" style="display: none;">
        <img src="payment_updated.gif" alt="Payment Updated Successfully" style="width: 200px; height: auto;">
        <h4 class="mt-3 text-success">Payment Updated Successfully!</h4>
        <p class="text-muted">You will be redirected to receivables in 5 seconds...</p>
        <a href="receiveable.php" class="btn btn-sm btn-outline-primary">Go Now</a>
    </div>

    <script>
        // Show success message with fade-in effect
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('success-container');
            container.style.opacity = 0;
            container.style.display = 'block';
            let opacity = 0;
            const fade = setInterval(() => {
                if (opacity >= 1) {
                    clearInterval(fade);
                } else {
                    opacity += 0.05;
                    container.style.opacity = opacity;
                }
            }, 50);

            // Auto redirect after 5 seconds
            setTimeout(() => {
                window.location.href = "receiveable.php";
            }, 5000);
        });
    </script>
    <?php exit; ?>
<?php endif; ?>


            
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $row['SaleID']; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Section</label>
                        <input type="text" class="form-control read-only-field" value="<?php echo htmlspecialchars($row['section']); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Party Name</label>
                        <input type="text" class="form-control read-only-field" value="<?php echo htmlspecialchars($row['PartyName']); ?>" readonly>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Passenger Name</label>
                        <input type="text" class="form-control read-only-field" value="<?php echo htmlspecialchars($row['PassengerName']); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ticket Number</label>
                        <input type="text" class="form-control read-only-field" value="<?php echo htmlspecialchars($row['TicketNumber']); ?>" readonly>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">PNR</label>
                        <input type="text" class="form-control read-only-field" value="<?php echo htmlspecialchars($row['PNR']); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bill Amount</label>
                        <input type="text" class="form-control read-only-field" name="bill_amount" value="<?php echo number_format($row['BillAmount'], 2); ?>" readonly>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select class="form-select" id="payment_status" name="payment_status" required>
                            <option value="Due" <?php echo $row['PaymentStatus'] == 'Due' ? 'selected' : ''; ?>>Due</option>
                            <option value="Partially Paid" <?php echo $row['PaymentStatus'] == 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                            <option value="Paid" <?php echo $row['PaymentStatus'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="paid_amount" class="form-label">Paid Amount</label>
                        <input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount" value="<?php echo number_format($row['PaidAmount'], 2); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">Select Method</option>
                            <option value="Cash" <?php echo $row['PaymentMethod'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="Bank Transfer" <?php echo $row['PaymentMethod'] == 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Check" <?php echo $row['PaymentMethod'] == 'Check' ? 'selected' : ''; ?>>Cheque</option>
                            <option value="Credit Card" <?php echo $row['PaymentMethod'] == 'Credit Card' ? 'selected' : ''; ?>>Bank Deposit</option>
                            <option value="Mobile Payment" <?php echo $row['PaymentMethod'] == 'Mobile Payment' ? 'selected' : ''; ?>>Cheque Clearing</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="bank">Bank Name:</label>
                        <select name="bank_id" id="bank" class="form-control">
                            <option value="">-- Select Bank --</option>
                            <?php foreach ($banks as $bank): ?>
                                <option value="<?= $bank['Bank_Name']; ?>" >
                                    <?= $bank['Bank_Name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo !empty($row['PaymentDate']) ? htmlspecialchars($row['PaymentDate']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="receive_date" class="form-label">Receive Date</label>
                        <input type="date" class="form-control" id="receive_date" name="receive_date" value="<?php echo !empty($row['ReceiveDate']) ? htmlspecialchars($row['ReceiveDate']) : ''; ?>">
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="receiveable.php" class="btn btn-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize datepickers
        flatpickr("#payment_date", {
            dateFormat: "Y-m-d",
            allowInput: true
        });
        
        flatpickr("#receive_date", {
            dateFormat: "Y-m-d",
            allowInput: true
        });
        
        // Auto-update payment status when paid amount changes
        document.getElementById('paid_amount').addEventListener('change', function() {
            const billAmount = parseFloat(document.querySelector('[name="bill_amount"]').value.replace(/,/g, ''));
            const paidAmount = parseFloat(this.value) || 0;
            const dueAmount = billAmount - paidAmount;
            const statusSelect = document.getElementById('payment_status');
            
            if (dueAmount <= 0) {
                statusSelect.value = 'Paid';
            } else if (paidAmount > 0) {
                statusSelect.value = 'Partially Paid';
            } else {
                statusSelect.value = 'Due';
            }
        });
        
        // Show/hide bank dropdown based on payment method
        document.getElementById('payment_method').addEventListener('change', function() {
            const bankSelect = document.getElementById('bank_id');
            if (this.value === 'Bank Transfer' || this.value === 'Check') {
                bankSelect.disabled = false;
            } else {
                bankSelect.disabled = true;
                bankSelect.value = '';
            }
        });
        
        // Initialize bank dropdown state
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethod = document.getElementById('payment_method').value;
            const bankSelect = document.getElementById('bank_id');
            if (paymentMethod !== 'Bank Transfer' && paymentMethod !== 'Check') {
                bankSelect.disabled = true;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>