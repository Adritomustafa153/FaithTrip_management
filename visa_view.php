<?php
// visa_view.php
require 'db.php';
require 'auth_check.php';

// Get visa ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: visa_list.php");
    exit;
}

$sql = "SELECT * FROM visa WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: visa_list.php");
    exit;
}

$visa = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Record Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .detail-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .detail-label {
            font-weight: 600;
            background-color: #f1f3f5;
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        .detail-value {
            padding: 8px 12px;
            margin-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 12px;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card detail-card">
                    <div class="detail-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="fas fa-passport me-2"></i> Visa Application Details</h3>
                        <div>
                            <a href="visa_list.php" class="btn btn-light me-2"><i class="fas fa-arrow-left"></i> Back to List</a>
                            <a href="visa_edit.php?id=<?php echo $id; ?>" class="btn btn-warning me-2"><i class="fas fa-edit"></i> Edit</a>
                            <a href="generate_visa_invoice.php?id=<?php echo $id; ?>" class="btn btn-success" target="_blank"><i class="fas fa-file-invoice-dollar"></i> Generate Invoice</a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-label"><i class="fas fa-user"></i> Applicant Name</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['name']); ?></div>
                                
                                <div class="detail-label"><i class="fas fa-globe"></i> Country</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['country']); ?></div>
                                
                                <div class="detail-label"><i class="fas fa-tag"></i> Visa Type</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['Type']); ?></div>
                                
                                <div class="detail-label"><i class="fas fa-sign-in-alt"></i> No. of Entry</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['NoOfEntry']); ?></div>
                                
                                <div class="detail-label"><i class="fas fa-clock"></i> Duration</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['Duration']); ?></div>
                                
                                <div class="detail-label"><i class="fas fa-building"></i> Source / Vendor</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['Source']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-label"><i class="fas fa-chart-line"></i> Net Payment (Cost)</div>
                                <div class="detail-value">৳ <?php echo number_format($visa['Net Payment'], 2); ?></div>
                                
                                <div class="detail-label"><i class="fas fa-dollar-sign"></i> Selling Price</div>
                                <div class="detail-value text-success fw-bold">৳ <?php echo number_format($visa['selling price'], 2); ?></div>
                                
                                <div class="detail-label"><i class="fas fa-chart-simple"></i> Profit</div>
                                <div class="detail-value text-primary">৳ <?php echo number_format($visa['profit'], 2); ?></div>
                                
                                <div class="detail-label"><i class="fas fa-hand-holding-usd"></i> Paid / Due</div>
                                <div class="detail-value">
                                    <span class="text-success">Paid: ৳ <?php echo number_format($visa['paid'], 2); ?></span><br>
                                    <span class="text-danger">Due: ৳ <?php echo number_format($visa['due'], 2); ?></span>
                                </div>
                                
                                <div class="detail-label"><i class="fas fa-credit-card"></i> Payment Status</div>
                                <div class="detail-value">
                                    <span class="badge bg-<?php 
                                        echo match($visa['payment_status']) {
                                            'Paid' => 'success',
                                            'Partial' => 'warning',
                                            'Pending', 'Due' => 'danger',
                                            'Refunded' => 'info',
                                            default => 'secondary'
                                        };
                                    ?> p-2"><?php echo htmlspecialchars($visa['payment_status']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-calendar-alt"></i> Order Date</div>
                                <div class="detail-value"><?php echo date('d M Y', strtotime($visa['orderdate'])); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-user-tie"></i> Sold By</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['sold_by']); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-users"></i> Party / Client Name</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['party name']); ?></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-stamp"></i> Visa Status</div>
                                <div class="detail-value">
                                    <?php 
                                    $status_class = match($visa['visa status']) {
                                        'Approved' => 'success',
                                        'Rejected' => 'danger',
                                        'Processing' => 'primary',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?> p-2"><?php echo htmlspecialchars($visa['visa status']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-hashtag"></i> Visa Number</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['visano'] ?: 'N/A'); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-money-bill-wave"></i> Payment Method</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['payment method'] ?: 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-university"></i> Received In</div>
                                <div class="detail-value"><?php echo htmlspecialchars($visa['received in'] ?: 'N/A'); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-exchange-alt"></i> Refund Net</div>
                                <div class="detail-value">৳ <?php echo number_format($visa['refund net'] ?? 0, 2); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-percent"></i> Service Charge</div>
                                <div class="detail-value">৳ <?php echo number_format($visa['service charge'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="detail-label"><i class="fas fa-undo-alt"></i> Refund to Client</div>
                                <div class="detail-value">৳ <?php echo number_format($visa['refund to client'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>