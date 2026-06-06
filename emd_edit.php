<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'auth_check.php';
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid EMD ID.";
    header("Location: emd_list.php");
    exit();
}

// Fetch EMD data
$stmt = $conn->prepare("SELECT * FROM sales WHERE SaleID = ? AND (Remarks IN ('Extra Baggage','Seat Purchase','Meal Purchase','Name Correction','Extra Leg Room') OR airlines = 'Extra Service' OR section = 'extra_service')");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$emd = $result->fetch_assoc();
$stmt->close();

if (!$emd) {
    $_SESSION['error'] = "EMD service not found.";
    header("Location: emd_list.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_emd'])) {
    $service_type = trim($_POST['service_type']);
    $selling_price = floatval($_POST['selling_price']);
    $net_price = floatval($_POST['net_price']);
    $source = trim($_POST['source']);
    $pnr = trim($_POST['pnr']);
    $ticket_number = trim($_POST['ticket_number']);
    $passenger = trim($_POST['passenger_name']);
    $description = trim($_POST['description']);
    $profit = $selling_price - $net_price;

    $update_stmt = $conn->prepare("UPDATE sales SET 
        Remarks = ?, PassengerName = ?, TicketRoute = ?, BillAmount = ?, NetPayment = ?, Profit = ?, Source = ?, PNR = ?, TicketNumber = ?
        WHERE SaleID = ?");
    $update_stmt->bind_param("sssdddsdsi", 
        $service_type, $passenger, $description, $selling_price, $net_price, 
        $profit, $source, $pnr, $ticket_number, $id);
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = "EMD service updated successfully.";
        header("Location: emd_list.php");
        exit();
    } else {
        $error = "Update failed: " . $conn->error;
    }
    $update_stmt->close();
}

// Fetch sources for dropdown
$sources_options = '';
$src_query = "SELECT agency_name FROM sources ORDER BY agency_name";
$src_res = $conn->query($src_query);
if ($src_res && $src_res->num_rows > 0) {
    while ($src = $src_res->fetch_assoc()) {
        $selected = ($src['agency_name'] == $emd['Source']) ? 'selected' : '';
        $sources_options .= "<option value=\"" . htmlspecialchars($src['agency_name']) . "\" $selected>" . htmlspecialchars($src['agency_name']) . "</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit EMD - Faith Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0f4fa 0%, #e2e8f0 100%);
            font-family: 'Poppins', 'Segoe UI', Roboto, sans-serif;
        }
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .edit-card {
            background: white;
            border-radius: 32px;
            box-shadow: 0 25px 40px -12px rgba(0,0,0,0.2);
            overflow: hidden;
            border: none;
        }
        /* Header with dark background and white text */
        .card-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white !important;
            padding: 1.3rem 1.8rem;
            font-weight: 700;
            font-size: 1.3rem;
            border-bottom: none;
        }
        .card-header i {
            margin-right: 10px;
        }
        .card-body {
            padding: 2rem 2rem 2rem 2rem;
        }
        .form-label {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }
        .form-control, .form-select {
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            padding: 0.7rem 0.9rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 0.2rem rgba(42,82,152,0.2);
        }
        /* Buttons container */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.8rem;
        }
        /* Cancel button */
        .btn-cancel {
            background: #64748b;
            border: none;
            border-radius: 50px;
            padding: 0.7rem 1.8rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cancel:hover {
            background: #475569;
            transform: translateY(-2px);
            color: white;
        }
        /* Update button (gradient orange) */
        .btn-update {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: none;
            border-radius: 50px;
            padding: 0.7rem 1.8rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-update:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(245,158,11,0.3);
            color: white;
        }
        /* Error alert */
        .alert-danger {
            border-radius: 50px;
        }
        /* Responsive */
        @media (max-width: 576px) {
            .card-body { padding: 1.5rem; }
            .form-actions { flex-direction: column; }
            .btn-cancel, .btn-update { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="edit-container">
        <div class="edit-card">
            <div class="card-header">
                <i class="fas fa-pen-alt me-2"></i> Edit EMD Service
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-cogs me-1"></i> Service Type *</label>
                            <select name="service_type" class="form-select" required>
                                <option value="Extra Baggage" <?= $emd['Remarks'] == 'Extra Baggage' ? 'selected' : '' ?>>✈️ Extra Baggage</option>
                                <option value="Seat Purchase" <?= $emd['Remarks'] == 'Seat Purchase' ? 'selected' : '' ?>>💺 Seat Purchase</option>
                                <option value="Meal Purchase" <?= $emd['Remarks'] == 'Meal Purchase' ? 'selected' : '' ?>>🍽️ Meal Purchase</option>
                                <option value="Name Correction" <?= $emd['Remarks'] == 'Name Correction' ? 'selected' : '' ?>>📝 Name Correction</option>
                                <option value="Extra Leg Room" <?= $emd['Remarks'] == 'Extra Leg Room' ? 'selected' : '' ?>>🦵 Extra Leg Room</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-building me-1"></i> Source *</label>
                            <select name="source" class="form-select" required>
                                <?= $sources_options ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-dollar-sign me-1"></i> Selling Price (BDT) *</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control" required value="<?= htmlspecialchars($emd['BillAmount']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-chart-line me-1"></i> Net Price (BDT) *</label>
                            <input type="number" step="0.01" name="net_price" class="form-control" required value="<?= htmlspecialchars($emd['NetPayment']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-barcode me-1"></i> PNR</label>
                            <input type="text" name="pnr" class="form-control" value="<?= htmlspecialchars($emd['PNR']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-ticket me-1"></i> Ticket Number</label>
                            <input type="text" name="ticket_number" class="form-control" value="<?= htmlspecialchars($emd['TicketNumber']) ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label"><i class="fas fa-user me-1"></i> Passenger Name</label>
                            <input type="text" name="passenger_name" class="form-control" value="<?= htmlspecialchars($emd['PassengerName']) ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label"><i class="fas fa-align-left me-1"></i> Description / Remarks</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($emd['TicketRoute']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="emd_list.php" class="btn-cancel">
                            <i class="fas fa-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" name="update_emd" class="btn-update">
                            <i class="fas fa-save me-1"></i> Update EMD
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>