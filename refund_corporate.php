<?php
include 'db.php';

// Function to check if a ticket is already refunded
function isTicketRefunded($conn, $pnr, $ticket_number) {
    $query = "SELECT COUNT(*) AS count FROM sales 
              WHERE (PNR = ? OR TicketNumber = ?) 
              AND Remarks = 'Refund'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $pnr, $ticket_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['count'] > 0;
}

// Fetch sources for dropdown
$sources_query = "SELECT agency_name FROM sources";
$sources_result = mysqli_query($conn, $sources_query);

// Get sale ID from URL parameter
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the sale record to be refunded
$sale_query = "SELECT * FROM sales WHERE SaleID = $sale_id";
$sale_result = mysqli_query($conn, $sale_query);
$sale_data = $sale_result->fetch_assoc();

// Initialize variables
$has_refund = false;
$refund_message = "";
$is_refunded = false;

// Check if current record is already refunded
if ($sale_data) {
    $is_refunded = ($sale_data['Remarks'] == 'Refund') || 
                  isTicketRefunded($conn, $sale_data['PNR'], $sale_data['TicketNumber']);
}

// Fetch sales records for search functionality
$where = "";
if (isset($_GET['search_term']) && !empty($_GET['search_term'])) {
    $search_term = $conn->real_escape_string($_GET['search_term']);
    $where = " WHERE (PassengerName LIKE '%$search_term%' OR 
             TicketNumber LIKE '%$search_term%' OR 
             PNR LIKE '%$search_term%') AND Remarks != 'Refund'";
    
    // Check if there are any refund records for this search term
    $refund_check_query = "SELECT COUNT(*) AS refund_count, MAX(refund_date) AS last_refund_date, 
                          MAX(refundtc) AS refund_amount FROM sales 
                          WHERE (PNR LIKE '%$search_term%' OR TicketNumber LIKE '%$search_term%') 
                          AND Remarks = 'Refund'";
    $refund_check_result = mysqli_query($conn, $refund_check_query);
    if ($refund_check_result) {
        $refund_data = $refund_check_result->fetch_assoc();
        $has_refund = $refund_data['refund_count'] > 0;
        
        if ($has_refund) {
            $refund_message = "This ticket was refunded on " . date('M d, Y', strtotime($refund_data['last_refund_date'])) . 
                             " with amount " . number_format($refund_data['refund_amount'], 2);
        }
    }
}

$search_query = "SELECT s.*, 'Sell' AS Status
                FROM sales s $where 
                ORDER BY s.SaleID DESC LIMIT 10";
$search_result = mysqli_query($conn, $search_query);

