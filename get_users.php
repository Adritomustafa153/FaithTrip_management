<?php
session_start();
require_once __DIR__ . '/../access_control.php';
requirePermission('user.manage');

header('Content-Type: application/json');

$conn = getDbConnection();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$suggest = isset($_GET['suggest']); // for suggestions, we limit to 10

$sql = "SELECT UserID, UserName, email, role, last_login, login_attempts, is_locked, image FROM user";
if ($search !== '') {
    $like = "%$search%";
    $sql .= " WHERE UserName LIKE ? OR email LIKE ? OR role LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode(['users' => $users]);
$stmt->close();
$conn->close();
?>