<?php
session_start();
require 'vendor/autoload.php';
require 'configs.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token. Please try again.';
        $message_type = 'error';
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // Check if email exists in database
        $stmt = $pdo->prepare("SELECT UserID, UserName FROM user WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $reset_token_hash = hash('sha256', $reset_token);
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Update user record with reset token
            $stmt = $pdo->prepare("UPDATE user SET reset_token_hash = :token_hash, reset_token_expires_at = :expiry WHERE UserID = :user_id");
            $stmt->execute([
                'token_hash' => $reset_token_hash,
                'expiry' => $expiry_time,
                'user_id' => $user['UserID']
            ]);
            
            // Send email with reset link
            $reset_link = BASE_URL . "reset_password.php?token=" . urlencode($reset_token);
            
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port = SMTP_PORT;
                
                // Recipients
                $mail->setFrom(SMTP_USER, 'Faith Travels and Tours LTD');
                $mail->addAddress($email, $user['UserName']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Hello " . htmlspecialchars($user['UserName']) . ",</p>
                    <p>You requested a password reset. Click the link below to reset your password:</p>
                    <p><a href='$reset_link' style='background-color: #3B82F6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this reset, please ignore this email.</p>
                    <br>
                    <p>Best regards,<br>Faith Travels and Tours LTD</p>
                ";
                
                $mail->AltBody = "Password Reset Request\n\nHello " . $user['UserName'] . ",\n\nYou requested a password reset. Use this link to reset your password: $reset_link\n\nThis link will expire in 1 hour.\n\nIf you didn't request this reset, please ignore this email.";
                
                $mail->send();
                
                $message = 'Password reset instructions have been sent to your email.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Failed to send email. Please try again later. Error: ' . $mail->ErrorInfo;
                $message_type = 'error';
            }
        } else {
            $message = 'If this email exists in our system, you will receive a password reset link.';
            $message_type = 'success'; // Don't reveal if email exists or not
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - BillBoard</title>
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
            
            <?php if ($message): ?>
                <div class="w-full mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <p class="text-gray-600 mb-6 text-center">Enter your email address and we'll send you instructions to reset your password.</p>
            
            <form action="forget_password.php" method="POST" class="w-full">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" id="email" name="email" required 
                        class="input-field p-3 w-full border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition"
                        autocomplete="email">
                </div>
                
                <button type="submit"
                    class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition mb-4">
                    Send Reset Instructions
                </button>
                
                <div class="text-center">
                    <a href="login.php" class="text-blue-500 hover:text-blue-700 hover:underline">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>