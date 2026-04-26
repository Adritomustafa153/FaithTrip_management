<?php
// manage_roles.php
session_start();
require 'configs.php';
require 'access_control.php';
requirePermission('roles.manage'); // only super_admin has this

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_role_perms'])) {
        $role = $_POST['role'];
        $selectedPerms = $_POST['permissions'] ?? [];
        
        // Delete old role permissions
        $pdo->prepare("DELETE FROM role_permissions WHERE role_name = ?")->execute([$role]);
        
        // Insert new ones
        foreach ($selectedPerms as $permName) {
            $stmt = $pdo->prepare("SELECT id FROM permissions WHERE name = ?");
            $stmt->execute([$permName]);
            $permId = $stmt->fetchColumn();
            if ($permId) {
                $pdo->prepare("INSERT INTO role_permissions (role_name, permission_id) VALUES (?, ?)")->execute([$role, $permId]);
            }
        }
        $_SESSION['message'] = "Permissions updated for role: $role";
    } elseif (isset($_POST['update_user_perms'])) {
        $userId = $_POST['user_id'];
        $extraPerms = $_POST['extra_permissions'] ?? [];
        
        // Delete existing overrides for this user
        $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$userId]);
        
        // Insert new overrides (allows)
        foreach ($extraPerms as $permName) {
            $stmt = $pdo->prepare("SELECT id FROM permissions WHERE name = ?");
            $stmt->execute([$permName]);
            $permId = $stmt->fetchColumn();
            if ($permId) {
                $pdo->prepare("INSERT INTO user_permissions (user_id, permission_id, is_allowed) VALUES (?, ?, 1)")->execute([$userId, $permId]);
            }
        }
        $_SESSION['message'] = "Extra permissions assigned to user.";
    }
    header("Location: manage_roles.php");
    exit;
}

// Get all permissions
$allPerms = $pdo->query("SELECT name, description FROM permissions ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get all roles (distinct from user table or predefined list)
$roles = $pdo->query("SELECT DISTINCT role FROM user WHERE role IS NOT NULL UNION SELECT 'admin_master' UNION SELECT 'accounts' UNION SELECT 'sales' UNION SELECT 'reservation' UNION SELECT 'test'")->fetchAll(PDO::FETCH_COLUMN);
$roles = array_unique($roles);
sort($roles);

// Get current role permissions if a role is selected
$selectedRole = $_GET['role'] ?? '';
$rolePerms = [];
if ($selectedRole) {
    $stmt = $pdo->prepare("SELECT p.name FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_name = ?");
    $stmt->execute([$selectedRole]);
    $rolePerms = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get users for extra permissions assignment
$users = $pdo->query("SELECT UserID, UserName, role FROM user WHERE role != 'super_admin' ORDER BY UserName")->fetchAll();
$selectedUser = $_GET['user_id'] ?? '';
$userExtraPerms = [];
if ($selectedUser) {
    $stmt = $pdo->prepare("SELECT p.name FROM user_permissions up JOIN permissions p ON up.permission_id = p.id WHERE up.user_id = ? AND up.is_allowed = 1");
    $stmt->execute([$selectedUser]);
    $userExtraPerms = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Roles & Permissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <h1>Manage Roles & Permissions</h1>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    
    <!-- Role Selection & Permissions -->
    <div class="card mb-4">
        <div class="card-header">Role Permissions</div>
        <div class="card-body">
            <form method="GET" action="">
                <label>Select Role:</label>
                <select name="role" onchange="this.form.submit()" class="form-select w-auto d-inline-block">
                    <option value="">-- Choose Role --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars($role) ?>" <?= $selectedRole === $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php if ($selectedRole): ?>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="role" value="<?= htmlspecialchars($selectedRole) ?>">
                    <div class="row">
                        <?php foreach ($allPerms as $perm): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm['name'] ?>" id="perm_<?= $perm['name'] ?>" <?= in_array($perm['name'], $rolePerms) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="perm_<?= $perm['name'] ?>">
                                        <?= htmlspecialchars($perm['name']) ?><br><small><?= htmlspecialchars($perm['description']) ?></small>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="update_role_perms" class="btn btn-primary mt-3">Update Role Permissions</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- User Extra Permissions -->
    <div class="card">
        <div class="card-header">User-Specific Extra Permissions</div>
        <div class="card-body">
            <form method="GET" action="">
                <label>Select User:</label>
                <select name="user_id" onchange="this.form.submit()" class="form-select w-auto d-inline-block">
                    <option value="">-- Choose User --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['UserID'] ?>" <?= $selectedUser == $user['UserID'] ? 'selected' : '' ?>><?= htmlspecialchars($user['UserName']) ?> (<?= $user['role'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php if ($selectedUser): ?>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="user_id" value="<?= $selectedUser ?>">
                    <div class="row">
                        <?php foreach ($allPerms as $perm): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="extra_permissions[]" value="<?= $perm['name'] ?>" id="extra_<?= $perm['name'] ?>" <?= in_array($perm['name'], $userExtraPerms) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="extra_<?= $perm['name'] ?>">
                                        <?= htmlspecialchars($perm['name']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="update_user_perms" class="btn btn-primary mt-3">Assign Extra Permissions</button>
                </form>
                <div class="alert alert-info mt-3">Note: These permissions are added ON TOP of the user's role permissions. To restrict a permission, you would need to edit role permissions or implement a deny table (not included).</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>