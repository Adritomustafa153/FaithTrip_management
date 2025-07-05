
<?php


$section = $conn->real_escape_string($_GET['section'] ?? '');

$sql = "SELECT DISTINCT PartyName FROM sales WHERE PartyName != ''";
if ($section) $sql .= " AND section = '$section'";

$result = $conn->query($sql);

echo '<option value="">All Parties</option>';
while ($row = $result->fetch_assoc()) {
    echo '<option value="'.htmlspecialchars($row['PartyName']).'">'
        .htmlspecialchars($row['PartyName']).'</option>';
}
?>