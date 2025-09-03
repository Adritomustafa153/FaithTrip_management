<?php
session_start();
include 'db.php';

// Check if ID parameter exists
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No loan ID specified!";
    $_SESSION['message_type'] = 'danger';
    header("Location: manage_loans.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch loan data
$query = "SELECT * FROM loan_management WHERE id = $id";
$result = mysqli_query($conn, $query);
$loan = mysqli_fetch_assoc($result);

if (!$loan) {
    $_SESSION['message'] = "Loan record not found!";
    $_SESSION['message_type'] = 'danger';
    header("Location: manage_loans.php");
    exit();
}

// Process form submission
if (isset($_POST['update_loan'])) {
    $loan_title = mysqli_real_escape_string($conn, $_POST['loan_title']);
    $loan_description = mysqli_real_escape_string($conn, $_POST['loan_description']);
    $loan_receive_date = mysqli_real_escape_string($conn, $_POST['loan_receive_date']);
    $loan_amount = mysqli_real_escape_string($conn, $_POST['loan_amount']);
    $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
    $payment_amount = mysqli_real_escape_string($conn, $_POST['payment_amount']);
    $paid_date = mysqli_real_escape_string($conn, $_POST['paid_date']);
    
    // Calculate remaining amount
    $remaining_amount = $loan_amount - $payment_amount;
    
    // If paid date is not provided, set to null
    if (empty($paid_date)) {
        $paid_date = null;
    }
    
    // Update query
    $update_query = "UPDATE loan_management SET 
                    loan_title = '$loan_title',
                    loan_description = '$loan_description',
                    loan_receive_date = '$loan_receive_date',
                    loan_amount = '$loan_amount',
                    payment_status = '$payment_status',
                    payment_amount = '$payment_amount',
                    paid_date = " . ($paid_date ? "'$paid_date'" : "NULL") . ",
                    remaining_amount = '$remaining_amount',
                    updated_at = NOW()
                    WHERE id = $id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['message'] = "Loan record updated successfully!";
        $_SESSION['message_type'] = 'success';
        header("Location: manage_loans.php");
        exit();
    } else {
        $_SESSION['message'] = "Error updating record: " . mysqli_error($conn);
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Loan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-warning {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            border: none;
            color: white;
        }
        .btn-warning:hover {
            background: linear-gradient(45deg, #e67e22, #f39c12);
            color: white;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center"><i class="fas fa-edit me-2"></i>Edit Loan</h3>
                    </div>
                    <div class="card-body">
                        <?php
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

                        <form action="edit_loan.php?id=<?php echo $id; ?>" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="loan_title" class="form-label required-field">Loan Title</label>
                                    <input type="text" class="form-control" id="loan_title" name="loan_title" value="<?php echo $loan['loan_title']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="loan_receive_date" class="form-label required-field">Receive Date</label>
                                    <input type="date" class="form-control" id="loan_receive_date" name="loan_receive_date" value="<?php echo $loan['loan_receive_date']; ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="loan_amount" class="form-label required-field">Loan Amount (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="loan_amount" name="loan_amount" value="<?php echo $loan['loan_amount']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_amount" class="form-label required-field">Payment Amount (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="payment_amount" name="payment_amount" value="<?php echo $loan['payment_amount']; ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_status" class="form-label required-field">Payment Status</label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="Pending" <?php echo $loan['payment_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Partially Paid" <?php echo $loan['payment_status'] == 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                                        <option value="Fully Paid" <?php echo $loan['payment_status'] == 'Fully Paid' ? 'selected' : ''; ?>>Fully Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="paid_date" class="form-label">Paid Date</label>
                                    <input type="date" class="form-control" id="paid_date" name="paid_date" value="<?php echo $loan['paid_date']; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="loan_description" class="form-label">Loan Description</label>
                                <textarea class="form-control" id="loan_description" name="loan_description" rows="3"><?php echo $loan['loan_description']; ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="manage_loans.php" class="btn btn-secondary me-md-2"><i class="fas fa-arrow-left me-1"></i> Back</a>
                                <button type="submit" class="btn btn-warning" name="update_loan"><i class="fas fa-save me-1"></i> Update Loan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>