<?php
session_start();
include 'db.php';

if (isset($_POST['add_loan'])) {
    // Get form data
    $loan_title = mysqli_real_escape_string($conn, $_POST['loan_title']);
    $loan_description = mysqli_real_escape_string($conn, $_POST['loan_description']);
    $loan_receive_date = mysqli_real_escape_string($conn, $_POST['loan_receive_date']);
    $loan_amount = mysqli_real_escape_string($conn, $_POST['loan_amount']);
    $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
    $payment_amount = mysqli_real_escape_string($conn, $_POST['payment_amount']);
    $paid_date = mysqli_real_escape_string($conn, $_POST['paid_date']);
    
    // Calculate remaining amount
    $remaining_amount = $loan_amount - $payment_amount;
    
    // If paid date is not provided, set to null
    if (empty($paid_date)) {
        $paid_date = null;
    }
    
    // Insert query
    $query = "INSERT INTO loan_management 
              (loan_title, loan_description, loan_receive_date, loan_amount, payment_status, 
               payment_amount, paid_date, remaining_amount) 
              VALUES 
              ('$loan_title', '$loan_description', '$loan_receive_date', '$loan_amount', '$payment_status', 
               '$payment_amount', " . ($paid_date ? "'$paid_date'" : "NULL") . ", '$remaining_amount')";
    
    // Execute query
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Loan record added successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['message_type'] = 'danger';
    }
    
    // Redirect back to the form
    header("Location: manage_loans.php");
    exit();
}
?>