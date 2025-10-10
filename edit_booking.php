<?php
require 'db.php';
require 'auth_check.php';

$bookingId = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission for editing
    $party_name = mysqli_real_escape_string($conn, $_POST['party_name']);
    $passenger_name = mysqli_real_escape_string($conn, $_POST['passenger_name']);
    $route = mysqli_real_escape_string($conn, $_POST['route']);
    $booked_by = mysqli_real_escape_string($conn, $_POST['booked_by']);
    $booked_on = mysqli_real_escape_string($conn, $_POST['booked_on']);
    $pcc = mysqli_real_escape_string($conn, $_POST['pcc']);
    $time_limit = mysqli_real_escape_string($conn, $_POST['time_limit']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $pnr = mysqli_real_escape_string($conn, $_POST['pnr']);
    $reference = mysqli_real_escape_string($conn, $_POST['reference']);

    $sql = "UPDATE bookings SET 
            party_name = ?, passenger_name = ?, route = ?, booked_by = ?, 
            booked_on = ?, pcc = ?, time_limit = ?, status = ?, pnr = ?, reference = ?
            WHERE booking_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssi", 
        $party_name, $passenger_name, $route, $booked_by, $booked_on, 
        $pcc, $time_limit, $status, $pnr, $reference, $bookingId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Booking updated successfully!";
    } else {
        $error = "Error updating booking: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

// Fetch current booking data
if ($bookingId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM bookings WHERE booking_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $bookingId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$booking) {
        die("Booking not found");
    }
} else {
    die("No booking ID provided");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
        <?php include 'nav.php';?>
    <div class="container mt-4">
        <h2>Edit Booking</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Party Name</label>
                    <input type="text" class="form-control" name="party_name" value="<?php echo htmlspecialchars($booking['party_name']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Passenger Name</label>
                    <input type="text" class="form-control" name="passenger_name" value="<?php echo htmlspecialchars($booking['passenger_name']); ?>" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Route</label>
                    <textarea class="form-control" name="route" rows="2"><?php echo htmlspecialchars($booking['route']); ?></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Booked By</label>
                    <input type="text" class="form-control" name="booked_by" value="<?php echo htmlspecialchars($booking['booked_by']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Booked On</label>
                    <input type="datetime-local" class="form-control" name="booked_on" value="<?php echo date('Y-m-d\TH:i', strtotime($booking['booked_on'])); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">PCC</label>
                    <input type="text" class="form-control" name="pcc" value="<?php echo htmlspecialchars($booking['pcc']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Time Limit</label>
                    <input type="datetime-local" class="form-control" name="time_limit" value="<?php echo date('Y-m-d\TH:i', strtotime($booking['time_limit'])); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="Pending" <?php echo $booking['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo $booking['status'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="Cancelled" <?php echo $booking['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="Completed" <?php echo $booking['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="On Hold" <?php echo $booking['status'] == 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">PNR</label>
                    <input type="text" class="form-control" name="pnr" value="<?php echo htmlspecialchars($booking['pnr']); ?>">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Reference</label>
                    <input type="text" class="form-control" name="reference" value="<?php echo htmlspecialchars($booking['reference']); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Booking</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>