<?php
require_once 'db.php';
require_once 'nav.php';

function getIataPayments($conn, $startDate, $endDate) {
    $query = "SELECT 
                SaleID,
                invoice_number,
                IssueDate,
                PassengerName,
                TicketRoute,
                NetPayment,
                SalesPersonName
              FROM sales 
              WHERE IssueDate BETWEEN ? AND ?
              AND Source = 'IATA'
              ORDER BY IssueDate";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $payments = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $payments;
}

// Determine which period to show
$currentDay = date('j');
$currentMonth = date('n');
$currentYear = date('Y');

if ($currentDay >= 10 && $currentDay <= 15) {
    // Show previous month's 16-30/31
    $prevMonth = $currentMonth - 1;
    $prevYear = $currentYear;
    
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear--;
    }
    
    $startDate = date('Y-m-16', strtotime("$prevYear-$prevMonth-01"));
    $endDate = date('Y-m-t', strtotime("$prevYear-$prevMonth-01"));
    $periodTitle = "IATA Payment Due (16th-30th of " . date('F Y', strtotime("$prevYear-$prevMonth-01")) . ")";
} else {
    // Show current month's 1-15
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-15');
    $periodTitle = "IATA Payment Due (1st-15th of " . date('F Y') . ")";
}

$iataPayments = getIataPayments($conn, $startDate, $endDate);
$totalAmount = array_sum(array_column($iataPayments, 'NetPayment'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $periodTitle; ?></title>
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb.min.css" />
</head>
<body>
    <div class="container mt-4">
        <h2><?php echo $periodTitle; ?></h2>
        <div class="alert alert-info">
            Total Amount Due: <strong><?php echo number_format($totalAmount, 2); ?></strong>
        </div>
        
        <?php if (count($iataPayments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Issue Date</th>
                            <th>Passenger</th>
                            <th>Route</th>
                            <th>Amount</th>
                            <th>Sales Person</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($iataPayments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                                <td><?php echo htmlspecialchars($payment['IssueDate']); ?></td>
                                <td><?php echo htmlspecialchars($payment['PassengerName']); ?></td>
                                <td><?php echo htmlspecialchars($payment['TicketRoute']); ?></td>
                                <td><?php echo number_format($payment['NetPayment'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['SalesPersonName']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                No IATA tickets found for this period.
            </div>
        <?php endif; ?>
    </div>

    <!-- MDB -->
    <!-- <script type="text/javascript" src="js/mdb.umd.min.js"></script> -->
</body>
</html>