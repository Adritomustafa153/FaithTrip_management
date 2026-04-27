<?php
// access_control.php - RBAC core with modal access denied
if (session_status() === PHP_SESSION_NONE) session_start();

function getDbConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli('localhost', 'root', '', 'faithtrip_accounts');
        if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function loadUserPermissions($userId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT role FROM user WHERE UserID = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) return [];

    $role = $user['role'];
    $perms = [];

    $stmt = $conn->prepare("SELECT p.name FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_name = ?");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $perms[] = $row['name'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT p.name FROM user_permissions up JOIN permissions p ON up.permission_id = p.id WHERE up.user_id = ? AND up.is_allowed = 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $perms[] = $row['name'];
    $stmt->close();

    return array_unique($perms);
}

function hasPermission($perm) {
    if (!isset($_SESSION['user_id'])) return false;
    if (isset($_SESSION['is_test_user']) && $_SESSION['is_test_user'] === true) return false;
    if (!isset($_SESSION['permissions'])) {
        $_SESSION['permissions'] = loadUserPermissions($_SESSION['user_id']);
    }
    return in_array($perm, $_SESSION['permissions']);
}

function showAccessDeniedModal($permission) {
    // Clean any previous output
    if (ob_get_level()) ob_end_clean();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; margin:0; }
        .modal-backdrop { z-index: 1040; }
        .modal { z-index: 1050; display: block; background: rgba(0,0,0,0.5); }
        .modal-content { border-radius: 1rem; text-align: center; padding: 1rem; }
    </style>
</head>
<body>
    <div class="modal show" id="accessDeniedModal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-ban"></i> Access Denied</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="window.history.back()"></button>
                </div>
                <div class="modal-body">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/access-denied-6074339-5006815.png" 
                         alt="Access Denied" style="max-width: 150px; margin-bottom: 1rem;">
                    <p>You do not have permission to view this page.</p>
                    
<<<<<<< HEAD
                </div>
=======
>>>>>>> 8c1e5f3 (server sync)
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Go Back</button>
                    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.body.style.overflow = 'hidden';
        document.querySelector('.modal').addEventListener('click', function(e) {
            if (e.target === this) window.history.back();
        });
    </script>
</body>
</html>
    <?php
    exit;
}

function requirePermission($perm) {
    if (!hasPermission($perm)) {
        // Store the missing permission in session
        $_SESSION['access_denied_permission'] = $perm;
        // Redirect to a dedicated access denied page
        header('Location: access_denied.php');
        exit;
    }
}

function requireLogin() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: accounts/login.php');
        exit;
    }
}

function isTestUser() {
    return isset($_SESSION['is_test_user']) && $_SESSION['is_test_user'] === true;
}

function clearPermissionsCache() {
    unset($_SESSION['permissions']);
}
?>