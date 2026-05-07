<?php
// Database connection
include 'db.php';
include 'auth_check.php';

// ✅ Use the same cart as invoice_cart2.php
if (!isset($_SESSION['invoice_cart'])) {
    $_SESSION['invoice_cart'] = [];
}

// Handle adding to cart – now adds to invoice_cart
if (isset($_POST['add_to_cart'])) {
    $sale_id = intval($_POST['sale_id']);
    $query = "SELECT * FROM sales WHERE SaleID = $sale_id AND Remarks IN ('Refund', 'Cancellation Charge')";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        if (!in_array($sale_id, $_SESSION['invoice_cart'])) {
            $_SESSION['invoice_cart'][] = $sale_id;
            $_SESSION['cart_message'] = "Item added to invoice cart!";
        } else {
            $_SESSION['cart_message'] = "Item already in cart!";
        }
        header("Location: invoice_cart2.php");
        exit();
    }
}

// Fetch company names for dropdown (only from relevant records)
$companyQuery = "SELECT DISTINCT PartyName FROM sales WHERE Remarks IN ('Refund', 'Cancellation Charge')";
$companyResult = $conn->query($companyQuery);

// Fetch refunded/cancel tickets with search filters
$where = " WHERE Remarks IN ('Refund', 'Cancellation Charge')";
if (isset($_GET['company']) && !empty($_GET['company'])) {
    $company = $conn->real_escape_string($_GET['company']);
    $where .= " AND PartyName = '$company'";
}
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = $conn->real_escape_string($_GET['invoice']);
    $where .= " AND invoice_number LIKE '%$invoice%'";
}
if (isset($_GET['pnr']) && !empty($_GET['pnr'])) {
    $pnr_ = $conn->real_escape_string($_GET['pnr']);
    $where .= " AND PNR LIKE '%$pnr_%'";
}

$refundQuery = "SELECT * FROM sales" . $where . " ORDER BY refund_date DESC, SaleID DESC";
$refundResult = $conn->query($refundQuery);

// Delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM sales WHERE SaleID=$id AND Remarks IN ('Refund', 'Cancellation Charge')";
    if ($conn->query($deleteQuery) === TRUE) {
        if (isset($_SESSION['invoice_cart']) && ($key = array_search($id, $_SESSION['invoice_cart'])) !== false) {
            unset($_SESSION['invoice_cart'][$key]);
        }
        $_SESSION['cart_message'] = "Record deleted successfully!";
        header("Location: refund.php");
        exit();
    } else {
        $_SESSION['cart_message'] = "Error deleting record: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund & Cancellation Records</title>
    <link rel="icon" href="logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            position: relative;
            min-height: 100vh;
            margin: 0;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("https://static.vecteezy.com/system/resources/previews/026/749/007/large_2x/airplane-in-sky-background-free-photo.jpg") no-repeat center center / cover;
            opacity: 0.5;
            z-index: -1;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: none;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #4a71ff;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        th {
            background-color: #4a71ff;
            color: white;
            position: sticky;
            top: 0;
            font-weight: 500;
            font-size: 14px;
            padding: 12px 15px !important;
        }
        td {
            padding: 10px 15px !important;
            vertical-align: middle;
        }
        tr:nth-child(odd) {
            background-color: rgba(74, 113, 255, 0.05);
        }
        tr:nth-child(even) {
            background-color: #ffffff;
        }
        .badge-refund {
            background-color: #dc3545;
            font-weight: 500;
        }
        .badge-cancel {
            background-color: #ffc107;
            color: #212529;
            font-weight: 500;
        }
        .search-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .action-btns .btn {
            margin: 2px 0;
            font-size: 12px;
            min-width: 100px;
        }
        .refund-amount {
            color: #dc3545;
            font-weight: 600;
        }
        .cancel-amount {
            color: #ff8c00;
            font-weight: 600;
        }
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1100;
        }
        .btn-primary {
            background-color: #4a71ff;
            border-color: #4a71ff;
        }
        .btn-primary:hover {
            background-color: #3a61e0;
            border-color: #3a61e0;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 8px 12px;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .cart-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        @media (max-width: 768px) {
            .action-btns .btn {
                width: 100%;
                margin: 5px 0;
            }
            body {
                padding-top: 60px;
            }
            .cart-btn {
                bottom: 70px;
                right: 10px;
            }
        }
    </style>
