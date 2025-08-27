<?php
include 'config.php';

$period = $_GET['period'] ?? 'monthly';
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Initialize response array
$response = [
    'total_sales' => 0,
    'total_profit' => 0,
    'sell_count' => 0,
    'reissue_count' => 0,
    'refund_count' => 0,
    'chart_data' => [
        'labels' => [],
        'sales' => [],
        'profit' => []
    ]
];

try {
    if ($period === 'monthly') {
        // Monthly report
        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate));
        
        // Get summary data
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN Remarks = 'Air Ticket Sale' THEN BillAmount ELSE 0 END) as sell_amount,
                SUM(CASE WHEN Remarks = 'Reissue' THEN BillAmount ELSE 0 END) as reissue_amount,
                SUM(CASE WHEN Remarks = 'Refund' THEN ABS(refundtc) ELSE 0 END) as refund_amount,
                SUM(CASE WHEN Remarks = 'Air Ticket Sale' THEN Profit ELSE 0 END) as sell_profit,
                SUM(CASE WHEN Remarks = 'Reissue' THEN Profit ELSE 0 END) as reissue_profit,
                SUM(CASE WHEN Remarks = 'Refund' THEN Profit ELSE 0 END) as refund_profit,
                SUM(CASE WHEN Remarks = 'Air Ticket Sale' THEN 1 ELSE 0 END) as sell_count,
                SUM(CASE WHEN Remarks = 'Reissue' THEN 1 ELSE 0 END) as reissue_count,
                SUM(CASE WHEN Remarks = 'Refund' THEN 1 ELSE 0 END) as refund_count
            FROM sales 
            WHERE IssueDate BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $summary = $stmt->fetch();
        
        // Calculate totals
        $totalSales = $summary['sell_amount'] + $summary['reissue_amount'] - $summary['refund_amount'];
        $totalProfit = $summary['sell_profit'] + $summary['reissue_profit'] + $summary['refund_profit'];
        
        // Prepare chart data (daily breakdown)
        $chartStmt = $pdo->prepare("
            SELECT 
                DATE(IssueDate) as day,
                SUM(CASE WHEN Remarks IN ('Air Ticket Sale', 'Reissue') THEN BillAmount ELSE 0 END) - 
                SUM(CASE WHEN Remarks = 'Refund' THEN ABS(refundtc) ELSE 0 END) as daily_sales,
                SUM(Profit) as daily_profit
            FROM sales
            WHERE IssueDate BETWEEN ? AND ?
            GROUP BY DATE(IssueDate)
            ORDER BY DATE(IssueDate)
        ");
        $chartStmt->execute([$startDate, $endDate]);
        $chartData = $chartStmt->fetchAll();
        
        $labels = [];
        $sales = [];
        $profit = [];
        
        foreach ($chartData as $row) {
            $labels[] = date('j M', strtotime($row['day']));
            $sales[] = (float)$row['daily_sales'];
            $profit[] = (float)$row['daily_profit'];
        }
        
        // Prepare response
        $response = [
            'total_sales' => $totalSales ?? 0,
            'total_profit' => $totalProfit ?? 0,
            'sell_count' => $summary['sell_count'] ?? 0,
            'reissue_count' => $summary['reissue_count'] ?? 0,
            'refund_count' => $summary['refund_count'] ?? 0,
            'chart_data' => [
                'labels' => $labels,
                'sales' => $sales,
                'profit' => $profit
            ]
        ];
    } else {
        // Yearly report
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";
        
        // Get summary data
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN Remarks = 'Air Ticket Sale' THEN BillAmount ELSE 0 END) as sell_amount,
                SUM(CASE WHEN Remarks = 'Reissue' THEN BillAmount ELSE 0 END) as reissue_amount,
                SUM(CASE WHEN Remarks = 'Refund' THEN ABS(refundtc) ELSE 0 END) as refund_amount,
                SUM(CASE WHEN Remarks = 'Air Ticket Sale' THEN Profit ELSE 0 END) as sell_profit,
                SUM(CASE WHEN Remarks = 'Reissue' THEN Profit ELSE 0 END) as reissue_profit,
                SUM(CASE WHEN Remarks = 'Refund' THEN Profit ELSE 0 END) as refund_profit,
                SUM(CASE WHEN Remarks = 'Air Ticket Sale' THEN 1 ELSE 0 END) as sell_count,
                SUM(CASE WHEN Remarks = 'Reissue' THEN 1 ELSE 0 END) as reissue_count,
                SUM(CASE WHEN Remarks = 'Refund' THEN 1 ELSE 0 END) as refund_count
            FROM sales 
            WHERE YEAR(IssueDate) = ?
        ");
        $stmt->execute([$year]);
        $summary = $stmt->fetch();
        
        // Calculate totals
        $totalSales = $summary['sell_amount'] + $summary['reissue_amount'] - $summary['refund_amount'];
        $totalProfit = $summary['sell_profit'] + $summary['reissue_profit'] + $summary['refund_profit'];
        
        // Prepare chart data (monthly breakdown)
        $chartStmt = $pdo->prepare("
            SELECT 
                MONTH(IssueDate) as month,
                SUM(CASE WHEN Remarks IN ('Air Ticket Sale', 'Reissue') THEN BillAmount ELSE 0 END) - 
                SUM(CASE WHEN Remarks = 'Refund' THEN ABS(refundtc) ELSE 0 END) as monthly_sales,
                SUM(Profit) as monthly_profit
            FROM sales
            WHERE YEAR(IssueDate) = ?
            GROUP BY MONTH(IssueDate)
            ORDER BY MONTH(IssueDate)
        ");
        $chartStmt->execute([$year]);
        $chartData = $chartStmt->fetchAll();
        
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $labels = [];
        $sales = [];
        $profit = [];
        
        foreach ($monthNames as $index => $name) {
            $monthNum = $index + 1;
            $found = false;
            
            foreach ($chartData as $row) {
                if ($row['month'] == $monthNum) {
                    $labels[] = $name;
                    $sales[] = (float)$row['monthly_sales'];
                    $profit[] = (float)$row['monthly_profit'];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $labels[] = $name;
                $sales[] = 0;
                $profit[] = 0;
            }
        }
        
        // Prepare response
        $response = [
            'total_sales' => $totalSales ?? 0,
            'total_profit' => $totalProfit ?? 0,
            'sell_count' => $summary['sell_count'] ?? 0,
            'reissue_count' => $summary['reissue_count'] ?? 0,
            'refund_count' => $summary['refund_count'] ?? 0,
            'chart_data' => [
                'labels' => $labels,
                'sales' => $sales,
                'profit' => $profit
            ]
        ];
    }
} catch (PDOException $e) {
    $response['error'] = "Database error: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>