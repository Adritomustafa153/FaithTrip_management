<?php
include('db.php');
require_once 'auth_check.php';

$filter = $_GET['filter'] ?? 'monthly';

// ======================= COMPREHENSIVE SALES FUNCTION (ALL TABLES) =======================
function calculateComprehensiveSales($conn, $startDate, $endDate, $periodType = 'custom') {
    $endDateNext = date('Y-m-d', strtotime($endDate . ' +1 day'));

    $result = [
        'total_sales' => 0,
        'total_purchase' => 0,
        'total_profit' => 0,
        'total_due' => 0,
        'total_reissue' => 0,
        'total_refund' => 0,
        'total_collection' => 0,
        'category_sales' => [
            'ticket' => 0, 'visa' => 0, 'student_visa' => 0, 'umrah' => 0, 'hotel' => 0
        ]
    ];

    // ---- AIR TICKETS (sales table) ----
    if ($periodType === 'daily') {
        // Use MySQL's CURDATE() to match the daily counter and avoid timezone issues
        $sales_sql = "SELECT 
                        COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN 0 WHEN Remarks = 'Reissue' THEN BillAmount ELSE BillAmount END),0) as total_sales,
                        COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN 0 ELSE NetPayment END),0) as total_net,
                        COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN 0 ELSE Profit END),0) as total_profit,
                        COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN BillAmount ELSE 0 END),0) as total_refund,
                        COALESCE(SUM(CASE WHEN Remarks = 'Reissue' THEN BillAmount ELSE 0 END),0) as total_reissue,
                        COALESCE(SUM(CASE WHEN PaymentStatus IN ('Due','Partially Paid') THEN DueAmount ELSE 0 END),0) as total_due
                    FROM sales 
                    WHERE DATE(IssueDate) = CURDATE()";
    } else {
        $sales_sql = "SELECT 
                        COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN 0 WHEN Remarks = 'Reissue' THEN BillAmount ELSE BillAmount END),0) as total_sales,
                        COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN 0 ELSE NetPayment END),0) as total_net,
                        COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN 0 ELSE Profit END),0) as total_profit,
                        COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN BillAmount ELSE 0 END),0) as total_refund,
                        COALESCE(SUM(CASE WHEN Remarks = 'Reissue' THEN BillAmount ELSE 0 END),0) as total_reissue,
                        COALESCE(SUM(CASE WHEN PaymentStatus IN ('Due','Partially Paid') THEN DueAmount ELSE 0 END),0) as total_due
                    FROM sales 
                    WHERE IssueDate >= '$startDate' AND IssueDate < '$endDateNext'";
    }
    $res = $conn->query($sales_sql);
    if ($res && $row = $res->fetch_assoc()) {
        $result['total_sales'] += $row['total_sales'];
        $result['total_profit'] += $row['total_profit'];
        $result['total_refund'] += $row['total_refund'];
        $result['total_reissue'] += $row['total_reissue'];
        $result['total_due'] += $row['total_due'];
        $result['category_sales']['ticket'] = $row['total_sales'];

        // Purchase for tickets (non-IATA, not refund) – also use CURDATE() for daily
        if ($periodType === 'daily') {
            $purchase_sql = "SELECT COALESCE(SUM(NetPayment),0) as total_net FROM sales 
                             WHERE DATE(IssueDate) = CURDATE()
                             AND Source != 'IATA' AND Source IS NOT NULL AND Source != '' AND Remarks != 'Refund'";
        } else {
            $purchase_sql = "SELECT COALESCE(SUM(NetPayment),0) as total_net FROM sales 
                             WHERE IssueDate >= '$startDate' AND IssueDate < '$endDateNext'
                             AND Source != 'IATA' AND Source IS NOT NULL AND Source != '' AND Remarks != 'Refund'";
        }
        $pres = $conn->query($purchase_sql);
        if ($pres && $prow = $pres->fetch_assoc()) $result['total_purchase'] += $prow['total_net'];
    }

    // ---- COLLECTION from payments table ----
    // For daily, also use CURDATE() for consistency (optional, but safe)
    if ($periodType === 'daily') {
        $col_sql = "SELECT COALESCE(SUM(Amount),0) as total_collection FROM payments 
                    WHERE DATE(PaymentDate) = CURDATE()";
    } else {
        $col_sql = "SELECT COALESCE(SUM(Amount),0) as total_collection FROM payments 
                    WHERE PaymentDate >= '$startDate' AND PaymentDate < '$endDateNext'";
    }
    $col_res = $conn->query($col_sql);
    if ($col_res && $col_row = $col_res->fetch_assoc()) $result['total_collection'] += $col_row['total_collection'];

    // ---- HOTEL (use same pattern: for daily, use DATE(issue_date) = CURDATE()) ----
    if ($periodType === 'daily') {
        $hotel_sql = "SELECT COALESCE(SUM(selling_price),0) as total_sales, COALESCE(SUM(net_price),0) as total_net,
                       COALESCE(SUM(profit),0) as total_profit, COALESCE(SUM(refund_to_client),0) as total_refund,
                       COALESCE(SUM(CASE WHEN payment_status IN ('Due','Partially Paid') THEN due_amount ELSE 0 END),0) as total_due
                FROM hotel WHERE DATE(issue_date) = CURDATE()";
    } else {
        $hotel_sql = "SELECT COALESCE(SUM(selling_price),0) as total_sales, COALESCE(SUM(net_price),0) as total_net,
                       COALESCE(SUM(profit),0) as total_profit, COALESCE(SUM(refund_to_client),0) as total_refund,
                       COALESCE(SUM(CASE WHEN payment_status IN ('Due','Partially Paid') THEN due_amount ELSE 0 END),0) as total_due
                FROM hotel WHERE issue_date >= '$startDate' AND issue_date < '$endDateNext'";
    }
    $res = $conn->query($hotel_sql);
    if ($res && $row = $res->fetch_assoc()) {
        $hotel_sales = $row['total_sales'] - $row['total_refund'];
        $result['total_sales'] += $hotel_sales;
        $result['total_profit'] += $row['total_profit'];
        $result['total_refund'] += $row['total_refund'];
        $result['total_due'] += $row['total_due'];
        $result['category_sales']['hotel'] = $hotel_sales;

        if ($periodType === 'daily') {
            $purchase_sql = "SELECT COALESCE(SUM(net_price),0) as total_net FROM hotel 
                             WHERE DATE(issue_date) = CURDATE()
                             AND source != 'OWN' AND source IS NOT NULL AND source != ''";
        } else {
            $purchase_sql = "SELECT COALESCE(SUM(net_price),0) as total_net FROM hotel 
                             WHERE issue_date >= '$startDate' AND issue_date < '$endDateNext'
                             AND source != 'OWN' AND source IS NOT NULL AND source != ''";
        }
        $pres = $conn->query($purchase_sql);
        if ($pres && $prow = $pres->fetch_assoc()) $result['total_purchase'] += $prow['total_net'];
    }

    // ---- STUDENT VISA (same pattern) ----
    if ($periodType === 'daily') {
        $student_sql = "SELECT COALESCE(SUM(Selling),0) as total_sales, COALESCE(SUM(net),0) as total_net,
                       COALESCE(SUM(profit),0) as total_profit, COALESCE(SUM(refund_to_client),0) as total_refund,
                       COALESCE(SUM(CASE WHEN payment_status IN ('Due','Partially Paid') THEN due ELSE 0 END),0) as total_due
                FROM student WHERE DATE(`received date`) = CURDATE()";
    } else {
        $student_sql = "SELECT COALESCE(SUM(Selling),0) as total_sales, COALESCE(SUM(net),0) as total_net,
                       COALESCE(SUM(profit),0) as total_profit, COALESCE(SUM(refund_to_client),0) as total_refund,
                       COALESCE(SUM(CASE WHEN payment_status IN ('Due','Partially Paid') THEN due ELSE 0 END),0) as total_due
                FROM student WHERE `received date` >= '$startDate' AND `received date` < '$endDateNext'";
    }
    $res = $conn->query($student_sql);
    if ($res && $row = $res->fetch_assoc()) {
        $student_sales = $row['total_sales'] - $row['total_refund'];
        $result['total_sales'] += $student_sales;
        $result['total_profit'] += $row['total_profit'];
        $result['total_refund'] += $row['total_refund'];
        $result['total_due'] += $row['total_due'];
        $result['category_sales']['student_visa'] = $student_sales;

        if ($periodType === 'daily') {
            $purchase_sql = "SELECT COALESCE(SUM(net),0) as total_net FROM student 
                             WHERE DATE(`received date`) = CURDATE()
                             AND source != 'OWN' AND source IS NOT NULL AND source != ''";
        } else {
            $purchase_sql = "SELECT COALESCE(SUM(net),0) as total_net FROM student 
                             WHERE `received date` >= '$startDate' AND `received date` < '$endDateNext'
                             AND source != 'OWN' AND source IS NOT NULL AND source != ''";
        }
        $pres = $conn->query($purchase_sql);
        if ($pres && $prow = $pres->fetch_assoc()) $result['total_purchase'] += $prow['total_net'];
    }

    // ---- UMRAH (same pattern) ----
    if ($periodType === 'daily') {
        $umrah_sql = "SELECT COALESCE(SUM(`selling price`),0) as total_sales, COALESCE(SUM(`net payment`),0) as total_net,
                       COALESCE(SUM(profit),0) as total_profit, COALESCE(SUM(`refund to client`),0) as total_refund,
                       COALESCE(SUM(CASE WHEN payment_status IN ('Due','Partially Paid') THEN due ELSE 0 END),0) as total_due
                FROM umrah WHERE DATE(orderdate) = CURDATE()";
    } else {
        $umrah_sql = "SELECT COALESCE(SUM(`selling price`),0) as total_sales, COALESCE(SUM(`net payment`),0) as total_net,
                       COALESCE(SUM(profit),0) as total_profit, COALESCE(SUM(`refund to client`),0) as total_refund,
                       COALESCE(SUM(CASE WHEN payment_status IN ('Due','Partially Paid') THEN due ELSE 0 END),0) as total_due
                FROM umrah WHERE orderdate >= '$startDate' AND orderdate < '$endDateNext'";
    }
    $res = $conn->query($umrah_sql);
    if ($res && $row = $res->fetch_assoc()) {
        $umrah_sales = $row['total_sales'] - $row['total_refund'];
        $result['total_sales'] += $umrah_sales;
        $result['total_profit'] += $row['total_profit'];
        $result['total_refund'] += $row['total_refund'];
        $result['total_due'] += $row['total_due'];
        $result['category_sales']['umrah'] = $umrah_sales;

        if ($periodType === 'daily') {
            $purchase_sql = "SELECT COALESCE(SUM(`net payment`),0) as total_net FROM umrah 
                             WHERE DATE(orderdate) = CURDATE()
                             AND source != 'OWN' AND source IS NOT NULL AND source != ''";
        } else {
            $purchase_sql = "SELECT COALESCE(SUM(`net payment`),0) as total_net FROM umrah 
                             WHERE orderdate >= '$startDate' AND orderdate < '$endDateNext'
                             AND source != 'OWN' AND source IS NOT NULL AND source != ''";
        }
        $pres = $conn->query($purchase_sql);
        if ($pres && $prow = $pres->fetch_assoc()) $result['total_purchase'] += $prow['total_net'];
    }

    // ---- VISA (same pattern) ----
    if ($periodType === 'daily') {
        $visa_sql = "SELECT COALESCE(SUM(`selling price`),0) as total_sales, COALESCE(SUM(`Net Payment`),0) as total_net,
                       COALESCE(SUM(profit),0) as total_profit, COALESCE(SUM(`refund to client`),0) as total_refund,
                       COALESCE(SUM(CASE WHEN payment_status IN ('Due','Partially Paid') THEN due ELSE 0 END),0) as total_due
                FROM visa WHERE DATE(orderdate) = CURDATE()";
    } else {
        $visa_sql = "SELECT COALESCE(SUM(`selling price`),0) as total_sales, COALESCE(SUM(`Net Payment`),0) as total_net,
                       COALESCE(SUM(profit),0) as total_profit, COALESCE(SUM(`refund to client`),0) as total_refund,
                       COALESCE(SUM(CASE WHEN payment_status IN ('Due','Partially Paid') THEN due ELSE 0 END),0) as total_due
                FROM visa WHERE orderdate >= '$startDate' AND orderdate < '$endDateNext'";
    }
    $res = $conn->query($visa_sql);
    if ($res && $row = $res->fetch_assoc()) {
        $visa_sales = $row['total_sales'] - $row['total_refund'];
        $result['total_sales'] += $visa_sales;
        $result['total_profit'] += $row['total_profit'];
        $result['total_refund'] += $row['total_refund'];
        $result['total_due'] += $row['total_due'];
        $result['category_sales']['visa'] = $visa_sales;

        if ($periodType === 'daily') {
            $purchase_sql = "SELECT COALESCE(SUM(`Net Payment`),0) as total_net FROM visa 
                             WHERE DATE(orderdate) = CURDATE()
                             AND source != 'OWN' AND source IS NOT NULL AND source != ''";
        } else {
            $purchase_sql = "SELECT COALESCE(SUM(`Net Payment`),0) as total_net FROM visa 
                             WHERE orderdate >= '$startDate' AND orderdate < '$endDateNext'
                             AND source != 'OWN' AND source IS NOT NULL AND source != ''";
        }
        $pres = $conn->query($purchase_sql);
        if ($pres && $prow = $pres->fetch_assoc()) $result['total_purchase'] += $prow['total_net'];
    }

    return $result;
}

