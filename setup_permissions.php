<?php
$conn = new mysqli('localhost','root','','faithtrip_accounts');
if ($conn->connect_error) die("Connection failed");

$permissions = [
    'dashboard.view','sales.view','sales.edit','sales.delete','financial.view','financial.edit',
    'bank.view','receivable.view','receivable.edit','user.manage','roles.manage','settings.manage'
];
foreach ($permissions as $p) {
    $conn->query("INSERT IGNORE INTO permissions (name, description) VALUES ('$p', '')");
}

$roles = ['super_admin','admin_master','accounts','sales','reservation','test'];
// Assign all perms to super_admin
$allPermIds = $conn->query("SELECT id FROM permissions")->fetch_all(MYSQLI_ASSOC);
foreach ($allPermIds as $perm) {
    $conn->query("INSERT IGNORE INTO role_permissions (role_name, permission_id) VALUES ('super_admin', {$perm['id']})");
}
// Assign specific perms to others (example)
$assign = [
    'admin_master' => ['dashboard.view','sales.view','sales.edit','sales.delete','financial.view','user.manage','settings.manage'],
    'accounts' => ['dashboard.view','financial.view','financial.edit','bank.view','receivable.view','receivable.edit'],
    'sales' => ['dashboard.view','sales.view','receivable.view'],
    'reservation' => ['dashboard.view','sales.view','sales.edit','receivable.view','receivable.edit'],
    'test' => []
];
foreach ($assign as $role=>$permsArr) {
    foreach ($permsArr as $perm) {
        $pid = $conn->query("SELECT id FROM permissions WHERE name='$perm'")->fetch_row()[0];
        if($pid) $conn->query("INSERT IGNORE INTO role_permissions (role_name, permission_id) VALUES ('$role', $pid)");
    }
}
echo "Setup complete. Delete this file now.";
?>