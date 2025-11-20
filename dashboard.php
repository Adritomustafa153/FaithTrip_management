<?php
include('db.php');
require_once 'auth_check.php';

$filter = $_GET['filter'] ?? 'monthly';

// Calculate summary metrics for cards
// Daily metrics - Using payments table for collection amounts
$dailySalesQuery = "SELECT 
    SUM(BillAmount) as sale_amount,
    SUM(Profit) as profit_amount
FROM sales 
WHERE DATE(IssueDate) = CURDATE()";
$dailyResult = mysqli_query($conn, $dailySalesQuery);
$dailySalesData = mysqli_fetch_assoc($dailyResult);

// Collection amount from payments table
$dailyCollectionQuery = "SELECT SUM(Amount) as collection_amount 
                         FROM payments 
                         WHERE DATE(PaymentDate) = CURDATE()";
$dailyCollectionResult = mysqli_query($conn, $dailyCollectionQuery);
$dailyCollectionData = mysqli_fetch_assoc($dailyCollectionResult);

// Purchase amount (non-IATA sources)
$dailyPurchaseQuery = "SELECT SUM(NetPayment) as purchase_amount 
                       FROM sales 
                       WHERE (Source NOT LIKE '%IATA%' OR Source IS NULL) AND 
                             DATE(IssueDate) = CURDATE()";
$dailyPurchaseResult = mysqli_query($conn, $dailyPurchaseQuery);
$dailyPurchaseData = mysqli_fetch_assoc($dailyPurchaseResult);

$dailyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE DATE(expense_date) = CURDATE()";
$dailyExpenseResult = mysqli_query($conn, $dailyExpenseQuery);
$dailyExpenseData = mysqli_fetch_assoc($dailyExpenseResult);

// Payment amount from payments table (for non-sale payments)
$dailyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE DATE(payment_date) = CURDATE()";
$dailyPaymentResult = mysqli_query($conn, $dailyPaymentQuery);
$dailyPaymentData = mysqli_fetch_assoc($dailyPaymentResult);

// Monthly metrics
$monthlySalesQuery = "SELECT 
    SUM(BillAmount) as sale_amount,
    SUM(Profit) as profit_amount
FROM sales 
WHERE MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE())";
$monthlyResult = mysqli_query($conn, $monthlySalesQuery);
$monthlySalesData = mysqli_fetch_assoc($monthlyResult);

// Collection amount from payments table
$monthlyCollectionQuery = "SELECT SUM(Amount) as collection_amount 
                           FROM payments 
                           WHERE MONTH(PaymentDate) = MONTH(CURDATE()) AND YEAR(PaymentDate) = YEAR(CURDATE())";
$monthlyCollectionResult = mysqli_query($conn, $monthlyCollectionQuery);
$monthlyCollectionData = mysqli_fetch_assoc($monthlyCollectionResult);

// Purchase amount (non-IATA sources)
$monthlyPurchaseQuery = "SELECT SUM(NetPayment) as purchase_amount 
                         FROM sales 
                         WHERE (Source NOT LIKE '%IATA%' OR Source IS NULL) AND 
                               MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE())";
$monthlyPurchaseResult = mysqli_query($conn, $monthlyPurchaseQuery);
$monthlyPurchaseData = mysqli_fetch_assoc($monthlyPurchaseResult);

$monthlyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
$monthlyExpenseResult = mysqli_query($conn, $monthlyExpenseQuery);
$monthlyExpenseData = mysqli_fetch_assoc($monthlyExpenseResult);

// Payment amount from payments table (for non-sale payments)
$monthlyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())";
$monthlyPaymentResult = mysqli_query($conn, $monthlyPaymentQuery);
$monthlyPaymentData = mysqli_fetch_assoc($monthlyPaymentResult);

// Yearly metrics
$yearlySalesQuery = "SELECT 
    SUM(BillAmount) as sale_amount,
    SUM(Profit) as profit_amount
FROM sales 
WHERE YEAR(IssueDate) = YEAR(CURDATE())";
$yearlyResult = mysqli_query($conn, $yearlySalesQuery);
$yearlySalesData = mysqli_fetch_assoc($yearlyResult);

// Collection amount from payments table
$yearlyCollectionQuery = "SELECT SUM(Amount) as collection_amount 
                          FROM payments 
                          WHERE YEAR(PaymentDate) = YEAR(CURDATE())";
$yearlyCollectionResult = mysqli_query($conn, $yearlyCollectionQuery);
$yearlyCollectionData = mysqli_fetch_assoc($yearlyCollectionResult);

// Purchase amount (non-IATA sources)
$yearlyPurchaseQuery = "SELECT SUM(NetPayment) as purchase_amount 
                        FROM sales 
                        WHERE (Source NOT LIKE '%IATA%' OR Source IS NULL) AND 
                              YEAR(IssueDate) = YEAR(CURDATE())";
$yearlyPurchaseResult = mysqli_query($conn, $yearlyPurchaseQuery);
$yearlyPurchaseData = mysqli_fetch_assoc($yearlyPurchaseResult);

$yearlyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE())";
$yearlyExpenseResult = mysqli_query($conn, $yearlyExpenseQuery);
$yearlyExpenseData = mysqli_fetch_assoc($yearlyExpenseResult);

// Payment amount from payments table (for non-sale payments)
$yearlyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE YEAR(payment_date) = YEAR(CURDATE())";
$yearlyPaymentResult = mysqli_query($conn, $yearlyPaymentQuery);
$yearlyPaymentData = mysqli_fetch_assoc($yearlyPaymentResult);

