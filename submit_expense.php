<?php
include 'db.php'; // adjust path as needed

$expense_date = $_POST['expense_date'];
$category = $_POST['category'];
$description = $_POST['description'];
$amount = $_POST['amount'];

// Validate input (basic)
if (!$expense_date || !$category || !$amount) {
    echo "Please fill all required fields.";
    exit;
}

$sql = "INSERT INTO expenses (expense_date, category, description, amount) 
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssd", $expense_date, $category, $description, $amount);

if ($stmt->execute()) {
    echo "Expense recorded successfully.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>