// Process refund if form is submitted and not already refunded
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_refunded) {
    $refund_charge = floatval($_POST['refund_charge']);
    $service_charge = floatval($_POST['service_charge']);
    $total_refund = $refund_charge + $service_charge;
    $refund_amount = abs(floatval($_POST['refund_amount']));
    $refund_tc = $refund_amount;
    $source = $conn->real_escape_string($_POST['source']);
    $refund_date = $conn->real_escape_string($_POST['refund_date']);
    
    // Insert a new record for the refund
    $insert_query = "INSERT INTO sales (
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, IssueDate, FlightDate, ReturnDate, 
                    PNR, BillAmount, NetPayment, Profit, PaymentStatus, 
                    PaymentMethod, SalesPersonName, Remarks, Source, refund_date, refundtc
                ) SELECT 
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, CURDATE(), FlightDate, ReturnDate, 
                    PNR, ?, ?, ?, 'Paid', 
                    PaymentMethod, SalesPersonName, 'Refund', ?, ?, ?
                FROM sales WHERE SaleID = ?";
    
    $profit = $service_charge;
    $net_payment = $refund_charge;
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("dddsssi", $total_refund, $net_payment, $profit, $source, $refund_date, $refund_tc, $sale_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        // Show loading page
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Processing Refund</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: Arial, sans-serif;
                }
                .loading-container {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(255,255,255,0.9);
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                }
                .loading-gif {
                    width: 100px;
                    height: 100px;
                }
                .loading-text {
                    margin-top: 20px;
                    font-size: 18px;
                    color: #333;
                }
            </style>
        </head>
        <body>
            <div class="loading-container">
                <img src="rfnd.gif" class="loading-gif" alt="Processing...">
                <p class="loading-text">Processing refund, please wait...</p>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = "refund_corporate.php?success=1";
                }, 3000);
            </script>
        </body>
        </html>';
        exit();
    } else {
        echo "<script>alert('Error processing refund.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Processing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            position: relative;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus, select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn-submit {
            background-color: #4a71ff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #3a5bd9;
        }
        .readonly {
            background-color: #e9ecef;
        }
        .refund-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #4a71ff;
        }
        .refund-section h4 {
            color: #4a71ff;
            margin-bottom: 20px;
        }
        .original-info {
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .search-results {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
            display: none;
        }
        .search-results a {
            display: block;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
        }
        .search-results a:hover {
            background-color: #f5f5f5;
        }
        .status-refunded {
            color: #dc3545;
            font-weight: bold;
        }
        .status-sell {
            color: #28a745;
            font-weight: bold;
        }
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #6c757d !important;
        }
        .btn-disabled:hover {
            background-color: #6c757d !important;
        }
        .alert-success {
            animation: fadeIn 0.5s;
        }
        .refund-notice {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .refund-details {
            margin-top: 10px;
            font-size: 14px;
            color: #856404;
        }
        .form-disabled {
            opacity: 0.7;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .form-row > div {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Refund processed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h2>Refund Processing</h2>
        
        <!-- Search Form -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-10 position-relative">
                    <label for="search_term">Search (Passenger, Ticket No, or PNR):</label>
                    <input type="text" id="search_term" name="search_term" class="form-control" 
                           placeholder="Search by Passenger Name, Ticket Number, or PNR"
                           value="<?= isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : '' ?>">
                    <div class="search-results" id="searchResults"></div>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </div>
        </form>

        <?php if ($sale_data): ?>
        <form action="" method="POST" id="refundForm" <?= $is_refunded ? 'class="form-disabled"' : '' ?>>
            <!-- Original Sale Information -->
            <div class="original-info">
                <h4>Original Sale Information</h4>
                <div class="row">
                    <div class="col-md-4">
                        <label>Company Name:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['PartyName']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Passenger Name:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['PassengerName']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Invoice Number:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['invoice_number']) ?>" readonly>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Airlines:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['airlines']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Ticket Route:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['TicketRoute']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>PNR:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['PNR']) ?>" readonly>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Ticket Number:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['TicketNumber']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Original Bill Amount:</label>
                        <input type="text" class="form-control readonly" value="<?= number_format($sale_data['BillAmount'], 2) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Original Net Payment:</label>
                        <input type="text" id="original_net" class="form-control readonly" value="<?= number_format($sale_data['NetPayment'], 2) ?>" readonly>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Original Profit:</label>
                        <input type="text" class="form-control readonly" value="<?= number_format($sale_data['Profit'], 2) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Flight Date:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['FlightDate']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Return Date:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['ReturnDate']) ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Refund Section -->
            <div class="refund-section">
                <h4>Refund Details</h4>
                <?php if ($is_refunded): ?>
                    <div class="alert alert-warning">
                        <strong>This ticket has already been refunded and cannot be refunded again.</strong>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-4">
                        <label for="source">Source (Agency Name):</label>
                        <select name="source" id="source" class="form-control" required <?= $is_refunded ? 'disabled' : '' ?>>
                            <option value="">Select Source</option>
                            <?php 
                            mysqli_data_seek($sources_result, 0);
                            while($row = mysqli_fetch_assoc($sources_result)): ?>
                                <option value="<?= htmlspecialchars($row['agency_name']) ?>">
                                    <?= htmlspecialchars($row['agency_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="salesperson">Salesperson:</label>
                        <input type="text" name="salesperson" class="form-control readonly" value="<?= htmlspecialchars($sale_data['SalesPersonName']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="refund_date">Refund Date:</label>
                        <input type="text" name="refund_date" id="refund_date" class="form-control" required <?= $is_refunded ? 'disabled' : '' ?>
                               value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="refund_charge">Refund Charge:</label>
                        <input type="number" name="refund_charge" id="refund_charge" class="form-control" required min="0" step="0.01" value="0" <?= $is_refunded ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label for="service_charge">Service Charge:</label>
                        <input type="number" name="service_charge" id="service_charge" class="form-control" required min="0" step="0.01" value="0" <?= $is_refunded ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label for="total_refund">Total Refund Charges:</label>
                        <input type="number" name="total_refund" id="total_refund" class="form-control readonly" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="refund_amount">Amount to Refund:</label>
                        <input type="number" name="refund_amount" id="refund_amount" class="form-control readonly" readonly>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <button type="submit" class="btn-submit <?= $is_refunded ? 'btn-disabled' : '' ?>" <?= $is_refunded ? 'disabled' : '' ?>>
                        <?= $is_refunded ? 'Already Refunded' : 'Process Refund' ?>
                    </button>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-danger">
                <?php if ($sale_id > 0): ?>
                    No valid sale record found for refund.
                <?php else: ?>
                    Please select a sale record to process refund.
                <?php endif; ?>
            </div>
            
            <?php if (isset($_GET['search_term']) && ($search_result->num_rows > 0 || $has_refund)): ?>
                <div class="mt-4">
                    <?php if ($has_refund): ?>
                        <div class="alert alert-warning">
                            <strong>Refund Notice:</strong> <?= $refund_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($search_result->num_rows > 0): ?>
                        <h5>Available Sales Records:</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Passenger</th>
                                    <th>Ticket No</th>
                                    <th>PNR</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $search_result->fetch_assoc()): 
                                    $is_already_refunded = isTicketRefunded($conn, $row['PNR'], $row['TicketNumber']);
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['PassengerName']) ?></td>
                                        <td><?= htmlspecialchars($row['TicketNumber']) ?></td>
                                        <td><?= htmlspecialchars($row['PNR']) ?></td>
                                        <td class="status-sell">
                                            <?= htmlspecialchars($row['Status']) ?>
                                        </td>
                                        <td>
                                            <?php if ($is_already_refunded): ?>
                                                <button class="btn btn-sm btn-secondary btn-disabled" disabled>
                                                    Already Refunded
                                                </button>
                                            <?php else: ?>
                                                <a href="?id=<?= $row['SaleID'] ?>&search_term=<?= urlencode($_GET['search_term']) ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    Select for Refund
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No available sales records found for this search. All matching records have been refunded.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // Initialize date picker
            flatpickr("#refund_date", {
                dateFormat: "Y-m-d",
                defaultDate: "today"
            });

            // Calculate refund amounts when charges change
            function calculateRefund() {
                const refundCharge = parseFloat($('#refund_charge').val()) || 0;
                const serviceCharge = parseFloat($('#service_charge').val()) || 0;
                const netPayment = parseFloat($('#original_net').val().replace(/,/g, '')) || 0;
                
                const totalRefund = refundCharge + serviceCharge;
                const refundAmount = netPayment - totalRefund;
                
                $('#total_refund').val(totalRefund.toFixed(2));
                $('#refund_amount').val(Math.max(0, refundAmount).toFixed(2));
            }

            // Bind calculation to input events
            $('#refund_charge, #service_charge').on('input', calculateRefund);

            // Form validation
            $('#refundForm').submit(function(e) {
                <?php if ($is_refunded): ?>
                    e.preventDefault();
                    alert('This ticket has already been refunded and cannot be refunded again.');
                    return false;
                <?php endif; ?>
                
                const refundAmount = parseFloat($('#refund_amount').val());
                if (isNaN(refundAmount) || refundAmount < 0) {
                    alert('Please enter valid refund charges.');
                    e.preventDefault();
                    return false;
                }
                
                if ($('#source').val() === '') {
                    alert('Please select a source/agency.');
                    e.preventDefault();
                    return false;
                }

                if (!$('#refund_date').val()) {
                    alert('Please select a refund date.');
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });

            // Live search functionality
            $('#search_term').on('input', function() {
                const searchTerm = $(this).val();
                if (searchTerm.length < 2) {
                    $('#searchResults').hide();
                    return;
                }
                
                $.get('search_refund.php', { term: searchTerm }, function(data) {
                    const results = $('#searchResults');
                    results.empty();
                    
                    if (data.length > 0) {
                        data.forEach(item => {
                            results.append(
                                `<a href="?id=${item.SaleID}&search_term=${encodeURIComponent(searchTerm)}">
                                    ${item.PassengerName} (Ticket: ${item.TicketNumber}, PNR: ${item.PNR})
                                </a>`
                            );
                        });
                        results.show();
                    } else {
                        results.hide();
                    }
                }, 'json');
            });

            // Hide results when clicking elsewhere
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#search_term, #searchResults').length) {
                    $('#searchResults').hide();
                }
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>