// NEW: Calculate comprehensive sales data from sales_record.php
function calculateComprehensiveSales($conn, $startDate, $endDate) {
    $result = [
        'total_sales' => 0,
        'total_purchase' => 0,
        'total_profit' => 0,
        'total_due' => 0,
        'total_reissue' => 0,
        'total_refund' => 0,
        'total_collection' => 0,
        'category_sales' => [
            'ticket' => 0,
            'visa' => 0,
            'student_visa' => 0,
            'umrah' => 0,
            'hotel' => 0
        ]
    ];

    // Calculate for SALES table (Air Tickets)
    $sales_sql = "SELECT 
                    COALESCE(SUM(CASE 
                        WHEN Remarks = 'Refund' THEN 0 
                        WHEN Remarks = 'Reissue' THEN BillAmount 
                        ELSE BillAmount 
                    END), 0) as total_sales,
                    COALESCE(SUM(CASE 
                        WHEN Remarks = 'Refund' THEN 0 
                        ELSE NetPayment 
                    END), 0) as total_net,
                    COALESCE(SUM(CASE 
                        WHEN Remarks = 'Refund' THEN 0 
                        ELSE Profit 
                    END), 0) as total_profit,
                    COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN BillAmount ELSE 0 END), 0) as total_refund,
                    COALESCE(SUM(CASE WHEN Remarks = 'Reissue' THEN BillAmount ELSE 0 END), 0) as total_reissue,
                    COALESCE(SUM(CASE 
                        WHEN PaymentStatus IN ('Due', 'Partially Paid') THEN DueAmount 
                        ELSE 0 
                    END), 0) as total_due
                  FROM sales 
                  WHERE IssueDate BETWEEN '$startDate' AND '$endDate'";
    
    $sales_result = $conn->query($sales_sql);
    if ($sales_result && $sales_row = $sales_result->fetch_assoc()) {
        $ticket_sales = $sales_row['total_sales'];
        $result['total_sales'] += $ticket_sales;
        $result['total_profit'] += $sales_row['total_profit'];
        $result['total_refund'] += $sales_row['total_refund'];
        $result['total_reissue'] += $sales_row['total_reissue'];
        $result['total_due'] += $sales_row['total_due'];
        $result['category_sales']['ticket'] = $ticket_sales;
        
        // Calculate purchase for tickets (NetPayment where Source is not IATA and not refund)
        $ticket_purchase_sql = "SELECT COALESCE(SUM(NetPayment), 0) as total_net 
                               FROM sales 
                               WHERE IssueDate BETWEEN '$startDate' AND '$endDate' 
                               AND Source != 'IATA' 
                               AND Source IS NOT NULL 
                               AND Source != ''
                               AND Remarks != 'Refund'";
        $purchase_result = $conn->query($ticket_purchase_sql);
        if ($purchase_result && $purchase_row = $purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $purchase_row['total_net'];
        }
    }

    // Calculate collection amount from payments table
    $collection_sql = "SELECT COALESCE(SUM(Amount), 0) as total_collection 
                      FROM payments 
                      WHERE PaymentDate BETWEEN '$startDate' AND '$endDate'";
    $collection_result = $conn->query($collection_sql);
    if ($collection_result && $collection_row = $collection_result->fetch_assoc()) {
        $result['total_collection'] += $collection_row['total_collection'];
    }

    // Calculate for HOTEL table
    $hotel_sql = "SELECT 
                    COALESCE(SUM(selling_price), 0) as total_sales,
                    COALESCE(SUM(net_price), 0) as total_net,
                    COALESCE(SUM(profit), 0) as total_profit,
                    COALESCE(SUM(refund_to_client), 0) as total_refund,
                    COALESCE(SUM(CASE 
                        WHEN payment_status IN ('Due', 'Partially Paid') THEN due_amount 
                        ELSE 0 
                    END), 0) as total_due
                  FROM hotel 
                  WHERE issue_date BETWEEN '$startDate' AND '$endDate'";
    
    $hotel_result = $conn->query($hotel_sql);
    if ($hotel_result && $hotel_row = $hotel_result->fetch_assoc()) {
        $hotel_sales = $hotel_row['total_sales'] - $hotel_row['total_refund'];
        $result['total_sales'] += $hotel_sales;
        $result['total_profit'] += $hotel_row['total_profit'];
        $result['total_refund'] += $hotel_row['total_refund'];
        $result['total_due'] += $hotel_row['total_due'];
        $result['category_sales']['hotel'] = $hotel_sales;
        
        // Calculate purchase for hotel (net_price where source is not "OWN")
        $hotel_purchase_sql = "SELECT COALESCE(SUM(net_price), 0) as total_net 
                              FROM hotel 
                              WHERE issue_date BETWEEN '$startDate' AND '$endDate' 
                              AND source != 'OWN' 
                              AND source IS NOT NULL 
                              AND source != ''";
        $hotel_purchase_result = $conn->query($hotel_purchase_sql);
        if ($hotel_purchase_result && $hotel_purchase_row = $hotel_purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $hotel_purchase_row['total_net'];
        }
    }

    // Calculate for STUDENT table
    $student_sql = "SELECT 
                    COALESCE(SUM(Selling), 0) as total_sales,
                    COALESCE(SUM(net), 0) as total_net,
                    COALESCE(SUM(profit), 0) as total_profit,
                    COALESCE(SUM(refund_to_client), 0) as total_refund,
                    COALESCE(SUM(CASE 
                        WHEN payment_status IN ('Due', 'Partially Paid') THEN due 
                        ELSE 0 
                    END), 0) as total_due
                  FROM student 
                  WHERE `received date` BETWEEN '$startDate' AND '$endDate'";
    
    $student_result = $conn->query($student_sql);
    if ($student_result && $student_row = $student_result->fetch_assoc()) {
        $student_sales = $student_row['total_sales'] - $student_row['total_refund'];
        $result['total_sales'] += $student_sales;
        $result['total_profit'] += $student_row['total_profit'];
        $result['total_refund'] += $student_row['total_refund'];
        $result['total_due'] += $student_row['total_due'];
        $result['category_sales']['student_visa'] = $student_sales;
        
        // Calculate purchase for student (net where source is not "OWN")
        $student_purchase_sql = "SELECT COALESCE(SUM(net), 0) as total_net 
                               FROM student 
                               WHERE `received date` BETWEEN '$startDate' AND '$endDate' 
                               AND source != 'OWN' 
                               AND source IS NOT NULL 
                               AND source != ''";
        $student_purchase_result = $conn->query($student_purchase_sql);
        if ($student_purchase_result && $student_purchase_row = $student_purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $student_purchase_row['total_net'];
        }
    }

    // Calculate for UMRAH table
    $umrah_sql = "SELECT 
                    COALESCE(SUM(`selling price`), 0) as total_sales,
                    COALESCE(SUM(`net payment`), 0) as total_net,
                    COALESCE(SUM(profit), 0) as total_profit,
                    COALESCE(SUM(`refund to client`), 0) as total_refund,
                    COALESCE(SUM(CASE 
                        WHEN payment_status IN ('Due', 'Partially Paid') THEN due 
                        ELSE 0 
                    END), 0) as total_due
                  FROM umrah 
                  WHERE orderdate BETWEEN '$startDate' AND '$endDate'";
    
    $umrah_result = $conn->query($umrah_sql);
    if ($umrah_result && $umrah_row = $umrah_result->fetch_assoc()) {
        $umrah_sales = $umrah_row['total_sales'] - $umrah_row['total_refund'];
        $result['total_sales'] += $umrah_sales;
        $result['total_profit'] += $umrah_row['total_profit'];
        $result['total_refund'] += $umrah_row['total_refund'];
        $result['total_due'] += $umrah_row['total_due'];
        $result['category_sales']['umrah'] = $umrah_sales;
        
        // Calculate purchase for umrah (net payment where source is not "OWN")
        $umrah_purchase_sql = "SELECT COALESCE(SUM(`net payment`), 0) as total_net 
                              FROM umrah 
                              WHERE orderdate BETWEEN '$startDate' AND '$endDate' 
                              AND source != 'OWN' 
                              AND source IS NOT NULL 
                              AND source != ''";
        $umrah_purchase_result = $conn->query($umrah_purchase_sql);
        if ($umrah_purchase_result && $umrah_purchase_row = $umrah_purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $umrah_purchase_row['total_net'];
        }
    }

    // Calculate for VISA table
    $visa_sql = "SELECT 
                    COALESCE(SUM(`selling price`), 0) as total_sales,
                    COALESCE(SUM(`Net Payment`), 0) as total_net,
                    COALESCE(SUM(profit), 0) as total_profit,
                    COALESCE(SUM(`refund to client`), 0) as total_refund,
                    COALESCE(SUM(CASE 
                        WHEN payment_status IN ('Due', 'Partially Paid') THEN due 
                        ELSE 0 
                    END), 0) as total_due
                  FROM visa 
                  WHERE orderdate BETWEEN '$startDate' AND '$endDate'";
    
    $visa_result = $conn->query($visa_sql);
    if ($visa_result && $visa_row = $visa_result->fetch_assoc()) {
        $visa_sales = $visa_row['total_sales'] - $visa_row['total_refund'];
        $result['total_sales'] += $visa_sales;
        $result['total_profit'] += $visa_row['total_profit'];
        $result['total_refund'] += $visa_row['total_refund'];
        $result['total_due'] += $visa_row['total_due'];
        $result['category_sales']['visa'] = $visa_sales;
        
        // Calculate purchase for visa (Net Payment where source is not "OWN")
        $visa_purchase_sql = "SELECT COALESCE(SUM(`Net Payment`), 0) as total_net 
                             FROM visa 
                             WHERE orderdate BETWEEN '$startDate' AND '$endDate' 
                             AND source != 'OWN' 
                             AND source IS NOT NULL 
                             AND source != ''";
        $visa_purchase_result = $conn->query($visa_purchase_sql);
        if ($visa_purchase_result && $visa_purchase_row = $visa_purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $visa_purchase_row['total_net'];
        }
    }

    return $result;
}

