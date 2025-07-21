<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch sources for dropdown
$sources_query = "SELECT agency_name FROM sources";
$sources_result = mysqli_query($conn, $sources_query);

// Get sale ID from URL parameter
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the sale record to be refunded
$sale_query = "SELECT * FROM sales WHERE SaleID = $sale_id AND Remarks = 'Sell'";
$sale_result = mysqli_query($conn, $sale_query);
$sale_data = $sale_result->fetch_assoc();

// Fetch sales records for search functionality
$where = "";
if (isset($_GET['search_term']) && !empty($_GET['search_term'])) {
    $search_term = $conn->real_escape_string($_GET['search_term']);
    $where = " WHERE (PassengerName LIKE '%$search_term%' OR 
             TicketNumber LIKE '%$search_term%' OR 
             PNR LIKE '%$search_term%') AND Remarks = 'Sell'";
} else {
    $where = " WHERE Remarks = 'Sell'";
}

$search_query = "SELECT SaleID, PassengerName, TicketNumber, PNR FROM sales $where LIMIT 10";
$search_result = mysqli_query($conn, $search_query);

// Process refund if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $refund_charge = floatval($_POST['refund_charge']);
    $service_charge = floatval($_POST['service_charge']);
    $total_refund = $refund_charge + $service_charge;
    $refund_amount = floatval($_POST['refund_amount']);
    $source = $conn->real_escape_string($_POST['source']);
    $remarks = "Refund";
    
    // Update the original sale record
    $update_query = "UPDATE sales SET 
                    Remarks = ?,
                    PaymentStatus = 'Paid',
                    Source = ?
                    WHERE SaleID = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $remarks, $source, $sale_id);
    $stmt->execute();
    
    // Insert a new record for the refund
    $insert_query = "INSERT INTO sales (
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, IssueDate, FlightDate, ReturnDate, 
                    PNR, BillAmount, NetPayment, Profit, PaymentStatus, 
                    PaymentMethod, SalesPersonName, invoice_number, Remarks, Source
                ) SELECT 
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, CURDATE(), FlightDate, ReturnDate, 
                    PNR, ?, ?, ?, 'Paid', 
                    'Refund', SalesPersonName, CONCAT('REF-', FLOOR(1000 + (RAND() * (1000000 - 1000))), 'Refund', ?
                FROM sales WHERE SaleID = ?";
    
    $profit = -1 * abs($total_refund); // Negative profit for refund
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ddss", $total_refund, $refund_amount, $profit, $source, $sale_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Refund processed successfully!'); window.location='invoice_list.php';</script>";
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
        <form action="" method="POST" id="refundForm">
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
                        <label>Ticket Route:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['TicketRoute']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>PNR:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['PNR']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Ticket Number:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['TicketNumber']) ?>" readonly>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Original Bill Amount:</label>
                        <input type="text" class="form-control readonly" value="<?= number_format($sale_data['BillAmount'], 2) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Original Net Payment:</label>
                        <input type="text" id="original_net" class="form-control readonly" value="<?= number_format($sale_data['NetPayment'], 2) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Original Profit:</label>
                        <input type="text" class="form-control readonly" value="<?= number_format($sale_data['Profit'], 2) ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Refund Section -->
            <div class="refund-section">
                <h4>Refund Details</h4>
                <div class="row">
                    <div class="col-md-6">
                        <label for="source">Source (Agency Name):</label>
                        <select name="source" id="source" class="form-control" required>
                            <option value="">Select Source</option>
                            <?php 
                            mysqli_data_seek($sources_result, 0); // Reset pointer
                            while($row = mysqli_fetch_assoc($sources_result)): ?>
                                <option value="<?= htmlspecialchars($row['agency_name']) ?>">
                                    <?= htmlspecialchars($row['agency_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="salesperson">Salesperson:</label>
                        <input type="text" name="salesperson" class="form-control readonly" value="<?= htmlspecialchars($sale_data['SalesPersonName']) ?>" readonly>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="refund_charge">Refund Charge:</label>
                        <input type="number" name="refund_charge" id="refund_charge" class="form-control" required min="0" step="0.01" value="0">
                    </div>
                    <div class="col-md-3">
                        <label for="service_charge">Service Charge:</label>
                        <input type="number" name="service_charge" id="service_charge" class="form-control" required min="0" step="0.01" value="0">
                    </div>
                    <div class="col-md-3">
                        <label for="total_refund">Total Refund:</label>
                        <input type="number" name="total_refund" id="total_refund" class="form-control readonly" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="refund_amount">Refund Amount:</label>
                        <input type="number" name="refund_amount" id="refund_amount" class="form-control readonly" readonly>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <button type="submit" class="btn-submit">Process Refund</button>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-danger">
                <?php if ($sale_id > 0): ?>
                    No valid sale record found for refund or this record has already been refunded.
                <?php else: ?>
                    Please select a sale record to process refund.
                <?php endif; ?>
            </div>
            
            <?php if (isset($_GET['search_term']) && $search_result->num_rows > 0): ?>
                <div class="mt-4">
                    <h5>Search Results:</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Passenger</th>
                                <th>Ticket No</th>
                                <th>PNR</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $search_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['PassengerName']) ?></td>
                                    <td><?= htmlspecialchars($row['TicketNumber']) ?></td>
                                    <td><?= htmlspecialchars($row['PNR']) ?></td>
                                    <td>
                                        <a href="?id=<?= $row['SaleID'] ?>&search_term=<?= urlencode($_GET['search_term']) ?>" class="btn btn-sm btn-primary">
                                            Select for Refund
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Calculate refund amounts when charges change
            $('#refund_charge, #service_charge').on('input', function() {
                const refundCharge = parseFloat($('#refund_charge').val()) || 0;
                const serviceCharge = parseFloat($('#service_charge').val()) || 0;
                const netPayment = parseFloat($('#original_net').val()) || 0;
                
                const totalRefund = refundCharge + serviceCharge;
                const refundAmount = netPayment - totalRefund;
                
                $('#total_refund').val(totalRefund.toFixed(2));
                $('#refund_amount').val(refundAmount.toFixed(2));
            });

            // Form validation
            $('#refundForm').submit(function(e) {
                const refundAmount = parseFloat($('#refund_amount').val());
                if (refundAmount < 0) {
                    alert('Refund amount cannot be negative. Please adjust charges.');
                    e.preventDefault();
                }
                
                if ($('#source').val() === '') {
                    alert('Please select a source/agency.');
                    e.preventDefault();
                }
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