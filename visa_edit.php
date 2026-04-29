<?php
// visa_edit.php
require 'db.php';
require 'auth_check.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_update = ($id > 0);

// Fetch existing data if update
$visa = [];
if ($is_update) {
    $stmt = $conn->prepare("SELECT * FROM visa WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        header("Location: visa_list.php");
        exit;
    }
    $visa = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $country = $_POST['country'] ?? '';
    $type = $_POST['Type'] ?? '';
    $no_of_entry = $_POST['NoOfEntry'] ?? '';
    $duration = $_POST['Duration'] ?? '';
    $source = $_POST['Source'] ?? '';
    $net_payment = floatval($_POST['Net Payment'] ?? 0);
    $selling_price = floatval($_POST['selling price'] ?? 0);
    $profit = floatval($_POST['profit'] ?? 0);
    $paid = floatval($_POST['paid'] ?? 0);
    $due = floatval($_POST['due'] ?? 0);
    $orderdate = $_POST['orderdate'] ?? date('Y-m-d');
    $sold_by = $_POST['sold_by'] ?? '';
    $party_name = $_POST['party name'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $visa_status = $_POST['visa status'] ?? '';
    $visano = $_POST['visano'] ?? '';
    $payment_method = $_POST['payment method'] ?? '';
    $received_in = $_POST['received in'] ?? '';
    $refund_net = floatval($_POST['refund net'] ?? 0);
    $service_charge = floatval($_POST['service charge'] ?? 0);
    $refund_to_client = floatval($_POST['refund to client'] ?? 0);
    
    if ($is_update) {
        $sql = "UPDATE visa SET 
                name=?, country=?, Type=?, NoOfEntry=?, Duration=?, Source=?, 
                `Net Payment`=?, `selling price`=?, profit=?, paid=?, due=?, 
                orderdate=?, sold_by=?, `party name`=?, payment_status=?, 
                `visa status`=?, visano=?, `payment method`=?, `received in`=?, 
                `refund net`=?, `service charge`=?, `refund to client`=?
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssddddsssssssssdddi", 
            $name, $country, $type, $no_of_entry, $duration, $source,
            $net_payment, $selling_price, $profit, $paid, $due,
            $orderdate, $sold_by, $party_name, $payment_status,
            $visa_status, $visano, $payment_method, $received_in,
            $refund_net, $service_charge, $refund_to_client, $id);
    } else {
        $sql = "INSERT INTO visa (
                name, country, Type, NoOfEntry, Duration, Source, 
                `Net Payment`, `selling price`, profit, paid, due, 
                orderdate, sold_by, `party name`, payment_status, 
                `visa status`, visano, `payment method`, `received in`, 
                `refund net`, `service charge`, `refund to client`
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssddddsssssssssddd", 
            $name, $country, $type, $no_of_entry, $duration, $source,
            $net_payment, $selling_price, $profit, $paid, $due,
            $orderdate, $sold_by, $party_name, $payment_status,
            $visa_status, $visano, $payment_method, $received_in,
            $refund_net, $service_charge, $refund_to_client);
    }
    
    if ($stmt->execute()) {
        if (!$is_update) $id = $stmt->insert_id;
        header("Location: visa_view.php?id=$id");
        exit;
    } else {
        $error = "Database error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_update ? 'Edit Visa Record' : 'Add New Visa Record'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card form-card">
                    <div class="form-header">
                        <h3 class="mb-0"><i class="fas fa-passport me-2"></i> <?php echo $is_update ? 'Edit Visa Record' : 'Add New Visa Record'; ?></h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required-field">Applicant Name</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($visa['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required-field">Country</label>
                                    <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($visa['country'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Visa Type</label>
                                    <input type="text" name="Type" class="form-control" value="<?php echo htmlspecialchars($visa['Type'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">No. of Entry</label>
                                    <select name="NoOfEntry" class="form-select">
                                        <option value="Single" <?php echo (($visa['NoOfEntry'] ?? '') == 'Single') ? 'selected' : ''; ?>>Single</option>
                                        <option value="Multiple" <?php echo (($visa['NoOfEntry'] ?? '') == 'Multiple') ? 'selected' : ''; ?>>Multiple</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Duration</label>
                                    <input type="text" name="Duration" class="form-control" value="<?php echo htmlspecialchars($visa['Duration'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Source / Vendor</label>
                                    <input type="text" name="Source" class="form-control" value="<?php echo htmlspecialchars($visa['Source'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sold By</label>
                                    <input type="text" name="sold_by" class="form-control" value="<?php echo htmlspecialchars($visa['sold_by'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label required-field">Net Payment (Cost)</label>
                                    <input type="number" step="0.01" name="Net Payment" class="form-control" value="<?php echo $visa['Net Payment'] ?? 0; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label required-field">Selling Price</label>
                                    <input type="number" step="0.01" name="selling price" class="form-control" value="<?php echo $visa['selling price'] ?? 0; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Profit</label>
                                    <input type="number" step="0.01" name="profit" class="form-control" value="<?php echo $visa['profit'] ?? 0; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Paid</label>
                                    <input type="number" step="0.01" name="paid" class="form-control" value="<?php echo $visa['paid'] ?? 0; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Due</label>
                                    <input type="number" step="0.01" name="due" class="form-control" value="<?php echo $visa['due'] ?? 0; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Order Date</label>
                                    <input type="date" name="orderdate" class="form-control" value="<?php echo $visa['orderdate'] ?? date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Party / Client Name</label>
                                    <input type="text" name="party name" class="form-control" value="<?php echo htmlspecialchars($visa['party name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="Pending" <?php echo (($visa['payment_status'] ?? '') == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Partial" <?php echo (($visa['payment_status'] ?? '') == 'Partial') ? 'selected' : ''; ?>>Partial</option>
                                        <option value="Paid" <?php echo (($visa['payment_status'] ?? '') == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Due" <?php echo (($visa['payment_status'] ?? '') == 'Due') ? 'selected' : ''; ?>>Due</option>
                                        <option value="Refunded" <?php echo (($visa['payment_status'] ?? '') == 'Refunded') ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Visa Status</label>
                                    <select name="visa status" class="form-select">
                                        <option value="Pending" <?php echo (($visa['visa status'] ?? '') == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Processing" <?php echo (($visa['visa status'] ?? '') == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="Approved" <?php echo (($visa['visa status'] ?? '') == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                        <option value="Rejected" <?php echo (($visa['visa status'] ?? '') == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Visa Number</label>
                                    <input type="text" name="visano" class="form-control" value="<?php echo htmlspecialchars($visa['visano'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <input type="text" name="payment method" class="form-control" value="<?php echo htmlspecialchars($visa['payment method'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Received In</label>
                                    <input type="text" name="received in" class="form-control" value="<?php echo htmlspecialchars($visa['received in'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Refund Net</label>
                                    <input type="number" step="0.01" name="refund net" class="form-control" value="<?php echo $visa['refund net'] ?? 0; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Service Charge</label>
                                    <input type="number" step="0.01" name="service charge" class="form-control" value="<?php echo $visa['service charge'] ?? 0; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Refund to Client</label>
                                    <input type="number" step="0.01" name="refund to client" class="form-control" value="<?php echo $visa['refund to client'] ?? 0; ?>">
                                </div>
                            </div>
                            
                            <div class="text-end mt-3">
                                <a href="visa_view.php?id=<?php echo $id; ?>" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary"><?php echo $is_update ? 'Update Record' : 'Add Record'; ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate profit and due
        const netPayment = document.querySelector('input[name="Net Payment"]');
        const sellingPrice = document.querySelector('input[name="selling price"]');
        const profitField = document.querySelector('input[name="profit"]');
        const paidField = document.querySelector('input[name="paid"]');
        const dueField = document.querySelector('input[name="due"]');
        
        function calculateProfit() {
            let net = parseFloat(netPayment.value) || 0;
            let selling = parseFloat(sellingPrice.value) || 0;
            profitField.value = (selling - net).toFixed(2);
        }
        
        function calculateDue() {
            let selling = parseFloat(sellingPrice.value) || 0;
            let paid = parseFloat(paidField.value) || 0;
            dueField.value = (selling - paid).toFixed(2);
        }
        
        sellingPrice.addEventListener('input', function() {
            calculateProfit();
            calculateDue();
        });
        netPayment.addEventListener('input', calculateProfit);
        paidField.addEventListener('input', calculateDue);
    </script>
</body>
</html>