<?php
// update_sale.php
include 'db_connection.php'; // adjust to your connection file

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reissue.php');
    exit;
}

$sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
if ($sale_id <= 0) {
    die("Invalid Sale ID.");
}

// Sanitize inputs
$passenger_name   = trim($_POST['passenger_name'] ?? '');
$ticket_route     = trim($_POST['ticket_route'] ?? '');
$issue_date       = !empty($_POST['issueDate']) ? $_POST['issueDate'] : null;
$airlines         = trim($_POST['airlines'] ?? '');
$pnr              = trim($_POST['pnr'] ?? '');
$ticket_number    = trim($_POST['TicketNumber'] ?? '');
$journey_date     = !empty($_POST['journey_date']) ? $_POST['journey_date'] : null;
$return_date      = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
$bill_amount      = floatval($_POST['BillAmount'] ?? 0);
$net_payment      = floatval($_POST['NetPayment'] ?? 0);
$profit           = floatval($_POST['Profit'] ?? ($bill_amount - $net_payment));
$sales_person     = trim($_POST['sales_person'] ?? '');
$payment_status   = trim($_POST['PaymentStatus'] ?? '');
$payment_method   = trim($_POST['PaymentMethod'] ?? '');
$seat_class       = trim($_POST['Class'] ?? '');
$paid_amount      = floatval($_POST['PaidAmount'] ?? 0);
$due_amount       = floatval($_POST['DueAmount'] ?? ($bill_amount - $paid_amount));
$bank_name        = !empty($_POST['BankName']) ? trim($_POST['BankName']) : null;
$received_date    = !empty($_POST['ReceivedDate']) ? $_POST['ReceivedDate'] : null;
$deposit_date     = !empty($_POST['DepositDate']) ? $_POST['DepositDate'] : null;
$clearing_date    = !empty($_POST['ClearingDate']) ? $_POST['ClearingDate'] : null;

// Update query
$query = "UPDATE sales SET 
    PassengerName = ?,
    TicketRoute = ?,
    IssueDate = ?,
    airlines = ?,
    PNR = ?,
    TicketNumber = ?,
    FlightDate = ?,
    ReturnDate = ?,
    BillAmount = ?,
    NetPayment = ?,
    Profit = ?,
    SalesPersonName = ?,
    PaymentStatus = ?,
    PaymentMethod = ?,
    Class = ?,
    PaidAmount = ?,
    DueAmount = ?,
    BankName = ?,
    ReceivedDate = ?,
    DepositDate = ?,
    ClearingDate = ?
WHERE SaleID = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "ssssssssdddsssssdssssi",
    $passenger_name,
    $ticket_route,
    $issue_date,
    $airlines,
    $pnr,
    $ticket_number,
    $journey_date,
    $return_date,
    $bill_amount,
    $net_payment,
    $profit,
    $sales_person,
    $payment_status,
    $payment_method,
    $seat_class,
    $paid_amount,
    $due_amount,
    $bank_name,
    $received_date,
    $deposit_date,
    $clearing_date,
    $sale_id
);

if ($stmt->execute()) {
    // Redirect back to reissue list with a success message
    header("Location: reissue.php?update=success");
    exit;
} else {
    echo "Update failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>