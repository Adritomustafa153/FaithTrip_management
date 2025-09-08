<?php
include 'auth_check.php';
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get sale details
    $sale_sql = "SELECT * FROM sales WHERE SaleID = ?";
    $sale_stmt = $conn->prepare($sale_sql);
    $sale_stmt->bind_param("i", $id);
    $sale_stmt->execute();
    $sale_result = $sale_stmt->get_result();
    $sale = $sale_result->fetch_assoc();
    
    // Get payment history
    $payment_sql = "SELECT * FROM payments WHERE SaleID = ? ORDER BY PaymentDate DESC, CreatedAt DESC";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("i", $id);
    $payment_stmt->execute();
    $payments = $payment_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2>Payment History</h2>
                <a href="receiveable.php" class="btn btn-secondary">Back to Receivables</a>
            </div>
            <div class="card-body">
                <h4>Sale Information</h4>
                <p><strong>Party:</strong> <?php echo htmlspecialchars($sale['PartyName']); ?></p>
                <p><strong>Passenger:</strong> <?php echo htmlspecialchars($sale['PassengerName']); ?></p>
                <p><strong>Bill Amount:</strong> <?php echo number_format($sale['BillAmount'], 2); ?></p>
                
                <h4 class="mt-4">Payment History</h4>
                <table class="table table-striped">
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
                        <?php if ($payments->num_rows > 0): ?>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
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
</body>
</html>