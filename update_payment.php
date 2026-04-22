<?php
// update_payment.php
require 'db.php';
require 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = intval($_POST['payment_id']);
    $sale_id = intval($_POST['sale_id']);
    $amount = floatval($_POST['amount']);
    $payment_date = $conn->real_escape_string($_POST['payment_date']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $bank_name = isset($_POST['bank_name']) ? $conn->real_escape_string($_POST['bank_name']) : '';
    $notes = $conn->real_escape_string($_POST['notes']);
    
    mysqli_begin_transaction($conn);
    try {
        // Update the payment record
        $update_query = "UPDATE payments 
                         SET Amount = $amount, 
                             PaymentDate = '$payment_date', 
                             PaymentMethod = '$payment_method', 
                             BankName = " . ($bank_name ? "'$bank_name'" : "NULL") . ", 
                             Notes = '$notes' 
                         WHERE PaymentID = $payment_id";
        if (!$conn->query($update_query)) {
            throw new Exception("Failed to update payment: " . $conn->error);
        }
        
        // Recalculate total paid for the sale
        $paid_query = "SELECT COALESCE(SUM(Amount), 0) as total_paid FROM payments WHERE SaleID = $sale_id";
        $paid_result = $conn->query($paid_query);
        $paid_row = $paid_result->fetch_assoc();
        $total_paid = $paid_row['total_paid'];
        
        // Get bill amount
        $sale_query = $conn->query("SELECT BillAmount FROM sales WHERE SaleID = $sale_id");
        $sale_row = $sale_query->fetch_assoc();
        $bill_amount = $sale_row['BillAmount'];
        
        // Update payment status
        $new_status = ($total_paid >= $bill_amount) ? 'Paid' : 'Partially Paid';
        $update_sale = "UPDATE sales SET PaymentStatus = '$new_status' WHERE SaleID = $sale_id";
        if (!$conn->query($update_sale)) {
            throw new Exception("Failed to update sale status: " . $conn->error);
        }
        
        mysqli_commit($conn);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>