// Set current dates automatically
$current_date = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');

// Calculate comprehensive sales data
$daily_start = $current_date;
$daily_end = $current_date;
$daily_comprehensive_sales = calculateComprehensiveSales($conn, $daily_start, $daily_end);

$monthly_start = date('Y-m-01');
$monthly_end = date('Y-m-t');
$monthly_comprehensive_sales = calculateComprehensiveSales($conn, $monthly_start, $monthly_end);

$yearly_start = $current_year . '-01-01';
$yearly_end = $current_year . '-12-31';
$yearly_comprehensive_sales = calculateComprehensiveSales($conn, $yearly_start, $yearly_end);

// Sales by section data - Based on filter
if ($filter === 'daily') {
    $salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales 
                   WHERE DATE(IssueDate) = CURDATE()
                   GROUP BY section";
    $pieChartTitle = "Sales by Section (Daily)";
} elseif ($filter === 'yearly') {
    $salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales 
                   WHERE YEAR(IssueDate) = YEAR(CURDATE())
                   GROUP BY section";
    $pieChartTitle = "Sales by Section (Yearly)";
} elseif ($filter === 'total') {
    $salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales 
                   GROUP BY section";
    $pieChartTitle = "Sales by Section (Total)";
} else {
    // Default to monthly
    $salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales 
                   WHERE MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE())
                   GROUP BY section";
    $pieChartTitle = "Sales by Section (Monthly)";
}

$salesResult = mysqli_query($conn, $salesQuery);

$salesData = ['Agent' => 0, 'Counter' => 0, 'Corporate' => 0];
while ($row = mysqli_fetch_assoc($salesResult)) {
    $key = ucfirst(strtolower($row['section']));
    if (array_key_exists($key, $salesData)) {
        $salesData[$key] = (float)$row['total'];
    }
}

// Expense data
$expenseQuery = "SELECT DATE_FORMAT(expense_date, '%b') AS month, SUM(amount) AS total 
                 FROM expenses 
                 WHERE YEAR(expense_date) = YEAR(CURDATE())
                 GROUP BY month 
                 ORDER BY MIN(expense_date)";
$expenseResult = mysqli_query($conn, $expenseQuery);

$expenseMonths = [];
$expenseTotals = [];

while ($row = mysqli_fetch_assoc($expenseResult)) {
    $expenseMonths[] = $row['month'];
    $expenseTotals[] = (float)$row['total'];
}

// Monthly sales vs profit data
$monthlySalesProfitQuery = "SELECT 
    DATE_FORMAT(IssueDate, '%b') AS month,
    SUM(BillAmount) AS total_sales,
    SUM(Profit) AS total_profit
FROM sales
WHERE YEAR(IssueDate) = YEAR(CURDATE())
GROUP BY month
ORDER BY MONTH(IssueDate)";

$monthlySalesProfitResult = mysqli_query($conn, $monthlySalesProfitQuery);

$monthlyLabels = [];
$monthlySales = [];
$monthlyProfit = [];

