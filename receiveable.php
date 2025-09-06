<?php
// Database connection
include 'db.php';

// Initialize variables
$section_filter = isset($_GET['section']) ? $_GET['section'] : '';
$party_filter = isset($_GET['party']) ? $_GET['party'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$pnr_search = isset($_GET['pnr']) ? $_GET['pnr'] : '';

// Fetch distinct sections for dropdown
$sections_sql = "SELECT DISTINCT section FROM sales 
                WHERE section != '' 
                AND section != 'Counter Sell'
                ORDER BY section";
$sections_result = $conn->query($sections_sql);

// Fetch parties for dropdown based on selected section (if any)
$parties_sql = "SELECT DISTINCT PartyName FROM sales WHERE PartyName != ''";
if (!empty($section_filter)) {
    $parties_sql .= " AND section = '" . $conn->real_escape_string($section_filter) . "'";
}
$parties_sql .= " ORDER BY PartyName";
$parties_result = $conn->query($parties_sql);

// Build the main query
$sql = "SELECT s.SaleID, s.section, s.PartyName, s.PassengerName, s.airlines, s.TicketRoute, s.TicketNumber, 
               s.IssueDate, s.PNR, s.BillAmount, s.Source, s.PaymentStatus, 
               COALESCE(SUM(p.Amount), 0) as PaidAmount, 
               (s.BillAmount - COALESCE(SUM(p.Amount), 0)) as DueAmount,
               s.SalesPersonName, DATEDIFF(CURDATE(), s.IssueDate) AS DaysPassed 
        FROM sales s
        LEFT JOIN payments p ON s.SaleID = p.SaleID
        WHERE (s.PaymentStatus = 'Due' OR s.PaymentStatus = 'Partially Paid')
        GROUP BY s.SaleID
        HAVING DueAmount > 0";

// Add filters if they exist
if (!empty($section_filter)) {
    $sql .= " AND s.section = '" . $conn->real_escape_string($section_filter) . "'";
}

if (!empty($party_filter)) {
    $sql .= " AND s.PartyName = '" . $conn->real_escape_string($party_filter) . "'";
}

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND s.IssueDate BETWEEN '" . $conn->real_escape_string($from_date) . "' 
              AND '" . $conn->real_escape_string($to_date) . "'";
}

if (!empty($pnr_search)) {
    $sql .= " AND s.PNR LIKE '%" . $conn->real_escape_string($pnr_search) . "%'";
}

$sql .= " ORDER BY s.IssueDate DESC";

$result = $conn->query($sql);

