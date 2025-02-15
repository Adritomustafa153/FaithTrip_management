<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

$query = "SELECT Bank_Name FROM banks ORDER BY Bank_Name";
$result = $conn->query($query);

$options = "<option value=''>Select Bank</option>";
while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['Bank_Name']}'>{$row['Bank_Name']}</option>";
}
echo $options;
?>