// ======================= EXPENSE & PAYMENT DATA (unchanged) =======================
$dailyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE DATE(expense_date) = CURDATE()";
$dailyExpenseResult = mysqli_query($conn, $dailyExpenseQuery);
$dailyExpenseData = mysqli_fetch_assoc($dailyExpenseResult);
$dailyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE DATE(payment_date) = CURDATE()";
$dailyPaymentResult = mysqli_query($conn, $dailyPaymentQuery);
$dailyPaymentData = mysqli_fetch_assoc($dailyPaymentResult);

$monthlyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
$monthlyExpenseResult = mysqli_query($conn, $monthlyExpenseQuery);
$monthlyExpenseData = mysqli_fetch_assoc($monthlyExpenseResult);
$monthlyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())";
$monthlyPaymentResult = mysqli_query($conn, $monthlyPaymentQuery);
$monthlyPaymentData = mysqli_fetch_assoc($monthlyPaymentResult);

$yearlyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE())";
$yearlyExpenseResult = mysqli_query($conn, $yearlyExpenseQuery);
$yearlyExpenseData = mysqli_fetch_assoc($yearlyExpenseResult);
$yearlyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE YEAR(payment_date) = YEAR(CURDATE())";
$yearlyPaymentResult = mysqli_query($conn, $yearlyPaymentQuery);
$yearlyPaymentData = mysqli_fetch_assoc($yearlyPaymentResult);

