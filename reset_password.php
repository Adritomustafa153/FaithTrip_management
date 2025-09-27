<?php
session_start();
require 'configs.php';

$error = '';
$success = '';
$valid_token = false;
$token = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_hash = hash('sha256', $token);
    
    // Check if token is valid and not expired
    $stmt = $pdo->prepare("SELECT UserID, reset_token_expires_at FROM user WHERE reset_token_hash = :token_hash");
    $stmt->execute(['token_hash' => $token_hash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && strtotime($user['reset_token_expires_at']) >= time()) {
        $valid_token = true;
        $user_id = $user['UserID'];
    } else {
        $error = 'Invalid or expired reset token.';
    }
} else {
    $error = 'No reset token provided.';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate passwords
        if (empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            // Hash the new password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $stmt = $pdo->prepare("UPDATE user SET Password = :password, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE UserID = :user_id");
            $stmt->execute([
                'password' => $password_hash,
                'user_id' => $user_id
            ]);
            
            $success = 'Password has been reset successfully. You can now <a href="login.php" class="text-blue-500 hover:underline">login</a> with your new password.';
            $valid_token = false; // Token is now invalid
        }
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BillBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Caveat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: rgba(198, 211, 255, 0.27);
        }
        .container {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            color: rgba(70, 46, 250, 0.43);
        }
        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(232, 239, 250, 0.3);
        }
        .handwriting {
            font-family: 'Caveat', cursive;
            font-size: 2.5rem;
            line-height: 1.2;
        }
        .brand-primary {
            color: #3B82F6;
        }
        .brand-secondary {
            color: #EF4444;
        }
    </style>
</head>
<body class="flex justify-center items-center min-h-screen p-4">
    <div class="container w-full max-w-md bg-white rounded-xl p-8">
        <div class="flex flex-col items-center">
            <img src="logo.jpg" alt="BillBoard Logo" class="h-20 mb-6">
            
            <div class="handwriting text-center mb-6">
                Reset <span class="brand-primary">Password</span>
            </div>
            
            <?php if ($error): ?>
                <div class="w-full mb-6 p-4 rounded-lg bg-red-50 text-red-700 border border-red-200">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="w-full mb-6 p-4 rounded-lg bg-green-50 text-green-700 border border-green-200">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token): ?>
                <p class="text-gray-600 mb-6 text-center">Please enter your new password below.</p>
                
                <form action="reset_password.php?token=<?php echo urlencode($token); ?>" method="POST" class="w-full">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-5">
                        <label for="password" class="block text-sm font-medium text-gray-600 mb-1">New Password</label>
                        <input type="password" id="password" name="password" required 
                            class="input-field p-3 w-full border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition"
                            autocomplete="new-password" minlength="8">
                        <div class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-600 mb-1">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                            class="input-field p-3 w-full border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition"
                            autocomplete="new-password" minlength="8">
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition mb-4">
                        Reset Password
                    </button>
                </form>
            <?php elseif (empty($error) && empty($success)): ?>
                <div class="text-center">
                    <p class="text-gray-600 mb-4">Loading...</p>
                </div>
            <?php endif; ?>
            
            <div class="text-center">
                <a href="login.php" class="text-blue-500 hover:text-blue-700 hover:underline">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>