<?php
// redirect_reissue.php

include 'db.php'; // adjust if your DB connection file is named differently

if (!isset($_GET['id'])) {
    die("Sale ID not provided.");
}

$sale_id = $_GET['id'];

// Fetch sale type from DB
$sql = "SELECT section FROM sales WHERE SaleID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$stmt->bind_result($sale_type);
$stmt->fetch();
$stmt->close();
$conn->close();

switch (strtolower($sale_type)) {
    case 'counter':
        header("Location: refund_counter.php?sale_id=$sale_id");
        break;
    case 'agent':
        header("Location: refund_agent.php?sale_id=$sale_id");
        break;
    case 'corporate':
        header("Location: refund_corporate.php?sale_id=$sale_id");
        break;
    default:
        echo "Unknown sale type: $sale_type";
}
exit;
?>
