<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session for cart functionality
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize refund cart if not exists
if (!isset($_SESSION['refund_cart'])) {
    $_SESSION['refund_cart'] = [];
}

// Handle adding to cart from POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sale_id'])) {
    $sale_id = intval($_POST['sale_id']);
    $query = "SELECT * FROM sales WHERE SaleID = $sale_id AND Remarks = 'Refund'";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        // Add to cart if not already present
        if (!in_array($sale_id, $_SESSION['refund_cart'])) {
            $_SESSION['refund_cart'][] = $sale_id;
            $_SESSION['cart_message'] = "Item added to refund invoice cart!";
        } else {
            $_SESSION['cart_message'] = "Item already in cart!";
        }
    }
    header("Location: refund_cart.php");
    exit();
}

// Handle item deletion from cart
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if (($key = array_search($delete_id, $_SESSION['refund_cart'])) !== false) {
        unset($_SESSION['refund_cart'][$key]);
        $_SESSION['cart_message'] = "Item removed from cart!";
    }
    header("Location: refund_cart.php");
    exit();
}

// Handle cart clearing
if (isset($_GET['clear_cart']) && $_GET['clear_cart'] == '1') {
    $_SESSION['refund_cart'] = [];
    $_SESSION['cart_message'] = "Cart cleared successfully!";
    header("Location: refund_cart.php");
    exit();
}

// Fetch cart data
$refunds = [];
$total_refund_charge = 0;
$total_refund_amount = 0;
if (!empty($_SESSION['refund_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['refund_cart']));
    $query = "SELECT *, BillAmount AS refund_charge, refundtc AS refund_amount FROM sales WHERE SaleID IN ($id_list)";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $refunds[] = $row;
        $total_refund_charge += $row['refund_charge'];
        $total_refund_amount += $row['refund_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Invoice Cart - FaithTrip</title>
    <link rel="icon" href="logo.jpg">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        .refund-amount {
            color: #dc3545;
            font-weight: 600;
        }
        .badge-refund {
            background-color: #dc3545;
            font-weight: 500;
        }
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1100;
        }
        .bg-refund-header {
            background-color: #dc3545;
            color: white;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'nav.php'; ?>

    <!-- Toast Notification -->
    <?php if (isset($_SESSION['cart_message'])) : ?>
        <div class="toast-container">
            <div class="toast show align-items-center text-white bg-success" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= $_SESSION['cart_message'] ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['cart_message']); ?>
    <?php endif; ?>

    <!-- Loading Animation Overlay -->
    <div id="loadingOverlay">
        <div class="text-center">
            <img src="gif/inv_loading.gif" alt="Loading..." style="width: 100px; height: 100px;" />
            <div id="loadingText">Generating Refund Invoice, Please wait...</div>
        </div>
    </div>

    <div class="container mt-5">
        <h2 class="mb-4"><i class="fas fa-exchange-alt me-2"></i>Refund Invoice Cart</h2>

        <div class="card p-3 mb-3">
            <form method="POST" action="generate_refund_invoice.php" id="invoiceForm">
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
                </div>
            </div>

            <?php if (!empty($refunds)): ?>
                <table class="table table-bordered table-striped">
                    <thead class="bg-refund-header">
                        <tr>
                            <th>SL</th>
                            <th>Travelers</th>
                            <th>Flight Info</th>
                            <th>Ticket Info</th>
                            <th>Refund Charge</th>
                            <th>Refund Amount</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $serial = 1; foreach ($refunds as $row): ?>
                            <tr>
                                <td><?= $serial++ ?></td>
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
                                <td class="refund-amount"><b><?= number_format($row['refund_charge'], 2) ?></b></td>
                                <td class="refund-amount"><b><?= number_format($row['refund_amount'], 2) ?></b></td>
                                <td><?= htmlspecialchars($row['Remarks']) ?></td>
                                <td>
                                    <a href="refund_cart.php?delete_id=<?= $row['SaleID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item from cart?')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-active">
                            <td colspan="4" class="text-end"><strong>Total Refund Charge</strong></td>
                            <td class="refund-amount"><strong><?= number_format($total_refund_charge, 2); ?></strong></td>
                            <td colspan="3"></td>
                        </tr>
                        <tr class="table-active">
                            <td colspan="4" class="text-end"><strong>Total Refund Amount</strong></td>
                            <td></td>
                            <td class="refund-amount"><strong><?= number_format($total_refund_amount, 2); ?></strong></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="table-active">
                            <?php $ait = $total_refund_amount * 0.003; ?>
                            <td colspan="4" class="text-end"><strong>AIT (0.3%)</strong></td>
                            <td></td>
                            <td class="refund-amount"><strong><?= number_format($ait, 2); ?></strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between mt-3">
                    <a href="refund_cart.php?clear_cart=1" class="btn btn-warning" onclick="return confirm('Clear the entire cart?')">
                        <i class="fas fa-broom me-1"></i> Clear Cart
                    </a>
                    <a href="refund_list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Refunds
                    </a>
                    <button type="submit" class="btn btn-primary" onclick="showLoading()">
                        <i class="fas fa-file-invoice me-1"></i> Generate Refund Invoice
                    </button>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>No items in the refund invoice cart. 
                    <a href="refund_list.php" class="alert-link">Browse refunds to add items</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function toggleManualAddress() {
        document.getElementById('address').readOnly = !document.getElementById('manualAddress').checked;
        if (document.getElementById('manualAddress').checked) {
            document.getElementById('address').value = '';
        }
    }

    function toggleManualName() {
        const isManual = document.getElementById('manualName').checked;
        document.getElementById('clientName').style.display = isManual ? 'none' : 'block';
        document.getElementById('manualClientName').style.display = isManual ? 'block' : 'none';
        document.getElementById('clientName').required = !isManual;
        document.getElementById('manualClientName').required = isManual;
        
        if (isManual) {
            document.getElementById('manualClientName').value = '';
        }
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
                document.getElementById('address').value = address || '';
            }
            document.getElementById('email').value = email || '';
        }
    });

    function showLoading() {
        if (confirm('Are you sure you want to generate the refund invoice?')) {
            document.getElementById('loadingOverlay').style.display = 'flex';
            return true;
        } else {
            return false;
        }
    }

    // Auto-hide toast after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const toastEl = document.querySelector('.toast');
        if (toastEl) {
            setTimeout(() => {
                const toast = bootstrap.Toast.getInstance(toastEl);
                if (toast) {
                    toast.hide();
                }
            }, 5000);
        }

        // Fix for dropdown disappearing
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('mouseenter', function() {
                const menu = this.querySelector('.dropdown-menu');
                if (menu) menu.style.display = 'block';
            });
            dropdown.addEventListener('mouseleave', function() {
                const menu = this.querySelector('.dropdown-menu');
                if (menu) menu.style.display = 'none';
            });
        });
    });
    </script>
</body>
</html>

<?php $conn->close(); ?>