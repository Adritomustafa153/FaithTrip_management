<?php
include('db.php');
require_once 'auth_check.php';

$period = $_GET['period'] ?? 'daily';
$type = $_GET['type'] ?? 'sales';

// Build date conditions based on period
$dateCondition = '';
if ($period === 'daily') {
    $dateCondition = "DATE(IssueDate) = CURDATE()";
} elseif ($period === 'monthly') {
    $dateCondition = "MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE())";
} elseif ($period === 'yearly') {
    $dateCondition = "YEAR(IssueDate) = YEAR(CURDATE())";
}

if ($type === 'sales') {
    // Sales Report
    $query = "SELECT 
                PartyName as party_name,
                PassengerName as passenger_name,
                IssueDate as issue_date,
                TicketNumber as ticket_number,
                PNR as pnr,
                BillAmount as selling_price,
                Profit as profit
              FROM sales 
              WHERE $dateCondition
              ORDER BY IssueDate DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
        exit;
    }
    
    $total_selling = 0;
    $total_profit = 0;
    ?>
    
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Party Name</th>
                    <th>Passenger Name</th>
                    <th>Details</th>
                    <th class="text-right">Selling Price</th>
                    <th class="text-right">Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $total_selling += $row['selling_price'];
                    $total_profit += $row['profit'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['party_name']) ?></td>
                    <td><?= htmlspecialchars($row['passenger_name']) ?></td>
                    <td>
                        <div class="sales-details">
                            <div class="sales-detail-row">
                                <span class="sales-detail-label">Issue Date:</span>
                                <span class="sales-detail-value"><?= date('M d, Y', strtotime($row['issue_date'])) ?></span>
                            </div>
                            <div class="sales-detail-row">
                                <span class="sales-detail-label">Ticket Number:</span>
                                <span class="sales-detail-value"><?= htmlspecialchars($row['ticket_number']) ?></span>
                            </div>
                            <div class="sales-detail-row">
                                <span class="sales-detail-label">PNR:</span>
                                <span class="sales-detail-value"><?= htmlspecialchars($row['pnr']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="text-right">৳<?= number_format($row['selling_price'], 2) ?></td>
                    <td class="text-right">৳<?= number_format($row['profit'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="3" class="text-right">Total:</td>
                    <td class="text-right">৳<?= number_format($total_selling, 2) ?></td>
                    <td class="text-right">৳<?= number_format($total_profit, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php
    if (mysqli_num_rows($result) === 0) {
        echo '<div class="alert alert-info text-center">No sales records found for the selected period.</div>';
    }
    
} elseif ($type === 'purchase') {
    // Purchase Report
    $query = "SELECT 
                IssueDate as purchase_date,
                Source as party_name,
                NetPayment as amount
              FROM sales 
              WHERE (Source NOT LIKE '%IATA%' OR Source IS NULL) AND $dateCondition
              ORDER BY IssueDate DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
        exit;
    }
    
    $total_amount = 0;
    ?>
    
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Purchase Date</th>
                    <th>Party Name</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $total_amount += $row['amount'];
                ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['purchase_date'])) ?></td>
                    <td><?= htmlspecialchars($row['party_name']) ?></td>
                    <td class="text-right">৳<?= number_format($row['amount'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="2" class="text-right">Total:</td>
                    <td class="text-right">৳<?= number_format($total_amount, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php
    if (mysqli_num_rows($result) === 0) {
        echo '<div class="alert alert-info text-center">No purchase records found for the selected period.</div>';
    }
    
} elseif ($type === 'payment') {
    // Payment Report
    $paymentDateCondition = '';
    if ($period === 'daily') {
        $paymentDateCondition = "DATE(payment_date) = CURDATE()";
    } elseif ($period === 'monthly') {
        $paymentDateCondition = "MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())";
    } elseif ($period === 'yearly') {
        $paymentDateCondition = "YEAR(payment_date) = YEAR(CURDATE())";
    }
    
    $query = "SELECT 
                payment_date,
                source,
                amount,
                payment_method
              FROM paid 
              WHERE $paymentDateCondition
              ORDER BY payment_date DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
        exit;
    }
    
    $total_amount = 0;
    ?>
    
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Payment Date</th>
                    <th>Party Name</th>
                    <th class="text-right">Amount</th>
                    <th>Payment Method</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $total_amount += $row['amount'];
                ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['payment_date'])) ?></td>
                    <td><?= htmlspecialchars($row['source']) ?></td>
                    <td class="text-right">৳<?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="2" class="text-right">Total:</td>
                    <td class="text-right">৳<?= number_format($total_amount, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php
    if (mysqli_num_rows($result) === 0) {
        echo '<div class="alert alert-info text-center">No payment records found for the selected period.</div>';
    }
    
} elseif ($type === 'collection') {
    // Collection Report
    $collectionDateCondition = '';
    if ($period === 'daily') {
        $collectionDateCondition = "DATE(PaymentDate) = CURDATE()";
    } elseif ($period === 'monthly') {
        $collectionDateCondition = "MONTH(PaymentDate) = MONTH(CURDATE()) AND YEAR(PaymentDate) = YEAR(CURDATE())";
    } elseif ($period === 'yearly') {
        $collectionDateCondition = "YEAR(PaymentDate) = YEAR(CURDATE())";
    }
    
    $query = "SELECT 
                p.PaymentDate as receiving_date,
                s.PartyName as party_name,
                p.Amount as amount,
                p.BankName as bank_name,
                p.PaymentMethod as payment_method,
                p.Notes as notes
              FROM payments p
              JOIN sales s ON p.SaleID = s.SaleID
              WHERE $collectionDateCondition
              ORDER BY p.PaymentDate DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
        exit;
    }
    
    $total_amount = 0;
    ?>
    
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Receiving Date</th>
                    <th>Party Name</th>
                    <th class="text-right">Amount</th>
                    <th>Bank Name</th>
                    <th>Payment Method</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $total_amount += $row['amount'];
                ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['receiving_date'])) ?></td>
                    <td><?= htmlspecialchars($row['party_name']) ?></td>
                    <td class="text-right">৳<?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['bank_name']) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                    <td><?= htmlspecialchars($row['notes']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="2" class="text-right">Total:</td>
                    <td class="text-right">৳<?= number_format($total_amount, 2) ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php
    if (mysqli_num_rows($result) === 0) {
        echo '<div class="alert alert-info text-center">No collection records found for the selected period.</div>';
    }
    
} elseif ($type === 'expense') {
    // Expense Report - Show total only
    $expenseDateCondition = '';
    if ($period === 'daily') {
        $expenseDateCondition = "DATE(expense_date) = CURDATE()";
    } elseif ($period === 'monthly') {
        $expenseDateCondition = "MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
    } elseif ($period === 'yearly') {
        $expenseDateCondition = "YEAR(expense_date) = YEAR(CURDATE())";
    }
    
    $query = "SELECT SUM(amount) as total_expense FROM expenses WHERE $expenseDateCondition";
    $result = mysqli_query($conn, $query);
    $expenseData = mysqli_fetch_assoc($result);
    $total_expense = $expenseData['total_expense'] ?? 0;
    ?>
    
    <div class="text-center py-4">
        <h3>Total Expense</h3>
        <div class="display-4 text-primary">৳<?= number_format($total_expense, 2) ?></div>
        <p class="text-muted mt-3">For <?= $period ?> period</p>
    </div>
    
    <?php
    if ($total_expense == 0) {
        echo '<div class="alert alert-info text-center">No expense records found for the selected period.</div>';
    }
}
?>