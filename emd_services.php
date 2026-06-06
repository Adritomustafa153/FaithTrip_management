<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'auth_check.php';
include 'db.php';

// Handle adding EMD service to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_emd'])) {
    $service_type = trim($_POST['service_type']);
    $selling_price = floatval($_POST['selling_price']);
    $net_price = floatval($_POST['net_price']);
    $source = trim($_POST['source']);
    $pnr = trim($_POST['pnr']);
    $ticket_number = trim($_POST['ticket_number']);
    $passenger = trim($_POST['passenger_name']);
    $description = trim($_POST['description']);

    if ($service_type && $selling_price > 0 && $net_price >= 0 && $source) {
        $remarks = $service_type;
        $airlines = 'Extra Service';
        $ticket_route = $description ?: $service_type;
        $issue_date = date('Y-m-d');
        $bill_amount = $selling_price;
        $net_payment = $net_price;
        $profit = $selling_price - $net_price;
        $section = 'extra_service';
        $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

        $stmt = $conn->prepare("INSERT INTO sales 
            (section, PassengerName, airlines, TicketRoute, IssueDate, BillAmount, NetPayment, Profit, Source, Remarks, created_by_user_id, PNR, TicketNumber, PaymentStatus) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sssssdddssiss", 
            $section, $passenger, $airlines, $ticket_route, $issue_date, 
            $bill_amount, $net_payment, $profit, $source, $remarks, $created_by,
            $pnr, $ticket_number);
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            if (!isset($_SESSION['invoice_cart'])) {
                $_SESSION['invoice_cart'] = [];
            }
            if (!in_array($new_id, $_SESSION['invoice_cart'])) {
                $_SESSION['invoice_cart'][] = $new_id;
            }
            $_SESSION['message'] = "EMD service added to cart!";
        } else {
            $_SESSION['error'] = "Failed to add EMD.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Please fill all required fields.";
    }
    header("Location: emd_services.php");
    exit();
}

// Fetch sources from `sources` table
$sources_options = '<option value="">Select source</option>';
$src_query = "SELECT agency_name FROM sources ORDER BY agency_name";
$src_res = $conn->query($src_query);
if ($src_res && $src_res->num_rows > 0) {
    while ($src = $src_res->fetch_assoc()) {
        $agency = htmlspecialchars($src['agency_name']);
        $sources_options .= "<option value=\"$agency\">$agency</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New EMD | Faith Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* RESET & GLOBAL */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #f5f3ff;  /* soft lavender background */
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap');
        
        .emd-page {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .emd-card {
            background: #ffffff;
            border-radius: 1.75rem;
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        /* NEW THEME: purple → indigo gradient header */
        .emd-header {
            background: linear-gradient(105deg, #7c3aed 0%, #4f46e5 100%);
            padding: 1.2rem 2rem;
        }
        .emd-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .emd-header h2 i { font-size: 1.4rem; }
        
        .emd-body { padding: 2rem; }
        
        /* Form grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.2rem;
        }
        .full-width { grid-column: span 2; }
        
        /* Labels */
        .field-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4c1d95;  /* deep purple */
            margin-bottom: 0.3rem;
        }
        .field-label i {
            margin-right: 4px;
            font-size: 0.7rem;
            color: #8b5cf6;
        }
        
        /* Inputs & selects */
        .field-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            font-size: 0.9rem;
            background: #ffffff;
            transition: 0.2s;
            outline: none;
        }
        .field-control:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
        }
        
        /* Action buttons – blue gradient */
        .action-row {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn-outline, .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-outline {
            background: #f1f5f9;
            color: #1e293b;
        }
        .btn-outline:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        .btn-primary-custom {
            background: linear-gradient(95deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .btn-primary-custom:hover {
            background: linear-gradient(95deg, #2563eb, #1d4ed8);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(59,130,246,0.25);
        }
        
        /* Alerts */
        .alert-soft {
            border-radius: 1.5rem;
            border: none;
            background: #fef9c3;
            color: #854d0e;
            padding: 0.7rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }
        .alert-soft-success {
            background: #dcfce7;
            color: #166534;
        }
        
        /* Info note with purple accent */
        .info-note {
            background: #f5f3ff;
            border-left: 4px solid #8b5cf6;
            border-radius: 1rem;
            padding: 0.8rem 1rem;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #4c1d95;
        }
        
        @media (max-width: 700px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .emd-body { padding: 1.2rem; }
            .action-row { flex-direction: column; }
            .btn-outline, .btn-primary-custom { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="emd-page">
        <div class="emd-card">
            <div class="emd-header">
                <h2><i class="fas fa-plus-circle"></i> New EMD Service</h2>
            </div>
            <div class="emd-body">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert-soft alert-soft-success">
                        <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['message']) ?>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-soft">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-grid">
                        <div>
                            <label class="field-label"><i class="fas fa-cogs"></i> Service Type *</label>
                            <select name="service_type" class="field-control" required>
                                <option value="">Select service</option>
                                <option value="Extra Baggage">✈️ Extra Baggage</option>
                                <option value="Seat Purchase">💺 Seat Purchase</option>
                                <option value="Meal Purchase">🍽️ Meal Purchase</option>
                                <option value="Name Correction">📝 Name Correction</option>
                                <option value="Extra Leg Room">🦵 Extra Leg Room</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label"><i class="fas fa-building"></i> Source *</label>
                            <select name="source" class="field-control" required>
                                <?= $sources_options ?>
                            </select>
                        </div>
                        <div>
                            <label class="field-label"><i class="fas fa-dollar-sign"></i> Selling Price (BDT) *</label>
                            <input type="number" step="0.01" name="selling_price" class="field-control" required placeholder="e.g., 2500.00">
                        </div>
                        <div>
                            <label class="field-label"><i class="fas fa-chart-line"></i> Net Price (BDT) *</label>
                            <input type="number" step="0.01" name="net_price" class="field-control" required placeholder="e.g., 2200.00">
                        </div>
                        <div>
                            <label class="field-label"><i class="fas fa-barcode"></i> PNR</label>
                            <input type="text" name="pnr" class="field-control" placeholder="Optional, e.g., ABC123">
                        </div>
                        <div>
                            <label class="field-label"><i class="fas fa-ticket"></i> Ticket Number</label>
                            <input type="text" name="ticket_number" class="field-control" placeholder="Optional, e.g., 1572128694124">
                        </div>
                        <div class="full-width">
                            <label class="field-label"><i class="fas fa-user"></i> Passenger Name</label>
                            <input type="text" name="passenger_name" class="field-control" placeholder="Optional – passenger name">
                        </div>
                        <div class="full-width">
                            <label class="field-label"><i class="fas fa-align-left"></i> Description</label>
                            <textarea name="description" class="field-control" rows="3" placeholder="Optional – e.g., 20kg extra baggage, window seat upgrade"></textarea>
                        </div>
                    </div>

                    <div class="action-row">
                        <a href="emd_list.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Cancel</a>
                        <button type="submit" name="add_emd" class="btn-primary-custom"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                    </div>

                    <div class="info-note">
                        <i class="fas fa-info-circle me-1"></i> 
                        <strong>Note:</strong> This EMD will be added to your current invoice cart. You can add multiple EMDs or tickets before generating the final invoice.
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>