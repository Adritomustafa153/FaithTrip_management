<?php
require 'db.php';
require 'auth_check.php';

// Get all bookings in ASCENDING order by time_limit
$query = "SELECT * FROM bookings ORDER BY time_limit ASC";
$result = mysqli_query($conn, $query);

$currentDateTime = date('Y-m-d H:i:s');
$notificationTimeLimit = date('Y-m-d H:i:s', strtotime('+30 minutes'));

if (mysqli_num_rows($result) > 0) {
    echo '<table class="bookings-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Booking ID</th>';
    echo '<th>Party Name</th>';
    echo '<th>Passenger Name</th>';
    echo '<th>Route</th>';
    echo '<th>Booked By</th>';
    echo '<th>Booked On</th>';
    echo '<th>PCC</th>';
    echo '<th>Time Limit</th>';
    echo '<th>Status</th>';
    echo '<th>PNR</th>';
    echo '<th>Reference</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($row = mysqli_fetch_assoc($result)) {
        $isUrgent = ($row['time_limit'] >= $currentDateTime && $row['time_limit'] <= $notificationTimeLimit);
        $rowClass = $isUrgent ? 'time-critical' : '';
        
        echo '<tr class="' . $rowClass . '">';
        echo '<td>' . htmlspecialchars($row['booking_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['party_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['passenger_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['route']) . '</td>';
        echo '<td>' . htmlspecialchars($row['booked_by']) . '</td>';
        echo '<td>' . htmlspecialchars($row['booked_on']) . '</td>';
        echo '<td>' . htmlspecialchars($row['pcc']) . '</td>';
        echo '<td>';
        echo htmlspecialchars($row['time_limit']);
        if ($isUrgent) {
            echo ' <span class="urgency-badge">URGENT</span>';
        }
        echo '</td>';
        echo '<td class="status-' . strtolower($row['status']) . '">' . htmlspecialchars($row['status']) . '</td>';
        echo '<td>' . htmlspecialchars($row['pnr']) . '</td>';
        echo '<td>' . htmlspecialchars($row['reference']) . '</td>';
        echo '<td>';
        echo '<button class="btn-action btn-edit" onclick="editBooking(' . $row['booking_id'] . ')">';
        echo '<i class="fas fa-edit"></i> Edit';
        echo '</button>';
        echo '<button class="btn-action btn-delete" onclick="deleteBooking(' . $row['booking_id'] . ')">';
        echo '<i class="fas fa-trash"></i> Delete';
        echo '</button>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<div class="alert alert-info text-center">No bookings found.</div>';
}
?>