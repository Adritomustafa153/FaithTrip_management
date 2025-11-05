<?php
include 'db.php';

$type = $_GET['type'] ?? '';
$clients = [];

if ($type == 'company') {
    $query = "SELECT DISTINCT PartyName as name FROM sales WHERE PartyName IS NOT NULL AND PartyName != '' 
              UNION SELECT DISTINCT partyName as name FROM hotel WHERE partyName IS NOT NULL AND partyName != ''";
} elseif ($type == 'agent') {
    $query = "SELECT DISTINCT PartyName as name FROM sales WHERE PartyName IS NOT NULL AND PartyName != '' 
              AND Section = 'agent'";
} else {
    $query = "SELECT DISTINCT PartyName as name FROM sales WHERE PartyName IS NOT NULL AND PartyName != ''";
}

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}

header('Content-Type: application/json');
echo json_encode($clients);
?>