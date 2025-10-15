<?php
// Database configuration
include 'db.php';

// Function to calculate sales for a given period
function calculateSales($conn, $startDate, $endDate) {
    $result = [
        'total_sales' => 0,
        'total_purchase' => 0,
        'total_profit' => 0,
        'category_sales' => [
            'ticket' => 0,
            'visa' => 0,
            'student_visa' => 0,
            'umrah' => 0,
            'hotel' => 0
        ]
    ];

    // Calculate for SALES table (Air Tickets) - CORRECTED CALCULATION
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
                    COALESCE(SUM(CASE WHEN Remarks = 'Refund' THEN BillAmount ELSE 0 END), 0) as total_refund
                  FROM sales 
                  WHERE IssueDate BETWEEN '$startDate' AND '$endDate'";
    
    $sales_result = $conn->query($sales_sql);
    if ($sales_result && $sales_row = $sales_result->fetch_assoc()) {
        $ticket_sales = $sales_row['total_sales'];
        $result['total_sales'] += $ticket_sales;
        $result['total_profit'] += $sales_row['total_profit'];
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

    // Calculate for HOTEL table
    $hotel_sql = "SELECT 
                    COALESCE(SUM(selling_price), 0) as total_sales,
                    COALESCE(SUM(net_price), 0) as total_net,
                    COALESCE(SUM(profit), 0) as total_profit,
                    COALESCE(SUM(refund_to_client), 0) as total_refund
                  FROM hotel 
                  WHERE issue_date BETWEEN '$startDate' AND '$endDate'";
    
    $hotel_result = $conn->query($hotel_sql);
    if ($hotel_result && $hotel_row = $hotel_result->fetch_assoc()) {
        $hotel_sales = $hotel_row['total_sales'] - $hotel_row['total_refund'];
        $result['total_sales'] += $hotel_sales;
        $result['total_profit'] += $hotel_row['total_profit'];
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
                    COALESCE(SUM(refund_to_client), 0) as total_refund
                  FROM student 
                  WHERE `received date` BETWEEN '$startDate' AND '$endDate'";
    
    $student_result = $conn->query($student_sql);
    if ($student_result && $student_row = $student_result->fetch_assoc()) {
        $student_sales = $student_row['total_sales'] - $student_row['total_refund'];
        $result['total_sales'] += $student_sales;
        $result['total_profit'] += $student_row['total_profit'];
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
                    COALESCE(SUM(`refund to client`), 0) as total_refund
                  FROM umrah 
                  WHERE orderdate BETWEEN '$startDate' AND '$endDate'";
    
    $umrah_result = $conn->query($umrah_sql);
    if ($umrah_result && $umrah_row = $umrah_result->fetch_assoc()) {
        $umrah_sales = $umrah_row['total_sales'] - $umrah_row['total_refund'];
        $result['total_sales'] += $umrah_sales;
        $result['total_profit'] += $umrah_row['total_profit'];
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
                    COALESCE(SUM(`refund to client`), 0) as total_refund
                  FROM visa 
                  WHERE orderdate BETWEEN '$startDate' AND '$endDate'";
    
    $visa_result = $conn->query($visa_sql);
    if ($visa_result && $visa_row = $visa_result->fetch_assoc()) {
        $visa_sales = $visa_row['total_sales'] - $visa_row['total_refund'];
        $result['total_sales'] += $visa_sales;
        $result['total_profit'] += $visa_row['total_profit'];
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

// Get date parameters from request or use current date
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Calculate daily sales
$daily_start = $date;
$daily_end = $date;
$daily_sales = calculateSales($conn, $daily_start, $daily_end);

// Calculate monthly sales
$monthly_start = date('Y-m-01', strtotime($month));
$monthly_end = date('Y-m-t', strtotime($month));
$monthly_sales = calculateSales($conn, $monthly_start, $monthly_end);

// Calculate yearly sales
$yearly_start = $year . '-01-01';
$yearly_end = $year . '-12-31';
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
</head>
<body>
    <h1>Sales Report - FaithTrip Accounts</h1>
    
    <!-- Filters -->
    <div>
        <form method="GET">
            <label for="date">Daily Date:</label>
            <input type="date" id="date" name="date" value="<?php echo $date; ?>">
            
            <label for="month">Monthly:</label>
            <input type="month" id="month" name="month" value="<?php echo $month; ?>">
            
            <label for="year">Yearly:</label>
            <input type="number" id="year" name="year" value="<?php echo $year; ?>" min="2020" max="2030">
            
            <button type="submit">Update Report</button>
        </form>
    </div>

    <!-- Daily Sales -->
    <div>
        <h2>Daily Sales Report - <?php echo $date; ?></h2>
        
        <h3>Summary</h3>
        <p><strong>Total Sales:</strong> BDT <?php echo formatCurrency($daily_sales['total_sales']); ?></p>
        <p><strong>Total Purchase:</strong> BDT <?php echo formatCurrency($daily_sales['total_purchase']); ?></p>
        <p><strong>Total Profit:</strong> BDT <?php echo formatCurrency($daily_sales['total_profit']); ?></p>

        <h3>Sales by Category</h3>
        <p><strong>Ticket:</strong> BDT <?php echo formatCurrency($daily_sales['category_sales']['ticket']); ?></p>
        <p><strong>Visa:</strong> BDT <?php echo formatCurrency($daily_sales['category_sales']['visa']); ?></p>
        <p><strong>Student Visa:</strong> BDT <?php echo formatCurrency($daily_sales['category_sales']['student_visa']); ?></p>
        <p><strong>Umrah:</strong> BDT <?php echo formatCurrency($daily_sales['category_sales']['umrah']); ?></p>
        <p><strong>Hotel:</strong> BDT <?php echo formatCurrency($daily_sales['category_sales']['hotel']); ?></p>
    </div>

    <hr>

    <!-- Monthly Sales -->
    <div>
        <h2>Monthly Sales Report - <?php echo date('F Y', strtotime($month)); ?></h2>
        
        <h3>Summary</h3>
        <p><strong>Total Sales:</strong> BDT <?php echo formatCurrency($monthly_sales['total_sales']); ?></p>
        <p><strong>Total Purchase:</strong> BDT <?php echo formatCurrency($monthly_sales['total_purchase']); ?></p>
        <p><strong>Total Profit:</strong> BDT <?php echo formatCurrency($monthly_sales['total_profit']); ?></p>

        <h3>Sales by Category</h3>
        <p><strong>Ticket:</strong> BDT <?php echo formatCurrency($monthly_sales['category_sales']['ticket']); ?></p>
        <p><strong>Visa:</strong> BDT <?php echo formatCurrency($monthly_sales['category_sales']['visa']); ?></p>
        <p><strong>Student Visa:</strong> BDT <?php echo formatCurrency($monthly_sales['category_sales']['student_visa']); ?></p>
        <p><strong>Umrah:</strong> BDT <?php echo formatCurrency($monthly_sales['category_sales']['umrah']); ?></p>
        <p><strong>Hotel:</strong> BDT <?php echo formatCurrency($monthly_sales['category_sales']['hotel']); ?></p>
    </div>

    <hr>

    <!-- Yearly Sales -->
    <div>
        <h2>Yearly Sales Report - <?php echo $year; ?></h2>
        
        <h3>Summary</h3>
        <p><strong>Total Sales:</strong> BDT <?php echo formatCurrency($yearly_sales['total_sales']); ?></p>
        <p><strong>Total Purchase:</strong> BDT <?php echo formatCurrency($yearly_sales['total_purchase']); ?></p>
        <p><strong>Total Profit:</strong> BDT <?php echo formatCurrency($yearly_sales['total_profit']); ?></p>

        <h3>Sales by Category</h3>
        <p><strong>Ticket:</strong> BDT <?php echo formatCurrency($yearly_sales['category_sales']['ticket']); ?></p>
        <p><strong>Visa:</strong> BDT <?php echo formatCurrency($yearly_sales['category_sales']['visa']); ?></p>
        <p><strong>Student Visa:</strong> BDT <?php echo formatCurrency($yearly_sales['category_sales']['student_visa']); ?></p>
        <p><strong>Umrah:</strong> BDT <?php echo formatCurrency($yearly_sales['category_sales']['umrah']); ?></p>
        <p><strong>Hotel:</strong> BDT <?php echo formatCurrency($yearly_sales['category_sales']['hotel']); ?></p>
    </div>

    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>