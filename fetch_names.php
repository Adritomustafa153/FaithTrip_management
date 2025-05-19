<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
$type = $_GET['type'];
$data = [];

if ($type === 'company') {
    $res = $conn->query("SELECT CompanyID as id, CompanyName as name, CompanyAddress as address FROM companyprofile");
} elseif ($type === 'agent') {
    $res = $conn->query("SELECT AgentsID as id, AgentName as name, ShopAddress as address FROM agents");
} elseif ($type === 'passenger') {
    $res = $conn->query("SELECT id as id, name as name, Address as address FROM passengers");
}

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
