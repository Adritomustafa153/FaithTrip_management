<?php
// received_payments.php
require 'db.php';
require 'auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Received Payments History</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .page-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container {
            overflow-x: auto;
        }
        .payment-method {
            font-weight: bold;
            color: #2c3e50;
        }
        .received-amount {
            font-weight: bold;
            color: #27ae60;
        }
        .due-amount {
            font-weight: bold;
            color: #e74c3c;
        }
        .btn-clear {
            background-color: #95a5a6;
            color: white;
        }
        .btn-clear:hover {
            background-color: #7f8c8d;
            color: white;
        }
        .table th {
            background-color: #3498db;
            color: white;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        .btn-edit {
            padding: 2px 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include 'nav.php'; ?>

    <div class="container-fluid mt-4">
        <h1 class="page-title">Received Payments History</h1>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search (Party Name/PNR/Invoice No)</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date"
                               value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date"
                               value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">All</option>
                            <option value="Cash" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="Bank Transfer" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Check" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'Check') ? 'selected' : ''; ?>>Check</option>
                            <option value="Mobile Banking" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'Mobile Banking') ? 'selected' : ''; ?>>Mobile Banking</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sales_person" class="form-label">Sales Person</label>
                        <select class="form-select" id="sales_person" name="sales_person">
                            <option value="">All</option>
                            <?php
                            $sales_persons_query = "SELECT id, name FROM sales_person";
                            $sales_persons_result = mysqli_query($conn, $sales_persons_query);
                            if ($sales_persons_result && mysqli_num_rows($sales_persons_result) > 0) {
                                while ($row = mysqli_fetch_assoc($sales_persons_result)) {
                                    $selected = (isset($_GET['sales_person']) && $_GET['sales_person'] == $row['id']) ? 'selected' : '';
                                    echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search"></i> Search</button>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <a href="received_payments.php" class="btn btn-clear"><i class="fas fa-times"></i> Clear</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Section -->
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Party Name</th>
                        <th>Invoice & PNR</th>
                        <th>Issue Date</th>
                        <th>Payments</th>
                        <th>Payment History</th>
                        <th>Notes</th>
                        <th>Sales Person</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT 
                                s.SaleID, 
                                s.PartyName, 
                                s.invoice_number, 
                                s.IssueDate, 
                                s.BillAmount, 
                                s.SalesPersonName,
                                s.PNR,
                                p.PaymentID,
                                p.PaymentDate,
                                p.Amount,
                                p.PaymentMethod,
                                p.BankName,
                                p.Notes,
                                p.PaymentType,
                                sp.name as SalesPersonName
                              FROM sales s
                              JOIN payments p ON s.SaleID = p.SaleID
                              LEFT JOIN sales_person sp ON s.SalesPersonName = sp.id
                              WHERE 1=1";
                    
                    $params = [];
                    $types = "";
                    
                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search = "%" . $_GET['search'] . "%";
                        $query .= " AND (s.PartyName LIKE ? OR s.invoice_number LIKE ? OR s.PNR LIKE ?)";
                        $params[] = $search; $params[] = $search; $params[] = $search;
                        $types .= "sss";
                    }
                    if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
                        $query .= " AND p.PaymentDate >= ?";
                        $params[] = $_GET['from_date'];
                        $types .= "s";
                    }
                    if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
                        $query .= " AND p.PaymentDate <= ?";
                        $params[] = $_GET['to_date'];
                        $types .= "s";
                    }
                    if (isset($_GET['payment_method']) && !empty($_GET['payment_method'])) {
                        $query .= " AND p.PaymentMethod = ?";
                        $params[] = $_GET['payment_method'];
                        $types .= "s";
                    }
                    if (isset($_GET['sales_person']) && !empty($_GET['sales_person'])) {
                        $query .= " AND s.SalesPersonName = ?";
                        $params[] = $_GET['sales_person'];
                        $types .= "s";
                    }
                    
                    $query .= " ORDER BY p.PaymentDate DESC, p.PaymentID DESC";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    if (!empty($params)) {
                        mysqli_stmt_bind_param($stmt, $types, ...$params);
                    }
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $due_amount = $row['BillAmount'] - $row['Amount'];
                            $issue_date = date('d M Y', strtotime($row['IssueDate']));
                            $payment_date = date('d M Y', strtotime($row['PaymentDate']));
                            $days_passed = floor((strtotime($row['PaymentDate']) - strtotime($row['IssueDate'])) / (60 * 60 * 24));
                            
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['PartyName']) . "</strong></td>";
                            echo "<td><strong>" . htmlspecialchars($row['invoice_number']) . "</strong><br><small class='text-muted'>PNR: " . (empty($row['PNR']) ? 'N/A' : htmlspecialchars($row['PNR'])) . "</small></td>";
                            echo "<td>" . $issue_date . "<br><small class='text-muted'>" . $days_passed . " days passed</small></td>";
                            echo "<td>Bill: " . number_format($row['BillAmount'], 2) . "<br><span class='received-amount'>Received: " . number_format($row['Amount'], 2) . "</span><br><span class='due-amount'>Due: " . number_format($due_amount, 2) . "</span></td>";
                            echo "<td><span class='payment-method'>" . htmlspecialchars($row['PaymentMethod']) . "</span><br><small class='text-muted'>" . (empty($row['BankName']) ? 'N/A' : htmlspecialchars($row['BankName'])) . "</small><br><small class='text-muted'>" . $payment_date . "</small></td>";
                            echo "<td>" . (empty($row['Notes']) ? 'N/A' : htmlspecialchars($row['Notes'])) . "</td>";
                            echo "<td>" . (empty($row['SalesPersonName']) ? 'N/A' : htmlspecialchars($row['SalesPersonName'])) . "</td>";
                            echo "<td><button class='btn btn-sm btn-primary btn-edit' data-paymentid='{$row['PaymentID']}' data-saleid='{$row['SaleID']}' data-amount='{$row['Amount']}' data-paymentdate='{$row['PaymentDate']}' data-paymentmethod='{$row['PaymentMethod']}' data-bankname='" . htmlspecialchars($row['BankName']) . "' data-notes='" . htmlspecialchars($row['Notes']) . "'><i class='fas fa-edit'></i> Edit</button></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center py-4'>No payment records found</td></tr>";
                    }
                    mysqli_stmt_close($stmt);
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPaymentForm">
                        <input type="hidden" id="edit_payment_id" name="payment_id">
                        <input type="hidden" id="edit_sale_id" name="sale_id">
                        <div class="mb-3">
                            <label for="edit_amount" class="form-label">Amount (Taka) *</label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_payment_date" class="form-label">Payment Date *</label>
                            <input type="date" class="form-control" id="edit_payment_date" name="payment_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="edit_payment_method" name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Check">Check</option>
                                <option value="Mobile Banking">Mobile Banking</option>
                            </select>
                        </div>
                        <div class="mb-3" id="edit_bank_group" style="display:none;">
                            <label for="edit_bank_name" class="form-label">Bank Name</label>
                            <input type="text" class="form-control" id="edit_bank_name" name="bank_name">
                        </div>
                        <div class="mb-3">
                            <label for="edit_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS + jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Handle Edit button click
            $('.btn-edit').click(function() {
                var paymentId = $(this).data('paymentid');
                var saleId = $(this).data('saleid');
                var amount = $(this).data('amount');
                var paymentDate = $(this).data('paymentdate');
                var paymentMethod = $(this).data('paymentmethod');
                var bankName = $(this).data('bankname');
                var notes = $(this).data('notes');
                
                $('#edit_payment_id').val(paymentId);
                $('#edit_sale_id').val(saleId);
                $('#edit_amount').val(amount);
                $('#edit_payment_date').val(paymentDate);
                $('#edit_payment_method').val(paymentMethod);
                $('#edit_notes').val(notes);
                
                // Show/hide bank field
                if (paymentMethod === 'Bank Transfer' || paymentMethod === 'Check') {
                    $('#edit_bank_group').show();
                    $('#edit_bank_name').val(bankName);
                } else {
                    $('#edit_bank_group').hide();
                    $('#edit_bank_name').val('');
                }
                
                $('#editPaymentModal').modal('show');
            });
            
            // Show/hide bank field on method change
            $('#edit_payment_method').change(function() {
                var method = $(this).val();
                if (method === 'Bank Transfer' || method === 'Check') {
                    $('#edit_bank_group').show();
                } else {
                    $('#edit_bank_group').hide();
                    $('#edit_bank_name').val('');
                }
            });
            
            // Submit edit form via AJAX
            $('#editPaymentForm').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: 'update_payment.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Payment updated successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Server error. Please try again.');
                    }
                });
            });
            
            // Auto-fill date filters if empty
            const today = new Date().toISOString().split('T')[0];
            if (!$('#from_date').val()) {
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                $('#from_date').val(thirtyDaysAgo.toISOString().split('T')[0]);
            }
            if (!$('#to_date').val()) {
                $('#to_date').val(today);
            }
        });
    </script>
</body>
</html>