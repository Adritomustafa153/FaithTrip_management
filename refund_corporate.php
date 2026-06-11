<?php
include 'auth_check.php';
include 'db.php';

// Fetch salespersons from sales_person table
$salespersons_query = "SELECT id, name FROM sales_person ORDER BY name";
$salespersons_result = mysqli_query($conn, $salespersons_query);

// Function to check if a ticket is already refunded
function isTicketRefunded($conn, $ticket_number) {
    $query = "SELECT COUNT(*) AS count FROM sales 
              WHERE TicketNumber = ? AND Remarks IN ('Refund', 'Source Refund', 'Refunded')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $ticket_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['count'] > 0;
}

// Get sources dropdown
$sources_query = "SELECT agency_name FROM sources";
$sources_result = mysqli_query($conn, $sources_query);

$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sale_query = "SELECT * FROM sales WHERE SaleID = $sale_id";
$sale_result = mysqli_query($conn, $sale_query);
$sale_data = $sale_result->fetch_assoc();

$is_refunded = false;
$pnr_has_refunds = false;
if ($sale_data) {
    $is_refunded = ($sale_data['Remarks'] == 'Refund' || $sale_data['Remarks'] == 'Refunded') || 
                  isTicketRefunded($conn, $sale_data['TicketNumber']);
    // Check if any other ticket in same PNR is refunded (just for info)
    $pnr_check = "SELECT COUNT(*) as cnt FROM sales WHERE PNR = ? AND Remarks IN ('Refund', 'Refunded') AND SaleID != ?";
    $stmt = $conn->prepare($pnr_check);
    $stmt->bind_param("si", $sale_data['PNR'], $sale_id);
    $stmt->execute();
    $pnr_has_refunds = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
}

// Handle search
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
$search_results = [];
if (!empty($search_term)) {
    $term = $conn->real_escape_string($search_term);
    $search_sql = "SELECT SaleID, PassengerName, TicketNumber, PNR, PartyName 
                   FROM sales 
                   WHERE (PassengerName LIKE '%$term%' OR TicketNumber LIKE '%$term%' OR PNR LIKE '%$term%')
                   AND Remarks NOT IN ('Refund', 'Source Refund', 'Refunded')
                   ORDER BY SaleID DESC LIMIT 10";
    $search_res = mysqli_query($conn, $search_sql);
    while ($row = mysqli_fetch_assoc($search_res)) {
        $search_results[] = $row;
    }
}

