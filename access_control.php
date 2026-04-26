<?php
// access_control.php - RBAC core
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

    // Role-based permissions
    $stmt = $conn->prepare("SELECT p.name FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_name = ?");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $perms[] = $row['name'];
    $stmt->close();

    // User-specific extra permissions
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
    if (!empty($_SESSION['is_test_user'])) return false;
    if (!isset($_SESSION['permissions'])) {
        $_SESSION['permissions'] = loadUserPermissions($_SESSION['user_id']);
    }
    return in_array($perm, $_SESSION['permissions']);
}

function requirePermission($perm) {
    if (!hasPermission($perm)) {
        $_SESSION['access_denied'] = "Missing permission: $perm";
        header('Location: dashboard.php');
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