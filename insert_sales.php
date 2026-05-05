<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("This script handles only POST requests.");
}

// Extract source first to decide if system is required
$source = $_POST['source_id'] ?? '';

// Required fields – system is only required if source contains "IATA"
$required = ['AgentID', 'PassengerName', 'airlines', 'TicketRoute', 'TicketNumber', 
             'IssueDate', 'FlightDate', 'PNR', 'BillAmount', 'NetPayment', 
             'PaymentStatus', 'PaidAmount', 'PaymentMethod', 'SalesPersonName', 
             'Class', 'source_id'];

if (stripos($source, 'IATA') !== false) {
    $required[] = 'system';
} else {
    // For non-IATA, we'll set a default system value if not provided
    if (empty($_POST['system'])) {
        $_POST['system'] = 'N/A';
    }
}

$missing = [];
foreach ($required as $field) {
    if (empty($_POST[$field]) && $_POST[$field] !== '0') {
        $missing[] = $field;
    }
}
if (!empty($missing)) {
    die("Missing required fields: " . implode(', ', $missing));
}

// Extract and sanitize values into local variables
$created_by = $_SESSION['user_id'] ?? null;

$party_name      = $_POST['AgentID'];
$passenger_name  = $_POST['PassengerName'];
$airlines        = $_POST['airlines'];
$ticket_route    = $_POST['TicketRoute'];
$ticket_number   = $_POST['TicketNumber'];
$issue_date      = $_POST['IssueDate'];
$flight_date     = $_POST['FlightDate'];
$return_date     = $_POST['ReturnDate'] ?? null;
$pnr             = $_POST['PNR'];
$bill_amount     = (float)$_POST['BillAmount'];
$net_payment     = (float)$_POST['NetPayment'];
$profit          = $bill_amount - $net_payment;
$payment_status  = $_POST['PaymentStatus'];
$paid_amount     = (float)$_POST['PaidAmount'];
$due_amount      = $bill_amount - $paid_amount;
$payment_method  = $_POST['PaymentMethod'];
$bank_name       = $_POST['BankName'] ?? null;
$branch_name     = $_POST['BranchName'] ?? null;
$account_number  = $_POST['AccountNumber'] ?? null;
$received_date   = $_POST['ReceivedDate'] ?? null;
$deposit_date    = $_POST['DepositDate'] ?? null;
$clearing_date   = $_POST['ClearingDate'] ?? null;
$sales_person    = $_POST['SalesPersonName'];
$class           = $_POST['Class'];
$source          = $_POST['source_id'];
$system          = $_POST['system'] ?? 'N/A'; // fallback

// SQL statement
$sql = "INSERT INTO sales (
    PartyName, section, PassengerName, airlines, TicketRoute, TicketNumber,
    IssueDate, FlightDate, ReturnDate, PNR, BillAmount, NetPayment, Profit,
    PaymentStatus, PaidAmount, DueAmount, PaymentMethod, BankName, BranchName,
    AccountNumber, ReceivedDate, DepositDate, ClearingDate, SalesPersonName,
    Class, Source, system, Remarks, created_by_user_id
) VALUES (
    ?, 'agent', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Air Ticket Sale', ?
)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters (27 placeholders)
$stmt->bind_param(
    "sssssssssdddsddsssssssssssi",
    $party_name,
    $passenger_name,
    $airlines,
    $ticket_route,
    $ticket_number,
    $issue_date,
    $flight_date,
    $return_date,
    $pnr,
    $bill_amount,
    $net_payment,
    $profit,
    $payment_status,
    $paid_amount,
    $due_amount,
    $payment_method,
    $bank_name,
    $branch_name,
    $account_number,
    $received_date,
    $deposit_date,
    $clearing_date,
    $sales_person,
    $class,
    $source,
    $system,
    $created_by
);

if ($stmt->execute()) {
    // Use absolute redirect to match your server structure
    header("Location: invoice_list?insert=success");
    exit();
} else {
    die("Execute failed: " . $stmt->error);
}
?>