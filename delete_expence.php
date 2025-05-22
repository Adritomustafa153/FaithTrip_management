<?php
$conn = new mysqli("localhost", "root", "", "office_expense");

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid expense ID.");
}

// Delete record
$stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Redirect back
header("Location: view.php?deleted=1");
exit;
?>
