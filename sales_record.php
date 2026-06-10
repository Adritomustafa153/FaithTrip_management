<?php
// Database configuration
include 'db.php';
require_once 'sales_functions.php';  // This file contains calculateSales() function

// Set current dates automatically
$current_date = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');

// Calculate daily, monthly, yearly using the function from sales_functions.php
$daily_start = $current_date;
$daily_end = $current_date;
$daily_sales = calculateSales($conn, $daily_start, $daily_end);

$monthly_start = date('Y-m-01');
$monthly_end = date('Y-m-t');
$monthly_sales = calculateSales($conn, $monthly_start, $monthly_end);

$yearly_start = $current_year . '-01-01';
$yearly_end = $current_year . '-12-31';
$yearly_sales = calculateSales($conn, $yearly_start, $yearly_end);

function formatCurrency($amount) {
    return number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - FaithTrip Accounts</title>
    <style>
        /* your existing CSS – unchanged */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            background: #f8f9fa;
        }
        .period-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .period-title {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .summary-grid-extended {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        @media (max-width: 1400px) {
            .summary-grid-extended {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        @media (max-width: 1200px) {
            .summary-grid-extended {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .summary-grid-extended {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .summary-grid-extended {
                grid-template-columns: 1fr;
            }
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .summary-card.negative {
            border-left-color: #dc3545;
        }
        .summary-card.warning {
            border-left-color: #ffc107;
        }
        .summary-card.info {
            border-left-color: #17a2b8;
        }
        .summary-card.success {
            border-left-color: #28a745;
        }
        .summary-card.primary {
            border-left-color: #007bff;
        }
        .summary-card.collection {
            border-left-color: #6f42c1;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-card .amount {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .summary-card.negative .amount {
            color: #dc3545;
        }
        .summary-card.warning .amount {
            color: #856404;
        }
        .summary-card.info .amount {
            color: #17a2b8;
        }
        .summary-card.success .amount {
            color: #28a745;
        }
        .summary-card.primary .amount {
            color: #007bff;
        }
        .summary-card.collection .amount {
            color: #6f42c1;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .category-grid {
                grid-template-columns: 1fr;
            }
        }
        .category-item {
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
            transition: transform 0.2s;
        }
        .category-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .category-item .label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .category-item .value {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .section-title {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            padding: 12px 20px;
            margin: 20px -20px 15px -20px;
            border-radius: 8px;
            font-weight: 600;
        }
        .metric-highlight {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        .card-icon {
            font-size: 24px;
            margin-bottom: 10px;
            opacity: 0.7;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <h1>
        <i class="fas fa-chart-line"></i> Sales Report - FaithTrip Accounts
    </h1>
    
    <!-- Daily Sales -->
    <div class="period-section">
        <div class="period-title">
            <h2><i class="fas fa-calendar-day"></i> Daily Sales Report - <?php echo date('F j, Y', strtotime($current_date)); ?> (Today)</h2>
        </div>
        
        <div class="summary-grid-extended">
            <div class="summary-card success">
                <div class="card-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3>Total Sales</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_sales']); ?></div>
                <div class="metric-highlight">All service categories</div>
            </div>
            <div class="summary-card collection">
                <div class="card-icon"><i class="fas fa-hand-holding-usd"></i></div>
                <h3>Collection Amount</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_collection']); ?></div>
                <div class="metric-highlight">Received payments</div>
            </div>
            <div class="summary-card">
                <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                <h3>Total Purchase</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_purchase']); ?></div>
                <div class="metric-highlight">Non-IATA sources</div>
            </div>
            <div class="summary-card success">
                <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Total Profit</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_profit']); ?></div>
                <div class="metric-highlight">Net profit after costs</div>
            </div>
            <div class="summary-card warning">
                <div class="card-icon"><i class="fas fa-clock"></i></div>
                <h3>Total Due</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_due']); ?></div>
                <div class="metric-highlight">Pending payments</div>
            </div>
            <div class="summary-card info">
                <div class="card-icon"><i class="fas fa-sync-alt"></i></div>
                <h3>Total Reissue</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_reissue']); ?></div>
                <div class="metric-highlight">Ticket reissues only</div>
            </div>
            <div class="summary-card negative">
                <div class="card-icon"><i class="fas fa-undo-alt"></i></div>
                <h3>Total Refund</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_refund']); ?></div>
                <div class="metric-highlight">All refunded amounts</div>
            </div>
        </div>

        <div class="section-title">
            <h3><i class="fas fa-layer-group"></i> Sales by Category</h3>
        </div>
        <div class="category-grid">
            <div class="category-item"><div class="label">Ticket Sales</div><div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['ticket']); ?></div></div>
            <div class="category-item"><div class="label">Visa Services</div><div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['visa']); ?></div></div>
            <div class="category-item"><div class="label">Student Visa</div><div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['student_visa']); ?></div></div>
            <div class="category-item"><div class="label">Umrah Packages</div><div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['umrah']); ?></div></div>
            <div class="category-item"><div class="label">Hotel Bookings</div><div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['hotel']); ?></div></div>
            <div class="category-item"><div class="label">EMD (Extra Services)</div><div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['emd'] ?? 0); ?></div></div>
        </div>
    </div>

    <!-- Monthly Sales -->
    <div class="period-section">
        <div class="period-title">
            <h2><i class="fas fa-calendar-alt"></i> Monthly Sales Report - <?php echo date('F Y'); ?> (Current Month)</h2>
        </div>
        
        <div class="summary-grid-extended">
            <div class="summary-card success"><div class="card-icon"><i class="fas fa-shopping-cart"></i></div><h3>Total Sales</h3><div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_sales']); ?></div><div class="metric-highlight">All service categories</div></div>
            <div class="summary-card collection"><div class="card-icon"><i class="fas fa-hand-holding-usd"></i></div><h3>Collection Amount</h3><div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_collection']); ?></div><div class="metric-highlight">Received payments</div></div>
            <div class="summary-card"><div class="card-icon"><i class="fas fa-money-bill-wave"></i></div><h3>Total Purchase</h3><div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_purchase']); ?></div><div class="metric-highlight">Non-IATA sources</div></div>
            <div class="summary-card success"><div class="card-icon"><i class="fas fa-chart-line"></i></div><h3>Total Profit</h3><div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_profit']); ?></div><div class="metric-highlight">Net profit after costs</div></div>
            <div class="summary-card warning"><div class="card-icon"><i class="fas fa-clock"></i></div><h3>Total Due</h3><div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_due']); ?></div><div class="metric-highlight">Pending payments</div></div>
            <div class="summary-card info"><div class="card-icon"><i class="fas fa-sync-alt"></i></div><h3>Total Reissue</h3><div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_reissue']); ?></div><div class="metric-highlight">Ticket reissues only</div></div>
            <div class="summary-card negative"><div class="card-icon"><i class="fas fa-undo-alt"></i></div><h3>Total Refund</h3><div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_refund']); ?></div><div class="metric-highlight">All refunded amounts</div></div>
        </div>

        <div class="section-title"><h3><i class="fas fa-layer-group"></i> Sales by Category</h3></div>
        <div class="category-grid">
            <div class="category-item"><div class="label">Ticket Sales</div><div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['ticket']); ?></div></div>
            <div class="category-item"><div class="label">Visa Services</div><div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['visa']); ?></div></div>
            <div class="category-item"><div class="label">Student Visa</div><div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['student_visa']); ?></div></div>
            <div class="category-item"><div class="label">Umrah Packages</div><div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['umrah']); ?></div></div>
            <div class="category-item"><div class="label">Hotel Bookings</div><div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['hotel']); ?></div></div>
            <div class="category-item"><div class="label">EMD (Extra Services)</div><div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['emd'] ?? 0); ?></div></div>
        </div>
    </div>

    <!-- Yearly Sales -->
    <div class="period-section">
        <div class="period-title">
            <h2><i class="fas fa-calendar"></i> Yearly Sales Report - <?php echo $current_year; ?> (Current Year)</h2>
        </div>
        
        <div class="summary-grid-extended">
            <div class="summary-card success"><div class="card-icon"><i class="fas fa-shopping-cart"></i></div><h3>Total Sales</h3><div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_sales']); ?></div><div class="metric-highlight">All service categories</div></div>
            <div class="summary-card collection"><div class="card-icon"><i class="fas fa-hand-holding-usd"></i></div><h3>Collection Amount</h3><div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_collection']); ?></div><div class="metric-highlight">Received payments</div></div>
            <div class="summary-card"><div class="card-icon"><i class="fas fa-money-bill-wave"></i></div><h3>Total Purchase</h3><div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_purchase']); ?></div><div class="metric-highlight">Non-IATA sources</div></div>
            <div class="summary-card success"><div class="card-icon"><i class="fas fa-chart-line"></i></div><h3>Total Profit</h3><div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_profit']); ?></div><div class="metric-highlight">Net profit after costs</div></div>
            <div class="summary-card warning"><div class="card-icon"><i class="fas fa-clock"></i></div><h3>Total Due</h3><div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_due']); ?></div><div class="metric-highlight">Pending payments</div></div>
            <div class="summary-card info"><div class="card-icon"><i class="fas fa-sync-alt"></i></div><h3>Total Reissue</h3><div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_reissue']); ?></div><div class="metric-highlight">Ticket reissues only</div></div>
            <div class="summary-card negative"><div class="card-icon"><i class="fas fa-undo-alt"></i></div><h3>Total Refund</h3><div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_refund']); ?></div><div class="metric-highlight">All refunded amounts</div></div>
        </div>

        <div class="section-title"><h3><i class="fas fa-layer-group"></i> Sales by Category</h3></div>
        <div class="category-grid">
            <div class="category-item"><div class="label">Ticket Sales</div><div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['ticket']); ?></div></div>
            <div class="category-item"><div class="label">Visa Services</div><div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['visa']); ?></div></div>
            <div class="category-item"><div class="label">Student Visa</div><div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['student_visa']); ?></div></div>
            <div class="category-item"><div class="label">Umrah Packages</div><div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['umrah']); ?></div></div>
            <div class="category-item"><div class="label">Hotel Bookings</div><div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['hotel']); ?></div></div>
            <div class="category-item"><div class="label">EMD (Extra Services)</div><div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['emd'] ?? 0); ?></div></div>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>