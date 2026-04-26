<?php
session_start();
require_once __DIR__ . '/access_control.php';
requirePermission('user.manage');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'No user ID provided']);
    exit;
}

// Prevent self-deletion
if ($id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
    exit;
}

$conn = getDbConnection();
$stmt = $conn->prepare("DELETE FROM user WHERE UserID = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>