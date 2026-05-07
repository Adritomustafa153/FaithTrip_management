<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$sale_id = intval($_POST['sale_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$payment_date = trim($_POST['payment_date'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$bank_name = trim($_POST['bank_name'] ?? '');
$user_remarks = trim($_POST['remarks'] ?? '');

if ($sale_id <= 0 || $amount <= 0 || empty($payment_date) || empty($payment_method)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$stmt = $conn->prepare("SELECT invoice_number, Source, refundtc FROM sales WHERE SaleID = ? AND Remarks = 'Refund'");
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$refund = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$refund) {
    echo json_encode(['success' => false, 'error' => 'Refund not found for SaleID: ' . $sale_id]);
    exit;
}

$invoice_no = $refund['invoice_number'];
$source = $refund['Source'];
$refund_amount = floatval($refund['refundtc']);

// Check total already paid
$stmt2 = $conn->prepare("SELECT SUM(amount) as total FROM paid WHERE invoice_no = ? AND remarks LIKE '%Refund payment%'");
$stmt2->bind_param('s', $invoice_no);
$stmt2->execute();
$total_paid = floatval($stmt2->get_result()->fetch_assoc()['total'] ?? 0);
$stmt2->close();

if (($total_paid + $amount) > $refund_amount) {
    echo json_encode(['success' => false, 'error' => 'Payment exceeds refund amount. Maximum: ' . number_format($refund_amount - $total_paid, 2)]);
    exit;
}

$final_remarks = "Refund payment";
if (!empty($bank_name)) $final_remarks .= " | Bank: $bank_name";
if (!empty($user_remarks)) $final_remarks .= " | $user_remarks";

$stmt3 = $conn->prepare("INSERT INTO paid (source, invoice_no, payment_method, amount, payment_date, remarks, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt3->bind_param('ssdds', $source, $invoice_no, $payment_method, $amount, $payment_date, $final_remarks);
if ($stmt3->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $stmt3->error]);
}
$stmt3->close();
$conn->close();
?>