<?php
header('Content-Type: application/json');
$conn = new mysqli('localhost', 'root', '', 'faithtrip_accounts');
if ($conn->connect_error) {
    echo json_encode(['exists' => false]);
    exit;
}
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$exists = false;
if ($email) {
    $stmt = $conn->prepare("SELECT UserID FROM user WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
}
echo json_encode(['exists' => $exists]);
$conn->close();
?>