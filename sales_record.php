<?php
// Database configuration
include 'db.php';

// Function to calculate sales for a given period
function calculateSales($conn, $startDate, $endDate) {
    $result = [
        'total_sales' => 0,
        'total_purchase' => 0,
        'total_profit' => 0,
        'total_due' => 0,
        'total_reissue' => 0,
        'total_refund' => 0,
        'total_collection' => 0, // NEW: Collection amount
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

    // NEW: Calculate collection amount from payments table
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

// Calculate daily sales (today)
$daily_start = $current_date;
$daily_end = $current_date;
$daily_sales = calculateSales($conn, $daily_start, $daily_end);

// Calculate monthly sales (current month)
$monthly_start = date('Y-m-01');
$monthly_end = date('Y-m-t');
$monthly_sales = calculateSales($conn, $monthly_start, $monthly_end);

// Calculate yearly sales (current year)
$yearly_start = $current_year . '-01-01';
$yearly_end = $current_year . '-12-31';
$yearly_sales = calculateSales($conn, $yearly_start, $yearly_end);

// Format numbers for display
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
            grid-template-columns: repeat(5, 1fr);
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
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Total Sales</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_sales']); ?></div>
                <div class="metric-highlight">All service categories</div>
            </div>
            <div class="summary-card collection">
                <div class="card-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <h3>Collection Amount</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_collection']); ?></div>
                <div class="metric-highlight">Received payments</div>
            </div>
            <div class="summary-card">
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>Total Purchase</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_purchase']); ?></div>
                <div class="metric-highlight">Non-IATA sources</div>
            </div>
            <div class="summary-card success">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Total Profit</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_profit']); ?></div>
                <div class="metric-highlight">Net profit after costs</div>
            </div>
            <div class="summary-card warning">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Total Due</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_due']); ?></div>
                <div class="metric-highlight">Pending payments</div>
            </div>
            <div class="summary-card info">
                <div class="card-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h3>Total Reissue</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_reissue']); ?></div>
                <div class="metric-highlight">Ticket reissues only</div>
            </div>
            <div class="summary-card negative">
                <div class="card-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3>Total Refund</h3>
                <div class="amount">BDT <?php echo formatCurrency($daily_sales['total_refund']); ?></div>
                <div class="metric-highlight">All refunded amounts</div>
            </div>
        </div>

        <div class="section-title">
            <h3><i class="fas fa-layer-group"></i> Sales by Category</h3>
        </div>
        <div class="category-grid">
            <div class="category-item">
                <div class="label">Ticket Sales</div>
                <div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['ticket']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Visa Services</div>
                <div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['visa']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Student Visa</div>
                <div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['student_visa']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Umrah Packages</div>
                <div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['umrah']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Hotel Bookings</div>
                <div class="value">BDT <?php echo formatCurrency($daily_sales['category_sales']['hotel']); ?></div>
            </div>
        </div>
    </div>

    <!-- Monthly Sales -->
    <div class="period-section">
        <div class="period-title">
            <h2><i class="fas fa-calendar-alt"></i> Monthly Sales Report - <?php echo date('F Y'); ?> (Current Month)</h2>
        </div>
        
        <div class="summary-grid-extended">
            <div class="summary-card success">
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Total Sales</h3>
                <div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_sales']); ?></div>
                <div class="metric-highlight">All service categories</div>
            </div>
            <div class="summary-card collection">
                <div class="card-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <h3>Collection Amount</h3>
                <div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_collection']); ?></div>
                <div class="metric-highlight">Received payments</div>
            </div>
            <div class="summary-card">
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>Total Purchase</h3>
                <div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_purchase']); ?></div>
                <div class="metric-highlight">Non-IATA sources</div>
            </div>
            <div class="summary-card success">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Total Profit</h3>
                <div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_profit']); ?></div>
                <div class="metric-highlight">Net profit after costs</div>
            </div>
            <div class="summary-card warning">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Total Due</h3>
                <div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_due']); ?></div>
                <div class="metric-highlight">Pending payments</div>
            </div>
            <div class="summary-card info">
                <div class="card-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h3>Total Reissue</h3>
                <div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_reissue']); ?></div>
                <div class="metric-highlight">Ticket reissues only</div>
            </div>
            <div class="summary-card negative">
                <div class="card-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3>Total Refund</h3>
                <div class="amount">BDT <?php echo formatCurrency($monthly_sales['total_refund']); ?></div>
                <div class="metric-highlight">All refunded amounts</div>
            </div>
        </div>

        <div class="section-title">
            <h3><i class="fas fa-layer-group"></i> Sales by Category</h3>
        </div>
        <div class="category-grid">
            <div class="category-item">
                <div class="label">Ticket Sales</div>
                <div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['ticket']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Visa Services</div>
                <div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['visa']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Student Visa</div>
                <div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['student_visa']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Umrah Packages</div>
                <div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['umrah']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Hotel Bookings</div>
                <div class="value">BDT <?php echo formatCurrency($monthly_sales['category_sales']['hotel']); ?></div>
            </div>
        </div>
    </div>

    <!-- Yearly Sales -->
    <div class="period-section">
        <div class="period-title">
            <h2><i class="fas fa-calendar"></i> Yearly Sales Report - <?php echo $current_year; ?> (Current Year)</h2>
        </div>
        
        <div class="summary-grid-extended">
            <div class="summary-card success">
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Total Sales</h3>
                <div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_sales']); ?></div>
                <div class="metric-highlight">All service categories</div>
            </div>
            <div class="summary-card collection">
                <div class="card-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <h3>Collection Amount</h3>
                <div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_collection']); ?></div>
                <div class="metric-highlight">Received payments</div>
            </div>
            <div class="summary-card">
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>Total Purchase</h3>
                <div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_purchase']); ?></div>
                <div class="metric-highlight">Non-IATA sources</div>
            </div>
            <div class="summary-card success">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Total Profit</h3>
                <div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_profit']); ?></div>
                <div class="metric-highlight">Net profit after costs</div>
            </div>
            <div class="summary-card warning">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Total Due</h3>
                <div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_due']); ?></div>
                <div class="metric-highlight">Pending payments</div>
            </div>
            <div class="summary-card info">
                <div class="card-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h3>Total Reissue</h3>
                <div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_reissue']); ?></div>
                <div class="metric-highlight">Ticket reissues only</div>
            </div>
            <div class="summary-card negative">
                <div class="card-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3>Total Refund</h3>
                <div class="amount">BDT <?php echo formatCurrency($yearly_sales['total_refund']); ?></div>
                <div class="metric-highlight">All refunded amounts</div>
            </div>
        </div>

        <div class="section-title">
            <h3><i class="fas fa-layer-group"></i> Sales by Category</h3>
        </div>
        <div class="category-grid">
            <div class="category-item">
                <div class="label">Ticket Sales</div>
                <div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['ticket']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Visa Services</div>
                <div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['visa']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Student Visa</div>
                <div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['student_visa']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Umrah Packages</div>
                <div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['umrah']); ?></div>
            </div>
            <div class="category-item">
                <div class="label">Hotel Bookings</div>
                <div class="value">BDT <?php echo formatCurrency($yearly_sales['category_sales']['hotel']); ?></div>
            </div>
        </div>
    </div>

    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>