// ======================= DAILY SALES COUNTER =======================
$dailySalesCountQuery = "SELECT COUNT(*) as ticket_count FROM sales WHERE DATE(IssueDate) = CURDATE()";
$dailySalesCountResult = mysqli_query($conn, $dailySalesCountQuery);
$dailySalesCountData = mysqli_fetch_assoc($dailySalesCountResult);
$dailySalesCount = $dailySalesCountData['ticket_count'] ?? 0;

// ======================= COMPREHENSIVE SALES =======================
$current_date = date('Y-m-d');
$current_year = date('Y');
$daily = calculateComprehensiveSales($conn, $current_date, $current_date, 'daily');
$monthly = calculateComprehensiveSales($conn, date('Y-m-01'), date('Y-m-t'));
$yearly = calculateComprehensiveSales($conn, "$current_year-01-01", "$current_year-12-31");

// ======================= PIE CHART (Sales by Section) =======================
if ($filter === 'daily') {
    $salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales WHERE DATE(IssueDate) = CURDATE() GROUP BY section";
    $pieChartTitle = "Sales by Section (Daily)";
} elseif ($filter === 'yearly') {
    $salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales WHERE YEAR(IssueDate) = YEAR(CURDATE()) GROUP BY section";
    $pieChartTitle = "Sales by Section (Yearly)";
} elseif ($filter === 'total') {
    $salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales GROUP BY section";
    $pieChartTitle = "Sales by Section (Total)";
} else {
    $salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales WHERE MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE()) GROUP BY section";
    $pieChartTitle = "Sales by Section (Monthly)";
}
$salesResult = mysqli_query($conn, $salesQuery);
$salesData = ['Agent' => 0, 'Counter' => 0, 'Corporate' => 0];
while ($row = mysqli_fetch_assoc($salesResult)) {
    $key = ucfirst(strtolower($row['section']));
    if (array_key_exists($key, $salesData)) $salesData[$key] = (float)$row['total'];
}

// ======================= EXPENSE BAR CHART =======================
$expenseQuery = "SELECT DATE_FORMAT(expense_date, '%b') AS month, SUM(amount) AS total FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE()) GROUP BY month ORDER BY MIN(expense_date)";
$expenseResult = mysqli_query($conn, $expenseQuery);
$expenseMonths = []; $expenseTotals = [];
while ($row = mysqli_fetch_assoc($expenseResult)) {
    $expenseMonths[] = $row['month'];
    $expenseTotals[] = (float)$row['total'];
}

// ======================= MONTHLY SALES VS PROFIT (ALL TABLES) =======================
$monthlyLabels = []; $monthlySales = []; $monthlyProfit = [];
for ($m = 1; $m <= 12; $m++) {
    $monthStart = date("Y-$m-01");
    $monthEnd   = date("Y-m-t", strtotime($monthStart));
    $data = calculateComprehensiveSales($conn, $monthStart, $monthEnd);
    $monthlyLabels[] = date('M', strtotime($monthStart));
    $monthlySales[]  = $data['total_sales'];
    $monthlyProfit[] = $data['total_profit'];
}

