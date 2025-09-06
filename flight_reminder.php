<?php
require_once 'db.php'; // Use the new connection handler

function getTodaysFlightsCount($conn) {
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as count FROM sales 
              WHERE (FlightDate = ? OR ReturnDate = ?) 
              AND Remarks = 'Air Ticket Sell' OR  'Reissue'";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        return 0;
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $today, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $row['count'] ?? 0;
}

$notificationCount = getTodaysFlightsCount($conn);
?>