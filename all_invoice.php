<?php
// Database connection
$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'faithtrip_accounts';
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$invoicesDirectory = __DIR__ . '/invoices/';

$searchTerm = '';
$dateFrom = '';
$dateTo = '';
$whereConditions = [];
$params = [];
$paramTypes = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['search'])) {
        $searchTerm = $_GET['search'];
        $whereConditions[] = "(Invoice_number LIKE ? OR PartyName LIKE ? OR PNR LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $paramTypes .= 'sss';
    }
    if (!empty($_GET['date_from'])) {
        $dateFrom = $_GET['date_from'];
        $whereConditions[] = "date >= ?";
        $params[] = $dateFrom;
        $paramTypes .= 's';
    }
    if (!empty($_GET['date_to'])) {
        $dateTo = $_GET['date_to'];
        $whereConditions[] = "date <= ?";
        $params[] = $dateTo . ' 23:59:59';
        $paramTypes .= 's';
    }
}

// MODIFIED QUERY: JOIN with user table to get creator name
$query = "SELECT invoices.*, user.UserName AS created_by_name 
          FROM invoices 
          LEFT JOIN user ON invoices.created_by_user_id = user.UserId";
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}
$query .= " ORDER BY date DESC";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - FaithTrip Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <style>
        .table-container { background-color: #fff; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); padding: 20px; margin-top: 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .action-buttons .btn { margin-left: 5px; margin-bottom: 5px; }
        .invoice-logo { width: 40px; height: 40px; background-color: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-size: 18px; }
        .badge-status { font-size: 0.85em; }
        .invoice-type-refund { color: #dc3545; font-weight: bold; }
        .invoice-type-regular { color: #198754; font-weight: bold; }
        .invoice-type-reissue { color: #fd7e14; font-weight: bold; }
        .search-form { background-color: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .navbar-custom { background-color: #2c3e50; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: #ecf0f1; }
        .navbar-custom .nav-link:hover { color: #3498db; }
        .btn-whatsapp { background-color: #25D366; color: white; border: none; }
        .btn-whatsapp:hover { background-color: #128C7E; color: white; }
        .whatsapp-modal .modal-header { background-color: #25D366; color: white; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container-fluid">
    <div class="table-container">
        <div class="page-header">
            <h2><i class="fas fa-file-invoice me-2"></i> Invoices</h2>
            <a href="#" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Create Invoice</a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="search-form">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="search" class="form-label">Search Invoices</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Invoice number, PNR, or Client name" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search me-1"></i> Search</button>
                            <a href="all_invoice.php" class="btn btn-secondary"><i class="fas fa-sync me-1"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table id="invoicesTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice Number</th>
                        <th>Date</th>
                        <th>Client Name</th>
                        <th>PNR</th>
                        <th>Flight Date</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Created By</th>   <!-- NEW COLUMN -->
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): 
                        $invoiceType = 'Sale';
                        $typeClass = 'invoice-type-regular';
                        if (strpos($row['Invoice_number'], 'RFD-') === 0) { $invoiceType = 'Refund'; $typeClass = 'invoice-type-refund'; }
                        elseif (strpos($row['Invoice_number'], 'RE-') === 0) { $invoiceType = 'Reissue'; $typeClass = 'invoice-type-reissue'; }
                        $pdfFileName = $row['PNR'] . '_' . $row['Invoice_number'] . '.pdf';
                        $pdfFilePath = $invoicesDirectory . $pdfFileName;
                        $pdfExists = file_exists($pdfFilePath);
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="invoice-logo"><i class="fas fa-file-invoice"></i></div>
                                <div><strong><?php echo $row['Invoice_number']; ?></strong></div>
                            </div>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo !empty($row['PartyName']) ? $row['PartyName'] : 'N/A'; ?></td>
                        <td><?php echo !empty($row['PNR']) ? $row['PNR'] : 'N/A'; ?></td>
                        <td><?php echo (!empty($row['FlightDate']) && $row['FlightDate'] != '0000-00-00') ? date('M d, Y', strtotime($row['FlightDate'])) : 'N/A'; ?></td>
                        <td><?php echo (!empty($row['SellingPrice'])) ? '৳ ' . number_format($row['SellingPrice'], 2) : 'N/A'; ?></td>
                        <td><span class="badge bg-secondary <?php echo $typeClass; ?>"><?php echo $invoiceType; ?></span></td>
                        <td><?php echo htmlspecialchars($row['created_by_name'] ?? 'Unknown'); ?></td>   <!-- DISPLAY CREATOR -->
                        <td>
                            <div class="action-buttons">
                                <?php if ($pdfExists): ?>
                                <a href="invoices/<?php echo $pdfFileName; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View PDF</a>
                                <a href="invoices/<?php echo $pdfFileName; ?>" download class="btn btn-sm btn-outline-success"><i class="fas fa-download"></i> Download</a>
                                <?php else: ?>
                                <span class="text-muted">PDF not found</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="10" class="text-center">No invoice records found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#invoicesTable').DataTable({
            "pageLength": 10,
            "order": [[0, "desc"]],
            "responsive": true,
            "language": { "search": "Search invoices:", "lengthMenu": "Show _MENU_ entries", "info": "Showing _START_ to _END_ of _TOTAL_ entries", "paginate": { "previous": "<i class='fas fa-chevron-left'></i>", "next": "<i class='fas fa-chevron-right'></i>" } }
        });
    });
</script>
</body>
</html>