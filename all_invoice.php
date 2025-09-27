<?php
// Database connection (db.php)
$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'faithtrip_accounts';
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Simple authentication check (auth_check.php)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Define the invoices directory
$invoicesDirectory = __DIR__ . '/invoices/';

// Handle search and filter
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

// Build the query
$query = "SELECT * FROM invoices";
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}
$query .= " ORDER BY date DESC";

// Prepare and execute the query
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <style>
        .table-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .action-buttons .btn {
            margin-left: 5px;
            margin-bottom: 5px;
        }
        .invoice-logo {
            width: 40px;
            height: 40px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 18px;
        }
        .badge-status {
            font-size: 0.85em;
        }
        .invoice-type-refund {
            color: #dc3545;
            font-weight: bold;
        }
        .invoice-type-regular {
            color: #198754;
            font-weight: bold;
        }
        .invoice-type-reissue {
            color: #fd7e14;
            font-weight: bold;
        }
        .search-form {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .navbar-custom {
            background-color: #2c3e50;
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: #ecf0f1;
        }
        .navbar-custom .nav-link:hover {
            color: #3498db;
        }
        .btn-whatsapp {
            background-color: #25D366;
            color: white;
            border: none;
        }
        .btn-whatsapp:hover {
            background-color: #128C7E;
            color: white;
        }
        .whatsapp-modal .modal-header {
            background-color: #25D366;
            color: white;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

    <div class="container-fluid">
        <div class="table-container">
            <div class="page-header">
                <h2><i class="fas fa-file-invoice me-2"></i> Invoices</h2>
                <a href="#" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Create Invoice
                </a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                    <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Form -->
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
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                                <a href="all_invoice.php" class="btn btn-secondary">
                                    <i class="fas fa-sync me-1"></i> Reset
                                </a>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                // Determine invoice type based on prefix
                                $invoiceType = 'Sale';
                                $typeClass = 'invoice-type-regular';
                                
                                if (strpos($row['Invoice_number'], 'RFD-') === 0) {
                                    $invoiceType = 'Refund';
                                    $typeClass = 'invoice-type-refund';
                                } elseif (strpos($row['Invoice_number'], 'RE-') === 0) {
                                    $invoiceType = 'Reissue';
                                    $typeClass = 'invoice-type-reissue';
                                }
                                
                                // Check if PDF file exists
                                $pdfFileName = $row['PNR'] . '_' . $row['Invoice_number'] . '.pdf';
                                $pdfFilePath = $invoicesDirectory . $pdfFileName;
                                $pdfExists = file_exists($pdfFilePath);
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="invoice-logo">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo $row['Invoice_number']; ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo !empty($row['PartyName']) ? $row['PartyName'] : 'N/A'; ?></td>
                            <td><?php echo !empty($row['PNR']) ? $row['PNR'] : 'N/A'; ?></td>
                            <td>
                                <?php 
                                if (!empty($row['FlightDate']) && $row['FlightDate'] != '0000-00-00') {
                                    echo date('M d, Y', strtotime($row['FlightDate']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($row['SellingPrice'])) {
                                    echo '৳ ' . number_format($row['SellingPrice'], 2);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary <?php echo $typeClass; ?>"><?php echo $invoiceType; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($pdfExists): ?>
                                    <a href="invoices/<?php echo $pdfFileName; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View PDF
                                    </a>
                                    <a href="invoices/<?php echo $pdfFileName; ?>" download class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <!-- <button type="button" class="btn btn-sm btn-whatsapp" data-bs-toggle="modal" data-bs-target="#whatsappModal" 
                                            data-invoice="<?php echo $row['Invoice_number']; ?>" 
                                            data-pdf="<?php echo $pdfFileName; ?>"
                                            data-client="<?php echo htmlspecialchars($row['PartyName']); ?>">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </button> -->
                                    <?php else: ?>
                                    <span class="text-muted">PDF not found</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; 
                        else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No invoice records found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- WhatsApp Modal -->
    <div class="modal fade whatsapp-modal" id="whatsappModal" tabindex="-1" aria-labelledby="whatsappModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="whatsappModalLabel"><i class="fab fa-whatsapp me-2"></i> Send via WhatsApp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="whatsappForm">
                        <input type="hidden" id="whatsappInvoice" name="invoice">
                        <input type="hidden" id="whatsappPdf" name="pdf">
                        <div class="mb-3">
                            <label for="clientName" class="form-label">Client Name</label>
                            <input type="text" class="form-control" id="clientName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phoneNumber" placeholder="e.g., 8801712345678" required>
                            <div class="form-text">Enter phone number with country code (e.g., 880 for Bangladesh)</div>
                        </div>
                        <div class="mb-3">
                            <label for="messageText" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="messageText" rows="4" required>Dear Sir/Madam,

Please find your invoice attached.

Thank you for your business.

Faith Travels and Tours LTD</textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-whatsapp" id="sendWhatsApp">
                        <i class="fab fa-whatsapp me-1"></i> Send via WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#invoicesTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "responsive": true,
                "language": {
                    "search": "Search invoices:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            // WhatsApp modal handling
            var whatsappModal = document.getElementById('whatsappModal');
            whatsappModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var invoice = button.getAttribute('data-invoice');
                var pdf = button.getAttribute('data-pdf');
                var client = button.getAttribute('data-client');
                
                var modalTitle = whatsappModal.querySelector('.modal-title');
                var invoiceInput = whatsappModal.querySelector('#whatsappInvoice');
                var pdfInput = whatsappModal.querySelector('#whatsappPdf');
                var clientInput = whatsappModal.querySelector('#clientName');
                
                modalTitle.textContent = 'Send ' + invoice + ' via WhatsApp';
                invoiceInput.value = invoice;
                pdfInput.value = pdf;
                clientInput.value = client;
            });

            // Send WhatsApp message
            $('#sendWhatsApp').click(function() {
                var phone = $('#phoneNumber').val().trim();
                var message = $('#messageText').val().trim();
                var invoice = $('#whatsappInvoice').val();
                var pdf = $('#whatsappPdf').val();
                
                if (!phone) {
                    alert('Please enter a phone number');
                    return;
                }
                
                if (!message) {
                    alert('Please enter a message');
                    return;
                }
                
                // Clean phone number (remove any non-digit characters)
                phone = phone.replace(/\D/g, '');
                
                // Encode message for URL
                var encodedMessage = encodeURIComponent(message);
                
                // Create WhatsApp URL
                var whatsappUrl = 'https://web.whatsapp.com/send?phone=' + phone + '&text=' + encodedMessage;
                
                // Open WhatsApp in a new tab
                window.open(whatsappUrl, '_blank');
                
                // Close the modal
                $('#whatsappModal').modal('hide');
            });
        });
    </script>
</body>
</html>