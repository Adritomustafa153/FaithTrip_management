<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'auth_check.php';
include 'db.php';

// Handle add to cart
if (isset($_GET['add_to_cart']) && is_numeric($_GET['add_to_cart'])) {
    $emd_id = intval($_GET['add_to_cart']);
    if (!isset($_SESSION['invoice_cart'])) $_SESSION['invoice_cart'] = [];
    if (!in_array($emd_id, $_SESSION['invoice_cart'])) {
        $_SESSION['invoice_cart'][] = $emd_id;
        $_SESSION['message'] = "EMD added to cart.";
    } else {
        $_SESSION['message'] = "EMD already in cart.";
    }
    header("Location: emd_list.php");
    exit();
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if (isset($_SESSION['invoice_cart'])) {
        $key = array_search($delete_id, $_SESSION['invoice_cart']);
        if ($key !== false) unset($_SESSION['invoice_cart'][$key]);
    }
    $del_stmt = $conn->prepare("DELETE FROM sales WHERE SaleID = ? AND (Remarks IN ('Extra Baggage','Seat Purchase','Meal Purchase','Name Correction','Extra Leg Room') OR airlines = 'Extra Service' OR section = 'extra_service')");
    $del_stmt->bind_param("i", $delete_id);
    if ($del_stmt->execute() && $del_stmt->affected_rows > 0) {
        $_SESSION['message'] = "EMD deleted successfully.";
    } else {
        $_SESSION['error'] = "Delete failed.";
    }
    $del_stmt->close();
    header("Location: emd_list.php");
    exit();
}

// Pagination & filters
$limit = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$service_filter = isset($_GET['service_type']) ? trim($_GET['service_type']) : '';
$source_filter = isset($_GET['source']) ? trim($_GET['source']) : '';

$emd_conditions = "(Remarks IN ('Extra Baggage','Seat Purchase','Meal Purchase','Name Correction','Extra Leg Room') OR airlines = 'Extra Service' OR section = 'extra_service')";
$where = $emd_conditions;
if ($search) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (PassengerName LIKE '%$search_esc%' OR TicketNumber LIKE '%$search_esc%' OR PNR LIKE '%$search_esc%')";
}
if ($service_filter) {
    $service_esc = mysqli_real_escape_string($conn, $service_filter);
    $where .= " AND Remarks = '$service_esc'";
}
if ($source_filter) {
    $source_esc = mysqli_real_escape_string($conn, $source_filter);
    $where .= " AND Source = '$source_esc'";
}

$count_sql = "SELECT COUNT(*) as total FROM sales WHERE $where";
$count_res = $conn->query($count_sql);
$total_rows = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT SaleID, IssueDate, PassengerName, Remarks, PNR, TicketNumber, 
               BillAmount, NetPayment, Profit, Source, airlines, TicketRoute
        FROM sales WHERE $where ORDER BY IssueDate DESC, SaleID DESC LIMIT $offset, $limit";
$result = $conn->query($sql);

// Filter options
$service_types = [];
$type_res = $conn->query("SELECT DISTINCT Remarks FROM sales WHERE $emd_conditions AND Remarks IS NOT NULL ORDER BY Remarks");
while ($row = $type_res->fetch_assoc()) $service_types[] = $row['Remarks'];

