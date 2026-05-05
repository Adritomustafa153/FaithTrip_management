<?php
// visa_cart.php – corrected table alignment
require 'auth_check.php';
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart
if (!isset($_SESSION['visa_cart'])) {
    $_SESSION['visa_cart'] = [];
}

// Handle add to cart
if (isset($_GET['add_id'])) {
    $visa_id = intval($_GET['add_id']);
    if (!in_array($visa_id, $_SESSION['visa_cart'])) {
        $_SESSION['visa_cart'][] = $visa_id;
    }
    header("Location: visa_cart.php");
    exit;
}

// Handle remove from cart
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    if (($key = array_search($remove_id, $_SESSION['visa_cart'])) !== false) {
        unset($_SESSION['visa_cart'][$key]);
    }
    header("Location: visa_cart.php");
    exit;
}

// Clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['visa_cart'] = [];
    header("Location: visa_cart.php");
    exit;
}

// Fetch cart items
$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION['visa_cart'])) {
    $ids = implode(',', array_map('intval', $_SESSION['visa_cart']));
    $query = "SELECT * FROM visa WHERE id IN ($ids) ORDER BY id";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $subtotal += floatval($row['selling price']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visa Invoice Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #loadingOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.9); display: none; z-index: 9999;
            justify-content: center; align-items: center; flex-direction: column;
        }
        .cc-bcc-fields { display: none; margin-top: 10px; }
        /* Ensure table cells stay inline */
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div id="loadingOverlay">
    <div class="text-center">
        <img src="gif/inv_loading.gif" alt="Loading..." style="width: 100px;">
        <div>Generating Invoice, Please wait...</div>
    </div>
</div>

<div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Visa Invoice Cart</h2>

    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="visa_list.php">Go to Visa List</a> to add records.
        </div>
    <?php else: ?>
        <div class="card p-3 mb-3">
            <form method="POST" action="generate_visa_invoice" id="invoiceForm">
                <!-- Pass all selected visa IDs -->
                <?php foreach (array_column($cart_items, 'id') as $vid): ?>
                    <input type="hidden" name="visa_ids[]" value="<?= $vid ?>">
                <?php endforeach; ?>
                <input type="hidden" name="cart_generated" value="1">

                <!-- Client details section (row) -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Type</label>
                        <select id="clientType" class="form-select" name="clientType" required>
                            <option value="">Select Type</option>
                            <option value="company">Company</option>
                            <option value="agent">Agent</option>
                            <option value="passenger">Counter Sell</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>
                            Name
                            <input type="checkbox" id="manualName" onchange="toggleManualName()"> Add Manually
                        </label>
                        <select id="clientName" class="form-select" name="ClientNameDropdown" required></select>
                        <input type="text" id="manualClientName" name="ClientNameManual" class="form-control mt-2" placeholder="Enter client name" style="display:none;" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>
                            Address <input type="checkbox" id="manualAddress" onchange="toggleManualAddress()"> Add Manually
                        </label>
                        <input type="text" id="address" name="address" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Email:</label>
                        <input type="text" id="email" name="client_email" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" id="addAIT" name="addAIT" value="1">
                            <label class="form-check-label">Add AIT (0.3%) – ৳ <?= number_format($subtotal * 0.003, 2) ?></label>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showCCBCC" onchange="toggleCCBCCFields()">
                            <label class="form-check-label">Add CC/BCC Recipients</label>
                        </div>
                        <div id="ccBCCFields" class="cc-bcc-fields">
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label>CC (comma separated emails):</label>
                                    <input type="text" name="cc_emails" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label>BCC (comma separated emails):</label>
                                    <input type="text" name="bcc_emails" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart items table - FIXED ALIGNMENT -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-3">
                        <thead class="bg-warning text-white">
                            <tr>
                                <th width="5%">SL</th>
                                <th width="30%">Applicant</th>
                                <th width="15%">Country</th>
                                <th width="15%">Type</th>
                                <th width="20%">Selling Price</th>
                                <th width="15%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cart_items)): ?>
                                <?php $serial = 1; foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td><?= $serial++ ?></td>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= htmlspecialchars($item['country']) ?></td>
                                        <td><?= htmlspecialchars($item['Type']) ?></td>
                                        <td>৳ <?= number_format($item['selling price'], 2) ?></td>
                                        <td>
                                            <a href="visa_cart.php?remove_id=<?= $item['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?')">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Subtotal row -->
                                <tr class="table-secondary">
                                    <td colspan="4" class="text-end fw-bold">Subtotal</td>
                                    <td class="fw-bold">৳ <?= number_format($subtotal, 2) ?></td>
                                    <td></td>
                                </tr>
                                <!-- AIT row (display only, actual AIT added in generate) -->
                                <tr>
                                    <td colspan="4" class="text-end">Advance Income Tax (AIT 0.3%)</td>
                                    <td>৳ <?= number_format($subtotal * 0.003, 2) ?></td>
                                    <td></td>
                                </tr>
                                <!-- Grand Total row -->
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end fw-bold">Grand Total</td>
                                    <td class="fw-bold">৳ <?= number_format($subtotal + ($subtotal * 0.003), 2) ?></td>
                                    <td></td>
                                </tr>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">No items in cart</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="visa_cart.php?clear_cart=1" class="btn btn-warning" onclick="return confirm('Clear entire cart?')">Clear Cart</a>
                    <a href="visa_list.php" class="btn btn-secondary">Add More Visas</a>
                    <button type="submit" class="btn btn-primary" onclick="showLoading()">Generate Invoice</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleManualAddress() {
    const addrField = document.getElementById('address');
    addrField.readOnly = !document.getElementById('manualAddress').checked;
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
    document.getElementById('loadingOverlay').style.display = 'flex';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>