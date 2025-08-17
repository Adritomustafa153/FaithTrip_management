<?php
// Start session with secure parameters
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $_SESSION['error'] = "Email and password are required";
        header("Location: login.php");
        exit();
    }

    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'faithtrip_accounts');
    
    if ($conn->connect_error) {
        $_SESSION['error'] = "Database connection failed";
        header("Location: login.php");
        exit();
    }

    // Get user from database including login attempt info
    $sql = "SELECT UserId, UserName, email, Password, login_attempts, last_failed_login, is_locked, lock_time, last_login 
            FROM user 
            WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if account is locked
        if ($user['is_locked'] == 1) {
            $lockTime = strtotime($user['lock_time']);
            $currentTime = time();
            $lockDuration = 30 * 60; // 30 minutes in seconds
            
            if (($currentTime - $lockTime) < $lockDuration) {
                $remainingTime = ceil(($lockDuration - ($currentTime - $lockTime)) / 60);
                $_SESSION['error'] = "Account locked. Try again in $remainingTime minutes.";
                header("Location: login.php");
                exit();
            } else {
                // Unlock the account if lock duration has passed
                $unlockSql = "UPDATE user SET is_locked = 0, login_attempts = 0 WHERE UserId = ?";
                $unlockStmt = $conn->prepare($unlockSql);
                $unlockStmt->bind_param('i', $user['UserId']);
                $unlockStmt->execute();
                $user['is_locked'] = 0;
                $user['login_attempts'] = 0;
            }
        }
        
        // Check if password is hashed (starts with $2y$)
        $isHashed = (strpos($user['Password'], '$2y$') === 0);
        
        // Verify password (works for both hashed and plain text during transition)
        $authenticated = false;
        
        if ($isHashed) {
            // New user - verify hash
            $authenticated = password_verify($password, $user['Password']);
        } else {
            // Old user - compare plain text
            $authenticated = ($password === $user['Password']);
        }
        
        if ($authenticated) {
            // Upgrade old user to hashed password if needed
            if (!$isHashed) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE user SET Password = ? WHERE UserId = ?");
                $update->bind_param('si', $hashedPassword, $user['UserId']);
                $update->execute();
            }
            
            // Reset security settings and update last login time
            $resetSql = "UPDATE user SET 
                        login_attempts = 0, 
                        is_locked = 0, 
                        last_login = NOW() 
                        WHERE UserId = ?";
            $resetStmt = $conn->prepare($resetSql);
            $resetStmt->bind_param('i', $user['UserId']);
            $resetStmt->execute();
            
            // Regenerate session ID
            session_regenerate_id(true);
            
            // Set session variables (including last login time)
            $_SESSION['user_id'] = $user['UserId'];
            $_SESSION['user_name'] = $user['UserName'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['last_login'] = $user['last_login']; // Previous login time
            $_SESSION['current_login'] = date('Y-m-d H:i:s'); // Current login time
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            header("Location: dashboard.php");
            exit();
        } else {
            // Increment failed login attempts
            $attempts = $user['login_attempts'] + 1;
            $remainingAttempts = max(0, 4 - $attempts);
            
            if ($attempts >= 4) {
                // Lock the account
                $lockSql = "UPDATE user SET 
                           login_attempts = ?, 
                           is_locked = 1, 
                           lock_time = NOW() 
                           WHERE UserId = ?";
                $lockStmt = $conn->prepare($lockSql);
                $lockStmt->bind_param('ii', $attempts, $user['UserId']);
                $lockStmt->execute();
                
                $_SESSION['error'] = "Account locked due to too many failed attempts. Try again in 30 minutes.";
            } else {
                // Update attempt count
                $updateSql = "UPDATE user SET 
                             login_attempts = ?, 
                             last_failed_login = NOW() 
                             WHERE UserId = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param('ii', $attempts, $user['UserId']);
                $updateStmt->execute();
                
                $_SESSION['error'] = "Invalid email or password. $remainingAttempts attempts remaining.";
            }
            
            header("Location: login.php");
            exit();
        }
    } else {
        // User not found - generic error to prevent user enumeration
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
}
?>