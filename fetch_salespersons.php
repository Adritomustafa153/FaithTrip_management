<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

$query = "SELECT name FROM sales_person ORDER BY name";
$result = $conn->query($query);

$options = "<option value=''>Sales Person</option>";
while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['name']}'>{$row['name']}</option>";
}
echo $options;
?>
