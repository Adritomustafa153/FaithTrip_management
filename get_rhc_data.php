<?php
// Database configuration
$host = '127.0.0.1';
$db   = 'faithtrip_accounts';
$user = 'root'; // Replace with your database username
$pass = ''; // Replace with your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Debug: Check if we can connect to the database
    error_log("Connected to database successfully");
    
} catch (\PDOException $e) {
    // Log error and return a response
    error_log("Database connection failed: " . $e->getMessage());
    
    // Return a JSON response even on error
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Database connection failed',
        'current_usage' => 0,
        'period_start' => date('Y-m-01'),
        'period_end' => date('Y-m-15'),
        'last_updated' => date('D, d M Y H:i:s')
    ]);
    exit;
}

// Determine current fortnight period
$currentDay = date('j');
$currentMonth = date('n');
$currentYear = date('Y');

if ($currentDay <= 15) {
    // First half of the month (1st to 15th)
    $periodStart = date('Y-m-01');
    $periodEnd = date('Y-m-15');
} else {
    // Second half of the month (16th to end of month)
    $periodStart = date('Y-m-16');
    $periodEnd = date('Y-m-t'); // t returns the number of days in the month
}

// Debug: Log the period being queried
error_log("Querying period: $periodStart to $periodEnd");

try {
    // Calculate total IATA sales for the current period
    $stmt = $pdo->prepare("SELECT SUM(BillAmount) as total FROM sales 
                          WHERE Source LIKE '%IATA%' AND Remarks = 'Sell' 
                          AND IssueDate BETWEEN :startDate AND :endDate");
    $stmt->execute([
        'startDate' => $periodStart,
        'endDate' => $periodEnd
    ]);
    $result = $stmt->fetch();

    // Debug: Log the query result
    error_log("Query result: " . print_r($result, true));
    
    $current_usage = $result['total'] ? (float)$result['total'] : 0;
    
    // Debug: Log the calculated usage
    error_log("Current usage: $current_usage");
    
} catch (Exception $e) {
    // Log error and set usage to 0
    error_log("Query failed: " . $e->getMessage());
    $current_usage = 0;
}

// Prepare response
$response = [
    'current_usage' => $current_usage,
    'period_start' => $periodStart,
    'period_end' => $periodEnd,
    'last_updated' => date('D, d M Y H:i:s')
];

header('Content-Type: application/json');
echo json_encode($response);
?>