<?php
include 'auth_check.php';
include 'db.php';

// Fetch banks from database
$banks = [];
$banks_sql = "SELECT * FROM banks ORDER BY Bank_Name";
$banks_result = $conn->query($banks_sql);
if ($banks_result) {
    while ($row = $banks_result->fetch_assoc()) {
        $banks[] = $row;
    }
}

// Check if form is submitted for a new payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_amount'])) {
    $id = $_POST['id'];
    $payment_amount = floatval(str_replace(',', '', $_POST['payment_amount']));
    $payment_date = $_POST['payment_date'];
    $payment_method = $_POST['payment_method'];
    $bank_id = $_POST['bank_id'];
    $notes = $_POST['notes'];
    
    if ($payment_amount > 0) {
        // Use the stored procedure to process payment
        $stmt = $conn->prepare("CALL ProcessPayment(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsss", $id, $payment_date, $payment_amount, $payment_method, $bank_id, $notes);
        
        if ($stmt->execute()) {
            $update_success = true;
            // Refresh the page to show updated data
            header("Location: edit_receivable.php?id=" . $id . "&success=1");
            exit();
        } else {
            $error = "Error processing payment: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Payment amount must be greater than zero";
    }
}

// Fetch the record to edit
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get sale details
    $sql = "SELECT * FROM sales WHERE SaleID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        die("Record not found");
    }
    
    // Get payment history for this sale
    $payment_history_sql = "SELECT * FROM payments WHERE SaleID = ? ORDER BY PaymentDate DESC, CreatedAt DESC";
    $payment_stmt = $conn->prepare($payment_history_sql);
    $payment_stmt->bind_param("i", $id);
    $payment_stmt->execute();
    $payment_history = $payment_stmt->get_result();
    
} else {
    die("Invalid request");
}

// Check for success message
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Payment processed successfully!";
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
        }
        .edit-container {
            max-width: 1000px;
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
        .payment-history {
            max-height: 300px;
            overflow-y: auto;
        }
        .table-responsive {
            font-size: 14px;
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
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <!-- Sale Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Sale Information</h5>
                </div>
                <div class="card-body">
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
                        <div class="col-md-4">
                            <label class="form-label">Payment Status</label>
                            <input type="text" class="form-control read-only-field" value="<?php echo htmlspecialchars($row['PaymentStatus']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Paid Amount</label>
                            <input type="text" class="form-control read-only-field" value="<?php echo number_format($row['PaidAmount'], 2); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due Amount</label>
                            <input type="text" class="form-control read-only-field" value="<?php echo number_format($row['DueAmount'], 2); ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Payment Form -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Add New Payment</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $row['SaleID']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="payment_amount" class="form-label">Payment Amount *</label>
                                <input type="number" step="0.01" class="form-control" id="payment_amount" 
                                       name="payment_amount" value="0" required min="0.01">
                            </div>
                            <div class="col-md-3">
                                <label for="payment_date" class="form-label">Payment Date *</label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Cheque</option>
                                    <option value="Bank Deposit">Bank Deposit</option>
                                    <option value="Cheque Clearing">Cheque Clearing</option>
                                    <option value="Mobile Banking">Mobile Banking</option>
                                    <option value="Credit Card">Credit Card</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="bank" class="form-label">Bank Name</label>
                                <select name="bank_id" id="bank" class="form-control">
                                    <option value="">-- Select Bank --</option>
                                    <?php foreach ($banks as $bank): ?>
                                        <option value="<?= $bank['Bank_Name']; ?>">
                                            <?= $bank['Bank_Name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-success">Add Payment</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive payment-history">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Bank</th>
                                    <th>Type</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payment_history->num_rows > 0): ?>
                                    <?php while ($payment = $payment_history->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['PaymentDate']); ?></td>
                                            <td><?php echo number_format($payment['Amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($payment['PaymentMethod']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['BankName']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['PaymentType']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['Notes']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No payment history found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="receiveable.php" class="btn btn-secondary me-md-2">Back to Receivables</a>
            </div>
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
        
        // Show/hide bank dropdown based on payment method
        document.getElementById('payment_method').addEventListener('change', function() {
            const bankSelect = document.getElementById('bank');
            if (this.value === 'Bank Transfer' || this.value === 'Check' || this.value === 'Bank Deposit' || this.value === 'Cheque Clearing') {
                bankSelect.disabled = false;
            } else {
                bankSelect.disabled = true;
                bankSelect.value = '';
            }
        });
        
        // Initialize bank dropdown state
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethod = document.getElementById('payment_method').value;
            const bankSelect = document.getElementById('bank');
            if (paymentMethod !== 'Bank Transfer' && paymentMethod !== 'Check' && paymentMethod !== 'Bank Deposit' && paymentMethod !== 'Cheque Clearing') {
                bankSelect.disabled = true;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>