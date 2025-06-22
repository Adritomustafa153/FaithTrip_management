<?php
include 'db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid ID.");
}

// Optional: Delete associated receipt file
$result = mysqli_query($conn, "SELECT receipt FROM paid WHERE id = $id");
$row = mysqli_fetch_assoc($result);
if ($row && $row['receipt']) {
    $file_path = "uploads/receipts/" . $row['receipt'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

$stmt = $conn->prepare("DELETE FROM paid WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location:paid.php"); // Change this to the actual page showing the list
exit;
