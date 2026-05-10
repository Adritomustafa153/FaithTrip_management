<?php
// get_report.php
include 'db.php';          // provides $conn (mysqli)
require_once 'sales_functions.php'; // shared calculation function

$period = $_GET['period'] ?? 'monthly';
$month  = $_GET['month'] ?? date('m');
$year   = $_GET['year']  ?? date('Y');

switch ($period) {
    case 'monthly':
        $startDate = "$year-$month-01";
        $endDate   = date("Y-m-t", strtotime($startDate));
        break;
    case 'yearly':
        $startDate = "$year-01-01";
        $endDate   = "$year-12-31";
        break;
    default:
        $startDate = date('Y-m-01');
        $endDate   = date('Y-m-t');
}

// Helper: next day for exclusive upper bound
$endDateNext = date('Y-m-d', strtotime($endDate . ' +1 day'));

// Get comprehensive sales data using the shared function
$sales = calculateSales($conn, $startDate, $endDate);

// Get transaction counts (sell, reissue, refund) only from 'sales' table
// using corrected date range
$counts_sql = "SELECT 
                SUM(CASE WHEN Remarks NOT IN ('Refund','Reissue') THEN 1 ELSE 0 END) as sell_count,
                SUM(CASE WHEN Remarks = 'Reissue' THEN 1 ELSE 0 END) as reissue_count,
                SUM(CASE WHEN Remarks = 'Refund' THEN 1 ELSE 0 END) as refund_count
               FROM sales 
               WHERE IssueDate >= '$startDate' AND IssueDate < '$endDateNext'";
$counts_res = mysqli_query($conn, $counts_sql);
$counts = mysqli_fetch_assoc($counts_res);

// Prepare chart data (daily for monthly, monthly for yearly)
if ($period === 'yearly') {
    $chartLabels = [];
    $chartSales  = [];
    $chartProfit = [];
    for ($m = 1; $m <= 12; $m++) {
        $mStart = "$year-" . str_pad($m,2,'0',STR_PAD_LEFT) . "-01";
        $mEnd   = date("Y-m-t", strtotime($mStart));
        $mData  = calculateSales($conn, $mStart, $mEnd);
        $chartLabels[] = date('M', strtotime($mStart));
        $chartSales[]  = $mData['total_sales'];
        $chartProfit[] = $mData['total_profit'];
    }
} else {
    // For monthly report, show daily breakdown (only from sales table for chart)
    $daily_sql = "SELECT 
                    DATE(IssueDate) as day,
                    SUM(CASE WHEN Remarks IN ('Air Ticket Sale', 'Reissue') THEN BillAmount ELSE 0 END) -
                    SUM(CASE WHEN Remarks = 'Refund' THEN ABS(refundtc) ELSE 0 END) as daily_sales,
                    SUM(Profit) as daily_profit
                  FROM sales
                  WHERE IssueDate >= '$startDate' AND IssueDate < '$endDateNext'
                  GROUP BY DATE(IssueDate)
                  ORDER BY DATE(IssueDate)";
    $daily_res = mysqli_query($conn, $daily_sql);
    $daily_data = [];
    while ($row = mysqli_fetch_assoc($daily_res)) {
        $daily_data[] = $row;
    }
    // Note: This daily breakdown only includes sales table, not hotel/etc.
    // The summary totals already include all tables via calculateSales().
    $chartLabels = [];
    $chartSales  = [];
    $chartProfit = [];
    foreach ($daily_data as $row) {
        $chartLabels[] = date('j M', strtotime($row['day']));
        $chartSales[]  = (float)$row['daily_sales'];
        $chartProfit[] = (float)$row['daily_profit'];
    }
    // If there are no sales table entries, still show the total as one bar
    if (empty($chartLabels)) {
        $chartLabels = [date('j M', strtotime($startDate))];
        $chartSales  = [$sales['total_sales']];
        $chartProfit = [$sales['total_profit']];
    }
}

echo json_encode([
    'total_sales'    => $sales['total_sales'],
    'total_profit'   => $sales['total_profit'],
    'sell_count'     => (int)($counts['sell_count'] ?? 0),
    'reissue_count'  => (int)($counts['reissue_count'] ?? 0),
    'refund_count'   => (int)($counts['refund_count'] ?? 0),
    'chart_data'     => [
        'labels' => $chartLabels,
        'sales'  => $chartSales,
        'profit' => $chartProfit
    ]
]);
?>