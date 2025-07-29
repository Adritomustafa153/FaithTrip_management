<?php
require_once 'db.php';

function getIataPaymentAmounts($conn) {
    $currentDay = date('j');
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    $amounts = [
        'first_period' => 0,
        'second_period' => 0,
        'show_reminder' => false,
        'period' => ''
    ];
    
    // Check if we're in reminder periods (10-15 or 25-30)
    if (($currentDay >= 10 && $currentDay <= 15) || ($currentDay >= 25 && $currentDay <= 30)) {
        $amounts['show_reminder'] = true;
        
        if ($currentDay >= 10 && $currentDay <= 15) {
            // Calculate for previous month's 16-30/31
            $amounts['period'] = '16th-30th of last month';
            $prevMonth = $currentMonth - 1;
            $prevYear = $currentYear;
            
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear--;
            }
            
            $startDate = date('Y-m-16', strtotime("$prevYear-$prevMonth-01"));
            $endDate = date('Y-m-t', strtotime("$prevYear-$prevMonth-01"));
            
        } else {
            // Calculate for current month's 1-15
            $amounts['period'] = '1st-15th of this month';
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-15');
        }
        
        // Query to get total net payment for the period
        $query = "SELECT SUM(NetPayment) as total FROM sales 
                  WHERE IssueDate BETWEEN ? AND ?
                  AND Source = 'IATA'";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($currentDay >= 10 && $currentDay <= 15) {
            $amounts['first_period'] = $row['total'] ?? 0;
        } else {
            $amounts['second_period'] = $row['total'] ?? 0;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return $amounts;
}

$iataReminder = getIataPaymentAmounts($conn);
?>