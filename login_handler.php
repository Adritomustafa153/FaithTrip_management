<?php
session_start();
require_once __DIR__ . '/access_control.php';  // ✅ correct slash

$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT UserID, UserName, email, Password, role, is_locked, login_attempts, lock_time FROM user WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['Password'])) {
        if ($user['is_locked']) {
            $_SESSION['error'] = "Account locked. Contact admin.";
            header('Location: login.php');
            exit;
        }

        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['user_name'] = $user['UserName'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['is_test_user'] = ($user['role'] === 'test');
        $_SESSION['permissions'] = loadUserPermissions($user['UserID']);

        $upd = $conn->prepare("UPDATE user SET login_attempts = 0, last_login = NOW() WHERE UserID = ?");
        $upd->bind_param('i', $user['UserID']);
        $upd->execute();

        header('Location: dashboard.php');
        exit;
    } else {
        if ($user) {
            $attempts = $user['login_attempts'] + 1;
            $lock = $attempts >= 5 ? 1 : 0;
            $upd = $conn->prepare("UPDATE user SET login_attempts = ?, is_locked = ? WHERE UserID = ?");
            $upd->bind_param('iii', $attempts, $lock, $user['UserID']);
            $upd->execute();
        }
        $_SESSION['error'] = "Invalid email or password";
        header('Location: login.php');
        exit;
    }
}
?>