// ======================= BOOKINGS NOTIFICATION =======================
$currentDateTime = date('Y-m-d H:i:s');
$notificationTimeLimit = date('Y-m-d H:i:s', strtotime('+30 minutes'));
$bookingsNotificationQuery = "SELECT COUNT(*) as notification_count FROM bookings WHERE time_limit BETWEEN ? AND ? AND status NOT IN ('Cancelled', 'Completed')";
$stmt = mysqli_prepare($conn, $bookingsNotificationQuery);
mysqli_stmt_bind_param($stmt, "ss", $currentDateTime, $notificationTimeLimit);
mysqli_stmt_execute($stmt);
$bookingsNotificationResult = mysqli_stmt_get_result($stmt);
$bookingsNotificationData = mysqli_fetch_assoc($bookingsNotificationResult);
mysqli_stmt_close($stmt);
$bookingsNotificationCount = $bookingsNotificationData['notification_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.jpg">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== MODERN COMPACT DESIGN (unchanged, same as previous) ========== */
        @import url('https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f0f4fa 0%, #e9eef4 100%);
            min-height: 100vh;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 1.8rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .dashboard-header h2 {
            font-weight: 800;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.3px;
            font-size: 1.5rem;
        }
        .header-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .daily-counter {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .btn-bookings, .btn-fare-calculator {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border: none;
            border-radius: 40px;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
            transition: all 0.25s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .btn-bookings:hover, .btn-fare-calculator:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.1);
        }
        .filter-box select {
            border-radius: 40px;
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            border: 1px solid #cddae9;
            background: white;
            font-weight: 500;
            cursor: pointer;
        }
        /* Summary Cards */
        .summary-cards {
            display: flex;
            gap: 1.5rem;
            padding: 0 1.5rem;
            margin-bottom: 1.8rem;
            flex-wrap: wrap;
        }
        .summary-card {
            flex: 1;
            min-width: 300px;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px rgba(0,0,0,0.08);
        }
        .summary-card-header {
            padding: 0.8rem 1.2rem;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.02);
            border-bottom: 1px solid #eff3f8;
        }
        .card-daily .summary-card-header { background: linear-gradient(120deg,#eef5ff, white); color:#1e4a76; }
        .card-monthly .summary-card-header { background: linear-gradient(120deg,#f5efff, white); color:#6b46c1; }
        .card-yearly .summary-card-header { background: linear-gradient(120deg,#e6f7ec, white); color:#2e7d32; }
        .summary-card-body {
            padding: 0.8rem 1.2rem;
        }
        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.35rem 0;
            border-bottom: 1px solid #f0f4fa;
            font-size: 0.75rem;
        }
        .metric-value {
            font-weight: 700;
            font-family: monospace;
            font-size: 0.8rem;
            background: #f8fafc;
            padding: 0.1rem 0.5rem;
            border-radius: 20px;
            color: #1e293b;
        }
        .category-group {
            margin-top: 0.5rem;
            padding-top: 0.4rem;
            border-top: 1px dashed #e2e8f0;
        }
        .category-title {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4b5563;
            margin-bottom: 0.3rem;
        }
        .btn-view-all {
            background: rgba(0,0,0,0.04);
            border: none;
            border-radius: 30px;
            padding: 0.2rem 0.7rem;
            font-size: 0.65rem;
            font-weight: 600;
            transition: 0.2s;
            color: #334155;
        }
        .btn-view-all:hover {
            background: rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        /* Charts */
        .charts-section {
            padding: 0 1.5rem;
        }
        .charts-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .chart-container, .chart-container-full {
            background: white;
            border-radius: 24px;
            padding: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
        }
        .chart-container {
            flex: 1;
            min-width: 260px;
        }
        .chart-container-full {
            width: 100%;
            margin-bottom: 1.5rem;
        }
        .chart-title {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 0.8rem;
            border-left: 4px solid #2a5298;
            padding-left: 0.8rem;
            color: #0f172a;
        }
        canvas {
            max-height: 260px;
            width: 100% !important;
        }
        /* RHC Card */
        .rhc-card { background: white; border-radius: 20px; overflow: hidden; }
        .rhc-header { background: linear-gradient(135deg,#1e3c72,#2a5298); color: white; padding: 0.8rem; text-align: center; font-weight: 700; font-size: 0.9rem; }
        .rhc-body { padding: 1rem; }
        .rhc-percentage { text-align: center; font-size: 28px; font-weight: 800; }
        .rhc-progress-container { height: 8px; background: #e2e8f0; border-radius: 10px; margin: 12px 0; }
        .rhc-progress-bar { height: 100%; background: linear-gradient(90deg, #10b981, #f59e0b, #ef4444); border-radius: 10px; }
        .rhc-detail { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.7rem; }
        .rhc-payment-info { background: #f1f5f9; border-left: 3px solid #2a5298; padding: 0.6rem; border-radius: 12px; margin-top: 10px; font-size: 0.7rem; }
        /* Bookings table enhanced */
        .bookings-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.8rem; }
        .bookings-table th { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 0.8rem 0.6rem; font-weight: 600; position: sticky; top: 0; }
        .bookings-table td { padding: 0.7rem 0.6rem; border-bottom: 1px solid #e2e8f0; }
        .bookings-table tbody tr:hover { background: #f8fafc; transform: scale(1.01); }
        .bookings-table tbody tr:nth-child(even) { background: #f9f9fc; }
        .status-pending, .status-confirmed, .status-cancelled, .status-completed { padding: 0.2rem 0.5rem; border-radius: 30px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        .btn-action { padding: 0.3rem 0.7rem; margin: 0 0.2rem; font-size: 0.7rem; border-radius: 30px; }
        .btn-edit { background: #f59e0b; color: white; border: none; }
        .btn-delete { background: #ef4444; color: white; border: none; }
        .urgency-badge { background: #dc2626; border-radius: 40px; padding: 0.2rem 0.6rem; font-size: 0.7rem; margin-left: 8px; }
        /* Enhanced Report Modal Styles */
        .report-modal-table-container {
            overflow-x: auto;
            border-radius: 16px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .report-table th {
            background: #f8fafc;
            color: #1e293b;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        .report-table td {
            padding: 0.65rem 1rem;
            border-bottom: 1px solid #eef2f6;
            vertical-align: middle;
        }
        .report-table tbody tr:hover {
            background: #fef9e8;
            transition: 0.1s;
        }
        .report-table tbody tr:nth-child(even) {
            background-color: #fafcff;
        }
        .text-right {
            text-align: right;
        }
        .report-table tfoot tr {
            background: #f1f5f9;
            font-weight: 600;
            border-top: 2px solid #cbd5e1;
        }
        .report-table tfoot td {
            padding: 0.75rem 1rem;
        }
        .alert-info {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            border-radius: 12px;
        }
        @media (max-width: 768px) {
            .summary-cards { flex-direction: column; padding: 0 1rem; }
            .charts-row { flex-direction: column; }
            .dashboard-header { flex-direction: column; gap: 0.8rem; text-align: center; }
            .header-controls { flex-wrap: wrap; justify-content: center; }
            .report-table th, .report-table td { padding: 0.5rem; font-size: 0.7rem; }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="dashboard-header">
        <h2><i class="fas fa-plane-departure me-2"></i>Faith Travel & Tours LTD</h2>
        <div class="header-controls">
            <div class="daily-counter">
                <i class="fas fa-ticket-alt"></i>
                <span>Today: <?= $dailySalesCount ?> Ticket<?= $dailySalesCount != 1 ? 's' : '' ?></span>
            </div>
            <button class="btn-bookings" data-bs-toggle="modal" data-bs-target="#bookingsModal">
                <i class="fas fa-ticket-alt me-1"></i> Bookings
                <?php if ($bookingsNotificationCount > 0): ?>
                    <span class="booking-notification-badge" style="position: absolute; margin-top: -8px; margin-left:5px; background: #dc2626; color:white; border-radius:40px; padding:0 6px;"><?= $bookingsNotificationCount ?></span>
                <?php endif; ?>
            </button>
            <button class="btn-fare-calculator" data-bs-toggle="modal" data-bs-target="#fareCalculatorModal">
                <i class="fas fa-calculator me-1"></i> Fare Calculator
            </button>
            <div class="filter-box">
                <label for="salesFilter" class="me-2 fw-semibold" style="font-size:0.85rem;">Filter:</label>
                <select id="salesFilter" onchange="updateDashboard()">
                    <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="daily" <?= $filter === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    <option value="total" <?= $filter === 'total' ? 'selected' : '' ?>>Total</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Cards (same as before) -->
    <div class="summary-cards">
        <div class="summary-card card-daily">
            <div class="summary-card-header"><span><i class="fas fa-calendar-day me-2"></i>Daily Report</span><div><button class="btn-view-all" onclick="viewReport('daily', 'sales')">Sales</button><button class="btn-view-all" onclick="viewReport('daily', 'purchase')">Purchase</button><button class="btn-view-all" onclick="viewReport('daily', 'payment')">Payment</button><button class="btn-view-all" onclick="viewReport('daily', 'collection')">Collection</button><button class="btn-view-all" onclick="viewReport('daily', 'expense')">Expense</button></div></div>
            <div class="summary-card-body">
                <div class="metric-item"><span>Total Sales</span><span class="metric-value">৳<?= number_format($daily['total_sales'],2) ?></span></div>
                <div class="metric-item"><span>Collection</span><span class="metric-value">৳<?= number_format($daily['total_collection'],2) ?></span></div>
                <div class="metric-item"><span>Purchase</span><span class="metric-value">৳<?= number_format($daily['total_purchase'],2) ?></span></div>
                <div class="metric-item"><span>Expense</span><span class="metric-value">৳<?= number_format($dailyExpenseData['expense_amount'] ?? 0,2) ?></span></div>
                <div class="metric-item"><span>Payment</span><span class="metric-value">৳<?= number_format($dailyPaymentData['payment_amount'] ?? 0,2) ?></span></div>
                <div class="metric-item"><span>Profit</span><span class="metric-value">৳<?= number_format($daily['total_profit'],2) ?></span></div>
                <div class="metric-item"><span>Due</span><span class="metric-value">৳<?= number_format($daily['total_due'],2) ?></span></div>
                <div class="metric-item"><span>Reissue</span><span class="metric-value">৳<?= number_format($daily['total_reissue'],2) ?></span></div>
                <div class="metric-item"><span>Refund</span><span class="metric-value">৳<?= number_format($daily['total_refund'],2) ?></span></div>
                <div class="category-group"><div class="category-title"><i class="fas fa-chart-pie me-1"></i>Breakdown by Service</div>
                <div class="metric-item"><span>✈️ Air Ticket</span><span class="metric-value">৳<?= number_format($daily['category_sales']['ticket'],2) ?></span></div>
                <div class="metric-item"><span>🛂 Visa</span><span class="metric-value">৳<?= number_format($daily['category_sales']['visa'],2) ?></span></div>
                <div class="metric-item"><span>🎓 Student Visa</span><span class="metric-value">৳<?= number_format($daily['category_sales']['student_visa'],2) ?></span></div>
                <div class="metric-item"><span>🕋 Umrah</span><span class="metric-value">৳<?= number_format($daily['category_sales']['umrah'],2) ?></span></div>
                <div class="metric-item"><span>🏨 Hotel</span><span class="metric-value">৳<?= number_format($daily['category_sales']['hotel'],2) ?></span></div></div>
            </div>
        </div>
        <div class="summary-card card-monthly">
            <div class="summary-card-header"><span><i class="fas fa-calendar-alt me-2"></i>Monthly Report</span><div><button class="btn-view-all" onclick="viewReport('monthly', 'sales')">Sales</button><button class="btn-view-all" onclick="viewReport('monthly', 'purchase')">Purchase</button><button class="btn-view-all" onclick="viewReport('monthly', 'payment')">Payment</button><button class="btn-view-all" onclick="viewReport('monthly', 'collection')">Collection</button><button class="btn-view-all" onclick="viewReport('monthly', 'expense')">Expense</button></div></div>
            <div class="summary-card-body">
                <div class="metric-item"><span>Total Sales</span><span class="metric-value">৳<?= number_format($monthly['total_sales'],2) ?></span></div>
                <div class="metric-item"><span>Collection</span><span class="metric-value">৳<?= number_format($monthly['total_collection'],2) ?></span></div>
                <div class="metric-item"><span>Purchase</span><span class="metric-value">৳<?= number_format($monthly['total_purchase'],2) ?></span></div>
                <div class="metric-item"><span>Expense</span><span class="metric-value">৳<?= number_format($monthlyExpenseData['expense_amount'] ?? 0,2) ?></span></div>
                <div class="metric-item"><span>Payment</span><span class="metric-value">৳<?= number_format($monthlyPaymentData['payment_amount'] ?? 0,2) ?></span></div>
                <div class="metric-item"><span>Profit</span><span class="metric-value">৳<?= number_format($monthly['total_profit'],2) ?></span></div>
                <div class="metric-item"><span>Due</span><span class="metric-value">৳<?= number_format($monthly['total_due'],2) ?></span></div>
                <div class="metric-item"><span>Reissue</span><span class="metric-value">৳<?= number_format($monthly['total_reissue'],2) ?></span></div>
                <div class="metric-item"><span>Refund</span><span class="metric-value">৳<?= number_format($monthly['total_refund'],2) ?></span></div>
                <div class="category-group"><div class="category-title"><i class="fas fa-chart-pie me-1"></i>Breakdown by Service</div>
                <div class="metric-item"><span>✈️ Air Ticket</span><span class="metric-value">৳<?= number_format($monthly['category_sales']['ticket'],2) ?></span></div>
                <div class="metric-item"><span>🛂 Visa</span><span class="metric-value">৳<?= number_format($monthly['category_sales']['visa'],2) ?></span></div>
                <div class="metric-item"><span>🎓 Student Visa</span><span class="metric-value">৳<?= number_format($monthly['category_sales']['student_visa'],2) ?></span></div>
                <div class="metric-item"><span>🕋 Umrah</span><span class="metric-value">৳<?= number_format($monthly['category_sales']['umrah'],2) ?></span></div>
                <div class="metric-item"><span>🏨 Hotel</span><span class="metric-value">৳<?= number_format($monthly['category_sales']['hotel'],2) ?></span></div></div>
            </div>
        </div>
        <div class="summary-card card-yearly">
            <div class="summary-card-header"><span><i class="fas fa-calendar me-2"></i>Yearly Report</span><div><button class="btn-view-all" onclick="viewReport('yearly', 'sales')">Sales</button><button class="btn-view-all" onclick="viewReport('yearly', 'purchase')">Purchase</button><button class="btn-view-all" onclick="viewReport('yearly', 'payment')">Payment</button><button class="btn-view-all" onclick="viewReport('yearly', 'collection')">Collection</button><button class="btn-view-all" onclick="viewReport('yearly', 'expense')">Expense</button></div></div>
            <div class="summary-card-body">
                <div class="metric-item"><span>Total Sales</span><span class="metric-value">৳<?= number_format($yearly['total_sales'],2) ?></span></div>
                <div class="metric-item"><span>Collection</span><span class="metric-value">৳<?= number_format($yearly['total_collection'],2) ?></span></div>
                <div class="metric-item"><span>Purchase</span><span class="metric-value">৳<?= number_format($yearly['total_purchase'],2) ?></span></div>
                <div class="metric-item"><span>Expense</span><span class="metric-value">৳<?= number_format($yearlyExpenseData['expense_amount'] ?? 0,2) ?></span></div>
                <div class="metric-item"><span>Payment</span><span class="metric-value">৳<?= number_format($yearlyPaymentData['payment_amount'] ?? 0,2) ?></span></div>
                <div class="metric-item"><span>Profit</span><span class="metric-value">৳<?= number_format($yearly['total_profit'],2) ?></span></div>
                <div class="metric-item"><span>Due</span><span class="metric-value">৳<?= number_format($yearly['total_due'],2) ?></span></div>
                <div class="metric-item"><span>Reissue</span><span class="metric-value">৳<?= number_format($yearly['total_reissue'],2) ?></span></div>
                <div class="metric-item"><span>Refund</span><span class="metric-value">৳<?= number_format($yearly['total_refund'],2) ?></span></div>
                <div class="category-group"><div class="category-title"><i class="fas fa-chart-pie me-1"></i>Breakdown by Service</div>
                <div class="metric-item"><span>✈️ Air Ticket</span><span class="metric-value">৳<?= number_format($yearly['category_sales']['ticket'],2) ?></span></div>
                <div class="metric-item"><span>🛂 Visa</span><span class="metric-value">৳<?= number_format($yearly['category_sales']['visa'],2) ?></span></div>
                <div class="metric-item"><span>🎓 Student Visa</span><span class="metric-value">৳<?= number_format($yearly['category_sales']['student_visa'],2) ?></span></div>
                <div class="metric-item"><span>🕋 Umrah</span><span class="metric-value">৳<?= number_format($yearly['category_sales']['umrah'],2) ?></span></div>
                <div class="metric-item"><span>🏨 Hotel</span><span class="metric-value">৳<?= number_format($yearly['category_sales']['hotel'],2) ?></span></div></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="charts-row">
            <div class="chart-container"><div class="rhc-card"><div class="rhc-header">IATA RHC Meter</div><div class="rhc-body"><div id="rhc-loading" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div><p class="mt-1 small">Loading...</p></div><div id="rhc-content" style="display:none;"><div class="rhc-percentage" id="rhc-percentage">0%</div><div class="rhc-progress-container"><div class="rhc-progress-bar" id="rhc-progress-bar" style="width:0%"></div></div><div class="rhc-detail"><span>Current usage</span><span id="rhc-current-usage">BDT 0</span></div><div class="rhc-detail"><span>RHC Limit</span><span>BDT 10,000,000</span></div><div class="rhc-detail"><span>Remaining (90%)</span><span id="rhc-remaining-balance">BDT 9,000,000</span></div><div class="rhc-detail"><span>Paid Amount</span><span id="rhc-paid-amount">BDT 0</span></div><div class="rhc-detail"><span>Fortnight Payment</span><span id="rhc-fortnight-payment">BDT 0</span></div><div class="rhc-payment-info">Next Payment Due: <span id="rhc-payment-due-date">30 Aug 2025</span><br><span id="rhc-payment-description">For tickets issued in Fortnight 1</span></div><div class="text-muted small mt-2">Last Updated: <span id="rhc-last-updated"></span></div></div></div></div></div>
            <div class="chart-container"><h4 class="chart-title"><?= $pieChartTitle ?></h4><canvas id="salesPieChart"></canvas></div>
            <div class="chart-container"><h4 class="chart-title">Expenses by Month</h4><canvas id="expenseBarChart"></canvas></div>
        </div>
        <div class="chart-container-full"><h4 class="chart-title">Monthly Sales vs Profit (All Services)</h4><canvas id="salesProfitChart"></canvas></div>
    </div>

    <!-- MODALS (unchanged) -->
    <div class="modal fade" id="bookingsModal" tabindex="-1" aria-labelledby="bookingsModalLabel" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-ticket-alt me-2"></i>All Bookings<?php if ($bookingsNotificationCount > 0): ?><span class="urgency-badge"><?= $bookingsNotificationCount ?> Urgent</span><?php endif; ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"><button class="btn-add" onclick="window.open('insert_booking.php', '_blank')"><i class="fas fa-plus me-1"></i>Add New Booking</button><div class="d-flex gap-2"><input type="text" id="pnrSearch" class="search-input" placeholder="Search by PNR..."><button class="btn btn-outline-primary btn-sm" onclick="refreshBookings()"><i class="fas fa-sync-alt"></i> Refresh</button><button class="btn btn-outline-secondary btn-sm" onclick="exportBookings()"><i class="fas fa-download"></i> Export</button></div></div><div class="table-container" style="overflow-x:auto;"><div id="bookingsLoading" class="text-center py-4"><div class="spinner-border text-primary"></div><p>Loading bookings...</p></div><div id="bookingsContent" style="display:none;"></div></div></div></div></div></div>

    <div class="modal fade" id="fareCalculatorModal" tabindex="-1" aria-labelledby="fareCalculatorModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Fare Calculator</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row"><div class="col-md-6"><h6 class="fw-bold mb-3">Tax Details</h6><form id="fareCalculatorForm"><div class="mb-2"><label class="form-label small">Base Fare</label><input type="number" id="baseFare" class="form-control form-control-sm" step="0.01" value="0"></div><div class="mb-2"><label class="form-label small">Commission (%)</label><input type="number" id="commission" class="form-control form-control-sm" step="0.01" value="0"></div><div class="row"><div class="col-6"><label class="form-label small">BD</label><input type="number" id="bd" class="form-control form-control-sm" step="0.01" value="0"></div><div class="col-6"><label class="form-label small">UT</label><input type="number" id="ut" class="form-control form-control-sm" step="0.01" value="0"></div></div><div class="row mt-2"><div class="col-6"><label class="form-label small">OW</label><input type="number" id="ow" class="form-control form-control-sm" step="0.01" value="0"></div><div class="col-6"><label class="form-label small">E5</label><input type="number" id="e5" class="form-control form-control-sm" step="0.01" value="0"></div></div><div class="row mt-2"><div class="col-6"><label class="form-label small">GB</label><input type="number" id="gb" class="form-control form-control-sm" step="0.01" value="0"></div><div class="col-6"><label class="form-label small">UB</label><input type="number" id="ub" class="form-control form-control-sm" step="0.01" value="0"></div></div><div class="row mt-2"><div class="col-6"><label class="form-label small">YR</label><input type="number" id="yr" class="form-control form-control-sm" step="0.01" value="0"></div><div class="col-6"><label class="form-label small">P7</label><input type="number" id="p7" class="form-control form-control-sm" step="0.01" value="0"></div></div><div class="mt-2"><label class="form-label small">P8</label><input type="number" id="p8" class="form-control form-control-sm" step="0.01" value="0"></div></form></div><div class="col-md-6"><h6 class="fw-bold mb-3">Calculations</h6><div class="bg-light p-3 rounded-4"><div class="d-flex justify-content-between mb-2 small"><span>Total Tax:</span><strong id="totalTax">0.00</strong></div><div class="d-flex justify-content-between mb-2 small"><span>Total Fare:</span><strong id="totalFare">0.00</strong></div><div class="d-flex justify-content-between mb-2 small"><span>Commission Amount:</span><strong id="commissionAmount">0.00</strong></div><div class="d-flex justify-content-between mb-2 small"><span>AIT (0.3%):</span><strong id="ait">0.00</strong></div><div class="d-flex justify-content-between border-top pt-2 mt-2"><span>Net Payment:</span><strong id="netPayment" class="text-primary fs-6">0.00</strong></div></div><div class="mt-3 d-flex gap-2"><button type="button" class="btn btn-primary w-50 btn-sm" id="calculateBtn">Calculate</button><button type="button" class="btn btn-secondary w-50 btn-sm" id="clearBtn">Clear</button></div></div></div></div></div></div></div>

    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Report Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body report-modal-table-container" id="reportContent"><div class="text-center py-4"><div class="spinner-border text-primary"></div><p>Loading...</p></div></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateDashboard() { const filter = document.getElementById('salesFilter').value; window.location.href = 'dashboard.php?filter=' + filter; }
        function loadBookings() { fetch('get_bookings.php').then(r=>r.text()).then(data=>{ document.getElementById('bookingsLoading').style.display='none'; document.getElementById('bookingsContent').style.display='block'; document.getElementById('bookingsContent').innerHTML=data; }).catch(e=>{ document.getElementById('bookingsLoading').innerHTML='<div class="alert alert-danger">Error loading bookings</div>'; }); }
        function refreshBookings() { document.getElementById('bookingsLoading').style.display='block'; document.getElementById('bookingsContent').style.display='none'; document.getElementById('pnrSearch').value=''; loadBookings(); }
        function searchBookings() { const term = document.getElementById('pnrSearch').value.toLowerCase(); document.querySelectorAll('.bookings-table tbody tr').forEach(row=>{ const pnr = row.cells[9]?.textContent.toLowerCase(); row.style.display = pnr?.includes(term) ? '' : 'none'; }); }
        function exportBookings() { const table = document.querySelector('.bookings-table'); if(!table) return; let csv=[]; document.querySelectorAll('.bookings-table tr').forEach(row=>{ const cols=row.querySelectorAll('td,th'); let rowData=[]; for(let i=0;i<cols.length-1;i++) rowData.push(cols[i].innerText); csv.push(rowData.join(',')); }); const blob=new Blob([csv.join('\n')],{type:'text/csv'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='bookings_export.csv'; a.click(); }
        function editBooking(id){ window.open(`edit_booking.php?id=${id}`,'_blank'); }
        function deleteBooking(id){ if(confirm('Delete booking?')){ fetch(`delete_booking.php?id=${id}`,{method:'DELETE'}).then(r=>r.json()).then(data=>{ if(data.success){ alert('Deleted'); refreshBookings(); }else alert('Error'); }); } }
        document.getElementById('bookingsModal').addEventListener('show.bs.modal',loadBookings);
        function fetchRHCData() { fetch('get_rhc_data1.php').then(r=>r.json()).then(updateRHCData).catch(()=>updateRHCData({ currentUsage:1602911, paidThisMonth:884009, fortnightPayment:4500000, paymentDueDate:"30 Aug 2025", paymentDescription:"For tickets issued in Fortnight 1" })); }
        function updateRHCData(data){ document.getElementById('rhc-loading').style.display='none'; document.getElementById('rhc-content').style.display='block'; const limit=10000000, limit90=limit*0.9; const percent=Math.min(90,(data.currentUsage/limit*100)).toFixed(0); const remaining=Math.max(0,limit90-data.currentUsage); const fmt=amt=>'BDT '+amt.toLocaleString('en-IN'); document.getElementById('rhc-percentage').innerText=percent+'%'; document.getElementById('rhc-current-usage').innerText=fmt(data.currentUsage); document.getElementById('rhc-remaining-balance').innerText=fmt(remaining); document.getElementById('rhc-paid-amount').innerText=fmt(data.paidThisMonth); document.getElementById('rhc-fortnight-payment').innerText=fmt(data.fortnightPayment); document.getElementById('rhc-payment-due-date').innerText=data.paymentDueDate; document.getElementById('rhc-progress-bar').style.width=Math.min(90,percent)+'%'; const el=document.getElementById('rhc-percentage'); el.classList.remove('risk-low','risk-medium','risk-high','risk-critical'); if(percent<25) el.classList.add('risk-low'); else if(percent<50) el.classList.add('risk-medium'); else if(percent<75) el.classList.add('risk-high'); else el.classList.add('risk-critical'); const now=new Date(); document.getElementById('rhc-last-updated').innerText=now.toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short',year:'numeric'}); }
        fetchRHCData();
        function viewReport(period, type) {
            const modalEl = document.getElementById('reportModal');
            const titleEl = modalEl.querySelector('.modal-title');
            if (titleEl) titleEl.innerText = period.charAt(0).toUpperCase() + period.slice(1) + ' ' + type.charAt(0).toUpperCase() + type.slice(1) + ' Report';
            document.getElementById('reportContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p>Loading report...</p></div>';
            new bootstrap.Modal(modalEl).show();
            fetch(`get_report_data.php?period=${period}&type=${type}`)
                .then(r => r.text())
                .then(html => { document.getElementById('reportContent').innerHTML = html; })
                .catch(e => { document.getElementById('reportContent').innerHTML = '<div class="alert alert-danger">Error loading report</div>'; });
        }
        function calculateFare(){ const bf=parseFloat(document.getElementById('baseFare').value)||0; const cp=parseFloat(document.getElementById('commission').value)||0; const comm=bf*cp/100; const bd=parseFloat(document.getElementById('bd').value)||0; const ut=parseFloat(document.getElementById('ut').value)||0; const ow=parseFloat(document.getElementById('ow').value)||0; const e5=parseFloat(document.getElementById('e5').value)||0; const gb=parseFloat(document.getElementById('gb').value)||0; const ub=parseFloat(document.getElementById('ub').value)||0; const yr=parseFloat(document.getElementById('yr').value)||0; const p7=parseFloat(document.getElementById('p7').value)||0; const p8=parseFloat(document.getElementById('p8').value)||0; const totalTax=bd+ut+ow+e5+gb+ub+yr+p7+p8; const totalFare=bf+totalTax; const ait=(totalFare-(bd+ut+e5))*0.003; const netPayment=(bf-comm)+totalTax+ait; document.getElementById('totalTax').innerText=totalTax.toFixed(2); document.getElementById('totalFare').innerText=totalFare.toFixed(2); document.getElementById('commissionAmount').innerText=comm.toFixed(2); document.getElementById('ait').innerText=ait.toFixed(2); document.getElementById('netPayment').innerText=netPayment.toFixed(2); }
        function clearCalculator(){ ['baseFare','commission','bd','ut','ow','e5','gb','ub','yr','p7','p8'].forEach(id=>{ const el=document.getElementById(id); if(el) el.value=''; }); document.getElementById('totalTax').innerText='0.00'; document.getElementById('totalFare').innerText='0.00'; document.getElementById('commissionAmount').innerText='0.00'; document.getElementById('ait').innerText='0.00'; document.getElementById('netPayment').innerText='0.00'; }
        document.getElementById('calculateBtn').addEventListener('click',calculateFare);
        document.getElementById('clearBtn').addEventListener('click',clearCalculator);
        document.querySelectorAll('#fareCalculatorForm input').forEach(i=>i.addEventListener('input',calculateFare));
        new Chart(document.getElementById('salesPieChart'),{type:'pie',data:{labels:['Agent','Counter','Corporate'],datasets:[{data:[<?= $salesData['Agent'] ?>,<?= $salesData['Counter'] ?>,<?= $salesData['Corporate'] ?>],backgroundColor:['#10b981','#3b82f6','#f59e0b']}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
        new Chart(document.getElementById('expenseBarChart'),{type:'bar',data:{labels:<?= json_encode($expenseMonths) ?>,datasets:[{label:'Expenses',data:<?= json_encode($expenseTotals) ?>,backgroundColor:'#3b82f6'}]},options:{responsive:true,scales:{y:{beginAtZero:true,ticks:{callback:v=>'৳'+v}}}}});
        new Chart(document.getElementById('salesProfitChart'),{type:'bar',data:{labels:<?= json_encode($monthlyLabels) ?>,datasets:[{label:'Total Sales (All Services)',data:<?= json_encode($monthlySales) ?>,backgroundColor:'#10b981'},{label:'Total Profit',data:<?= json_encode($monthlyProfit) ?>,backgroundColor:'#3b82f6'}]},options:{responsive:true,scales:{y:{beginAtZero:true,ticks:{callback:v=>'৳'+v}}},plugins:{legend:{position:'bottom'}}}});
    </script>
</body>
</html>