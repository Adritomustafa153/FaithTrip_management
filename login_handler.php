<?php
// ------------------------------
// Start session with secure parameters
// ------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure'   => !empty($_SERVER['HTTPS']), // Secure only if HTTPS
        'cookie_httponly' => true,                      // Prevent JS access
        'cookie_samesite' => 'Strict',                  // Mitigate CSRF
        'use_strict_mode' => true                       // Prevent session fixation
    ]);
}

// Force regenerate session ID on every fresh POST to avoid fixation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_regenerate_id(true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ------------------------------
    // Validate and sanitize inputs
    // ------------------------------
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $_SESSION['error'] = "Email and password are required";
        header("Location: login.php");
        exit();
    }

    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // ------------------------------
    // Database connection
    // ------------------------------
    $conn = new mysqli('localhost', 'root', '', 'faithtrip_accounts');
    
    if ($conn->connect_error) {
        $_SESSION['error'] = "Database connection failed";
        header("Location: login.php");
        exit();
    }

    // ------------------------------
    // Get user from database including login attempt info
    // ------------------------------
    $sql = "SELECT UserId, UserName, email, Password, login_attempts, last_failed_login, 
                   is_locked, lock_time, last_login 
            FROM user 
            WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // ------------------------------
        // Check if account is locked
        // ------------------------------
        if ($user['is_locked'] == 1) {
            $lockTime = strtotime($user['lock_time']);
            $currentTime = time();
            $lockDuration = 30 * 60; // 30 minutes
            
            if (($currentTime - $lockTime) < $lockDuration) {
                $remainingTime = ceil(($lockDuration - ($currentTime - $lockTime)) / 60);
                $_SESSION['error'] = "Account locked. Try again in $remainingTime minutes.";
                header("Location: login.php");
                exit();
            } else {
                // Unlock account after lock duration
                $unlockSql = "UPDATE user SET is_locked = 0, login_attempts = 0 WHERE UserId = ?";
                $unlockStmt = $conn->prepare($unlockSql);
                $unlockStmt->bind_param('i', $user['UserId']);
                $unlockStmt->execute();
                $user['is_locked'] = 0;
                $user['login_attempts'] = 0;
            }
        }
        
        // ------------------------------
        // Password verification
        // ------------------------------
        $isHashed = (strpos($user['Password'], '$2y$') === 0);
        $authenticated = false;
        
        if ($isHashed) {
            $authenticated = password_verify($password, $user['Password']);
        } else {
            $authenticated = ($password === $user['Password']);
        }
        
        if ($authenticated) {
            // Upgrade to hashed password if old plain text
            if (!$isHashed) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE user SET Password = ? WHERE UserId = ?");
                $update->bind_param('si', $hashedPassword, $user['UserId']);
                $update->execute();
            }
            
            // Reset attempts and update last login
            $resetSql = "UPDATE user SET 
                        login_attempts = 0, 
                        is_locked = 0, 
                        last_login = NOW() 
                        WHERE UserId = ?";
            $resetStmt = $conn->prepare($resetSql);
            $resetStmt->bind_param('i', $user['UserId']);
            $resetStmt->execute();
            
            // ------------------------------
            // Secure session setup
            // ------------------------------
            session_regenerate_id(true); // prevent session fixation
            
            $_SESSION['user_id']       = $user['UserId'];
            $_SESSION['user_name']     = htmlspecialchars($user['UserName'], ENT_QUOTES, 'UTF-8'); // XSS safe
            $_SESSION['user_email']    = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');    // XSS safe
            $_SESSION['last_login']    = $user['last_login'];
            $_SESSION['current_login'] = date('Y-m-d H:i:s');
            $_SESSION['logged_in']     = true;
            $_SESSION['last_activity'] = time();
            
            header("Location: dashboard.php");
            exit();
        } else {
            // ------------------------------
            // Failed login handling
            // ------------------------------
            $attempts = $user['login_attempts'] + 1;
            $remainingAttempts = max(0, 4 - $attempts);
            
            if ($attempts >= 4) {
                // Lock account
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
        // ------------------------------
        // User not found (generic error)
        // ------------------------------
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
}
?>
