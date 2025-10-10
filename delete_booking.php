<?php
require 'db.php';
require 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $bookingId = $_GET['id'] ?? ($input['id'] ?? null);
    
    if ($bookingId) {
        $stmt = mysqli_prepare($conn, "DELETE FROM bookings WHERE booking_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $bookingId);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'No booking ID provided']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>