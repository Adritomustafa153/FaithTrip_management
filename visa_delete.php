<?php
// visa_delete.php
require 'db.php';
require 'auth_check.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: visa_list.php");
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM visa WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Visa record deleted successfully.";
    $_SESSION['msg_type'] = "success";
} else {
    $_SESSION['message'] = "Delete failed: " . $conn->error;
    $_SESSION['msg_type'] = "danger";
}

$stmt->close();
header("Location: visa_list.php");
exit;
?>