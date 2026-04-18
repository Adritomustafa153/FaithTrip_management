<?php
include 'auth_check.php';
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart
if (!isset($_SESSION['invoice_cart'])) {
    $_SESSION['invoice_cart'] = [];
}

// Handle add to cart (supports sale, void, refund, reissue)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_id'])) {
    $sell_id = intval($_POST['sell_id']);
    if (!in_array($sell_id, $_SESSION['invoice_cart'])) {
        $_SESSION['invoice_cart'][] = $sell_id;
    }
    header("Location: invoice_cart2.php");
    exit();
}

// Handle item deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if (($key = array_search($delete_id, $_SESSION['invoice_cart'])) !== false) {
        unset($_SESSION['invoice_cart'][$key]);
    }
    header("Location: invoice_cart2.php");
    exit();
}

// Handle cart clearing
if (isset($_GET['clear_cart']) && $_GET['clear_cart'] == '1') {
    $_SESSION['invoice_cart'] = [];
    header("Location: invoice_cart2.php");
    exit();
}

// Fetch cart data with amount calculation based on transaction type
$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION['invoice_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['invoice_cart']));
    $query = "SELECT * FROM sales WHERE SaleID IN ($id_list)";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $remarks = $row['Remarks'] ?? '';
        $amount = 0;
        $type_label = '';
        
        if ($remarks == 'Air Ticket Sale' || $remarks == '' || $remarks === null) {
            // Normal sale: BillAmount is credit (adds to total)
            $amount = floatval($row['BillAmount']);
            $type_label = 'Sale';
        } elseif ($remarks == 'Void Transaction') {
            // Void transaction: NetPayment is debit (adds to total as charge)
            $amount = floatval($row['NetPayment']);
            $type_label = 'Void Charge';
        } elseif ($remarks == 'Refund') {
            // Refund: refundtc is debit (deduct from total)
            $amount = -floatval($row['refundtc']);
            $type_label = 'Refund';
        } elseif ($remarks == 'Reissue') {
            // MODIFIED: Use Selling Price (BillAmount) instead of NetPayment
            $amount = floatval($row['BillAmount']);
            $type_label = 'Reissue';
        } else {
            // Fallback: treat as sale
            $amount = floatval($row['BillAmount']);
            $type_label = 'Sale';
        }
        
        $cart_items[] = [
            'data' => $row,
            'amount' => $amount,
            'type_label' => $type_label
        ];
        $subtotal += $amount;
    }
}

