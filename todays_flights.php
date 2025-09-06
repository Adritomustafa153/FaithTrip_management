<?php
require_once 'db.php';
require_once 'nav.php';

function getTodaysFlightsWithDetails($conn) {
    $today = date('Y-m-d');
    
    $query = "SELECT 
                SaleID, 
                PartyName,
                PassengerName, 
                airlines, 
                TicketRoute, 
                FlightDate, 
                ReturnDate,
                Class,
                PNR,
                TicketNumber,
                SalesPersonName
              FROM sales 
              WHERE (
                    (FlightDate = ? AND FlightDate != '0000-00-00') 
                    OR 
                    (ReturnDate = ? AND ReturnDate != '0000-00-00')
              )
              AND Remarks IN ('Air Ticket Sale', 'Reissue')
              ORDER BY FlightDate, ReturnDate";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $today, $today);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $flights = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $flights[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $flights;
}

$todaysFlights = getTodaysFlightsWithDetails($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Flights</title>
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb.min.css" />
</head>
<body>
    <div class="container mt-4">
        <h2>Today's Flights (<?php echo date('Y-m-d'); ?>)</h2>
        
        <?php if (count($todaysFlights) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Party</th>
                            <th>Passenger</th>
                            <th>Airline</th>
                            <th>Route</th>
                            <th>Flight Date</th>
                            <th>Return Date</th>
                            <th>Class</th>
                            <th>PNR</th>
                            <th>Sales Person</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todaysFlights as $flight): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($flight['PartyName'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($flight['PassengerName']); ?></td>
                                <td><?php echo htmlspecialchars($flight['airlines']); ?></td>
                                <td><?php echo htmlspecialchars($flight['TicketRoute']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($flight['FlightDate']);
                                    if ($flight['FlightDate'] == date('Y-m-d')) {
                                        echo ' <span class="badge bg-primary">Departing</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($flight['ReturnDate']);
                                    if ($flight['ReturnDate'] == date('Y-m-d')) {
                                        echo ' <span class="badge bg-success">Returning</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($flight['Class']); ?></td>
                                <td><?php echo htmlspecialchars($flight['PNR']); ?></td>
                                <td><?php echo htmlspecialchars($flight['SalesPersonName'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No flights scheduled for today.
            </div>
        <?php endif; ?>
    </div>

    <!-- MDB -->
    <!-- <script type="text/javascript" src="js/mdb.umd.min.js"></script> -->
</body>
</html>