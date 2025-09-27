<?php
include 'auth_check.php';
include 'db.php';

$sale_id = $_GET['id'] ?? 0;

// Fetch sale details
$sale_sql = "SELECT s.*, COALESCE(SUM(p.Amount), 0) as PaidAmount 
             FROM sales s 
             LEFT JOIN payments p ON s.SaleID = p.SaleID 
             WHERE s.SaleID = ? 
             GROUP BY s.SaleID";
$stmt = $conn->prepare($sale_sql);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();

if (!$sale) {
    die("Sale record not found");
}

$due_amount = $sale['BillAmount'] - $sale['PaidAmount'];

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_date = $_POST['payment_date'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $bank_name = $_POST['bank_name'];
    $notes = $_POST['notes'];
    
    // Determine payment type
    $total_paid_after = $sale['PaidAmount'] + $amount;
    $payment_type = ($total_paid_after >= $sale['BillAmount']) ? 'Full' : 'Partial';
    
    // Insert into payments table
    $insert_payment_sql = "INSERT INTO payments (SaleID, PaymentDate, Amount, PaymentMethod, BankName, Notes, PaymentType) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_payment_sql);
    $stmt->bind_param("isdssss", $sale_id, $payment_date, $amount, $payment_method, $bank_name, $notes, $payment_type);
    
    if ($stmt->execute()) {
        // Update sales table payment status
        $new_payment_status = ($total_paid_after >= $sale['BillAmount']) ? 'Paid' : 
                             ($total_paid_after > 0 ? 'Partially Paid' : 'Due');
        
        $update_sales_sql = "UPDATE sales SET 
                            PaymentStatus = ?,
                            PaidAmount = ?,
                            DueAmount = ?,
                            PaymentMethod = ?,
                            BankName = ?,
                            ReceivedDate = ?
                            WHERE SaleID = ?";
        
        $stmt = $conn->prepare($update_sales_sql);
        $stmt->bind_param("sdssssi", $new_payment_status, $total_paid_after, 
                         ($sale['BillAmount'] - $total_paid_after), $payment_method, 
                         $bank_name, $payment_date, $sale_id);
        $stmt->execute();
        
        header("Location: receiveable.php?payment=success");
        exit();
    } else {
        $error = "Error recording payment: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 800px; margin-top: 30px; }
        .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border: none; border-radius: 10px; }
        .card-header { background: linear-gradient(135deg, #3498db, #2c3e50); color: white; border-radius: 10px 10px 0 0 !important; }
        .btn-primary { background-color: #3498db; border-color: #3498db; }
        .summary-box { background-color: #e8f4fc; border-left: 4px solid #3498db; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Record Payment</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Sale Information Summary -->
                <div class="summary-box mb-4">
                    <h5>Sale Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Passenger:</strong> <?php echo htmlspecialchars($sale['PassengerName']); ?></p>
                            <p><strong>PNR:</strong> <?php echo htmlspecialchars($sale['PNR']); ?></p>
                            <p><strong>Ticket No:</strong> <?php echo htmlspecialchars($sale['TicketNumber']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Bill Amount:</strong> ৳<?php echo number_format($sale['BillAmount'], 2); ?></p>
                            <p><strong>Paid Amount:</strong> ৳<?php echo number_format($sale['PaidAmount'], 2); ?></p>
                            <p><strong>Due Amount:</strong> ৳<?php echo number_format($due_amount, 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_date" class="form-label">Payment Date *</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Payment Amount *</label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   min="0.01" max="<?php echo $due_amount; ?>" step="0.01" 
                                   value="<?php echo $due_amount; ?>" required>
                            <small class="form-text text-muted">Max: ৳<?php echo number_format($due_amount, 2); ?></small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="Cash Payment">Cash Payment</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Bank Deposit">Bank Deposit</option>
                                <option value="Cheque Deposit">Cheque Deposit</option>
                                <option value="Cheque Clearing">Cheque Clearing</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Mobile Banking">Mobile Banking</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bank_name" class="form-label">Bank Name</label>
                            <select class="form-select" id="bank_name" name="bank_name">
                                <option value="">Select Bank</option>
                                <option value="BRAC Bank Limited">BRAC Bank Limited</option>
                                <option value="Dutch Bangla Bank Limited">Dutch Bangla Bank Limited</option>
                                <option value="Islami Bank Bangladesh Limited">Islami Bank Bangladesh Limited</option>
                                <option value="Eastern Bank PLC">Eastern Bank PLC</option>
                                <option value="Southeast Bank Limited">Southeast Bank Limited</option>
                                <option value="City Bank Ltd">City Bank Ltd</option>
                                <option value="United Commercial Bank">United Commercial Bank</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Any additional notes about this payment..."></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="receiveable.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide bank field based on payment method
        document.getElementById('payment_method').addEventListener('change', function() {
            const bankField = document.getElementById('bank_name');
            const method = this.value;
            
            if (method.includes('Bank') || method.includes('Cheque') || method === 'Credit Card') {
                bankField.required = true;
                bankField.closest('.col-md-6').style.display = 'block';
            } else {
                bankField.required = false;
                bankField.closest('.col-md-6').style.display = 'block'; // Still show but not required
            }
        });
        
        // Validate amount doesn't exceed due amount
        document.getElementById('amount').addEventListener('change', function() {
            const maxAmount = <?php echo $due_amount; ?>;
            if (parseFloat(this.value) > maxAmount) {
                alert('Payment amount cannot exceed due amount of ৳' + maxAmount.toFixed(2));
                this.value = maxAmount;
            }
        });
    </script>
</body>
</html>