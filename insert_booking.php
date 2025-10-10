<?php
// Include necessary files
require 'db.php';
require 'auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Booking</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .required:after {
            content: " *";
            color: red;
        }
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: 1px solid #e3e6f0;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 30px;
            font-weight: 600;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include 'nav.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Booking</h5>
                        <a href="bookings_list.php" class="btn btn-light btn-sm">
                            <i class="fas fa-list me-1"></i>View All Bookings
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        // Handle form submission
                        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                            // Sanitize input data
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

                            // Insert query
                            $sql = "INSERT INTO bookings (party_name, passenger_name, route, booked_by, booked_on, pcc, time_limit, status, pnr, reference) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "ssssssssss", 
                                $party_name, $passenger_name, $route, $booked_by, $booked_on, 
                                $pcc, $time_limit, $status, $pnr, $reference);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Success!</strong> Booking has been added successfully.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>';
                            } else {
                                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>Error!</strong> Failed to add booking: ' . mysqli_error($conn) . '
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>';
                            }
                            
                            mysqli_stmt_close($stmt);
                        }
                        ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="party_name" class="form-label required">Party Name</label>
                                    <input type="text" class="form-control" id="party_name" name="party_name" required 
                                           placeholder="Enter party or group name">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="passenger_name" class="form-label required">Passenger Name</label>
                                    <input type="text" class="form-control" id="passenger_name" name="passenger_name" required 
                                           placeholder="Enter passenger name">
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="route" class="form-label">Route</label>
                                    <textarea class="form-control" id="route" name="route" rows="2" 
                                              placeholder="Enter travel route details"></textarea>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="booked_by" class="form-label">Booked By</label>
                                    <input type="text" class="form-control" id="booked_by" name="booked_by" 
                                           placeholder="Person who made booking" value="<?php echo $_SESSION['username'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="booked_on" class="form-label">Booked On</label>
                                    <input type="datetime-local" class="form-control" id="booked_on" name="booked_on" 
                                           value="<?php echo date('Y-m-d\TH:i'); ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="pcc" class="form-label">PCC</label>
                                    <input type="text" class="form-control" id="pcc" name="pcc" 
                                           placeholder="Enter PCC code">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="time_limit" class="form-label">Time Limit</label>
                                    <input type="datetime-local" class="form-control" id="time_limit" name="time_limit">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Pending" selected>Pending</option>
                                        <option value="Confirmed">Confirmed</option>
                                        <option value="Cancelled">Cancelled</option>
                                        <option value="Completed">Completed</option>
                                        <option value="On Hold">On Hold</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="pnr" class="form-label">PNR</label>
                                    <input type="text" class="form-control" id="pnr" name="pnr" 
                                           placeholder="Enter PNR number">
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="reference" class="form-label">Reference</label>
                                    <input type="text" class="form-control" id="reference" name="reference" 
                                           placeholder="Enter reference information">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="reset" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-save me-1"></i>Save Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set default time limit to 24 hours from now
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
            
            const timeLimitField = document.getElementById('time_limit');
            if (timeLimitField && !timeLimitField.value) {
                timeLimitField.value = tomorrow.toISOString().slice(0, 16);
            }
            
            // Auto-focus on first field
            document.getElementById('party_name').focus();
        });
    </script>
</body>
</html>