<?php
include 'auth_check.php';
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart
if (!isset($_SESSION['combined_invoice_cart'])) {
    $_SESSION['combined_invoice_cart'] = [];
}

// Handle item deletion
if (isset($_GET['delete_item'])) {
    $delete_index = intval($_GET['delete_item']);
    if (isset($_SESSION['combined_invoice_cart'][$delete_index])) {
        unset($_SESSION['combined_invoice_cart'][$delete_index]);
        $_SESSION['combined_invoice_cart'] = array_values($_SESSION['combined_invoice_cart']); // Reindex array
    }
    header('Location: combined_invoice_cart.php');
    exit;
}

// Handle cart clearing
if (isset($_GET['clear_cart']) && $_GET['clear_cart'] == '1') {
    $_SESSION['combined_invoice_cart'] = [];
    header('Location: combined_invoice_cart.php');
    exit;
}

// Handle extra service addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_extra_service'])) {
    $service_name = $_POST['service_name'];
    $service_amount = floatval($_POST['service_amount']);
    $service_remarks = $_POST['service_remarks'];
    
    if (!empty($service_name) && $service_amount > 0) {
        $extra_service = [
            'service_type' => 'extra_service',
            'service_name' => $service_name,
            'amount' => $service_amount,
            'remarks' => $service_remarks
        ];
        
        $_SESSION['combined_invoice_cart'][] = $extra_service;
    }
    header('Location: combined_invoice_cart.php');
    exit;
}

// Fetch cart data
$cart_items = [];
$total_amount = 0;

foreach ($_SESSION['combined_invoice_cart'] as $index => $cart_item) {
    if ($cart_item['service_type'] == 'airticket') {
        $query = "SELECT * FROM sales WHERE SaleID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $cart_item['record_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $row['service_type'] = 'airticket';
            $row['cart_index'] = $index;
            $cart_items[] = $row;
            $total_amount += $row['BillAmount'];
        }
        $stmt->close();
        
    } elseif ($cart_item['service_type'] == 'hotel') {
        $query = "SELECT * FROM hotel WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $cart_item['record_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $row['service_type'] = 'hotel';
            $row['cart_index'] = $index;
            $cart_items[] = $row;
            $total_amount += $row['selling_price'];
        }
        $stmt->close();
        
    } elseif ($cart_item['service_type'] == 'extra_service') {
        $cart_item['cart_index'] = $index;
        $cart_items[] = $cart_item;
        $total_amount += $cart_item['amount'];
    }
    // Add similar blocks for visa, umrah, student as needed
}

