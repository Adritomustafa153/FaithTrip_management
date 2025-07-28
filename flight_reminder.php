<?php
require_once 'db.php';

function getTodaysFlightsCount($conn) {
    $today = date('Y-m-d');
    
    // Modified query to properly filter for today's flights only
    $query = "SELECT COUNT(*) as count FROM sales 
              WHERE (
                    (FlightDate = ? AND FlightDate != '0000-00-00') 
                    OR 
                    (ReturnDate = ? AND ReturnDate != '0000-00-00')
              )
              AND Remarks = 'Sell'";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $today, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $row['count'];
}

$notificationCount = getTodaysFlightsCount($conn);
?>