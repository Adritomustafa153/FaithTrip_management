<?php
include 'auth_check.php';
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if cart is empty
if (!isset($_SESSION['combined_invoice_cart']) || empty($_SESSION['combined_invoice_cart'])) {
    die("Cart is empty. Please add items to generate invoice.");
}

// Process form data
$clientType = $_POST['clientType'];
$clientName = !empty($_POST['ClientNameManual']) ? $_POST['ClientNameManual'] : $_POST['ClientNameDropdown'];
$address = $_POST['address'];
$email = $_POST['client_email'] ?? '';
$cc_emails = $_POST['cc_emails'] ?? '';
$bcc_emails = $_POST['bcc_emails'] ?? '';
$addAIT = isset($_POST['addAIT']) ? true : false;

// Generate invoice number
$invoice_number = "CI-" . date('Ymd') . "-" . rand(1000, 9999);

// Calculate totals
$total_amount = 0;
$cart_items = [];

foreach ($_SESSION['combined_invoice_cart'] as $cart_item) {
    if ($cart_item['service_type'] == 'airticket') {
        $query = "SELECT * FROM sales WHERE SaleID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $cart_item['record_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $row['service_type'] = 'airticket';
            $cart_items[] = $row;
            $total_amount += $row['BillAmount'];
        }
        $stmt->close();
        
    } elseif ($cart_item['service_type'] == 'hotel') {
        $query = "SELECT * FROM hotel WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $cart_item['record_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $row['service_type'] = 'hotel';
            $cart_items[] = $row;
            $total_amount += $row['selling_price'];
        }
        $stmt->close();
        
    } elseif ($cart_item['service_type'] == 'extra_service') {
        $cart_items[] = $cart_item;
        $total_amount += $cart_item['amount'];
    }
}

// Calculate AIT
$ait = $addAIT ? $total_amount * 0.003 : 0;
$grand_total = $total_amount + $ait;

// Insert into combined_invoices table (you'll need to create this table)
$insert_query = "INSERT INTO combined_invoices (invoice_number, client_type, client_name, address, email, total_amount, ait, grand_total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("sssssddd", $invoice_number, $clientType, $clientName, $address, $email, $total_amount, $ait, $grand_total);
$stmt->execute();
$invoice_id = $stmt->insert_id;
$stmt->close();

// Insert invoice items
foreach ($cart_items as $item) {
    if ($item['service_type'] == 'airticket') {
        $description = "Air Ticket: " . $item['airlines'] . " - " . $item['TicketRoute'];
        $amount = $item['BillAmount'];
        $reference = $item['PNR'];
    } elseif ($item['service_type'] == 'hotel') {
        $description = "Hotel: " . $item['hotelName'] . " - " . $item['room_type'];
        $amount = $item['selling_price'];
        $reference = $item['reference_number'];
    } elseif ($item['service_type'] == 'extra_service') {
        $description = "Extra Service: " . $item['service_name'];
        $amount = $item['amount'];
        $reference = "EXTRA";
    }
    
    $item_query = "INSERT INTO combined_invoice_items (invoice_id, service_type, description, amount, reference, remarks) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($item_query);
    $remarks = $item['Remarks'] ?? $item['remarks'] ?? '';
    $stmt->bind_param("issdss", $invoice_id, $item['service_type'], $description, $amount, $reference, $remarks);
    $stmt->execute();
    $stmt->close();
}

// Clear the cart after successful invoice generation
$_SESSION['combined_invoice_cart'] = [];

// Redirect to invoice view
header("Location: view_combined_invoice.php?id=" . $invoice_id);
exit;
?>