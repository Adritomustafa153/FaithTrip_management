<?php
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
$sql_agents = "SELECT SUM(s.BillAmount) AS AgentSales 
               FROM sales s 
               INNER JOIN agents a 
               ON TRIM(LOWER(s.PartyName)) = TRIM(LOWER(a.AgentName))";
$result_agents = $conn->query($sql_agents);

$sql_debug = "SELECT s.PartyName, a.AgentName, s.BillAmount 
              FROM sales s 
              INNER JOIN agents a 
              ON TRIM(LOWER(s.PartyName)) = TRIM(LOWER(a.AgentName))";
$result_debug = $conn->query($sql_debug);

if ($result_debug->num_rows > 0) {
    while ($row = $result_debug->fetch_assoc()) {
        echo "Matched: " . $row['PartyName'] . " -> " . $row['AgentName'] . " (Amount: " . $row['BillAmount'] . ")<br>";
    }
} else {
    echo "No matches found between sales and agents.";
}


if ($result_agents->num_rows > 0) {
    $row = $result_agents->fetch_assoc();
    $agent_sales = $row['AgentSales'];
    echo "Agent Sales: " . $agent_sales; // Debugging line
} else {
    echo "No matching agent sales found."; // Debugging line
}
?>