$source_list = [];
$src_res = $conn->query("SELECT agency_name FROM sources ORDER BY agency_name");
while ($row = $src_res->fetch_assoc()) {
    $source_list[] = $row['agency_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>EMD Services | Faith Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f8 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap');
        
        .emd-container {
            max-width: 1400px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
        }
        /* Header area */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.8rem;
        }
        .page-title {
            font-weight: 800;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.3px;
        }
        .page-title i {
            background: none;
            color: #2a5298;
            margin-right: 8px;
        }
        .btn-new-emd {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 60px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            color: white;
            transition: all 0.25s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-new-emd:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16,185,129,0.25);
            color: white;
        }
        /* Filter card */
        .filter-card {
            background: white;
            border-radius: 28px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.03);
        }
        /* Alert messages */
        .alert-modern {
            border-radius: 60px;
            border: none;
            padding: 0.8rem 1.2rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        /* Table design – elegant & clean */
        .emd-table-wrapper {
            background: white;
            border-radius: 28px;
            padding: 0;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .emd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .emd-table thead tr {
            background: #f8fafc;
            border-bottom: 1px solid #e9edf2;
        }
        .emd-table th {
            padding: 1rem 0.8rem;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
        }
        .emd-table td {
            padding: 1rem 0.8rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f2f5;
        }
        .emd-table tbody tr {
            transition: all 0.2s;
        }
        .emd-table tbody tr:hover {
            background: #fef9e8;
            transform: scale(1.01);
        }
        /* Badge for service type */
        .service-badge {
            background: #eef2ff;
            color: #1e40af;
            font-weight: 600;
            padding: 0.3rem 0.8rem;
            border-radius: 40px;
            font-size: 0.75rem;
            display: inline-block;
            white-space: nowrap;
        }
        /* Amount styling */
        .amount-selling { font-weight: 700; color: #0f172a; }
        .amount-profit { font-weight: 700; color: #10b981; }
        /* Action buttons – beautiful & consistent */
        .action-group {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.45rem 1rem;
            border-radius: 60px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-cart { background: #10b981; color: white; }
        .btn-cart:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(16,185,129,0.3); color: white; }
        .btn-edit { background: #f59e0b; color: white; }
        .btn-edit:hover { background: #d97706; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(245,158,11,0.3); color: white; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(239,68,68,0.3); color: white; }
        /* Pagination */
        .pagination-custom {
            margin-top: 1.5rem;
            justify-content: center;
        }
        .pagination-custom .page-link {
            border-radius: 60px;
            margin: 0 4px;
            color: #2a5298;
            border: none;
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            font-weight: 500;
        }
        .pagination-custom .page-item.active .page-link {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
        }
        /* Responsive */
        @media (max-width: 992px) {
            .emd-table th, .emd-table td { padding: 0.7rem 0.5rem; font-size: 0.75rem; }
            .btn-action { padding: 0.35rem 0.7rem; gap: 4px; font-size: 0.7rem; }
            .action-group { gap: 4px; }
        }
        @media (max-width: 768px) {
            .emd-container { padding: 0 1rem; }
            .emd-table-wrapper { overflow-x: auto; }
            .emd-table { min-width: 800px; }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="emd-container">
        <!-- Header -->
        <div class="header-actions">
            <h1 class="page-title"><i class="fas fa-ticket-alt"></i> EMD Services</h1>
            <a href="emd_services.php" class="btn-new-emd"><i class="fas fa-plus-circle"></i> New EMD</a>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><i class="fas fa-search me-1"></i> Search</label>
                    <input type="text" name="search" class="form-control form-control-sm rounded-pill" placeholder="Passenger, PNR, Ticket..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><i class="fas fa-tag me-1"></i> Service Type</label>
                    <select name="service_type" class="form-select form-select-sm rounded-pill">
                        <option value="">All</option>
                        <?php foreach ($service_types as $st): ?>
                            <option value="<?= htmlspecialchars($st) ?>" <?= $service_filter == $st ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><i class="fas fa-building me-1"></i> Source</label>
                    <select name="source" class="form-select form-select-sm rounded-pill">
                        <option value="">All</option>
                        <?php foreach ($source_list as $src): ?>
                            <option value="<?= htmlspecialchars($src) ?>" <?= $source_filter == $src ? 'selected' : '' ?>><?= htmlspecialchars($src) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill"><i class="fas fa-sliders-h me-1"></i> Apply</button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="emd-table-wrapper">
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="emd-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Date</th><th>Passenger</th><th>Service</th><th>PNR</th><th>Ticket No.</th>
                            <th>Selling (BDT)</th><th>Net (BDT)</th><th>Profit (BDT)</th><th>Source</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?= $row['SaleID'] ?></td>
                                <td><?= date('d-m-Y', strtotime($row['IssueDate'])) ?></td>
                                <td><?= htmlspecialchars($row['PassengerName'] ?: '—') ?></td>
                                <td><span class="service-badge"><?= htmlspecialchars($row['Remarks']) ?></span></td>
                                <td><?= htmlspecialchars($row['PNR'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($row['TicketNumber'] ?: '—') ?></td>
                                <td class="amount-selling"><?= number_format($row['BillAmount'], 2) ?></td>
                                <td><?= number_format($row['NetPayment'], 2) ?></td>
                                <td class="amount-profit"><?= number_format($row['Profit'], 2) ?></td>
                                <td><?= htmlspecialchars($row['Source'] ?: '—') ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="emd_list.php?add_to_cart=<?= $row['SaleID'] ?>" class="btn-action btn-cart" title="Add to cart" onclick="return confirm('Add to invoice cart?')"><i class="fas fa-cart-plus"></i> Cart</a>
                                        <a href="emd_edit.php?id=<?= $row['SaleID'] ?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="emd_list.php?delete=<?= $row['SaleID'] ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Permanently delete this EMD?')"><i class="fas fa-trash-alt"></i> Del</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center py-3">
                    <ul class="pagination pagination-custom">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&service_type=<?= urlencode($service_filter) ?>&source=<?= urlencode($source_filter) ?>">Previous</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&service_type=<?= urlencode($service_filter) ?>&source=<?= urlencode($source_filter) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&service_type=<?= urlencode($service_filter) ?>&source=<?= urlencode($source_filter) ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No EMD services found.</p>
                    <a href="emd_services.php" class="btn btn-primary rounded-pill px-4">Create your first EMD</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>