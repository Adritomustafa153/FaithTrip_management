<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "faithtrip_accounts";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date information
$currentDay = date('j');
$currentMonth = date('n');
$currentYear = date('Y');

// Calculate current fortnight
if ($currentDay <= 15) {
    $currentFortnight = 1;
    $fortnightStart = date('Y-m-01');
    $fortnightEnd = date('Y-m-15');
} else {
    $currentFortnight = 2;
    $fortnightStart = date('Y-m-16');
    $fortnightEnd = date('Y-m-t');
}

// Calculate previous fortnight for billing
if ($currentFortnight == 1) {
    // If current is fortnight 1, previous was fortnight 2 of previous month
    $prevMonth = $currentMonth - 1;
    $prevYear = $currentYear;
    
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear--;
    }
    
    $prevFortnightStart = date('Y-m-16', strtotime("$prevYear-$prevMonth-01"));
    $prevFortnightEnd = date('Y-m-t', strtotime("$prevYear-$prevMonth-01"));
    $paymentDueDate = date('Y-m-t', strtotime("$currentYear-$currentMonth-01"));
    $paymentDescription = "For tickets issued in Fortnight 2 of " . date('F Y', strtotime("$prevYear-$prevMonth-01"));
} else {
    // If current is fortnight 2, previous was fortnight 1 of current month
    $prevFortnightStart = date('Y-m-01');
    $prevFortnightEnd = date('Y-m-15');
    $paymentDueDate = date('Y-m-t');
    $paymentDescription = "For tickets issued in Fortnight 1 of " . date('F Y');
}

// Query to calculate total NetPayment for all IATA tickets (current usage)
// This should be the sum of all IATA tickets minus payments
$sql = "SELECT COALESCE(SUM(NetPayment), 0) as totalIssued 
        FROM sales 
        WHERE Source = 'IATA'";

$result = $conn->query($sql);
$totalIssued = 0;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalIssued = $row['totalIssued'] ? (float)$row['totalIssued'] : 0;
}

// Query to calculate total paid amount to IATA (all time)
$sqlPaid = "SELECT COALESCE(SUM(amount), 0) as totalPaid 
            FROM paid 
            WHERE source = 'IATA'";

$resultPaid = $conn->query($sqlPaid);
$totalPaid = 0;

if ($resultPaid && $resultPaid->num_rows > 0) {
    $rowPaid = $resultPaid->fetch_assoc();
    $totalPaid = $rowPaid['totalPaid'] ? (float)$rowPaid['totalPaid'] : 0;
}

// Query to calculate total paid amount to IATA for current month
$sqlPaidThisMonth = "SELECT COALESCE(SUM(amount), 0) as paidThisMonth 
                     FROM paid 
                     WHERE source = 'IATA' 
                     AND YEAR(payment_date) = $currentYear 
                     AND MONTH(payment_date) = $currentMonth";

$resultPaidThisMonth = $conn->query($sqlPaidThisMonth);
$paidThisMonth = 0;

if ($resultPaidThisMonth && $resultPaidThisMonth->num_rows > 0) {
    $rowPaidThisMonth = $resultPaidThisMonth->fetch_assoc();
    $paidThisMonth = $rowPaidThisMonth['paidThisMonth'] ? (float)$rowPaidThisMonth['paidThisMonth'] : 0;
}

// Query to calculate fortnight payment (tickets from previous fortnight)
$sqlFortnight = "SELECT COALESCE(SUM(NetPayment), 0) as fortnightPayment 
                 FROM sales 
                 WHERE Source = 'IATA' 
                 AND IssueDate BETWEEN '$prevFortnightStart' AND '$prevFortnightEnd'";

$resultFortnight = $conn->query($sqlFortnight);
$fortnightPayment = 0;

if ($resultFortnight && $resultFortnight->num_rows > 0) {
    $rowFortnight = $resultFortnight->fetch_assoc();
    $fortnightPayment = $rowFortnight['fortnightPayment'] ? (float)$rowFortnight['fortnightPayment'] : 0;
}

// Calculate current usage (all issued tickets minus all payments)
$currentUsage = max(0, $totalIssued - $totalPaid);

// Close connection
$conn->close();

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'currentUsage' => $currentUsage,
    'paidThisMonth' => $paidThisMonth,
    'fortnightPayment' => $fortnightPayment,
    'paymentDueDate' => date('d M Y', strtotime($paymentDueDate)),
    'paymentDescription' => $paymentDescription
]);
?>