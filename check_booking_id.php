<?php
header("Content-Type: application/json"); // Ensure the response is JSON

// Database connection
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Check if Booking ID is received via POST
if (!empty($_POST['bookingId'])) {
    $bookingId = trim($_POST['bookingId']);

    // Use prepared statements to avoid SQL injection
    $stmt = $conn->prepare("SELECT COUNT(*) FROM hotel WHERE reference_number = ?");
    $stmt->bind_param("s", $bookingId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    // Return JSON response
    echo json_encode(["exists" => $count > 0]);
    exit;
} else {
    echo json_encode(["error" => "No Booking ID provided"]);
    exit;
}
?>
