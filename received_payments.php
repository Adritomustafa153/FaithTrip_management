<?php
// received_payments.php
require 'db.php';
require 'auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Received Payments History</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .page-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container {
            overflow-x: auto;
        }
        .payment-method {
            font-weight: bold;
            color: #2c3e50;
        }
        .received-amount {
            font-weight: bold;
            color: #27ae60;
        }
        .due-amount {
            font-weight: bold;
            color: #e74c3c;
        }
        .btn-clear {
            background-color: #95a5a6;
            color: white;
        }
        .btn-clear:hover {
            background-color: #7f8c8d;
            color: white;
        }
        .table th {
            background-color: #3498db;
            color: white;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include 'nav.php'; ?>

    <div class="container-fluid mt-4">
        <h1 class="page-title">Received Payments History</h1>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="row g-3">
                    <!-- Search by PNR/PartyName/InvoiceNo -->
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search (Party Name/PNR/Invoice No)</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    
                    <!-- Date Filter -->
                    <div class="col-md-2">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date"
                               value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date"
                               value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>">
                    </div>
                    
                    <!-- Payment Method Filter -->
                    <div class="col-md-2">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">All</option>
                            <option value="Cash" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="Bank Transfer" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Check" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'Check') ? 'selected' : ''; ?>>Check</option>
                            <option value="Mobile Banking" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'Mobile Banking') ? 'selected' : ''; ?>>Mobile Banking</option>
                        </select>
                    </div>
                    
                    <!-- Sales Person Filter -->
                    <div class="col-md-2">
                        <label for="sales_person" class="form-label">Sales Person</label>
                        <select class="form-select" id="sales_person" name="sales_person">
                            <option value="">All</option>
                            <?php
                            // Fetch sales persons from sales_person table
                            $sales_persons_query = "SELECT id, name FROM sales_person";
                            $sales_persons_result = mysqli_query($conn, $sales_persons_query);
                            
                            if ($sales_persons_result && mysqli_num_rows($sales_persons_result) > 0) {
                                while ($row = mysqli_fetch_assoc($sales_persons_result)) {
                                    $selected = (isset($_GET['sales_person']) && $_GET['sales_person'] == $row['id']) ? 'selected' : '';
                                    echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <a href="received_payments.php" class="btn btn-clear">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Section -->
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Party Name</th>
                        <th>Invoice & PNR</th>
                        <th>Issue Date</th>
                        <th>Payments</th>
                        <th>Payment History</th>
                        <th>Notes</th>
                        <th>Sales Person</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build the query based on filters
                    $query = "SELECT 
                                s.SaleID, 
                                s.PartyName, 
                                s.invoice_number, 
                                s.IssueDate, 
                                s.BillAmount, 
                                s.SalesPersonName,
                                s.PNR,
                                p.PaymentID,
                                p.PaymentDate,
                                p.Amount,
                                p.PaymentMethod,
                                p.BankName,
                                p.Notes,
                                p.PaymentType,
                                sp.name as SalesPersonName
                              FROM sales s
                              JOIN payments p ON s.SaleID = p.SaleID
                              LEFT JOIN sales_person sp ON s.SalesPersonName = sp.id
                              WHERE 1=1";
                    
                    $params = [];
                    $types = "";
                    
                    // Apply search filter
                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search = "%" . $_GET['search'] . "%";
                        $query .= " AND (s.PartyName LIKE ? OR s.invoice_number LIKE ? OR s.PNR LIKE ?)";
                        $params[] = $search;
                        $params[] = $search;
                        $params[] = $search;
                        $types .= "sss";
                    }
                    
                    // Apply date filter
                    if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
                        $query .= " AND p.PaymentDate >= ?";
                        $params[] = $_GET['from_date'];
                        $types .= "s";
                    }
                    
                    if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
                        $query .= " AND p.PaymentDate <= ?";
                        $params[] = $_GET['to_date'];
                        $types .= "s";
                    }
                    
                    // Apply payment method filter
                    if (isset($_GET['payment_method']) && !empty($_GET['payment_method'])) {
                        $query .= " AND p.PaymentMethod = ?";
                        $params[] = $_GET['payment_method'];
                        $types .= "s";
                    }
                    
                    // Apply sales person filter
                    if (isset($_GET['sales_person']) && !empty($_GET['sales_person'])) {
                        $query .= " AND s.SalesPersonName = ?";
                        $params[] = $_GET['sales_person'];
                        $types .= "s";
                    }
                    
                    $query .= " ORDER BY p.PaymentDate DESC, p.PaymentID DESC";
                    
                    // Debug: Uncomment to see the query
                    // echo "<!-- Query: " . $query . " -->";
                    
                    // Prepare and execute the query
                    $stmt = mysqli_prepare($conn, $query);
                    
                    if (!empty($params)) {
                        mysqli_stmt_bind_param($stmt, $types, ...$params);
                    }
                    
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    $has_results = false;
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        $has_results = true;
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Calculate due amount
                            $due_amount = $row['BillAmount'] - $row['Amount'];
                            
                            // Format dates
                            $issue_date = date('d M Y', strtotime($row['IssueDate']));
                            $payment_date = date('d M Y', strtotime($row['PaymentDate']));
                            
                            // Calculate days passed
                            $days_passed = floor((strtotime($row['PaymentDate']) - strtotime($row['IssueDate'])) / (60 * 60 * 24));
                            
                            echo "<tr>";

                            // Party Name
                            echo "<td>
                                    <div><strong>" . htmlspecialchars($row['PartyName']) . "</strong></div>
                                  </td>";
                            
                            // Invoice No / PNR
                            echo "<td>
                                    <div><strong>" . htmlspecialchars($row['invoice_number']) . "</strong></div>
                                    <div class='text-muted small'>PNR: " . (isset($row['PNR']) && !empty($row['PNR']) ? htmlspecialchars($row['PNR']) : 'N/A') . "</div>
                                  </td>";
                            
                            // Issue Date / Day Passes
                            echo "<td>
                                    <div>" . $issue_date . "</div>
                                    <div class='text-muted small'>" . $days_passed . " days passed</div>
                                  </td>";
                            
                            // Bill / Received / Due Amount
                            echo "<td>
                                    <div>Bill: " . number_format($row['BillAmount'], 2) . "</div>
                                    <div class='received-amount'>Received: " . number_format($row['Amount'], 2) . "</div>
                                    <div class='due-amount'>Due: " . number_format($due_amount, 2) . "</div>
                                  </td>";
                            
                            // Payment Method / Bank / Date
                            echo "<td>
                                    <div class='payment-method'>" . htmlspecialchars($row['PaymentMethod']) . "</div>
                                    <div class='text-muted small'>" . (empty($row['BankName']) ? 'N/A' : htmlspecialchars($row['BankName'])) . "</div>
                                    <div class='text-muted small'>" . $payment_date . "</div>
                                  </td>";
                            
                            // Notes
                            echo "<td>" . (empty($row['Notes']) ? 'N/A' : htmlspecialchars($row['Notes'])) . "</td>";
                            
                            // Sales Person
                            echo "<td>" . (empty($row['SalesPersonName']) ? 'N/A' : htmlspecialchars($row['SalesPersonName'])) . "</td>";
                            
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center py-4'>No payment records found</td></tr>";
                    }
                    
                    // Close statement
                    if (isset($stmt)) {
                        mysqli_stmt_close($stmt);
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-fill date fields if needed
            const today = new Date().toISOString().split('T')[0];
            
            // If from_date is empty, set to 30 days ago
            if (!document.getElementById('from_date').value) {
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                document.getElementById('from_date').value = thirtyDaysAgo.toISOString().split('T')[0];
            }
            
            // If to_date is empty, set to today
            if (!document.getElementById('to_date').value) {
                document.getElementById('to_date').value = today;
            }
        });
    </script>
</body>
</html>