// Calculate totals
$total_bill = 0;
$total_due = 0;
$total_paid = 0;

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $total_bill += $row['BillAmount'];
        $total_due += $row['DueAmount'];
        $total_paid += $row['PaidAmount'];
    }
    // Reset pointer to beginning for the display loop
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receivable Payments Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Datepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-header {
            color: var(--secondary-color);
            margin-bottom: 25px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            text-align: center;
            margin-top: 20px;
        }
        
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
        }
        
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        .table {
            margin-bottom: 0;
            font-size: 14px;
        }
        
        .table th {
            background-color: var(--secondary-color);
            color: white;
            position: sticky;
            top: 0;
            font-weight: 500;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .total-row {
            font-weight: bold;
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .status-due {
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .status-partial {
            color: #f39c12;
            font-weight: bold;
        }
        
        /* Zebra striping */
        tr:nth-child(odd) {
            background-color: rgba(238, 241, 255, 0.5);
        }
        
        tr:nth-child(even) {
            background-color: #ffffff;
        }
        
        .action-buttons {
            min-width: 120px;
        }
        
        .btn-sm {
            font-size: 12px;
            padding: 4px 8px;
            margin: 2px 0;
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            color: white !important;
        }
        
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white !important;
        }
        
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white !important;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white !important;
        }
        
        .form-control, .form-select {
            font-size: 14px;
        }
        
        .read-only-field {
            background-color: #e9ecef;
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .filter-section .col-md-3, 
            .filter-section .col-md-2 {
                margin-bottom: 15px;
            }
            
            .table-responsive {
                font-size: 12px;
            }
            
            .action-buttons {
                min-width: auto;
            }
            
            .btn-sm {
                margin: 1px;
                padding: 3px 5px;
                font-size: 11px;
            }
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background-color: white;
                font-size: 12pt;
            }
            
            .table th {
                background-color: #343a40 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php';?>
    <div class="container-fluid">
        <h2 class="page-header">Receivable Payments Management</h2>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section" onchange="this.form.submit()">
                            <option value="">All Sections</option>
                            <?php
                            if ($sections_result->num_rows > 0) {
                                while($row = $sections_result->fetch_assoc()) {
                                    $selected = ($section_filter == $row['section']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($row['section']) . '" ' . $selected . '>' 
                                         . htmlspecialchars($row['section']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="party" class="form-label">Party Name</label>
                        <select class="form-select" id="party" name="party" onchange="this.form.submit()">
                            <option value="">All Parties</option>
                            <?php
                            if ($parties_result->num_rows > 0) {
                                while($row = $parties_result->fetch_assoc()) {
                                    $selected = ($party_filter == $row['PartyName']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($row['PartyName']) . '" ' . $selected . '>' 
                                         . htmlspecialchars($row['PartyName']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="pnr" class="form-label">PNR Search</label>
                        <input type="text" class="form-control" id="pnr" name="pnr" placeholder="Enter PNR" value="<?php echo htmlspecialchars($pnr_search); ?>">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                        <a href="export_receivables.php?section=<?= urlencode($section_filter) ?>&party=<?= urlencode($party_filter) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&pnr=<?= urlencode($pnr_search) ?>" class="btn btn-success no-print">Excel
                            <i class="fas fa-file-excel"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Section -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Party Name</th>
                            <th>Passenger</th>
                            <th>Airline</th>
                            <th>Route</th>
                            <th>Ticket No</th>
                            <th>Issue Date</th>
                            <th>Days Passed</th>
                            <th>PNR</th>
                            <th>Bill Amount</th>
                            <th>Status</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Sales Person</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $issue_date = new DateTime($row['IssueDate']);
                                $today = new DateTime();
                                $interval = $issue_date->diff($today);
                                $day_passes = $interval->days;
                                
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['section']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['PartyName']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['PassengerName']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['airlines']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['TicketRoute']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['TicketNumber']) . '<br>' . '</td>';
                                echo '<td>' . htmlspecialchars($row['IssueDate']) . '</td>';
                                echo '<td>' . $day_passes . ' days</td>';
                                echo '<td>' . htmlspecialchars($row['PNR']) . '</td>';
                                echo '<td>' . number_format($row['BillAmount'], 2) . '</td>';
                                
                                // Payment status with color coding
                                $status_class = ($row['DueAmount'] == $row['BillAmount']) ? 'status-due' : 'status-partial';
                                $status_text = ($row['DueAmount'] == $row['BillAmount']) ? 'Due' : 'Partially Paid';
                                echo '<td class="' . $status_class . '">' . $status_text . '</td>';
                                
                                echo '<td>' . number_format($row['PaidAmount'], 2) . '</td>';
                                echo '<td>' . number_format($row['DueAmount'], 2) . '</td>';
                                echo '<td>' . htmlspecialchars($row['SalesPersonName']) . '</td>';
                                
                                // Action buttons
                                echo '<td class="action-buttons">';
                                echo '<a href="edit_receivable.php?id=' . $row['SaleID'] . '" class="btn btn-success btn-sm mb-1">';
                                echo '<i class="fas fa-money-bill-wave"></i> Pay</a>';
                                
                                echo '<a href="payment_history.php?id=' . $row['SaleID'] . '" class="btn btn-info btn-sm mb-1">';
                                echo '<i class="fas fa-history"></i> History</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="15" class="text-center py-4">No receivable payments found matching your criteria</td></tr>';
                        }
                        ?>
                        
                        <!-- Total row -->
                        <tr class="total-row">
                            <td colspan="9" class="text-end">Total:</td>
                            <td><?php echo number_format($total_bill, 2); ?></td>
                            <td></td>
                            <td><?php echo number_format($total_paid, 2); ?></td>
                            <td><?php echo number_format($total_due, 2); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Datepicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</body>
</html>