<?php
// sales_functions.php – Single source of truth for sales calculations
// Fixed to handle DATETIME columns correctly for daily reports

function calculateSales($conn, $startDate, $endDate) {
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

    // Helper to get next day for exclusive upper bound
    $endDateNext = date('Y-m-d', strtotime($endDate . ' +1 day'));

    // ---------- SALES table (Air Tickets) ----------
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
                  WHERE IssueDate >= '$startDate' AND IssueDate < '$endDateNext'";
    
    $sales_result = $conn->query($sales_sql);
    if ($sales_result && $sales_row = $sales_result->fetch_assoc()) {
        $ticket_sales = $sales_row['total_sales'];
        $result['total_sales'] += $ticket_sales;
        $result['total_profit'] += $sales_row['total_profit'];
        $result['total_refund'] += $sales_row['total_refund'];
        $result['total_reissue'] += $sales_row['total_reissue'];
        $result['total_due'] += $sales_row['total_due'];
        $result['category_sales']['ticket'] = $ticket_sales;
        
        // Purchase for tickets (non‑IATA, not refund)
        $ticket_purchase_sql = "SELECT COALESCE(SUM(NetPayment), 0) as total_net 
                               FROM sales 
                               WHERE IssueDate >= '$startDate' AND IssueDate < '$endDateNext'
                               AND Source != 'IATA' 
                               AND Source IS NOT NULL 
                               AND Source != ''
                               AND Remarks != 'Refund'";
        $purchase_result = $conn->query($ticket_purchase_sql);
        if ($purchase_result && $purchase_row = $purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $purchase_row['total_net'];
        }
    }

    // ---------- Collection from payments table ----------
    $collection_sql = "SELECT COALESCE(SUM(Amount), 0) as total_collection 
                      FROM payments 
                      WHERE PaymentDate >= '$startDate' AND PaymentDate < '$endDateNext'";
    $collection_result = $conn->query($collection_sql);
    if ($collection_result && $collection_row = $collection_result->fetch_assoc()) {
        $result['total_collection'] += $collection_row['total_collection'];
    }

    // ---------- HOTEL table ----------
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
                  WHERE issue_date >= '$startDate' AND issue_date < '$endDateNext'";
    
    $hotel_result = $conn->query($hotel_sql);
    if ($hotel_result && $hotel_row = $hotel_result->fetch_assoc()) {
        $hotel_sales = $hotel_row['total_sales'] - $hotel_row['total_refund'];
        $result['total_sales'] += $hotel_sales;
        $result['total_profit'] += $hotel_row['total_profit'];
        $result['total_refund'] += $hotel_row['total_refund'];
        $result['total_due'] += $hotel_row['total_due'];
        $result['category_sales']['hotel'] = $hotel_sales;
        
        $hotel_purchase_sql = "SELECT COALESCE(SUM(net_price), 0) as total_net 
                              FROM hotel 
                              WHERE issue_date >= '$startDate' AND issue_date < '$endDateNext'
                              AND source != 'OWN' 
                              AND source IS NOT NULL 
                              AND source != ''";
        $hotel_purchase_result = $conn->query($hotel_purchase_sql);
        if ($hotel_purchase_result && $hotel_purchase_row = $hotel_purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $hotel_purchase_row['total_net'];
        }
    }

    // ---------- STUDENT table ----------
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
                  WHERE `received date` >= '$startDate' AND `received date` < '$endDateNext'";
    
    $student_result = $conn->query($student_sql);
    if ($student_result && $student_row = $student_result->fetch_assoc()) {
        $student_sales = $student_row['total_sales'] - $student_row['total_refund'];
        $result['total_sales'] += $student_sales;
        $result['total_profit'] += $student_row['total_profit'];
        $result['total_refund'] += $student_row['total_refund'];
        $result['total_due'] += $student_row['total_due'];
        $result['category_sales']['student_visa'] = $student_sales;
        
        $student_purchase_sql = "SELECT COALESCE(SUM(net), 0) as total_net 
                               FROM student 
                               WHERE `received date` >= '$startDate' AND `received date` < '$endDateNext'
                               AND source != 'OWN' 
                               AND source IS NOT NULL 
                               AND source != ''";
        $student_purchase_result = $conn->query($student_purchase_sql);
        if ($student_purchase_result && $student_purchase_row = $student_purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $student_purchase_row['total_net'];
        }
    }

    // ---------- UMRAH table ----------
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
                  WHERE orderdate >= '$startDate' AND orderdate < '$endDateNext'";
    
    $umrah_result = $conn->query($umrah_sql);
    if ($umrah_result && $umrah_row = $umrah_result->fetch_assoc()) {
        $umrah_sales = $umrah_row['total_sales'] - $umrah_row['total_refund'];
        $result['total_sales'] += $umrah_sales;
        $result['total_profit'] += $umrah_row['total_profit'];
        $result['total_refund'] += $umrah_row['total_refund'];
        $result['total_due'] += $umrah_row['total_due'];
        $result['category_sales']['umrah'] = $umrah_sales;
        
        $umrah_purchase_sql = "SELECT COALESCE(SUM(`net payment`), 0) as total_net 
                              FROM umrah 
                              WHERE orderdate >= '$startDate' AND orderdate < '$endDateNext'
                              AND source != 'OWN' 
                              AND source IS NOT NULL 
                              AND source != ''";
        $umrah_purchase_result = $conn->query($umrah_purchase_sql);
        if ($umrah_purchase_result && $umrah_purchase_row = $umrah_purchase_result->fetch_assoc()) {
            $result['total_purchase'] += $umrah_purchase_row['total_net'];
        }
    }

    // ---------- VISA table ----------
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
                  WHERE orderdate >= '$startDate' AND orderdate < '$endDateNext'";
    
    $visa_result = $conn->query($visa_sql);
    if ($visa_result && $visa_row = $visa_result->fetch_assoc()) {
        $visa_sales = $visa_row['total_sales'] - $visa_row['total_refund'];
        $result['total_sales'] += $visa_sales;
        $result['total_profit'] += $visa_row['total_profit'];
        $result['total_refund'] += $visa_row['total_refund'];
        $result['total_due'] += $visa_row['total_due'];
        $result['category_sales']['visa'] = $visa_sales;
        
        $visa_purchase_sql = "SELECT COALESCE(SUM(`Net Payment`), 0) as total_net 
                             FROM visa 
                             WHERE orderdate >= '$startDate' AND orderdate < '$endDateNext'
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
?>