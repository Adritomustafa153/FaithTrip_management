<?php
include 'config.php';

$period = $_GET['period'] ?? 'monthly';
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

try {
    if ($period === 'monthly') {
        // Monthly details
        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate));
        
        $stmt = $pdo->prepare("
            SELECT 
                IssueDate,
                PassengerName,
                airlines,
                TicketRoute,
                BillAmount,
                Profit,
                Remarks,
                invoice_number
            FROM sales
            WHERE IssueDate BETWEEN ? AND ?
            ORDER BY IssueDate
        ");
        $stmt->execute([$startDate, $endDate]);
        $transactions = $stmt->fetchAll();
        
        $title = "Monthly Details for " . date('F Y', strtotime($startDate));
    } else {
        // Yearly details
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(IssueDate, '%Y-%m') as month,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN Remarks = 'Sell' THEN BillAmount ELSE 0 END) as sell_amount,
                SUM(CASE WHEN Remarks = 'Reissue' THEN BillAmount ELSE 0 END) as reissue_amount,
                SUM(CASE WHEN Remarks = 'Refund' THEN ABS(refundtc) ELSE 0 END) as refund_amount,
                SUM(CASE WHEN Remarks = 'Sell' THEN Profit ELSE 0 END) as sell_profit,
                SUM(CASE WHEN Remarks = 'Reissue' THEN Profit ELSE 0 END) as reissue_profit,
                SUM(CASE WHEN Remarks = 'Refund' THEN Profit ELSE 0 END) as refund_profit,
                SUM(CASE WHEN Remarks = 'Sell' THEN 1 ELSE 0 END) as sell_count,
                SUM(CASE WHEN Remarks = 'Reissue' THEN 1 ELSE 0 END) as reissue_count,
                SUM(CASE WHEN Remarks = 'Refund' THEN 1 ELSE 0 END) as refund_count
            FROM sales
            WHERE YEAR(IssueDate) = ?
            GROUP BY DATE_FORMAT(IssueDate, '%Y-%m')
            ORDER BY DATE_FORMAT(IssueDate, '%Y-%m')
        ");
        $stmt->execute([$year]);
        $transactions = $stmt->fetchAll();
        
        $title = "Yearly Details for $year";
    }
    
    // Generate HTML
    echo "<h3>$title</h3>";
    
    if ($period === 'monthly') {
        echo '<table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Passenger</th>
                    <th>Airline</th>
                    <th>Route</th>
                    <th>Amount</th>
                    <th>Profit</th>
                    <th>Type</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($transactions as $transaction) {
            $rowClass = '';
            if ($transaction['Remarks'] === 'Sell') $rowClass = 'sell';
            if ($transaction['Remarks'] === 'Reissue') $rowClass = 'reissue';
            if ($transaction['Remarks'] === 'Refund') $rowClass = 'refund';
            
            echo "<tr class='$rowClass'>
                <td>" . date('d M Y', strtotime($transaction['IssueDate'])) . "</td>
                <td>" . htmlspecialchars($transaction['PassengerName']) . "</td>
                <td>" . htmlspecialchars($transaction['airlines']) . "</td>
                <td>" . htmlspecialchars($transaction['TicketRoute']) . "</td>
                <td>" . number_format($transaction['BillAmount'], 2) . "</td>
                <td>" . number_format($transaction['Profit'], 2) . "</td>
                <td>{$transaction['Remarks']}</td>
                <td>{$transaction['invoice_number']}</td>
            </tr>";
        }
        
        echo '</tbody></table>';
    } else {
        echo '<table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Transactions</th>
                    <th>Sell Count</th>
                    <th>Reissue Count</th>
                    <th>Refund Count</th>
                    <th>Total Sales</th>
                    <th>Total Profit</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($transactions as $transaction) {
            $monthName = date('F', mktime(0, 0, 0, $transaction['month'], 1));
            $totalSales = $transaction['sell_amount'] + $transaction['reissue_amount'] - $transaction['refund_amount'];
            $totalProfit = $transaction['sell_profit'] + $transaction['reissue_profit'] + $transaction['refund_profit'];
            
            echo "<tr>
                <td>$monthName</td>
                <td>{$transaction['transaction_count']}</td>
                <td class='sell'>{$transaction['sell_count']}</td>
                <td class='reissue'>{$transaction['reissue_count']}</td>
                <td class='refund'>{$transaction['refund_count']}</td>
                <td>" . number_format($totalSales, 2) . "</td>
                <td>" . number_format($totalProfit, 2) . "</td>
            </tr>";
        }
        
        echo '</tbody></table>';
    }
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>