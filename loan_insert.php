<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Management - Insert New Loan</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(45deg, #3a7bd5, #00d2ff);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(45deg, #3a7bd5, #00d2ff);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #00d2ff, #3a7bd5);
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <!-- Include navigation -->
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center"><i class="fas fa-plus-circle me-2"></i>Add New Loan</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Display success/error messages
                        if (isset($_SESSION['message'])) {
                            $alertType = $_SESSION['message_type'] == 'success' ? 'alert-success' : 'alert-danger';
                            echo '<div class="alert '.$alertType.' alert-dismissible fade show" role="alert">
                                    '.$_SESSION['message'].'
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                            unset($_SESSION['message']);
                            unset($_SESSION['message_type']);
                        }
                        ?>

                        <form action="insert_loan.php" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="loan_title" class="form-label required-field">Loan Title</label>
                                    <input type="text" class="form-control" id="loan_title" name="loan_title" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="loan_receive_date" class="form-label required-field">Receive Date</label>
                                    <input type="date" class="form-control" id="loan_receive_date" name="loan_receive_date" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="loan_amount" class="form-label required-field">Loan Amount (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="loan_amount" name="loan_amount" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_amount" class="form-label required-field">Payment Amount (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="payment_amount" name="payment_amount" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_status" class="form-label required-field">Payment Status</label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Partially Paid">Partially Paid</option>
                                        <option value="Fully Paid">Fully Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="paid_date" class="form-label">Paid Date</label>
                                    <input type="date" class="form-control" id="paid_date" name="paid_date">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="loan_description" class="form-label">Loan Description</label>
                                <textarea class="form-control" id="loan_description" name="loan_description" rows="3"></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2"><i class="fas fa-undo me-1"></i> Reset</button>
                                <button type="submit" class="btn btn-primary" name="add_loan"><i class="fas fa-save me-1"></i> Add Loan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set today's date as default for loan receive date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('loan_receive_date').value = today;
            
            // Calculate remaining amount when loan amount or payment amount changes
            const loanAmountInput = document.getElementById('loan_amount');
            const paymentAmountInput = document.getElementById('payment_amount');
            
            if (loanAmountInput && paymentAmountInput) {
                loanAmountInput.addEventListener('input', calculateRemaining);
                paymentAmountInput.addEventListener('input', calculateRemaining);
            }
        });
        
        function calculateRemaining() {
            const loanAmount = parseFloat(document.getElementById('loan_amount').value) || 0;
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
            const remaining = loanAmount - paymentAmount;
            
            // You can display this somewhere if needed
            console.log("Remaining amount: " + remaining.toFixed(2));
        }
    </script>
</body>
</html>