// Process refund
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_refunded && $sale_data) {
    $refund_charge = floatval($_POST['refund_charge']);
    $service_charge = floatval($_POST['service_charge']);
    $is_involuntary = isset($_POST['involuntary']) && $_POST['involuntary'] == '1';
    
    // Override for involuntary
    if ($is_involuntary) {
        $refund_charge = 0;
        $service_charge = 0;
    }
    $total_charges = $refund_charge + $service_charge;
    $source = $conn->real_escape_string($_POST['source']);
    $refund_date = $conn->real_escape_string($_POST['refund_date']);
    $sales_person_id = intval($_POST['sales_person_id']);
    
    // Get salesperson name
    $sp_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM sales_person WHERE id = $sales_person_id"));
    $sales_person_name = $sp_row ? $sp_row['name'] : $sale_data['SalesPersonName'];
    
    $payment_status = $sale_data['PaymentStatus'];
    $paid_amount = floatval($sale_data['PaidAmount'] ?? 0);
    $selling_price = floatval($sale_data['BillAmount']);
    $original_net = floatval($sale_data['NetPayment']);
    
    $client_refund = 0;
    $extra_charge = 0;
    
    // ================== REFUND CALCULATION ==================
    if ($is_involuntary) {
        // Involuntary: client gets full selling price
        $client_refund = $selling_price;
        $extra_charge = 0;
    } else {
        // Voluntary
        if ($payment_status == 'Paid') {
            $client_refund = $selling_price - $total_charges;
            if ($client_refund < 0) $client_refund = 0;
        } 
        elseif ($payment_status == 'Partially Paid') {
            $client_refund = $paid_amount - $total_charges;
            if ($client_refund < 0) {
                $extra_charge = abs($client_refund);
                $client_refund = 0;
            }
        }
        else { // Due
            $extra_charge = $total_charges;
            $client_refund = 0;
        }
    }
    
    // ================== UPDATE ORIGINAL SALE ==================
    $update_original = "UPDATE sales SET Remarks = 'Refunded' WHERE SaleID = ?";
    $stmt_up = $conn->prepare($update_original);
    $stmt_up->bind_param("i", $sale_id);
    $stmt_up->execute();
    
    // ================== CLIENT REFUND RECORD ==================
    if ($client_refund > 0) {
        $refund_bill_amount = $client_refund;   // For involuntary, this equals selling price
        $refund_net_payment = $is_involuntary ? 0 : $refund_charge;
        $refund_profit = $is_involuntary ? 0 : $service_charge;
        
        $sql1 = "INSERT INTO sales (
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, IssueDate, FlightDate, ReturnDate, 
                    PNR, BillAmount, NetPayment, Profit, PaymentStatus, 
                    PaymentMethod, SalesPersonName, Remarks, Source, refund_date, refundtc
                ) SELECT 
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, CURDATE(), FlightDate, ReturnDate, 
                    PNR, ?, ?, ?, 'Paid', 
                    PaymentMethod, ?, 'Refund', ?, ?, ?
                FROM sales WHERE SaleID = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("dddsdssi", $refund_bill_amount, $refund_net_payment, $refund_profit, $sales_person_name, $source, $refund_date, $client_refund, $sale_id);
        $stmt1->execute();
    }
    
    // ================== SOURCE REFUND RECORD ==================
    $source_refund_amount = $is_involuntary ? $original_net : ($original_net - $refund_charge);
    if ($source_refund_amount > 0) {
        $sql2 = "INSERT INTO sales (
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, IssueDate, FlightDate, ReturnDate, 
                    PNR, BillAmount, NetPayment, Profit, PaymentStatus, 
                    PaymentMethod, SalesPersonName, Remarks, Source, refund_date, refundtc
                ) SELECT 
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, CURDATE(), FlightDate, ReturnDate, 
                    PNR, ?, ?, ?, 'Paid', 
                    PaymentMethod, ?, 'Source Refund', ?, ?, 0
                FROM sales WHERE SaleID = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("dddsdss", $source_refund_amount, $refund_charge, $service_charge, $sales_person_name, $source, $refund_date, $sale_id);
        $stmt2->execute();
    }
    
    // ================== CANCELLATION CHARGE RECORD ==================
    if ($extra_charge > 0) {
        $sql3 = "INSERT INTO sales (
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, IssueDate, FlightDate, ReturnDate, 
                    PNR, BillAmount, NetPayment, Profit, PaymentStatus, 
                    PaymentMethod, SalesPersonName, Remarks, Source, refund_date, refundtc
                ) SELECT 
                    section, PartyName, PassengerName, airlines, TicketRoute, 
                    TicketNumber, Class, CURDATE(), FlightDate, ReturnDate, 
                    PNR, ?, ?, 0, 'Due', 
                    PaymentMethod, ?, 'Cancellation Charge', ?, ?, 0
                FROM sales WHERE SaleID = ?";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param("ddsdss", $extra_charge, $extra_charge, $sales_person_name, $source, $refund_date, $sale_id);
        $stmt3->execute();
    }
    
    // Show loading page with GIF and redirect after 3 seconds
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Processing Refund</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                background: #f5f5f5;
            }
            .loading-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255,255,255,0.95);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }
            .loading-gif {
                width: 100px;
                height: 100px;
                margin-bottom: 20px;
            }
            .loading-text {
                font-size: 18px;
                color: #333;
                font-weight: 500;
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Processing - Corporate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* same CSS as before – keep unchanged */
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f8f9fa; }
        .container { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0px 4px 15px rgba(0,0,0,0.1); margin-top: 20px; position: relative; }
        h2 { color: #2c3e50; margin-bottom: 25px; text-align: center; font-weight: 600; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: 500; margin-bottom: 8px; display: block; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; font-size: 16px; }
        .btn-submit { background-color: #4a71ff; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; width: 100%; transition: background-color 0.3s; }
        .btn-submit:hover { background-color: #3a5bd9; }
        .readonly { background-color: #e9ecef; }
        .refund-section { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #4a71ff; }
        .original-info { background-color: #e8f4fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .search-results { position: absolute; z-index: 1000; width: 100%; max-height: 200px; overflow-y: auto; background: white; border: 1px solid #ddd; border-radius: 0 0 5px 5px; display: none; }
        .search-results a { display: block; padding: 8px 15px; color: #333; text-decoration: none; }
        .search-results a:hover { background-color: #f5f5f5; }
        .btn-disabled { opacity: 0.6; cursor: not-allowed; background-color: #6c757d !important; }
        .alert-success { animation: fadeIn 0.5s; }
        .pnr-notice { background-color: #d1ecf1; padding: 10px; border-radius: 5px; margin-bottom: 10px; border-left: 4px solid #17a2b8; font-size: 14px; }
        .form-disabled { opacity: 0.7; }
        .involuntary-check { margin: 10px 0; }
        .involuntary-check label { font-weight: normal; display: inline; margin-left: 5px; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @media (max-width: 768px) { .container { padding: 15px; } .form-row > div { margin-bottom: 15px; } }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Refund processed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h2>Refund Processing (Corporate)</h2>
        
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-10 position-relative">
                    <label for="search_term">Search (Passenger, Ticket No, or PNR):</label>
                    <input type="text" id="search_term" name="search_term" class="form-control" 
                           placeholder="Search by Passenger Name, Ticket Number, or PNR"
                           value="<?= htmlspecialchars($search_term) ?>">
                    <div id="searchResults" class="search-results"></div>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </div>
        </form>

        <?php if (!empty($search_results) && empty($sale_data)): ?>
            <div class="mt-4">
                <h5>Search Results – Select a ticket to refund:</h5>
                <table class="table table-bordered">
                    <thead><tr><th>Passenger</th><th>Ticket No</th><th>PNR</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($search_results as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['PassengerName']) ?></td>
                                <td><?= htmlspecialchars($row['TicketNumber']) ?></td>
                                <td><?= htmlspecialchars($row['PNR']) ?></td>
                                <td><a href="?id=<?= $row['SaleID'] ?>&search_term=<?= urlencode($search_term) ?>" class="btn btn-sm btn-primary">Select</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($sale_data): ?>
        <?php if ($pnr_has_refunds): ?>
            <div class="pnr-notice">
                <strong>Note:</strong> Other tickets in PNR <?= htmlspecialchars($sale_data['PNR']) ?> have been refunded, 
                but this ticket (<?= htmlspecialchars($sale_data['TicketNumber']) ?>) is still available for refund.
            </div>
        <?php endif; ?>
        
        <form action="" method="POST" id="refundForm" <?= $is_refunded ? 'class="form-disabled"' : '' ?>>
            <div class="original-info">
                <h4>Original Sale Information</h4>
                <div class="row">
                    <div class="col-md-4">
                        <label>Party Name:</label>
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
                        <label>Original Selling Price:</label>
                        <input type="text" id="original_bill" class="form-control readonly" value="<?= number_format($sale_data['BillAmount'], 2) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Payment Status:</label>
                        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($sale_data['PaymentStatus']) ?>" readonly>
                    </div>
                </div>
                <?php if ($sale_data['PaymentStatus'] == 'Partially Paid'): ?>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Paid Amount:</label>
                        <input type="text" class="form-control readonly" value="<?= number_format($sale_data['PaidAmount'], 2) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Due Amount:</label>
                        <input type="text" class="form-control readonly" value="<?= number_format($sale_data['DueAmount'], 2) ?>" readonly>
                    </div>
                </div>
                <?php endif; ?>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Original Net Payment (Cost):</label>
                        <input type="text" id="original_net" class="form-control readonly" value="<?= number_format($sale_data['NetPayment'], 2) ?>" readonly>
                    </div>
                </div>
            </div>

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
                        <label for="sales_person_id">Sales Person:</label>
                        <select name="sales_person_id" id="sales_person_id" class="form-control" required <?= $is_refunded ? 'disabled' : '' ?>>
                            <option value="">Select Sales Person</option>
                            <?php 
                            mysqli_data_seek($salespersons_result, 0);
                            while($sp = mysqli_fetch_assoc($salespersons_result)): ?>
                                <option value="<?= $sp['id'] ?>" <?= ($sp['name'] == $sale_data['SalesPersonName']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="refund_date">Refund Date:</label>
                        <input type="text" name="refund_date" id="refund_date" class="form-control" required <?= $is_refunded ? 'disabled' : '' ?>
                               value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="refund_charge">Refund Charge (Source Charge):</label>
                        <input type="number" name="refund_charge" id="refund_charge" class="form-control" required min="0" step="0.01" value="0" <?= $is_refunded ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label for="service_charge">Service Charge (Your Profit):</label>
                        <input type="number" name="service_charge" id="service_charge" class="form-control" required min="0" step="0.01" value="0" <?= $is_refunded ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label>Total Refund Charges:</label>
                        <input type="number" id="total_refund" class="form-control readonly" readonly>
                    </div>
                    <div class="col-md-3">
                        <label>Amount to Refund (to Client):</label>
                        <input type="number" id="refund_amount" class="form-control readonly" readonly>
                    </div>
                </div>
                <div class="row mt-2 involuntary-check">
                    <div class="col-md-12">
                        <input type="checkbox" name="involuntary" id="involuntary" value="1" <?= $is_refunded ? 'disabled' : '' ?>>
                        <label for="involuntary">Involuntary Refund (Airline Cancellation – Full refund, no charges)</label>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12" id="payment_status_info"></div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <button type="submit" class="btn-submit <?= $is_refunded ? 'btn-disabled' : '' ?>" <?= $is_refunded ? 'disabled' : '' ?>>
                        <?= $is_refunded ? 'Already Refunded' : 'Process Refund' ?>
                    </button>
                </div>
            </div>
        </form>
        <?php elseif (!$is_refunded && empty($search_term)): ?>
            <div class="alert alert-info">Please search for a ticket using the search box above.</div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            flatpickr("#refund_date", { dateFormat: "Y-m-d", defaultDate: "today" });

            let paymentStatus = "<?= $sale_data['PaymentStatus'] ?? '' ?>";
            let paidAmount = parseFloat("<?= $sale_data['PaidAmount'] ?? 0 ?>");
            let sellingPrice = parseFloat("<?= $sale_data['BillAmount'] ?? 0 ?>");

            function calculateRefund() {
                let refundCharge = parseFloat($('#refund_charge').val()) || 0;
                let serviceCharge = parseFloat($('#service_charge').val()) || 0;
                let totalCharges = refundCharge + serviceCharge;
                let isInvoluntary = $('#involuntary').is(':checked');
                let clientRefund = 0;
                let infoText = "";

                if (isInvoluntary) {
                    clientRefund = sellingPrice;
                    infoText = `Involuntary Refund (Airline Cancellation).<br>Client paid full amount: ${sellingPrice.toFixed(2)} BDT – Full refund to client.`;
                    $('#refund_charge').val(0);
                    $('#service_charge').val(0);
                    totalCharges = 0;
                } else {
                    if (paymentStatus === 'Paid') {
                        clientRefund = sellingPrice - totalCharges;
                        if (clientRefund < 0) clientRefund = 0;
                        infoText = `Client has paid full amount (${sellingPrice.toFixed(2)} BDT).<br>Refund to client = Selling Price - Total Charges = ${clientRefund.toFixed(2)} BDT.`;
                    } 
                    else if (paymentStatus === 'Partially Paid') {
                        clientRefund = paidAmount - totalCharges;
                        if (clientRefund < 0) {
                            let extraCharge = Math.abs(clientRefund);
                            infoText = `Client paid only ${paidAmount.toFixed(2)} BDT. Total Charges (${totalCharges.toFixed(2)}) exceed paid amount.<br>No refund; client owes ${extraCharge.toFixed(2)} BDT as Cancellation Charge.`;
                            clientRefund = 0;
                        } else {
                            infoText = `Client paid ${paidAmount.toFixed(2)} BDT. Refund to client = Paid Amount - Total Charges = ${clientRefund.toFixed(2)} BDT.`;
                        }
                    } 
                    else {
                        clientRefund = 0;
                        infoText = `Payment status is Due. Client has not paid anything.<br>No refund to client. Total Charges (${totalCharges.toFixed(2)} BDT) will be added as a Cancellation Charge.`;
                    }
                }

                $('#total_refund').val(totalCharges.toFixed(2));
                $('#refund_amount').val(clientRefund.toFixed(2));
                $('#payment_status_info').html(`<div class="alert alert-info">${infoText}</div>`);
            }

            $('#refund_charge, #service_charge, #involuntary').on('input change', function() {
                if ($('#involuntary').is(':checked')) {
                    $('#refund_charge').prop('readonly', true);
                    $('#service_charge').prop('readonly', true);
                } else {
                    $('#refund_charge').prop('readonly', false);
                    $('#service_charge').prop('readonly', false);
                }
                calculateRefund();
            });
            calculateRefund();

            $('#refundForm').submit(function(e) {
                <?php if ($is_refunded): ?>
                    e.preventDefault();
                    alert('This ticket has already been refunded and cannot be refunded again.');
                    return false;
                <?php endif; ?>
                
                if ($('#source').val() === '') {
                    alert('Please select a source/agency.');
                    e.preventDefault();
                    return false;
                }
                if ($('#sales_person_id').val() === '') {
                    alert('Please select a sales person.');
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

            $('#search_term').on('input', function() {
                let term = $(this).val();
                if (term.length < 2) { $('#searchResults').hide(); return; }
                $.get('search_refund.php', { term: term }, function(data) {
                    let results = $('#searchResults');
                    results.empty();
                    if (data.length > 0) {
                        data.forEach(item => {
                            results.append(`<a href="?id=${item.SaleID}&search_term=${encodeURIComponent(term)}">${item.PassengerName} (Ticket: ${item.TicketNumber}, PNR: ${item.PNR})</a>`);
                        });
                        results.show();
                    } else { results.hide(); }
                }, 'json');
            });
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#search_term, #searchResults').length) $('#searchResults').hide();
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>