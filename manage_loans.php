<?php
session_start();
include 'db.php';

// Fetch all loans
$query = "SELECT * FROM loan_management ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Loans</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        .status-partial {
            background-color: #3498db;
            color: white;
        }
        .status-paid {
            background-color: #2ecc71;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="fas fa-list me-2"></i>Manage Loans</h3>
                        <a href="loan_insert.php" class="btn btn-light"><i class="fas fa-plus me-1"></i> Add New Loan</a>
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

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Receive Date</th>
                                        <th>Loan Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Remaining</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)): 
                                        $statusClass = '';
                                        if ($row['payment_status'] == 'Fully Paid') {
                                            $statusClass = 'status-paid';
                                        } elseif ($row['payment_status'] == 'Partially Paid') {
                                            $statusClass = 'status-partial';
                                        } else {
                                            $statusClass = 'status-pending';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['loan_title']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($row['loan_receive_date'])); ?></td>
                                        <td>₱<?php echo number_format($row['loan_amount'], 2); ?></td>
                                        <td>₱<?php echo number_format($row['payment_amount'], 2); ?></td>
                                        <td>₱<?php echo number_format($row['remaining_amount'], 2); ?></td>
                                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['payment_status']; ?></span></td>
                                        <td>
                                            <a href="edit_loan.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                            <a href="delete_loan.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this loan record?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>