// Calculate AIT if checkbox was checked
$ait = 0;
if (isset($_POST['addAIT']) && $_POST['addAIT'] == '1') {
    $ait = $subtotal * 0.003;
}
$gt = $subtotal + $ait;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255,255,255,0.85);
            display: none;
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        #loadingText {
            margin-top: 15px;
            font-size: 18px;
            color: #333;
        }
        .cc-bcc-fields {
            display: none;
            margin-top: 10px;
        }
        .amount-negative {
            color: #dc3545;
        }
        .amount-positive {
            color: #28a745;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'nav.php'; ?>

<div id="loadingOverlay">
    <div class="text-center">
        <img src="gif/inv_loading.gif" alt="Loading..." style="width: 100px; height: 100px;" />
        <div id="loadingText">Generating Invoice, Please wait...</div>
    </div>
</div>

<div class="container mt-5">
    <h2 class="mb-4">Invoice Cart</h2>

    <div class="card p-3 mb-3">
    <form method="POST" action="generate_invoice.php" id="invoiceForm">
        <div class="row">
            <div class="col-md-4">
                <label>Type</label>
                <select id="clientType" class="form-select" name="clientType" required>
                    <option value="">Select Type</option>
                    <option value="company">Company</option>
                    <option value="agent">Agent</option>
                    <option value="passenger">Counter Sell</option>
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

    <?php if (!empty($cart_items)): ?>
        <table class="table table-bordered table-striped">
            <thead class="bg-warning text-white">
                <tr style="color: blue;">
                    <th>SL</th>
                    <th>Type</th>
                    <th>Travelers</th>
                    <th>Flight Info</th>
                    <th>Ticket Info</th>
                    <th>Amount (BDT)</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $serial = 1; foreach ($cart_items as $item): 
                    $row = $item['data'];
                    $amount = $item['amount'];
                    $type_label = $item['type_label'];
                    $amount_class = $amount >= 0 ? 'amount-positive' : 'amount-negative';
                    $display_amount = number_format(abs($amount), 2);
                    $sign = $amount >= 0 ? '+' : '-';
                ?>
                    <tr>
                        <td><?= $serial++ ?></td>
                        <td><?= htmlspecialchars($type_label) ?></td>
                        <td><?= htmlspecialchars($row['PassengerName']) ?></td>
                        <td>
                            Travel Route: <b><?= htmlspecialchars($row['TicketRoute']) ?></b><br>
                            Airlines: <b><?= htmlspecialchars($row['airlines']) ?></b><br>
                            Departure Date: <b><?= htmlspecialchars($row['FlightDate']) ?></b><br>
                            Return Date: <b><?= htmlspecialchars($row['ReturnDate']) ?></b>
                        </td>
                        <td>
                            Ticket Number: <b><?= htmlspecialchars($row['TicketNumber']) ?></b><br>
                            PNR: <b><?= htmlspecialchars($row['PNR']) ?></b><br>
                            Ticket Issue Date: <b><?= htmlspecialchars($row['IssueDate']) ?></b><br>
                            Seat Class: <b><?= htmlspecialchars($row['Class']) ?></b>
                        </td>
                        <td class="<?= $amount_class ?>">
                            <?= $sign ?> <?= $display_amount ?>
                        </td>
                        <td><?= htmlspecialchars($row['Remarks']) ?></td>
                        <td>
                            <a href="invoice_cart2.php?delete_id=<?= $row['SaleID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="5" class="text-end"><strong>Subtotal</strong></td>
                    <td><strong><?= number_format($subtotal, 2) ?></strong></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end">Advance Income Tax (AIT 0.3%)</td>
                    <td><strong><?= number_format($ait, 2) ?></strong></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end"><strong>Grand Total</strong></td>
                    <td><strong><?= number_format($gt, 2) ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <div class="d-flex justify-content-between mt-3">
            <a href="invoice_cart2.php?clear_cart=1" class="btn btn-warning" onclick="return confirm('Clear the entire cart?')">Clear Cart</a>
            <a href="invoice_list.php">
                <button type="button" style="font-size: 14px;border-radius: 8px;padding: 10px 20px;background-color:rgb(5, 200, 50);box-shadow: 0 8px 16px rgba(0,0,0,0.2), 0 6px 20px rgba(0,0,0,0.19);">Home</button>
            </a>
            <button type="submit" class="btn btn-primary" onclick="showLoading()">Generate Invoice</button>
        </div>
    <?php else: ?>
        <div class="alert alert-info mt-3">No items in the invoice cart.</div>
    <?php endif; ?>
    </form>
</div>

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

document.getElementById('clientType').addEventListener('change', function () {
    let type = this.value;
    fetch(`fetch_names.php?type=${type}`)
        .then(res => res.json())
        .then(data => {
            let options = '<option value="">Select</option>';
            data.forEach(item => {
                options += `<option value="${item.name}" data-address="${item.address}" data-email="${item.email}">${item.name}</option>`;
            });
            document.getElementById('clientName').innerHTML = options;
        });
});

document.getElementById('clientName').addEventListener('change', function () {
    if (!document.getElementById('manualName').checked) {
        let selected = this.options[this.selectedIndex];
        let address = selected.getAttribute('data-address');
        let email = selected.getAttribute('data-email');
        if (!document.getElementById('manualAddress').checked) {
            document.getElementById('address').value = address;
        }
        document.getElementById('email').value = email || '';
    }
});

function showLoading() {
    if (confirm('Are you sure you want to generate the invoice?')) {
        document.getElementById('loadingOverlay').style.display = 'flex';
        // Form will submit naturally
    } else {
        event.preventDefault();
    }
}
</script>

</body>
</html>