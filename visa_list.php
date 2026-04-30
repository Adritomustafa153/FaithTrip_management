<?php
// visa_list.php
require 'db.php';
require 'auth_check.php';

// Handle delete confirmation message (if any)
// Messages are stored in session by visa_delete.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Records List</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        body { background-color: #f8f9fa; padding-bottom: 50px; }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 10px 10px;
        }
        .status-badge { font-size: 0.8em; padding: 5px 10px; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .amount-paid { color: #28a745; font-weight: 600; }
        .amount-due { color: #dc3545; font-weight: 600; }
        .action-buttons .btn { padding: 3px 8px; font-size: 0.875rem; margin: 2px; }
        .summary-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .summary-card:hover { transform: translateY(-5px); }
        .summary-header { border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 10px; margin-bottom: 15px; }
        .summary-value { font-size: 1.5rem; font-weight: bold; color: #2c3e50; }
        .period-selector { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .vendor-summary { background: white; border-radius: 8px; padding: 15px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .vendor-item { padding: 8px 0; border-bottom: 1px solid #eee; }
        .vendor-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-passport me-2"></i> Visa Processing Records</h1>
            <p class="lead">View and manage all visa processing records</p>
            <a href="visa_insert.php" class="btn btn-light mt-3">
                <i class="fas fa-plus-circle me-2"></i> Add New Visa Record
            </a>
        </div>
    </div>
    
    <div class="container">
        <?php
        // Display session messages (from delete, insert, edit)
        if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['msg_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php
        // Get current month and year for filtering
        $current_month = date('Y-m');
        $current_year = date('Y');
        
        // Totals queries
        $sql_totals = "SELECT SUM(`selling price`) as total_selling, SUM(profit) as total_profit, SUM(paid) as total_paid, SUM(due) as total_due FROM visa";
        $sql_monthly = "SELECT SUM(`selling price`) as monthly_selling, SUM(profit) as monthly_profit, SUM(paid) as monthly_paid, SUM(due) as monthly_due FROM visa WHERE DATE_FORMAT(orderdate, '%Y-%m') = '$current_month'";
        $sql_yearly = "SELECT SUM(`selling price`) as yearly_selling, SUM(profit) as yearly_profit, SUM(paid) as yearly_paid, SUM(due) as yearly_due FROM visa WHERE YEAR(orderdate) = '$current_year'";
        $sql_vendors = "SELECT `sold_by` as vendor, COUNT(*) as total_visas, SUM(`selling price`) as total_selling, SUM(profit) as total_profit, SUM(paid) as total_paid, SUM(due) as total_due FROM visa WHERE `sold_by` IS NOT NULL AND `sold_by` != '' GROUP BY `sold_by` ORDER BY total_selling DESC";
        
        $result_totals = $conn->query($sql_totals);
        $result_monthly = $conn->query($sql_monthly);
        $result_yearly = $conn->query($sql_yearly);
        $result_vendors = $conn->query($sql_vendors);
        
        $totals = $result_totals->fetch_assoc() ?? ['total_selling'=>0,'total_profit'=>0,'total_paid'=>0,'total_due'=>0];
        $monthly = $result_monthly->fetch_assoc() ?? ['monthly_selling'=>0,'monthly_profit'=>0,'monthly_paid'=>0,'monthly_due'=>0];
        $yearly = $result_yearly->fetch_assoc() ?? ['yearly_selling'=>0,'yearly_profit'=>0,'yearly_paid'=>0,'yearly_due'=>0];
        
        $totals = array_map(fn($v)=>$v??0, $totals);
        $monthly = array_map(fn($v)=>$v??0, $monthly);
        $yearly = array_map(fn($v)=>$v??0, $yearly);
        ?>
        
        <!-- Financial Summary -->
        <div class="period-selector">
            <div class="row">
                <div class="col-md-12">
                    <h4><i class="fas fa-chart-bar me-2"></i> Financial Summary</h4>
                    <p class="text-muted">Showing statistics for: 
                        <strong>Current Month (<?php echo date('F Y'); ?>)</strong> | 
                        <strong>Current Year (<?php echo date('Y'); ?>)</strong> | 
                        <strong>All Time</strong>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card summary-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Selling Price</h6>
                                <div class="summary-value">৳ <?php echo number_format($totals['total_selling'], 2); ?></div>
                                <small>Month: ৳ <?php echo number_format($monthly['monthly_selling'], 2); ?> | Year: ৳ <?php echo number_format($yearly['yearly_selling'], 2); ?></small>
                            </div>
                            <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card summary-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Profit</h6>
                                <div class="summary-value">৳ <?php echo number_format($totals['total_profit'], 2); ?></div>
                                <small>Month: ৳ <?php echo number_format($monthly['monthly_profit'], 2); ?> | Year: ৳ <?php echo number_format($yearly['yearly_profit'], 2); ?></small>
                            </div>
                            <i class="fas fa-chart-line fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card summary-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Paid</h6>
                                <div class="summary-value">৳ <?php echo number_format($totals['total_paid'], 2); ?></div>
                                <small>Month: ৳ <?php echo number_format($monthly['monthly_paid'], 2); ?> | Year: ৳ <?php echo number_format($yearly['yearly_paid'], 2); ?></small>
                            </div>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card summary-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Due</h6>
                                <div class="summary-value">৳ <?php echo number_format($totals['total_due'], 2); ?></div>
                                <small>Month: ৳ <?php echo number_format($monthly['monthly_due'], 2); ?> | Year: ৳ <?php echo number_format($yearly['yearly_due'], 2); ?></small>
                            </div>
                            <i class="fas fa-exclamation-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vendor Summary (by sold_by) -->
        <?php if ($result_vendors->num_rows > 0): ?>
        <div class="vendor-summary">
            <h5 class="summary-header"><i class="fas fa-users me-2"></i> Vendor Performance Summary (by Sold By)</h5>
            <div class="row">
                <?php while($vendor = $result_vendors->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="vendor-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($vendor['vendor']); ?></h6>
                                <small class="text-muted"><?php echo $vendor['total_visas']; ?> visas</small>
                            </div>
                            <div class="text-end">
                                <div class="text-success">৳ <?php echo number_format($vendor['total_selling'], 2); ?></div>
                                <small class="text-primary">Profit: ৳ <?php echo number_format($vendor['total_profit'], 2); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main DataTable -->
        <?php
        $sql = "SELECT * FROM visa ORDER BY orderdate DESC, id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0):
        ?>
        <div class="card shadow mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i> All Visa Records</h5>
                <span class="badge bg-primary">Total: <?php echo $result->num_rows; ?> records</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="visaTable" class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th><th>Applicant</th><th>Country</th><th>Type</th><th>Status</th>
                                <th>Order Date</th><th>Selling Price</th><th>Paid/Due</th><th>Payment Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <?php if($row['party name']): ?><br><small class="text-muted">Client: <?php echo htmlspecialchars($row['party name']); ?></small><?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['country']); ?></td>
                                <td><?php echo htmlspecialchars($row['Type']); ?><br><small class="text-muted"><?php echo htmlspecialchars($row['NoOfEntry']); ?></small></td>
                                <td>
                                    <?php 
                                    $status_class = match($row['visa status']) {
                                        'Approved' => 'status-approved',
                                        'Rejected' => 'status-rejected',
                                        'Processing' => 'status-processing',
                                        default => 'status-pending'
                                    };
                                    ?>
                                    <span class="badge rounded-pill <?php echo $status_class; ?> status-badge"><?php echo htmlspecialchars($row['visa status']); ?></span>
                                  </td>
                                <td><?php echo date('d M Y', strtotime($row['orderdate'])); ?></td>
                                <td class="amount-paid">৳ <?php echo number_format($row['selling price'], 2); ?></td>
                                <td>
                                    <span class="amount-paid">৳ <?php echo number_format($row['paid'], 2); ?></span><br>
                                    <span class="amount-due">৳ <?php echo number_format($row['due'], 2); ?></span>
                                 </td>
                                <td>
                                    <span class="badge bg-<?php echo match($row['payment_status']) {
                                        'Paid' => 'success', 'Partial' => 'warning', 'Pending' => 'secondary',
                                        'Due' => 'danger', 'Refunded' => 'info', default => 'light text-dark'
                                    }; ?>">
                                        <?php echo htmlspecialchars($row['payment_status']); ?>
                                    </span>
                                 </td>
                                <td class="action-buttons">
                                    <a href="visa_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info mb-1" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="visa_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning mb-1" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="visa_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to delete this visa record? This action cannot be undone.');" title="Delete"><i class="fas fa-trash"></i></a>
                                    <a href="generate_visa_invoice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success mb-1" title="Single Invoice" target="_blank"><i class="fas fa-file-invoice-dollar"></i> Invoice</a>
                                    <a href="visa_cart.php?add_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-secondary mb-1" title="Add to Cart"><i class="fas fa-cart-plus"></i> Cart</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-2x mb-3"></i>
            <h4>No visa records found</h4>
            <p>Start by adding your first visa processing record.</p>
            <a href="visa_insert.php" class="btn btn-primary"><i class="fas fa-plus-circle me-2"></i> Add First Visa Record</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#visaTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]],
                "language": {
                    "search": "Search records:",
                    "lengthMenu": "Show _MENU_ records per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ records",
                    "paginate": { "first": "First", "last": "Last", "next": "Next", "previous": "Previous" }
                }
            });
        });
    </script>
</body>
</html>