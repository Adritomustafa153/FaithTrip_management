<?php
// visa_insert.php
require 'db.php';
require 'auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Processing - Insert Record</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }
        .visa-form-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .form-header h2 {
            color: #007bff;
            font-weight: 600;
        }
        .required:after {
            content: " *";
            color: red;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        .form-section h5 {
            color: #495057;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .btn-custom {
            padding: 10px 30px;
            font-weight: 600;
        }
        .amount-field {
            font-weight: 600;
            color: #28a745;
        }
        .calc-box {
            background: #e9f7ef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container visa-form-container">
        <div class="form-header">
            <h2><i class="fas fa-passport me-2"></i> Visa Processing - New Record</h2>
            <p class="text-muted">Fill in the details below to add a new visa processing record</p>
        </div>

        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize and validate input
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $country = mysqli_real_escape_string($conn, $_POST['country']);
            $type = mysqli_real_escape_string($conn, $_POST['type']);
            $no_of_entry = mysqli_real_escape_string($conn, $_POST['no_of_entry']);
            $duration = mysqli_real_escape_string($conn, $_POST['duration']);
            $source = mysqli_real_escape_string($conn, $_POST['source']);
            $net_payment = floatval($_POST['net_payment']);
            $selling_price = floatval($_POST['selling_price']);
            $party_name = mysqli_real_escape_string($conn, $_POST['party_name']);
            $orderdate = mysqli_real_escape_string($conn, $_POST['orderdate']);
            $sold_by = mysqli_real_escape_string($conn, $_POST['sold_by']);
            $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
            $visa_status = mysqli_real_escape_string($conn, $_POST['visa_status']);
            $visano = mysqli_real_escape_string($conn, $_POST['visano']);
            $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
            $received_in = mysqli_real_escape_string($conn, $_POST['received_in']);
            
            // Calculate profit
            $profit = $selling_price - $net_payment;
            
            // Calculate paid and due
            $paid = floatval($_POST['paid']);
            $due = $selling_price - $paid;
            
            // Optional fields (refunds)
            $refund_net = isset($_POST['refund_net']) ? floatval($_POST['refund_net']) : 0;
            $service_charge = isset($_POST['service_charge']) ? floatval($_POST['service_charge']) : 0;
            $refund_to_client = isset($_POST['refund_to_client']) ? floatval($_POST['refund_to_client']) : 0;

            // Insert query
            $sql = "INSERT INTO visa (
                name, country, Type, NoOfEntry, Duration, Source, 
                `Net Payment`, `selling price`, profit, paid, due, orderdate, 
                sold_by, `party name`, payment_status, `visa status`, visano, 
                `payment method`, `received in`, `refund net`, `service charge`, 
                `refund to client`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?
            )";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssdddddssssssssddd",
                $name, $country, $type, $no_of_entry, $duration, $source,
                $net_payment, $selling_price, $profit, $paid, $due, $orderdate,
                $sold_by, $party_name, $payment_status, $visa_status, $visano,
                $payment_method, $received_in, $refund_net, $service_charge, $refund_to_client
            );

            if ($stmt->execute()) {
                $success = "Visa record inserted successfully!";
                $last_id = $stmt->insert_id;
                
                // Clear form if needed
                $_POST = array();
            } else {
                $error = "Error inserting record: " . $stmt->error;
            }
            $stmt->close();
        }
        ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <?php if (isset($last_id)): ?>
                    <br><strong>Record ID: <?php echo $last_id; ?></strong>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="visaForm">
            <div class="row">
                <!-- Column 1: Applicant Details -->
                <div class="col-md-6">
                    <div class="form-section">
                        <h5><i class="fas fa-user me-2"></i> Applicant Details</h5>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label required">Applicant Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="country" class="form-label required">Destination Country</label>
                            <input type="text" class="form-control" id="country" name="country" 
                                   value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Visa Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Select Type</option>
                                    <option value="Tourist" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Tourist') ? 'selected' : ''; ?>>Tourist</option>
                                    <option value="Business" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Business') ? 'selected' : ''; ?>>Business</option>
                                    <option value="Student" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Student') ? 'selected' : ''; ?>>Student</option>
                                    <option value="Work" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Work') ? 'selected' : ''; ?>>Work</option>
                                    <option value="Transit" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Transit') ? 'selected' : ''; ?>>Transit</option>
                                    <option value="Other" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="no_of_entry" class="form-label">No. of Entry</label>
                                <select class="form-select" id="no_of_entry" name="no_of_entry">
                                    <option value="">Select</option>
                                    <option value="Single" <?php echo (isset($_POST['no_of_entry']) && $_POST['no_of_entry'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                    <option value="Double" <?php echo (isset($_POST['no_of_entry']) && $_POST['no_of_entry'] == 'Double') ? 'selected' : ''; ?>>Double</option>
                                    <option value="Multiple" <?php echo (isset($_POST['no_of_entry']) && $_POST['no_of_entry'] == 'Multiple') ? 'selected' : ''; ?>>Multiple</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration/Validity</label>
                            <input type="text" class="form-control" id="duration" name="duration" 
                                   value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : ''; ?>" 
                                   placeholder="e.g., 30 Days, 6 Months, 1 Year">
                        </div>
                    </div>
                    
                    <!-- Financial Details -->
                    <div class="form-section">
                        <h5><i class="fas fa-money-bill-wave me-2"></i> Financial Details</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="net_payment" class="form-label required">Net Payment (Cost)</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="net_payment" name="net_payment" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['net_payment']) ? $_POST['net_payment'] : '0'; ?>" 
                                           required onchange="calculateProfit()">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="selling_price" class="form-label required">Selling Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="selling_price" name="selling_price" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['selling_price']) ? $_POST['selling_price'] : '0'; ?>" 
                                           required onchange="calculateProfit(); calculateDue()">
                                </div>
                            </div>
                        </div>
                        
                        <div class="calc-box">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Profit:</label>
                                    <h5 id="profitDisplay" class="amount-field">৳ 0.00</h5>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label for="paid" class="form-label">Amount Paid</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="paid" name="paid" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['paid']) ? $_POST['paid'] : '0'; ?>" 
                                           onchange="calculateDue()">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="due" class="form-label">Due Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="due" name="due" 
                                           step="0.01" min="0" readonly 
                                           value="<?php echo isset($_POST['due']) ? $_POST['due'] : '0'; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Column 2: Processing Details -->
                <div class="col-md-6">
                    <div class="form-section">
                        <h5><i class="fas fa-cogs me-2"></i> Processing Details</h5>
                        
                        <div class="mb-3">
                            <label for="source" class="form-label">Source/Vendor</label>
                            <input type="text" class="form-control" id="source" name="source" 
                                   value="<?php echo isset($_POST['source']) ? htmlspecialchars($_POST['source']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="party_name" class="form-label">Party/Client Name</label>
                            <input type="text" class="form-control" id="party_name" name="party_name" 
                                   value="<?php echo isset($_POST['party_name']) ? htmlspecialchars($_POST['party_name']) : ''; ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="orderdate" class="form-label required">Order Date</label>
                                <input type="date" class="form-control" id="orderdate" name="orderdate" 
                                       value="<?php echo isset($_POST['orderdate']) ? $_POST['orderdate'] : date('Y-m-d'); ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="sold_by" class="form-label">Sold By/Agent</label>
                                <input type="text" class="form-control" id="sold_by" name="sold_by" 
                                       value="<?php echo isset($_POST['sold_by']) ? htmlspecialchars($_POST['sold_by']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="payment_status" class="form-label">Payment Status</label>
                                <select class="form-select" id="payment_status" name="payment_status">
                                    <option value="">Select Status</option>
                                    <option value="Paid" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                    <option value="Partial" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Partial') ? 'selected' : ''; ?>>Partial</option>
                                    <option value="Pending" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Due" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Due') ? 'selected' : ''; ?>>Due</option>
                                    <option value="Refunded" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Refunded') ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="visa_status" class="form-label">Visa Status</label>
                                <select class="form-select" id="visa_status" name="visa_status">
                                    <option value="">Select Status</option>
                                    <option value="Applied" <?php echo (isset($_POST['visa_status']) && $_POST['visa_status'] == 'Applied') ? 'selected' : ''; ?>>Applied</option>
                                    <option value="Processing" <?php echo (isset($_POST['visa_status']) && $_POST['visa_status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Approved" <?php echo (isset($_POST['visa_status']) && $_POST['visa_status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo (isset($_POST['visa_status']) && $_POST['visa_status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="Delivered" <?php echo (isset($_POST['visa_status']) && $_POST['visa_status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="On Hold" <?php echo (isset($_POST['visa_status']) && $_POST['visa_status'] == 'On Hold') ? 'selected' : ''; ?>>On Hold</option>
                                    <option value="Cancelled" <?php echo (isset($_POST['visa_status']) && $_POST['visa_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="visano" class="form-label">Visa Number/Reference</label>
                            <input type="text" class="form-control" id="visano" name="visano" 
                                   value="<?php echo isset($_POST['visano']) ? htmlspecialchars($_POST['visano']) : ''; ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="">Select Method</option>
                                    <option value="Cash" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                    <option value="Bank Transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="Mobile Banking" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Mobile Banking') ? 'selected' : ''; ?>>Mobile Banking</option>
                                    <option value="Credit Card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Credit Card') ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="Cheque" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="received_in" class="form-label">Received In (Account)</label>
                                <input type="text" class="form-control" id="received_in" name="received_in" 
                                       value="<?php echo isset($_POST['received_in']) ? htmlspecialchars($_POST['received_in']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Refund Details (Optional) -->
                    <div class="form-section">
                        <h5><i class="fas fa-exchange-alt me-2"></i> Refund Details (Optional)</h5>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="refund_net" class="form-label">Refund Net</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="refund_net" name="refund_net" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['refund_net']) ? $_POST['refund_net'] : '0'; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="service_charge" class="form-label">Service Charge</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="service_charge" name="service_charge" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['service_charge']) ? $_POST['service_charge'] : '0'; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="refund_to_client" class="form-label">Refund to Client</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="refund_to_client" name="refund_to_client" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['refund_to_client']) ? $_POST['refund_to_client'] : '0'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <small class="text-muted">Fill these fields only if there's a refund involved.</small>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="clearForm()">
                            <i class="fas fa-redo me-2"></i> Clear Form
                        </button>
                        
                        <div>
                            <a href="visa_list.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-list me-2"></i> View All Visa Records
                            </a>
                            <button type="submit" class="btn btn-success btn-custom">
                                <i class="fas fa-save me-2"></i> Save Visa Record
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calculate profit automatically
        function calculateProfit() {
            const netPayment = parseFloat(document.getElementById('net_payment').value) || 0;
            const sellingPrice = parseFloat(document.getElementById('selling_price').value) || 0;
            const profit = sellingPrice - netPayment;
            
            document.getElementById('profitDisplay').textContent = '৳ ' + profit.toFixed(2);
        }
        
        // Calculate due amount automatically
        function calculateDue() {
            const sellingPrice = parseFloat(document.getElementById('selling_price').value) || 0;
            const paid = parseFloat(document.getElementById('paid').value) || 0;
            const due = sellingPrice - paid;
            
            document.getElementById('due').value = due.toFixed(2);
            
            // Update payment status based on amounts
            const paymentStatus = document.getElementById('payment_status');
            if (paid === 0) {
                paymentStatus.value = 'Pending';
            } else if (paid > 0 && paid < sellingPrice) {
                paymentStatus.value = 'Partial';
            } else if (paid === sellingPrice) {
                paymentStatus.value = 'Paid';
            }
        }
        
        // Clear form function
        function clearForm() {
            if (confirm('Are you sure you want to clear all form data?')) {
                document.getElementById('visaForm').reset();
                document.getElementById('profitDisplay').textContent = '৳ 0.00';
                document.getElementById('due').value = '0';
            }
        }
        
        // Initialize calculations on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateProfit();
            calculateDue();
        });
    </script>
</body>
</html>