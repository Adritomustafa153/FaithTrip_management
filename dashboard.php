<?php
include('db.php');
require_once 'auth_check.php';

$filter = $_GET['filter'] ?? 'monthly';
$whereClause = "";

if ($filter === 'monthly') {
    $whereClause = "WHERE MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE())";
} elseif ($filter === 'yearly') {
    $whereClause = "WHERE YEAR(IssueDate) = YEAR(CURDATE())";
}

$salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales $whereClause GROUP BY section";
$salesResult = mysqli_query($conn, $salesQuery);

$salesData = ['Agent' => 0, 'Counter' => 0, 'Corporate' => 0];
while ($row = mysqli_fetch_assoc($salesResult)) {
    $key = ucfirst(strtolower($row['section']));
    if (array_key_exists($key, $salesData)) {
        $salesData[$key] = (float)$row['total'];
    }
}

$expenseQuery = "SELECT DATE_FORMAT(expense_date, '%b') AS month, SUM(amount) AS total FROM expenses GROUP BY month ORDER BY MIN(expense_date)";
$expenseResult = mysqli_query($conn, $expenseQuery);

$expenseMonths = [];
$expenseTotals = [];

while ($row = mysqli_fetch_assoc($expenseResult)) {
    $expenseMonths[] = $row['month'];
    $expenseTotals[] = (float)$row['total'];
}

// New query for monthly sales vs profit
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

// Calculate summary metrics for cards
// Daily metrics - Fixed to handle cases where no tickets were issued but payments were made
$dailySalesQuery = "SELECT 
    SUM(BillAmount) as sale_amount,
    SUM(Profit) as profit_amount
FROM sales 
WHERE DATE(IssueDate) = CURDATE()";
$dailyResult = mysqli_query($conn, $dailySalesQuery);
$dailySalesData = mysqli_fetch_assoc($dailyResult);

// Collection amount from sales table (regardless of issue date)
$dailyCollectionQuery = "SELECT SUM(PaidAmount) as collection_amount 
                         FROM sales 
                         WHERE DATE(ReceivedDate) = CURDATE() OR 
                               (ReceivedDate IS NULL AND DATE(ClearingDate) = CURDATE()) OR
                               (ReceivedDate IS NULL AND ClearingDate IS NULL AND DATE(DepositDate) = CURDATE())";
$dailyCollectionResult = mysqli_query($conn, $dailyCollectionQuery);
$dailyCollectionData = mysqli_fetch_assoc($dailyCollectionResult);

// Purchase amount (non-IATA sources, regardless of issue date)
$dailyPurchaseQuery = "SELECT SUM(NetPayment) as purchase_amount 
                       FROM sales 
                       WHERE (Source NOT LIKE '%IATA%' OR Source IS NULL) AND 
                             (DATE(ReceivedDate) = CURDATE() OR 
                              DATE(ClearingDate) = CURDATE() OR 
                              DATE(IssueDate) = CURDATE() OR 
                              DATE(DepositDate) = CURDATE())";
$dailyPurchaseResult = mysqli_query($conn, $dailyPurchaseQuery);
$dailyPurchaseData = mysqli_fetch_assoc($dailyPurchaseResult);

$dailyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE DATE(expense_date) = CURDATE()";
$dailyExpenseResult = mysqli_query($conn, $dailyExpenseQuery);
$dailyExpenseData = mysqli_fetch_assoc($dailyExpenseResult);

$dailyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE DATE(payment_date) = CURDATE()";
$dailyPaymentResult = mysqli_query($conn, $dailyPaymentQuery);
$dailyPaymentData = mysqli_fetch_assoc($dailyPaymentResult);

// Monthly metrics - Fixed to handle all transactions in the month regardless of issue date
$monthlySalesQuery = "SELECT 
    SUM(BillAmount) as sale_amount,
    SUM(Profit) as profit_amount
FROM sales 
WHERE MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE())";
$monthlyResult = mysqli_query($conn, $monthlySalesQuery);
$monthlySalesData = mysqli_fetch_assoc($monthlyResult);

// Collection amount from sales table (regardless of issue date)
$monthlyCollectionQuery = "SELECT SUM(PaidAmount) as collection_amount 
                           FROM sales 
                           WHERE (MONTH(ReceivedDate) = MONTH(CURDATE()) AND YEAR(ReceivedDate) = YEAR(CURDATE())) OR 
                                 (ReceivedDate IS NULL AND MONTH(ClearingDate) = MONTH(CURDATE()) AND YEAR(ClearingDate) = YEAR(CURDATE())) OR
                                 (ReceivedDate IS NULL AND ClearingDate IS NULL AND MONTH(DepositDate) = MONTH(CURDATE()) AND YEAR(DepositDate) = YEAR(CURDATE()))";
$monthlyCollectionResult = mysqli_query($conn, $monthlyCollectionQuery);
$monthlyCollectionData = mysqli_fetch_assoc($monthlyCollectionResult);

// Purchase amount (non-IATA sources, regardless of issue date)
$monthlyPurchaseQuery = "SELECT SUM(NetPayment) as purchase_amount 
                         FROM sales 
                         WHERE (Source NOT LIKE '%IATA%' OR Source IS NULL) AND 
                               (MONTH(ReceivedDate) = MONTH(CURDATE()) AND YEAR(ReceivedDate) = YEAR(CURDATE()) OR 
                                MONTH(ClearingDate) = MONTH(CURDATE()) AND YEAR(ClearingDate) = YEAR(CURDATE()) OR 
                                MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE()))";
                                
$monthlyPurchaseResult = mysqli_query($conn, $monthlyPurchaseQuery);
$monthlyPurchaseData = mysqli_fetch_assoc($monthlyPurchaseResult);

$monthlyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
$monthlyExpenseResult = mysqli_query($conn, $monthlyExpenseQuery);
$monthlyExpenseData = mysqli_fetch_assoc($monthlyExpenseResult);

$monthlyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())";
$monthlyPaymentResult = mysqli_query($conn, $monthlyPaymentQuery);
$monthlyPaymentData = mysqli_fetch_assoc($monthlyPaymentResult);

// Yearly metrics - Fixed to handle all transactions in the year regardless of issue date
$yearlySalesQuery = "SELECT 
    SUM(BillAmount) as sale_amount,
    SUM(Profit) as profit_amount
FROM sales 
WHERE YEAR(IssueDate) = YEAR(CURDATE())";
$yearlyResult = mysqli_query($conn, $yearlySalesQuery);
$yearlySalesData = mysqli_fetch_assoc($yearlyResult);

// Collection amount from sales table (regardless of issue date)
$yearlyCollectionQuery = "SELECT SUM(PaidAmount) as collection_amount 
                          FROM sales 
                          WHERE YEAR(ReceivedDate) = YEAR(CURDATE()) OR 
                                (ReceivedDate IS NULL AND YEAR(ClearingDate) = YEAR(CURDATE())) OR
                                (ReceivedDate IS NULL AND ClearingDate IS NULL AND YEAR(DepositDate) = YEAR(CURDATE()))";
$yearlyCollectionResult = mysqli_query($conn, $yearlyCollectionQuery);
$yearlyCollectionData = mysqli_fetch_assoc($yearlyCollectionResult);

// Purchase amount (non-IATA sources, regardless of issue date)
$yearlyPurchaseQuery = "SELECT SUM(NetPayment) as purchase_amount 
                        FROM sales 
                        WHERE (Source NOT LIKE '%IATA%' OR Source IS NULL) AND 
                              (YEAR(ReceivedDate) = YEAR(CURDATE()) OR 
                               YEAR(ClearingDate) = YEAR(CURDATE()) OR 
                               YEAR(DepositDate) = YEAR(CURDATE()))";
$yearlyPurchaseResult = mysqli_query($conn, $yearlyPurchaseQuery);
$yearlyPurchaseData = mysqli_fetch_assoc($yearlyPurchaseResult);

$yearlyExpenseQuery = "SELECT SUM(amount) as expense_amount FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE())";
$yearlyExpenseResult = mysqli_query($conn, $yearlyExpenseQuery);
$yearlyExpenseData = mysqli_fetch_assoc($yearlyExpenseResult);

$yearlyPaymentQuery = "SELECT SUM(amount) as payment_amount FROM paid WHERE YEAR(payment_date) = YEAR(CURDATE())";
$yearlyPaymentResult = mysqli_query($conn, $yearlyPaymentQuery);
$yearlyPaymentData = mysqli_fetch_assoc($yearlyPaymentResult);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .summary-cards {
                flex-direction: column;
            }
            
            .charts-row {
                flex-direction: column;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-box {
                margin-top: 15px;
                width: 100%;
            }
            
            select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php' ?>
    <div class="dashboard-header">
        <h2>Faith Travel and Tours LTD Dashboard</h2>
        <div class="filter-box">
            <label for="salesFilter">Filter by:</label>
            <select id="salesFilter" onchange="updateDashboard()">
                <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                <option value="total" <?= $filter === 'total' ? 'selected' : '' ?>>Total</option>
            </select>
        </div>
    </div>

    <div class="summary-cards">
        <!-- Daily Summary Card -->
        <div class="summary-card card-daily">
            <div class="summary-card-header">Daily Report</div>
            <div class="summary-card-body">
                <div class="metric-item">
                    <span>Sale Amount:</span>
                    <span class="metric-value">৳<?= number_format($dailySalesData['sale_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Collection Amount:</span>
                    <span class="metric-value">৳<?= number_format($dailyCollectionData['collection_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Purchase Amount:</span>
                    <span class="metric-value">৳<?= number_format($dailyPurchaseData['purchase_amount'] ?? 0, 2) ?></span>
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
                    <span class="metric-value">৳<?= number_format($dailySalesData['profit_amount'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Monthly Summary Card -->
        <div class="summary-card card-monthly">
            <div class="summary-card-header">Monthly Report</div>
            <div class="summary-card-body">
                <div class="metric-item">
                    <span>Sale Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthlySalesData['sale_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Collection Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthlyCollectionData['collection_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Purchase Amount:</span>
                    <span class="metric-value">৳<?= number_format($monthlyPurchaseData['purchase_amount'] ?? 0, 2) ?></span>
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
                    <span class="metric-value">৳<?= number_format($monthlySalesData['profit_amount'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Yearly Summary Card -->
        <div class="summary-card card-yearly">
            <div class="summary-card-header">Yearly Report</div>
            <div class="summary-card-body">
                <div class="metric-item">
                    <span>Sale Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearlySalesData['sale_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Collection Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearlyCollectionData['collection_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="metric-item">
                    <span>Purchase Amount:</span>
                    <span class="metric-value">৳<?= number_format($yearlyPurchaseData['purchase_amount'] ?? 0, 2) ?></span>
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
                    <span class="metric-value">৳<?= number_format($yearlySalesData['profit_amount'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="charts-section">
        <!-- First row of charts -->
        <div class="charts-row">
            <!-- RHC Card -->
            <div class="chart-container">
                <!-- <h4 class="chart-title">RHC Amount</h4> -->
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
                <h4 class="chart-title">Sales by Section</h4>
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

    <script>
    function updateDashboard() {
        const filter = document.getElementById('salesFilter').value;
        window.location.href = `dashboard.php?filter=${filter}`;
    }

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