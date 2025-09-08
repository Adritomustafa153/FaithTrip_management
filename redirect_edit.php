<?php
include 'db.php';

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

// Redirect based on sale type
switch (strtolower($sale_type)) {
    case 'counter':
        header("Location: edit_counter_sell.php?id=$sale_id");
        break;
    case 'agent':
        header("Location: edit_agents.php?id=$sale_id");
        break;
    case 'corporate':
        header("Location: edit_corporate.php?sale_id=$sale_id");
        break;
    default:
        // If no specific section or unknown, redirect to general edit page
        header("Location: edit_sales.php?id=$sale_id");
        break;
}
exit;
?>