while ($row = mysqli_fetch_assoc($monthlySalesProfitResult)) {
    $monthlyLabels[] = $row['month'];
    $monthlySales[] = (float)$row['total_sales'];
    $monthlyProfit[] = (float)$row['total_profit'];
}

// FIXED: Get bookings count for notifications (time limit within 30 minutes)
$currentDateTime = date('Y-m-d H:i:s');
$notificationTimeLimit = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$bookingsNotificationQuery = "SELECT COUNT(*) as notification_count 
                              FROM bookings 
                              WHERE time_limit BETWEEN ? AND ? 
                              AND status NOT IN ('Cancelled', 'Completed')";
$stmt = mysqli_prepare($conn, $bookingsNotificationQuery);
mysqli_stmt_bind_param($stmt, "ss", $currentDateTime, $notificationTimeLimit);
mysqli_stmt_execute($stmt);
$bookingsNotificationResult = mysqli_stmt_get_result($stmt);
$bookingsNotificationData = mysqli_fetch_assoc($bookingsNotificationResult);
mysqli_stmt_close($stmt);

$bookingsNotificationCount = $bookingsNotificationData['notification_count'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="logo.jpg">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ALL YOUR EXISTING CSS STYLES REMAIN EXACTLY THE SAME */
        :root {
            --iata-blue: #0033a0;
            --iata-light-blue: #0099d7;
            --iata-gray: #f2f2f2;
            --iata-dark-gray: #666666;
            --iata-green: #00a651;
            --iata-yellow: #ffcc00;
            --iata-orange: #ff9900;
            --iata-red: #ed1c24;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            padding: 0;
            margin: 0;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .dashboard-header h2{
            text-align: center;
            flex: 1;
            margin-left: 180px;
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .filter-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-box label {
            margin-bottom: 0;
            font-weight: 500;
        }
        
        select {
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        
        .btn-fare-calculator {
            background-color: var(--iata-blue);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-fare-calculator:hover {
            background-color: var(--iata-light-blue);
        }

        .btn-bookings {
            background-color: var(--iata-green);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            position: relative;
        }
        
        .btn-bookings:hover {
            background-color: #008a45;
        }
        
        .booking-notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--iata-red);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .summary-cards {
            display: flex;
            gap: 20px;
            padding: 0 20px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            flex: 1;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .summary-card-header {
            padding: 15px;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .summary-card-body {
            padding: 15px;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .metric-value {
            font-weight: bold;
        }
        
        .card-daily .summary-card-header {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        
        .card-monthly .summary-card-header {
            background-color: #f3e5f5;
            color: #4a148c;
        }
        
        .card-yearly .summary-card-header {
            background-color: #e8f5e9;
            color: #1b5e20;
        }
        
        .charts-section {
            padding: 0 20px;
        }
        
        .charts-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            flex: 1;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            min-height: 450px;
            display: flex;
            flex-direction: column;
        }
        
        .chart-container-full {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .chart-title {
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }
        
        canvas {
            display: block;
            margin: 0 auto;
            width: 100% !important;
            max-height: 280px;
        }
        
        /* RHC Card Styles */
        .rhc-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .rhc-header {
            background: var(--iata-blue);
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .rhc-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .rhc-percentage {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
            color: var(--iata-blue);
        }
        
        .rhc-progress-container {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 12px 0;
        }
        
        .rhc-progress-bar {
            height: 100%;
            border-radius: 5px;
            background: linear-gradient(to right, 
                var(--iata-green) 0%, 
                var(--iata-green) 25%, 
                var(--iata-yellow) 25%, 
                var(--iata-yellow) 50%, 
                var(--iata-orange) 50%, 
                var(--iata-orange) 75%, 
                var(--iata-red) 75%, 
                var(--iata-red) 100%);
        }
        
        .rhc-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .rhc-label {
            color: var(--iata-dark-gray);
        }
        
        .rhc-value {
            font-weight: bold;
            color: var(--iata-blue);
        }
        
        .rhc-payment-info {
            background: #e8f4ff;
            border-left: 3px solid var(--iata-blue);
            padding: 8px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .rhc-update {
            text-align: center;
            color: var(--iata-dark-gray);
            font-size: 11px;
            margin-top: auto;
            padding-top: 10px;
        }
        
        .risk-low { color: var(--iata-green); }
        .risk-medium { color: var(--iata-yellow); }
        .risk-high { color: var(--iata-orange); }
        .risk-critical { color: var(--iata-red); }
        
        /* View All Button Styles */
        .btn-view-all {
            background: transparent;
            border: 1px solid currentColor;
            color: inherit;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-view-all:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }
        
        /* Report Modal Styles */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .report-table th,
        .report-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .report-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .report-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .modal-lg {
            max-width: 95%;
        }
        
        .modal-xl {
            max-width: 90%;
        }
        
        .sales-details {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .sales-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .sales-detail-label {
            font-weight: 500;
            color: #495057;
        }
        
        .sales-detail-value {
            color: #212529;
        }

        /* Bookings Table Styles */
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .bookings-table th,
        .bookings-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .bookings-table th {
            background-color: var(--iata-blue);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .bookings-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-confirmed { color: #28a745; font-weight: bold; }
        .status-cancelled { color: #dc3545; font-weight: bold; }
        .status-completed { color: #17a2b8; font-weight: bold; }
        .status-on-hold { color: #6c757d; font-weight: bold; }
        
        .time-critical {
            background-color: #fff3cd !important;
            color: #856404;
            font-weight: bold;
        }
        
        .btn-action {
            padding: 4px 8px;
            margin: 2px;
            font-size: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-edit:hover {
            background-color: #e0a800;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .btn-add {
            background-color: var(--iata-green);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 15px;
        }
        
        .btn-add:hover {
            background-color: #008a45;
        }
        
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .urgency-badge {
            background-color: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 5px;
        }

        /* Search Bar Styles */
        .search-container {
            margin-bottom: 15px;
        }
        
        .search-input {
            width: 300px;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--iata-blue);
            box-shadow: 0 0 0 2px rgba(0, 51, 160, 0.25);
        }
        
        /* Fare Calculator Styles */
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background-color: var(--iata-blue);
            color: white;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        
        .modal-title {
            font-weight: 600;
            text-align: center;
            width: 100%;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .calculator-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .calculator-title {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: bold;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .base-fare-container {
            text-align: right;
        }
        
        .tax-container {
            text-align: left;
        }
        
        .result-container {
            background-color: #e9f7ef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid #27ae60;
        }
        
        .result-label {
            font-weight: 600;
            color: #27ae60;
        }
        
        .btn-calculate {
            width: 48%;
            margin-top: 25px;
            background-color: #3498db;
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 6px;
            transition: all 0.3s;
            margin-right: 2%;
        }
        
        .btn-clear {
            width: 48%;
            margin-top: 25px;
            background-color: #6c757d;
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 6px;
            transition: all 0.3s;
            margin-left: 2%;
        }
        
        .btn-calculate:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-clear:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            color: #495057;
        }
        
        .tax-column {
            border-right: 1px dashed #dee2e6;
            padding-right: 30px;
        }
        
        .calculation-column {
            padding-left: 30px;
        }
        
        .section-title {
            font-weight: 600;
            color: #3498db;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .form-control {
            border-radius: 5px;
            border: 1px solid #ced4da;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .result-value {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.05rem;
        }
        
        .calculator-buttons {
            display: flex;
            justify-content: space-between;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-controls {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
            }
            
            .filter-box {
                width: 100%;
            }
            
            select {
                width: 100%;
            }
            
            .summary-cards {
                flex-direction: column;
            }
            
            .charts-row {
                flex-direction: column;
            }
            
            .modal-lg {
                max-width: 100%;
                margin: 10px;
            }
            
            .modal-xl {
                max-width: 100%;
                margin: 10px;
            }
            
            .report-table {
                font-size: 12px;
            }
            
            .bookings-table {
                font-size: 12px;
            }
            
            .report-table th,
            .report-table td {
                padding: 6px 8px;
            }
            
            .bookings-table th,
            .bookings-table td {
                padding: 6px 8px;
            }
            
            .search-input {
                width: 100%;
            }
            
            .tax-column {
                border-right: none;
                border-bottom: 1px dashed #dee2e6;
                padding-right: 15px;
                padding-bottom: 30px;
                margin-bottom: 30px;
            }
            
            .calculation-column {
                padding-left: 15px;
            }
            
            .calculator-buttons {
                flex-direction: column;
            }
            
            .btn-calculate, .btn-clear {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php' ?>
    <div class="dashboard-header">
        <h2>Faith Travel and Tours LTD Dashboard</h2>
        <div class="header-controls">
            <button class="btn-bookings" data-bs-toggle="modal" data-bs-target="#bookingsModal">
                <i class="fas fa-ticket-alt"></i> Bookings
                <?php if ($bookingsNotificationCount > 0): ?>
                    <span class="booking-notification-badge"><?php echo $bookingsNotificationCount; ?></span>
                <?php endif; ?>
            </button>
            <button class="btn-fare-calculator" data-bs-toggle="modal" data-bs-target="#fareCalculatorModal">
                <i class="fas fa-calculator"></i> Fare Calculator
            </button>
            <div class="filter-box">
                <label for="salesFilter">Filter by:</label>
                <select id="salesFilter" onchange="updateDashboard()">
                    <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="daily" <?= $filter === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    <option value="total" <?= $filter === 'total' ? 'selected' : '' ?>>Total</option>
                </select>
            </div>
        </div>
    </div>

    <div class="summary-cards">
        <!-- Daily Summary Card -->
        <div class="summary-card card-daily">
            <div class="summary-card-header">
                <span>Daily Report</span>
                <div>
                    <button class="btn-view-all" onclick="viewReport('daily', 'sales')">Sales</button>
                    <button class="btn-view-all" onclick="viewReport('daily', 'purchase')">Purchase</button>
                    <button class="btn-view-all" onclick="viewReport('daily', 'payment')">Payment</button>
                    <button class="btn-view-all" onclick="viewReport('daily', 'collection')">Collection</button>
                    <button class="btn-view-all" onclick="viewReport('daily', 'expense')">Expense</button>
                </div>
            </div>
            <div class="summary-card-body">
                <div class="metric-item">
                    <span>Sale Amount:</span>
                    <span class="metric-value">৳<?= number_format($daily_comprehensive_sales['total_sales'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Collection Amount:</span>
                    <span class="metric-value">৳<?= number_format($daily_comprehensive_sales['total_collection'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Purchase Amount:</span>
                    <span class="metric-value">৳<?= number_format($daily_comprehensive_sales['total_purchase'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Expense Amount:</span>
                    <span class="metric-value">৳<?= number_format($dailyExpenseData['expense_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Payment Amount:</span>
                    <span class="metric-value">৳<?= number_format($dailyPaymentData['payment_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Profit:</span>
                    <span class="metric-value">৳<?= number_format($daily_comprehensive_sales['total_profit'] ?? 0, 2) ?></span>
                </div>
                <!-- NEW FIELDS FROM SALES_RECORD.PHP -->
                <div class="metric-item">
                    <span>Due Amount:</span>
                    <span class="metric-value">৳<?= number_format($daily_comprehensive_sales['total_due'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Reissue Amount:</span>
                    <span class="metric-value">৳<?= number_format($daily_comprehensive_sales['total_reissue'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Refund Amount:</span>
                    <span class="metric-value">৳<?= number_format($daily_comprehensive_sales['total_refund'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Monthly Summary Card -->
        <div class="summary-card card-monthly">
            <div class="summary-card-header">
                <span>Monthly Report</span>
                <div>
                    <button class="btn-view-all" onclick="viewReport('monthly', 'sales')">Sales</button>
                    <button class="btn-view-all" onclick="viewReport('monthly', 'purchase')">Purchase</button>
                    <button class="btn-view-all" onclick="viewReport('monthly', 'payment')">Payment</button>
                    <button class="btn-view-all" onclick="viewReport('monthly', 'collection')">Collection</button>
                    <button class="btn-view-all" onclick="viewReport('monthly', 'expense')">Expense</button>
                </div>
            </div>
            <div class="summary-card-body">
                <div class="metric-item">
                    <span>Sale Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthly_comprehensive_sales['total_sales'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Collection Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthly_comprehensive_sales['total_collection'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Purchase Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthly_comprehensive_sales['total_purchase'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Expense Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthlyExpenseData['expense_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Payment Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthlyPaymentData['payment_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Profit:</span>
                    <span class="metric-value">৳<?= number_format($monthly_comprehensive_sales['total_profit'] ?? 0, 2) ?></span>
                </div>
                <!-- NEW FIELDS FROM SALES_RECORD.PHP -->
                <div class="metric-item">
                    <span>Due Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthly_comprehensive_sales['total_due'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Reissue Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthly_comprehensive_sales['total_reissue'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Refund Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthly_comprehensive_sales['total_refund'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Yearly Summary Card -->
        <div class="summary-card card-yearly">
            <div class="summary-card-header">
                <span>Yearly Report</span>
                <div>
                    <button class="btn-view-all" onclick="viewReport('yearly', 'sales')">Sales</button>
                    <button class="btn-view-all" onclick="viewReport('yearly', 'purchase')">Purchase</button>
                    <button class="btn-view-all" onclick="viewReport('yearly', 'payment')">Payment</button>
                    <button class="btn-view-all" onclick="viewReport('yearly', 'collection')">Collection</button>
                    <button class="btn-view-all" onclick="viewReport('yearly', 'expense')">Expense</button>
                </div>
            </div>
            <div class="summary-card-body">
                <div class="metric-item">
                    <span>Sale Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearly_comprehensive_sales['total_sales'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Collection Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearly_comprehensive_sales['total_collection'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Purchase Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearly_comprehensive_sales['total_purchase'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Expense Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearlyExpenseData['expense_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Payment Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearlyPaymentData['payment_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Profit:</span>
                    <span class="metric-value">৳<?= number_format($yearly_comprehensive_sales['total_profit'] ?? 0, 2) ?></span>
                </div>
                <!-- NEW FIELDS FROM SALES_RECORD.PHP -->
                <div class="metric-item">
                    <span>Due Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearly_comprehensive_sales['total_due'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Reissue Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearly_comprehensive_sales['total_reissue'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Refund Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearly_comprehensive_sales['total_refund'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ALL YOUR EXISTING CHARTS, MODALS, AND FUNCTIONALITY REMAIN EXACTLY THE SAME -->
    <div class="charts-section">
        <!-- First row of charts -->
        <div class="charts-row">
            <!-- RHC Card -->
            <div class="chart-container">
                <div class="rhc-card">
                    <div class="rhc-header">IATA RHC Meter</div>
                    <div class="rhc-body">
                        <div id="rhc-loading" style="text-align: center; padding: 20px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="rhc-content" style="display: none;">
                            <div class="rhc-percentage risk-low" id="rhc-percentage">0%</div>
                            <div style="text-align: center; margin-bottom: 10px; font-size: 13px; color: #666;">Percentage usage</div>
                            
                            <div class="rhc-progress-container">
                                <div class="rhc-progress-bar" id="rhc-progress-bar" style="width: 0%"></div>
                            </div>
                            
                            <div class="rhc-detail">
                                <span class="rhc-label">Current usage</span>
                                <span class="rhc-value" id="rhc-current-usage">BDT 0</span>
                            </div>
                            <div class="rhc-detail">
                                <span class="rhc-label">RHC Limit</span>
                                <span class="rhc-value">BDT 10,000,000</span>
                            </div>
                            <div class="rhc-detail">
                                <span class="rhc-label">Remaining Balance (90% of RHC Amount)</span>
                                <span class="rhc-value" id="rhc-remaining-balance">BDT 9,000,000</span>
                            </div>
                            <div class="rhc-detail">
                                <span class="rhc-label">Paid Amount</span>
                                <span class="rhc-value" id="rhc-paid-amount">BDT 0</span>
                            </div>
                            <div class="rhc-detail">
                                <span class="rhc-label">Fortnight Payment</span>
                                <span class="rhc-value" id="rhc-fortnight-payment">BDT 0</span>
                            </div>
                            
                            <div class="rhc-payment-info">
                                Next Payment Due: <span id="rhc-payment-due-date" style="font-weight: bold;">30 Aug 2025</span><br>
                                <span id="rhc-payment-description">For tickets issued in Fortnight 1</span>
                            </div>
                            
                            <div class="rhc-update">
                                Last Updated: <span id="rhc-last-updated">Tue, 26 Aug 2025</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Pie Chart -->
            <div class="chart-container">
                <h4 class="chart-title"><?= $pieChartTitle ?></h4>
                <canvas id="salesPieChart"></canvas>
            </div>
            
            <!-- Expense Bar Chart -->
            <div class="chart-container">
                <h4 class="chart-title">Expenses by Month</h4>
                <canvas id="expenseBarChart"></canvas>
            </div>
        </div>
        
        <!-- Second row - Full width chart -->
        <div class="chart-container-full">
            <h4 class="chart-title">Monthly Sales vs Profit</h4>
            <canvas id="salesProfitChart"></canvas>
        </div>
    </div>

    <!-- Bookings Modal -->
    <div class="modal fade" id="bookingsModal" tabindex="-1" aria-labelledby="bookingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingsModalLabel">
                        <i class="fas fa-ticket-alt me-2"></i>All Bookings
                        <?php if ($bookingsNotificationCount > 0): ?>
                            <span class="urgency-badge"><?php echo $bookingsNotificationCount; ?> Urgent</span>
                        <?php endif; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button class="btn-add" onclick="window.open('insert_booking.php', '_blank')">
                            <i class="fas fa-plus me-2"></i>Add New Booking
                        </button>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="search-container">
                                <input type="text" id="pnrSearch" class="search-input" placeholder="Search by PNR..." onkeyup="searchBookings()">
                            </div>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshBookings()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="exportBookings()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <div id="bookingsLoading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading bookings...</p>
                        </div>
                        <div id="bookingsContent" style="display: none;">
                            <!-- Bookings content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fare Calculator Modal -->
    <div class="modal fade" id="fareCalculatorModal" tabindex="-1" aria-labelledby="fareCalculatorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fareCalculatorModalLabel">Fare Calculator</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="calculator-container">
                        <div class="row">
                            <!-- Left Column - Tax Inputs -->
                            <div class="col-md-6 tax-column">
                                <h5 class="section-title">Tax Details</h5>
                                
                                <form id="fareCalculatorForm">
                                    <!-- Base Fare -->
                                    <div class="row mb-3">
                                        <div class="col-md-6 base-fare-container">
                                            <label for="baseFare" class="form-label">Base Fare</label>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="baseFare" step="0.01" min="0" value="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Commission -->
                                    <div class="row mb-3">
                                        <div class="col-md-6 base-fare-container">
                                            <label for="commission" class="form-label">Commission (%)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="commission" step="0.01" min="0" max="100" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Tax Fields -->
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="bd" class="form-label">BD (Embarkation Fee)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="bd" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="ut" class="form-label">UT (Travel Tax)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="ut" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="ow" class="form-label">OW (Excise Duty Tax)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="ow" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="e5" class="form-label">E5 (Value Added Tax on Embarkation Fees)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="e5" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="gb" class="form-label">GB (Air Passenger Duty)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="gb" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="ub" class="form-label">UB (Passenger Service Charge)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="ub" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="yr" class="form-label">YR (Fuel Charges)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="yr" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="p7" class="form-label">P7 (P7)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="p7" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6 tax-container">
                                            <label for="p8" class="form-label">P8 (Passenger Security Fee)</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" class="form-control tax-input" id="p8" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Right Column - Calculations -->
                            <div class="col-md-6 calculation-column">
                                <h5 class="section-title">Calculations</h5>
                                
                                <!-- Calculation Results -->
                                <div class="result-container">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <span class="result-label">Total Tax:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <span id="totalTax" class="result-value">0.00</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <span class="result-label">Total Fare:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <span id="totalFare" class="result-value">0.00</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <span class="result-label">Commission:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <span id="commissionAmount" class="result-value">0.00</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <span class="result-label">AIT (0.3%):</span>
                                        </div>
                                        <div class="col-md-6">
                                            <span id="ait" class="result-value">0.00</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <span class="result-label">Net Payment:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <span id="netPayment" class="result-value">0.00</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="calculator-buttons">
                                    <button type="button" class="btn btn-primary btn-calculate" id="calculateBtn">Calculate</button>
                                    <button type="button" class="btn btn-secondary btn-clear" id="clearBtn">Clear</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">Report Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="reportContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ALL YOUR EXISTING JAVASCRIPT FUNCTIONS REMAIN EXACTLY THE SAME
        function updateDashboard() {
            const filter = document.getElementById('salesFilter').value;
            window.location.href = 'dashboard.php?filter=' + filter;
        }

        // Bookings functionality
        function loadBookings() {
            fetch('get_bookings.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('bookingsLoading').style.display = 'none';
                    document.getElementById('bookingsContent').style.display = 'block';
                    document.getElementById('bookingsContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('bookingsLoading').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading bookings: ${error}
                        </div>
                    `;
                });
        }

        function refreshBookings() {
            document.getElementById('bookingsLoading').style.display = 'block';
            document.getElementById('bookingsContent').style.display = 'none';
            document.getElementById('pnrSearch').value = ''; // Clear search
            loadBookings();
        }

        function searchBookings() {
            const searchTerm = document.getElementById('pnrSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.bookings-table tbody tr');
            
            rows.forEach(row => {
                const pnrCell = row.cells[9]; // PNR is in the 10th column (index 9)
                if (pnrCell) {
                    const pnrText = pnrCell.textContent.toLowerCase();
                    if (pnrText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        function exportBookings() {
            // Simple CSV export functionality
            const table = document.querySelector('.bookings-table');
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length - 1; j++) { // Exclude action column
                    row.push(cols[j].innerText);
                }
                
                csv.push(row.join(','));
            }
            
            // Download CSV file
            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');
            downloadLink.download = 'bookings_export.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function editBooking(bookingId) {
            // Open edit page in new tab
            window.open(`edit_booking.php?id=${bookingId}`, '_blank');
        }

        function deleteBooking(bookingId) {
            if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
                fetch(`delete_booking.php?id=${bookingId}`, {
                    method: 'DELETE',
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking deleted successfully!');
                        refreshBookings();
                    } else {
                        alert('Error deleting booking: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error deleting booking: ' + error);
                });
            }
        }

        // Load bookings when modal is shown
        document.getElementById('bookingsModal').addEventListener('show.bs.modal', function() {
            loadBookings();
        });

        // RHC Card functionality
        function fetchRHCData() {
            fetch('get_rhc_data1.php')
                .then(response => response.json())
                .then(data => {
                    updateRHCData(data);
                })
                .catch(error => {
                    console.error('Error fetching RHC data:', error);
                    // Fallback to static data if server fails
                    updateRHCData({
                        currentUsage: 1602911,
                        paidThisMonth: 884009,
                        fortnightPayment: 4500000,
                        paymentDueDate: "30 Aug 2025",
                        paymentDescription: "For tickets issued in Fortnight 1"
                    });
                });
        }
        
        function updateRHCData(data) {
            // Hide loading, show content
            document.getElementById('rhc-loading').style.display = 'none';
            document.getElementById('rhc-content').style.display = 'block';
            
            // Constants
            const RHC_LIMIT = 10000000;
            const RHC_LIMITP = RHC_LIMIT * 0.90;
            
            // Calculate percentage based on current usage (capped at 90%)
            const percentage = Math.min(90, (data.currentUsage / RHC_LIMIT * 100)).toFixed(0);
            const remainingBalance = Math.max(0, RHC_LIMITP - data.currentUsage);
            
            // Format currency function
            function formatCurrency(amount) {
                return 'BDT ' + amount.toLocaleString('en-IN');
            }
            
            // Update the display
            document.getElementById('rhc-percentage').textContent = percentage + '%';
            document.getElementById('rhc-current-usage').textContent = formatCurrency(data.currentUsage);
            document.getElementById('rhc-remaining-balance').textContent = formatCurrency(remainingBalance);
            document.getElementById('rhc-paid-amount').textContent = formatCurrency(data.paidThisMonth);
            document.getElementById('rhc-fortnight-payment').textContent = formatCurrency(data.fortnightPayment);
            document.getElementById('rhc-payment-due-date').textContent = data.paymentDueDate;
            document.getElementById('rhc-payment-description').textContent = data.paymentDescription;
            
            // Update progress bar (capped at 90%)
            const progressPercentage = Math.min(90, percentage);
            document.getElementById('rhc-progress-bar').style.width = progressPercentage + '%';
            
            // Update risk color based on percentage
            const percentageElement = document.getElementById('rhc-percentage');
            percentageElement.className = 'rhc-percentage '; // Reset classes
            
            if (percentage < 25) {
                percentageElement.classList.add('risk-low');
            } else if (percentage < 50) {
                percentageElement.classList.add('risk-medium');
            } else if (percentage < 75) {
                percentageElement.classList.add('risk-high');
            } else {
                percentageElement.classList.add('risk-critical');
            }
            
            // Update last updated time
            const now = new Date();
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            const day = days[now.getDay()];
            const dateNum = now.getDate();
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            
            document.getElementById('rhc-last-updated').textContent = 
                `${day}, ${dateNum} ${month} ${year}`;
        }
        
        // Initial RHC data fetch
        fetchRHCData();

        // View Report functionality
        function viewReport(period, type) {
            // Show loading state
            document.getElementById('reportContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading ${type} report...</p>
                </div>
            `;
            
            // Create and show modal
            const modal = new bootstrap.Modal(document.getElementById('reportModal'));
            document.getElementById('reportModalLabel').textContent = `${period.charAt(0).toUpperCase() + period.slice(1)} ${type.charAt(0).toUpperCase() + type.slice(1)} Report`;
            modal.show();
            
            // Fetch data via AJAX
            fetch(`get_report_data.php?period=${period}&type=${type}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('reportContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('reportContent').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading report: ${error}
                        </div>
                    `;
                });
        }

        // Fare Calculator functionality
        document.getElementById('calculateBtn').addEventListener('click', function() {
            calculateFare();
        });
        
        // Clear button functionality
        document.getElementById('clearBtn').addEventListener('click', function() {
            clearCalculator();
        });
        
        // Add event listeners to all input fields to recalculate when values change
        document.querySelectorAll('#fareCalculatorForm input').forEach(input => {
            input.addEventListener('input', calculateFare);
        });
        
        function formatNumber(num) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        }
        
        function calculateFare() {
            // Get base fare value
            const baseFare = parseFloat(document.getElementById('baseFare').value) || 0;
            
            // Get commission percentage
            const commissionPercentage = parseFloat(document.getElementById('commission').value) || 0;
            
            // Calculate commission amount
            const commissionAmount = baseFare * (commissionPercentage / 100);
            
            // Get all tax values
            const bd = parseFloat(document.getElementById('bd').value) || 0;
            const ut = parseFloat(document.getElementById('ut').value) || 0;
            const ow = parseFloat(document.getElementById('ow').value) || 0;
            const e5 = parseFloat(document.getElementById('e5').value) || 0;
            const gb = parseFloat(document.getElementById('gb').value) || 0;
            const ub = parseFloat(document.getElementById('ub').value) || 0;
            const yr = parseFloat(document.getElementById('yr').value) || 0;
            const p7 = parseFloat(document.getElementById('p7').value) || 0;
            const p8 = parseFloat(document.getElementById('p8').value) || 0;
            
            // Calculate total tax
            const totalTax = bd + ut + ow + e5 + gb + ub + yr + p7 + p8;
            
            // Calculate total fare
            const totalFare = baseFare + totalTax;
            
            // Calculate AIT
            const ait = (totalFare - (bd + ut + e5)) * 0.003;
            
            // Calculate net payment
            const netPayment = (baseFare - commissionAmount) + totalTax + ait;
            
            // Update the display with calculated values
            document.getElementById('totalTax').textContent = formatNumber(totalTax);
            document.getElementById('totalFare').textContent = formatNumber(totalFare);
            document.getElementById('commissionAmount').textContent = formatNumber(commissionAmount);
            document.getElementById('ait').textContent = formatNumber(ait);
            document.getElementById('netPayment').textContent = formatNumber(netPayment);
        }
        
        function clearCalculator() {
            // Clear all input fields
            document.getElementById('baseFare').value = '';
            document.getElementById('commission').value = '';
            document.getElementById('bd').value = '';
            document.getElementById('ut').value = '';
            document.getElementById('ow').value = '';
            document.getElementById('e5').value = '';
            document.getElementById('gb').value = '';
            document.getElementById('ub').value = '';
            document.getElementById('yr').value = '';
            document.getElementById('p7').value = '';
            document.getElementById('p8').value = '';
            
            // Reset result values to 0
            document.getElementById('totalTax').textContent = '0.00';
            document.getElementById('totalFare').textContent = '0.00';
            document.getElementById('commissionAmount').textContent = '0.00';
            document.getElementById('ait').textContent = '0.00';
            document.getElementById('netPayment').textContent = '0.00';
        }
        
        // Initialize calculator with empty values when modal is shown
        document.getElementById('fareCalculatorModal').addEventListener('show.bs.modal', function() {
            clearCalculator();
        });

        // Charts
        const salesPieCtx = document.getElementById('salesPieChart').getContext('2d');
        new Chart(salesPieCtx, {
            type: 'pie',
            data: {
                labels: ['Agent', 'Counter', 'Corporate'],
                datasets: [{
                    data: [<?= $salesData['Agent'] ?>, <?= $salesData['Counter'] ?>, <?= $salesData['Corporate'] ?>],
                    backgroundColor: [
                        'rgba(8, 180, 42, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgb(255, 64, 0)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        const expenseBarCtx = document.getElementById('expenseBarChart').getContext('2d');
        new Chart(expenseBarCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($expenseMonths) ?>,
                datasets: [{
                    label: 'Expenses',
                    data: <?= json_encode($expenseTotals) ?>,
                    backgroundColor: 'rgb(0, 64, 255)',
                    borderColor: 'rgb(99, 255, 255)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // New chart for monthly sales vs profit
        const salesProfitCtx = document.getElementById('salesProfitChart').getContext('2d');
        new Chart(salesProfitCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthlyLabels) ?>,
                datasets: [
                    {
                        label: 'Sales',
                        data: <?= json_encode($monthlySales) ?>,
                        backgroundColor: 'rgba(0, 255, 89, 0.66)',
                        borderColor: 'rgba(0, 255, 89, 0.66)',
                        borderWidth: 1
                    },
                    {
                        label: 'Profit',
                        data: <?= json_encode($monthlyProfit) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 0.7)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>