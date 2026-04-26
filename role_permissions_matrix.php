<?php
session_start();
require_once __DIR__ . '/access_control.php';
requirePermission('roles.manage');

$conn = getDbConnection();

// Handle AJAX toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_permission') {
    header('Content-Type: application/json');
    $role = $_POST['role'];
    $permission = $_POST['permission'];
    $checked = $_POST['checked'] === 'true';
    
    $stmt = $conn->prepare("SELECT id FROM permissions WHERE name = ?");
    $stmt->bind_param('s', $permission);
    $stmt->execute();
    $permId = $stmt->get_result()->fetch_column();
    $stmt->close();
    
    if ($permId) {
        if ($checked) {
            $ins = $conn->prepare("INSERT IGNORE INTO role_permissions (role_name, permission_id) VALUES (?, ?)");
            $ins->bind_param('si', $role, $permId);
            $ins->execute();
            $ins->close();
        } else {
            $del = $conn->prepare("DELETE FROM role_permissions WHERE role_name = ? AND permission_id = ?");
            $del->bind_param('si', $role, $permId);
            $del->execute();
            $del->close();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Permission not found']);
    }
    exit;
}

// Fetch all roles (columns)
$roles = [];
$rolesQuery = $conn->query("SELECT role_name FROM roles ORDER BY role_name");
if ($rolesQuery && $rolesQuery->num_rows) {
    while ($row = $rolesQuery->fetch_assoc()) $roles[] = $row['role_name'];
} else {
    $rolesQuery2 = $conn->query("SELECT DISTINCT role FROM user WHERE role IS NOT NULL AND role != ''");
    while ($row = $rolesQuery2->fetch_assoc()) $roles[] = $row['role'];
}
if (empty($roles)) $roles = ['super_admin', 'admin_master', 'accounts', 'sales', 'reservation', 'test'];

// Fetch all permissions (rows)
$permsQuery = $conn->query("SELECT id, name, description FROM permissions ORDER BY name");
$permissions = [];
while ($p = $permsQuery->fetch_assoc()) $permissions[] = $p;

// Build matrix: permission -> role -> has
$matrix = [];
foreach ($permissions as $p) {
    $permName = $p['name'];
    $matrix[$permName] = [];
    foreach ($roles as $role) {
        $matrix[$permName][$role] = false;
    }
}
// Fill matrix from role_permissions
foreach ($roles as $role) {
    $stmt = $conn->prepare("SELECT p.name FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_name = ?");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $matrix[$row['name']][$role] = true;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Permissions Matrix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .matrix-table th { background: #4e73df; color: white; text-align: center; vertical-align: middle; }
        .matrix-table td { text-align: center; vertical-align: middle; }
        .perm-col { background: #f8f9fc; font-weight: bold; text-align: left !important; }
        .toggle-switch { width: 40px; height: 20px; background: #ccc; border-radius: 20px; display: inline-block; position: relative; cursor: pointer; transition: 0.2s; }
        .toggle-switch.active { background: #28a745; }
        .toggle-switch span { position: absolute; width: 16px; height: 16px; background: white; border-radius: 50%; top: 2px; left: 2px; transition: 0.2s; }
        .toggle-switch.active span { left: 22px; }
        .badge-desc { font-size: 0.7rem; background: #e9ecef; color: #495057; margin-top: 4px; display: inline-block; padding: 2px 6px; border-radius: 12px; }
    </style>
</head>
<body>
<?php include 'nav.php'; // nav.php is in the same folder (faithtrip/accounts) ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-lock"></i> Role Permissions Matrix</h2>
        <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
    </div>
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered matrix-table">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th><?= ucfirst(htmlspecialchars($role)) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $p): 
                            $permName = $p['name'];
                            $isSuperAdmin = ($_SESSION['role'] === 'super_admin');
                        ?>
                        <tr>
                            <td class="perm-col">
                                <?= htmlspecialchars($permName) ?>
                                <div class="badge-desc"><?= htmlspecialchars($p['description']) ?></div>
                            </td>
                            <?php foreach ($roles as $role): 
                                $has = $matrix[$permName][$role];
                            ?>
                                <td>
                                    <?php if ($isSuperAdmin): ?>
                                        <div class="toggle-switch <?= $has ? 'active' : '' ?>" data-role="<?= htmlspecialchars($role) ?>" data-perm="<?= htmlspecialchars($permName) ?>">
                                            <span></span>
                                        </div>
                                    <?php else: ?>
                                        <?= $has ? '<i class="fas fa-check-circle text-success fa-lg"></i>' : '<i class="fas fa-times-circle text-danger fa-lg"></i>' ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    $('.toggle-switch').click(function() {
        var $toggle = $(this);
        var role = $toggle.data('role');
        var perm = $toggle.data('perm');
        var newState = !$toggle.hasClass('active');
        $.ajax({
            url: 'role_permissions_matrix.php',
            type: 'POST',
            data: { action: 'toggle_permission', role: role, permission: perm, checked: newState },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    if (newState) $toggle.addClass('active');
                    else $toggle.removeClass('active');
                } else alert('Error: ' + (res.error || 'Update failed'));
            },
            error: function() { alert('Server error'); }
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>