<?php
// Database connection (db.php)
$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'faithtrip_accounts';
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Simple authentication check (auth_check.php)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Navigation file (nav.php)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profiles - FaithTrip Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <style>
        .table-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .action-buttons .btn {
            margin-left: 5px;
        }
        .company-logo {
            width: 40px;
            height: 40px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 18px;
        }
        .badge-status {
            font-size: 0.85em;
        }
        .passport-expiry-warning {
            color: #dc3545;
            font-weight: bold;
        }
        .navbar-custom {
            background-color: #2c3e50;
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: #ecf0f1;
        }
        .navbar-custom .nav-link:hover {
            color: #3498db;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

    <div class="container-fluid">
        <div class="table-container">
            <div class="page-header">
                <h2><i class="fas fa-building me-2"></i> Company Profiles</h2>
                <a href="corporate_insert.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add Company
                </a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                    <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table id="companyTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Staff Name</th>
                            <th>Designation</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Passport Expiry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $mysqli->query("SELECT * FROM companyprofile ORDER BY CompanyID DESC");
                        
                        if ($result && $result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                // Check if passport is expired or expiring soon (within 60 days)
                                $passportWarning = '';
                                if (!empty($row['PassportExpiryDate']) && $row['PassportExpiryDate'] != '0000-00-00') {
                                    $expiryDate = new DateTime($row['PassportExpiryDate']);
                                    $today = new DateTime();
                                    $interval = $today->diff($expiryDate);
                                    
                                    if ($expiryDate < $today) {
                                        $passportWarning = '<span class="passport-expiry-warning">Expired</span>';
                                    } elseif ($interval->days < 60) {
                                        $passportWarning = '<span class="passport-expiry-warning">' . $interval->days . ' days</span>';
                                    }
                                }
                        ?>
                        <tr>
                            <td><?php echo $row['CompanyID']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="company-logo">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo $row['CompanyName']; ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo substr($row['CompanyAddress'], 0, 30); ?>...</small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $row['StaffName']; ?></td>
                            <td>
                                <span class="badge bg-secondary badge-status"><?php echo $row['Designation']; ?></span>
                            </td>
                            <td>
                                <?php if (!empty($row['CompanyEmail'])): ?>
                                    <a href="mailto:<?php echo $row['CompanyEmail']; ?>"><?php echo $row['CompanyEmail']; ?></a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['CompanyPhoneNumber'])): ?>
                                    <a href="tel:<?php echo $row['CompanyPhoneNumber']; ?>"><?php echo $row['CompanyPhoneNumber']; ?></a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($row['PassportExpiryDate']) && $row['PassportExpiryDate'] != '0000-00-00') {
                                    echo date('M d, Y', strtotime($row['PassportExpiryDate']));
                                    if (!empty($passportWarning)) {
                                        echo '<br>' . $passportWarning;
                                    }
                                } else {
                                    echo '<span class="text-muted">Not set</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_company.php?id=<?php echo $row['CompanyID']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $row['CompanyID']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal<?php echo $row['CompanyID']; ?>" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewModalLabel">Company Details - <?php echo $row['CompanyName']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Company Information</h6>
                                                <p><strong>Company Name:</strong> <?php echo $row['CompanyName']; ?></p>
                                                <p><strong>Address:</strong> <?php echo $row['CompanyAddress']; ?></p>
                                                <p><strong>Email:</strong> <?php echo !empty($row['CompanyEmail']) ? $row['CompanyEmail'] : 'Not provided'; ?></p>
                                                <p><strong>Phone:</strong> <?php echo !empty($row['CompanyPhoneNumber']) ? $row['CompanyPhoneNumber'] : 'Not provided'; ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Staff Information</h6>
                                                <p><strong>Staff Name:</strong> <?php echo $row['StaffName']; ?></p>
                                                <p><strong>Designation:</strong> <?php echo $row['Designation']; ?></p>
                                                <p><strong>Date of Birth:</strong> 
                                                    <?php 
                                                    if (!empty($row['StaffDateOfBirth']) && $row['StaffDateOfBirth'] != '0000-00-00') {
                                                        echo date('M d, Y', strtotime($row['StaffDateOfBirth']));
                                                    } else {
                                                        echo 'Not provided';
                                                    }
                                                    ?>
                                                </p>
                                                <p><strong>Personal Address:</strong> <?php echo !empty($row['PersonAddress']) ? $row['PersonAddress'] : 'Not provided'; ?></p>
                                                <p><strong>Personal Phone:</strong> <?php echo !empty($row['PersonPhoneNumber']) ? $row['PersonPhoneNumber'] : 'Not provided'; ?></p>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <h6>Passport Information</h6>
                                                <p><strong>Passport Number:</strong> <?php echo !empty($row['PassportNumber']) ? $row['PassportNumber'] : 'Not provided'; ?></p>
                                                <p><strong>Expiry Date:</strong> 
                                                    <?php 
                                                    if (!empty($row['PassportExpiryDate']) && $row['PassportExpiryDate'] != '0000-00-00') {
                                                        echo date('M d, Y', strtotime($row['PassportExpiryDate']));
                                                        if (!empty($passportWarning)) {
                                                            echo ' <span class="badge bg-danger">' . strip_tags($passportWarning) . '</span>';
                                                        }
                                                    } else {
                                                        echo 'Not provided';
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="edit_company.php?id=<?php echo $row['CompanyID']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit me-1"></i> Edit Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; 
                        else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No company records found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#companyTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "responsive": true,
                "language": {
                    "search": "Search companies:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });
        });
    </script>
</body>
</html>