<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "DELETE FROM expenses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: view_expense.php?message=Expense deleted successfully&success=true");
    } else {
        header("Location: view_expense.php?message=Error deleting expense&success=false");
    }
} else {
    header("Location: view_expense.php?message=Invalid request&success=false");
}
exit();
?>