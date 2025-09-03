<?php
include 'db.php';
include 'nav.php';

// Fetch all banks from the database
$sql = "SELECT * FROM banks ORDER BY id DESC";
$result = $conn->query($sql);

// Calculate total balance
$total_balance = 0;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $total_balance += $row['Balance'];
    }
    // Reset pointer
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bank Accounts</title>
    <style>
        .bank-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
            height: 100%;
        }
        .bank-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .bank-logo {
            height: 80px; /* Increased logo size */
            width: 80px;  /* Increased logo size */
            object-fit: contain;
            border-radius: 50%;
            background-color: #f8f9fa;
            padding: 5px;
        }
        .balance-positive {
            color: #28a745;
            font-weight: bold;
        }
        .balance-negative {
            color: #dc3545;
            font-weight: bold;
        }
        .taka-icon {
            font-family: Arial, sans-serif;
            font-weight: bold;
        }
        .card-footer {
            background-color: rgba(0,0,0,0.03);
            border-top: 1px solid rgba(0,0,0,0.125);
        }
        .stats-card {
            border-left: 4px solid #007bff;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .total-balance {
            font-size: 1.8rem; /* Smaller font for total balance */
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-university"></i> Bank Accounts</h2>
            <a href="insert_bank.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Bank Account
            </a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Accounts</h5>
                        <p class="card-text display-4"><?php echo $result->num_rows; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Balance</h5>
                        <p class="card-text total-balance taka-icon <?php echo $total_balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                            ৳ <?php echo number_format($total_balance, 2); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Active Accounts</h5>
                        <p class="card-text display-4"><?php echo $result->num_rows; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Cards -->
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card bank-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['Bank_Name']); ?></h5>
                                <?php if (!empty($row['logo'])): ?>
                                    <img src="data:image/png;base64,<?php echo base64_encode($row['logo']); ?>" 
                                         class="bank-logo" alt="<?php echo $row['Bank_Name']; ?> logo">
                                <?php else: ?>
                                    <i class="fas fa-university fa-2x text-secondary"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Account Title:</strong> <?php echo htmlspecialchars($row['A/C_Title']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Account Number:</strong> <?php echo htmlspecialchars($row['A/C_Number']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Branch:</strong> <?php echo htmlspecialchars($row['Branch_Name']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Routing Number:</strong> <?php echo $row['Routing_Number']; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Balance:</strong> 
                                    <span class="taka-icon <?php echo $row['Balance'] >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                                        ৳ <?php echo number_format($row['Balance'], 2); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer action-buttons text-center">
                                <a href="edit_bank.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_bank.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this bank account?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <h4>No bank accounts found</h4>
                        <p>Click the button above to add your first bank account.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>