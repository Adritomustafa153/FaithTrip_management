<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

// Check if the search term is provided, otherwise default to an empty string
$search = $_POST['search'] ?? '';

// Prepare the query to search for agents by AgentName or AgentsID
$query = "SELECT ShopName, AgentName, AgentsID FROM agents WHERE AgentName LIKE '%$search%' OR AgentsID LIKE '%$search%' ORDER BY AgentName";
$result = $conn->query($query);

// Check if the query executed successfully
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Start building the options with a default "Select Agent" option
$options = "<option value=''>Select Agent</option>";

// Loop through the results and append each agent as an option
while ($row = $result->fetch_assoc()) {
    // Debugging: Print the row to see what data is being fetched
    echo "<pre>";
    print_r($row);
    echo "</pre>";

    // Use the correct fields: ShopName, AgentName, and AgentsID
    $options .= "<option value='{$row['ShopName']}'> {$row['ShopName']} - {$row['AgentName']} ({$row['AgentsID']}) </option>";
}

// Output the options
echo $options;

// Close the database connection
$conn->close();
?>