// Calculate AIT if checkbox was checked in previous submission
$ait = 0;
if (isset($_POST['addAIT']) && $_POST['addAIT'] == '1') {
    $ait = $total_amount * 0.003;
}
$grand_total = $total_amount + $ait;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Combined Invoice Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .service-badge {
            font-size: 0.7em;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .airticket-badge { background: #007bff; color: white; }
        .hotel-badge { background: #28a745; color: white; }
        .extra-service-badge { background: #6c757d; color: white; }
        .cc-bcc-fields { display: none; margin-top: 10px; }
    </style>
</head>
<body class="bg-light">
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Combined Invoice Cart</h2>
            <div>
                <a href="combined_cart_search.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Add More Items
                </a>
            </div>
        </div>

        <!-- Client Information Form -->
        <div class="card p-3 mb-3">
            <form method="POST" action="generate_combined_invoice.php" id="invoiceForm">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select id="clientType" class="form-select" name="clientType" required>
                            <option value="">Select Type</option>
                            <option value="company">Company</option>
                            <option value="agent">Agent</option>
                            <option value="counter">Counter Sell</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>
                            Name
                            <input type="checkbox" id="manualName" onchange="toggleManualName()"> Add Manually
                        </label>
                        <select id="clientName" class="form-select" name="ClientNameDropdown" required></select>
                        <input type="text" id="manualClientName" name="ClientNameManual" class="form-control mt-2" placeholder="Enter client name" style="display:none;" />
                    </div>
                    <div class="col-md-4">
                        <label>
                            Address <input type="checkbox" id="manualAddress" onchange="toggleManualAddress()"> Add Manually
                        </label>
                        <input type="text" id="address" name="address" class="form-control" required>
                    </div>
                    <div class="col-md-4 mt-2">
                        <label>Email:</label>
                        <input type="text" id="email" name="client_email" class="form-control">
                    </div>
                    
                    <!-- Add AIT Checkbox -->
                    <div class="col-md-4 mt-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="addAIT" name="addAIT" value="1" <?php echo (isset($_POST['addAIT']) && $_POST['addAIT'] == '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="addAIT">Add AIT (0.3%)</label>
                        </div>
                    </div>
                    
                    <!-- CC and BCC Fields -->
                    <div class="col-md-12 mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showCCBCC" onchange="toggleCCBCCFields()">
                            <label class="form-check-label" for="showCCBCC">
                                Add CC/BCC Recipients
                            </label>
                        </div>
                        
                        <div id="ccBCCFields" class="cc-bcc-fields">
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label>CC (comma separated emails):</label>
                                    <input type="text" id="cc_emails" name="cc_emails" class="form-control" placeholder="email1@example.com, email2@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label>BCC (comma separated emails):</label>
                                    <input type="text" id="bcc_emails" name="bcc_emails" class="form-control" placeholder="email1@example.com, email2@example.com">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Items -->
            <?php if (!empty($cart_items)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Cart Items (<?php echo count($cart_items); ?> items)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">Service</th>
                                        <th width="20%">Details</th>
                                        <th width="15%">Passenger/Customer</th>
                                        <th width="20%">Description</th>
                                        <th width="10%">Dates</th>
                                        <th width="10%">Reference</th>
                                        <th width="10%">Amount</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <span class="service-badge <?php echo $item['service_type']; ?>-badge">
                                                    <?php echo strtoupper($item['service_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($item['service_type'] == 'airticket'): ?>
                                                    <strong><?php echo htmlspecialchars($item['airlines']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($item['TicketRoute']); ?></small>
                                                <?php elseif ($item['service_type'] == 'hotel'): ?>
                                                    <strong>Hotel Booking</strong><br>
                                                    <small><?php echo htmlspecialchars($item['hotelName']); ?></small>
                                                <?php elseif ($item['service_type'] == 'extra_service'): ?>
                                                    <strong>Extra Service</strong><br>
                                                    <small><?php echo htmlspecialchars($item['service_name']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['service_type'] == 'airticket'): ?>
                                                    <?php echo htmlspecialchars($item['PassengerName']); ?>
                                                <?php elseif ($item['service_type'] == 'hotel'): ?>
                                                    <?php echo htmlspecialchars($item['pessengerName']); ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['service_type'] == 'airticket'): ?>
                                                    Class: <?php echo htmlspecialchars($item['Class']); ?><br>
                                                    Remarks: <?php echo htmlspecialchars($item['Remarks']); ?>
                                                <?php elseif ($item['service_type'] == 'hotel'): ?>
                                                    Room: <?php echo htmlspecialchars($item['room_type'] . ' - ' . $item['room_category']); ?><br>
                                                    Category: <?php echo htmlspecialchars($item['hotel_category']); ?>
                                                <?php elseif ($item['service_type'] == 'extra_service'): ?>
                                                    <?php echo htmlspecialchars($item['remarks']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['service_type'] == 'airticket'): ?>
                                                    Depart: <?php echo $item['FlightDate']; ?><br>
                                                    Return: <?php echo $item['ReturnDate']; ?>
                                                <?php elseif ($item['service_type'] == 'hotel'): ?>
                                                    Check-in: <?php echo $item['checkin_date']; ?><br>
                                                    Check-out: <?php echo $item['checkout_date']; ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['service_type'] == 'airticket'): ?>
                                                    PNR: <?php echo $item['PNR']; ?><br>
                                                    Ticket: <?php echo $item['TicketNumber']; ?>
                                                <?php elseif ($item['service_type'] == 'hotel'): ?>
                                                    Ref: <?php echo $item['reference_number']; ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong>
                                                    <?php if ($item['service_type'] == 'airticket'): ?>
                                                        ৳<?php echo number_format($item['BillAmount'], 2); ?>
                                                    <?php elseif ($item['service_type'] == 'hotel'): ?>
                                                        ৳<?php echo number_format($item['selling_price'], 2); ?>
                                                    <?php else: ?>
                                                        ৳<?php echo number_format($item['amount'], 2); ?>
                                                    <?php endif; ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <a href="combined_invoice_cart.php?delete_item=<?php echo $item['cart_index']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('Remove this item from cart?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Extra Services Form -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Add Extra Services</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-3">
                                <input type="text" name="service_name" class="form-control" placeholder="Service Name" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="service_amount" class="form-control" placeholder="Amount" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="service_remarks" class="form-control" placeholder="Remarks/Description">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="add_extra_service" class="btn btn-success w-100">
                                    <i class="fas fa-plus"></i> Add Service
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <td class="text-end"><strong>Subtotal:</strong></td>
                                        <td><strong>৳<?php echo number_format($total_amount, 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-end">Advance Income Tax (AIT):</td>
                                        <td>৳<?php echo number_format($ait, 2); ?></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td class="text-end"><strong>Grand Total:</strong></td>
                                        <td><strong>৳<?php echo number_format($grand_total, 2); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-3">
                    <div>
                        <a href="combined_invoice_cart.php?clear_cart=1" class="btn btn-warning" onclick="return confirm('Clear the entire cart?')">
                            <i class="fas fa-trash"></i> Clear Cart
                        </a>
                    </div>
                    <div>
                        <a href="combined_cart_search.php" class="btn btn-info me-2">
                            <i class="fas fa-search"></i> Add More Items
                        </a>
                        <button type="submit" class="btn btn-success" onclick="return confirm('Generate combined invoice?')">
                            <i class="fas fa-file-invoice"></i> Generate Combined Invoice
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <h4>Your combined invoice cart is empty</h4>
                    <p>Start by adding sales records from different services to create a combined invoice.</p>
                    <a href="combined_cart_search.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search and Add Items
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleManualAddress() {
            document.getElementById('address').readOnly = !document.getElementById('manualAddress').checked;
        }

        function toggleManualName() {
            const isManual = document.getElementById('manualName').checked;
            document.getElementById('clientName').style.display = isManual ? 'none' : 'block';
            document.getElementById('manualClientName').style.display = isManual ? 'block' : 'none';
            document.getElementById('clientName').required = !isManual;
            document.getElementById('manualClientName').required = isManual;
        }

        function toggleCCBCCFields() {
            const showCCBCC = document.getElementById('showCCBCC').checked;
            document.getElementById('ccBCCFields').style.display = showCCBCC ? 'block' : 'none';
        }

        // Populate client dropdown based on type
        document.getElementById('clientType').addEventListener('change', function() {
            const clientType = this.value;
            const clientDropdown = document.getElementById('clientName');
            
            if (clientType) {
                fetch('get_clients.php?type=' + clientType)
                    .then(response => response.json())
                    .then(data => {
                        clientDropdown.innerHTML = '<option value="">Select Client</option>';
                        data.forEach(client => {
                            const option = document.createElement('option');
                            option.value = client.name;
                            option.textContent = client.name;
                            clientDropdown.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                clientDropdown.innerHTML = '<option value="">Select Client</option>';
            }
        });
    </script>
</body>
</html>