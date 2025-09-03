<?php
session_start();
include 'db.php';

// Check if ID parameter exists
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No loan ID specified!";
    $_SESSION['message_type'] = 'danger';
    header("Location: manage_loans.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Delete query
$query = "DELETE FROM loan_management WHERE id = $id";

if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = "Loan record deleted successfully!";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Error deleting record: " . mysqli_error($conn);
    $_SESSION['message_type'] = 'danger';
}

header("Location: manage_loans.php");
exit();
?>