</head>
<body>
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

    <!-- Floating Cart Button (points to invoice_cart2.php, shows count of invoice_cart) -->
    <a href="invoice_cart2.php" class="btn btn-danger cart-btn rounded-pill">
        <i class="fas fa-shopping-cart me-2"></i> Invoice Cart 
        <span class="badge bg-white text-danger"><?= count($_SESSION['invoice_cart']) ?></span>
    </a>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0 fs-4"><i class="fas fa-exchange-alt me-2"></i>Refund & Cancellation Records</h2>
                <div>
                    <span class="badge bg-secondary"><?= $refundResult->num_rows ?> records</span>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Search Form -->
                <div class="search-container">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="company" class="form-label">Company</label>
                            <select name="company" class="form-select">
                                <option value="">All Companies</option>
                                <?php while ($row = $companyResult->fetch_assoc()) : ?>
                                    <option value="<?= htmlspecialchars($row['PartyName']) ?>" 
                                        <?= (isset($_GET['company']) && $_GET['company'] == $row['PartyName']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['PartyName']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="invoice" class="form-label">Invoice Number</label>
                            <input type="text" name="invoice" class="form-control" placeholder="Search Invoice" 
                                value="<?= isset($_GET['invoice']) ? htmlspecialchars($_GET['invoice']) : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="pnr" class="form-label">PNR</label>
                            <input type="text" name="pnr" class="form-control" placeholder="Search PNR" 
                                value="<?= isset($_GET['pnr']) ? htmlspecialchars($_GET['pnr']) : '' ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Records Table -->
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Company</th>
                                <th>Passenger</th>
                                <th>Invoice</th>
                                <th>Route</th>
                                <th>Airline</th>
                                <th>PNR</th>
                                <th>Ticket</th>
                                <th>Date</th>
                                <th>Amount (BDT)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($refundResult->num_rows > 0) : ?>
                                <?php while ($row = $refundResult->fetch_assoc()) : 
                                    $record_date = new DateTime($row['refund_date'] ?: $row['IssueDate']);
                                    $is_refund = ($row['Remarks'] == 'Refund');
                                    $display_amount = $is_refund ? $row['refundtc'] : $row['BillAmount'];
                                    $amount_class = $is_refund ? 'refund-amount' : 'cancel-amount';
                                    $badge_class = $is_refund ? 'badge-refund' : 'badge-cancel';
                                    $badge_text = $is_refund ? 'Refunded' : 'Cancellation Charge';
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $badge_class ?> rounded-pill"><?= $badge_text ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['PartyName']) ?></td>
                                        <td><?= htmlspecialchars($row['PassengerName']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['invoice_number']) ?>
                                            <?php if (!empty($row['invoice_number'])) : ?>
                                                <div class="mt-1">
                                                    <a href="invoices/<?= $row['invoice_number'] ?>.pdf" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-file-invoice me-1"></i>View
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                         </td>
                                        <td><?= htmlspecialchars($row['TicketRoute']) ?></td>
                                        <td><?= htmlspecialchars($row['airlines']) ?></td>
                                        <td><?= htmlspecialchars($row['PNR']) ?></td>
                                        <td><?= htmlspecialchars($row['TicketNumber']) ?></td>
                                        <td><?= $record_date->format('Y-m-d') ?></td>
                                        <td class="<?= $amount_class ?>"><?= number_format($display_amount, 2) ?></td>
                                        <td>
                                            <span class="badge <?= $badge_class ?> rounded-pill"><?= $badge_text ?></span>
                                        </td>
                                        <td class="action-btns">
                                            <form method="POST" action="">
                                                <input type="hidden" name="sale_id" value="<?= $row['SaleID'] ?>">
                                                <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-cart-plus"></i> Add to Invoice
                                                </button>
                                            </form>
                                            <a href="redirect_edit.php?id=<?= $row['SaleID'] ?>" class="btn btn-sm btn-success mt-1">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                            <a href="refund.php?delete=<?= $row['SaleID'] ?>" class="btn btn-sm btn-danger mt-1" 
                                               onclick="return confirm('Are you sure you want to delete this record?')">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>No refund or cancellation charge records found.
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer text-muted small">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Showing <?= $refundResult->num_rows ?> records
                    </div>
                    <div>
                        <i class="fas fa-sync-alt me-1"></i>Last updated: <?= date('Y-m-d H:i:s') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toastEl = document.querySelector('.toast');
            if (toastEl) {
                const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
                setTimeout(() => { toast.hide(); }, 5000);
            }
            const amountCells = document.querySelectorAll('.refund-amount, .cancel-amount');
            amountCells.forEach(cell => {
                const amount = parseFloat(cell.textContent.replace(/[^0-9.]/g, ''));
                if (amount > 50000) {
                    cell.innerHTML += ' <i class="fas fa-exclamation-triangle"></i>';
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>