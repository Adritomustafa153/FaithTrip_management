<?php
include 'config.php';

$period = $_GET['period'] ?? 'monthly';
$month  = $_GET['month'] ?? date('m');
$year   = $_GET['year']  ?? date('Y');

try {
    if ($period === 'monthly') {
        $startDate = "$year-$month-01";
        $endDate   = date("Y-m-t", strtotime($startDate));
        $title = "Monthly Details for " . date('F Y', strtotime($startDate));
        
        // UNION ALL five tables
        $sql = "
            SELECT 
                IssueDate as txn_date,
                'Air Ticket' as category,
                PassengerName as client_name,
                airlines as reference,
                TicketRoute as description,
                BillAmount as amount,
                Profit as profit,
                Remarks as status,
                invoice_number as doc_number
            FROM sales
            WHERE IssueDate BETWEEN :start AND :end
            
            UNION ALL
            
            SELECT 
                issue_date,
                'Hotel',
                client_name,
                source as reference,
                CONCAT(hotel_name, ' - ', room_type) as description,
                selling_price,
                profit,
                payment_status,
                invoice_number
            FROM hotel
            WHERE issue_date BETWEEN :start AND :end
            
            UNION ALL
            
            SELECT 
                `received date`,
                'Student Visa',
                client_name,
                source as reference,
                CONCAT(country, ' - ', university) as description,
                Selling,
                profit,
                payment_status,
                invoice_number
            FROM student
            WHERE `received date` BETWEEN :start AND :end
            
            UNION ALL
            
            SELECT 
                orderdate,
                'Umrah',
                client_name,
                source as reference,
                package_name as description,
                `selling price`,
                profit,
                payment_status,
                invoice_number
            FROM umrah
            WHERE orderdate BETWEEN :start AND :end
            
            UNION ALL
            
            SELECT 
                orderdate,
                'Visa',
                client_name,
                source as reference,
                country as description,
                `selling price`,
                profit,
                payment_status,
                invoice_number
            FROM visa
            WHERE orderdate BETWEEN :start AND :end
            
            ORDER BY txn_date DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start' => $startDate, 'end' => $endDate]);
        $transactions = $stmt->fetchAll();
        
        // Output HTML table
        echo "<h3>$title</h3>";
        echo '<table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Client</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Amount (BDT)</th>
                    <th>Profit (BDT)</th>
                    <th>Status</th>
                    <th>Invoice/Ref</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($transactions as $tx) {
            $rowClass = '';
            if ($tx['status'] == 'Refund') $rowClass = 'refund';
            elseif ($tx['status'] == 'Reissue') $rowClass = 'reissue';
            elseif (in_array($tx['status'], ['Due','Partially Paid'])) $rowClass = 'due';
            
            echo "<tr class='$rowClass'>
                <td>" . date('d M Y', strtotime($tx['txn_date'])) . "</td>
                <td>" . htmlspecialchars($tx['category']) . "</td>
                <td>" . htmlspecialchars($tx['client_name']) . "</td>
                <td>" . htmlspecialchars($tx['reference']) . "</td>
                <td>" . htmlspecialchars($tx['description']) . "</td>
                <td>" . number_format($tx['amount'], 2) . "</td>
                <td>" . number_format($tx['profit'], 2) . "</td>
                <td>" . htmlspecialchars($tx['status']) . "</td>
                <td>" . htmlspecialchars($tx['doc_number']) . "</td>
            </tr>";
        }
        
        echo '</tbody></table>';
        
    } else { // Yearly report – group by month, show totals per category
        $title = "Yearly Details for $year";
        
        // Get monthly aggregated data from all five tables
        // We'll create a UNION for monthly totals per category
        $sql = "
            SELECT 
                DATE_FORMAT(IssueDate, '%Y-%m') as month,
                'Air Ticket' as category,
                COUNT(*) as txn_count,
                SUM(BillAmount) as total_amount,
                SUM(Profit) as total_profit
            FROM sales
            WHERE YEAR(IssueDate) = :year
            GROUP BY month
            
            UNION ALL
            
            SELECT 
                DATE_FORMAT(issue_date, '%Y-%m'),
                'Hotel',
                COUNT(*),
                SUM(selling_price),
                SUM(profit)
            FROM hotel
            WHERE YEAR(issue_date) = :year
            GROUP BY month
            
            UNION ALL
            
            SELECT 
                DATE_FORMAT(`received date`, '%Y-%m'),
                'Student Visa',
                COUNT(*),
                SUM(Selling),
                SUM(profit)
            FROM student
            WHERE YEAR(`received date`) = :year
            GROUP BY month
            
            UNION ALL
            
            SELECT 
                DATE_FORMAT(orderdate, '%Y-%m'),
                'Umrah',
                COUNT(*),
                SUM(`selling price`),
                SUM(profit)
            FROM umrah
            WHERE YEAR(orderdate) = :year
            GROUP BY month
            
            UNION ALL
            
            SELECT 
                DATE_FORMAT(orderdate, '%Y-%m'),
                'Visa',
                COUNT(*),
                SUM(`selling price`),
                SUM(profit)
            FROM visa
            WHERE YEAR(orderdate) = :year
            GROUP BY month
            
            ORDER BY month, category
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['year' => $year]);
        $rows = $stmt->fetchAll();
        
        // Re‑organize by month
        $monthlyData = [];
        foreach ($rows as $row) {
            $month = $row['month'];
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'total_sales' => 0,
                    'total_profit' => 0,
                    'total_transactions' => 0,
                    'categories' => []
                ];
            }
            $monthlyData[$month]['total_sales'] += $row['total_amount'];
            $monthlyData[$month]['total_profit'] += $row['total_profit'];
            $monthlyData[$month]['total_transactions'] += $row['txn_count'];
            $monthlyData[$month]['categories'][] = [
                'category' => $row['category'],
                'count' => $row['txn_count'],
                'sales' => $row['total_amount'],
                'profit' => $row['total_profit']
            ];
        }
        
        // Output HTML
        echo "<h3>$title</h3>";
        echo '<table class="report-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Transactions</th>
                    <th>Total Sales (BDT)</th>
                    <th>Total Profit (BDT)</th>
                    <th>Breakdown by Category</th>
                </tr>
            </thead>
            <tbody>';
        
        $monthNames = [
            '01'=>'Jan', '02'=>'Feb', '03'=>'Mar', '04'=>'Apr', '05'=>'May', '06'=>'Jun',
            '07'=>'Jul', '08'=>'Aug', '09'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dec'
        ];
        
        ksort($monthlyData);
        foreach ($monthlyData as $month => $data) {
            $monthPart = substr($month, 5, 2);
            $monthName = $monthNames[$monthPart] . ' ' . substr($month, 0, 4);
            
            // Build category breakdown HTML
            $breakdown = '<ul style="margin:0; padding-left:15px;">';
            foreach ($data['categories'] as $cat) {
                $breakdown .= "<li><strong>{$cat['category']}</strong>: {$cat['count']} txns, " 
                            . number_format($cat['sales'],2) . " BDT sales, "
                            . number_format($cat['profit'],2) . " BDT profit</li>";
            }
            $breakdown .= '</ul>';
            
            echo "<tr>
                <td>{$monthName}</td>
                <td>{$data['total_transactions']}</td>
                <td>" . number_format($data['total_sales'], 2) . "</td>
                <td>" . number_format($data['total_profit'], 2) . "</td>
                <td>{$breakdown}</td>
            </tr>";
        }
        
        echo '</tbody></table>';
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>