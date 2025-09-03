<?php
session_start();
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
    <title>Login </title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Caveat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: rgba(198, 211, 255, 0.27);
        }
        .login-container {
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
        .loading-gif {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .loading-gif img {
            max-width: 200px;
        }
    </style>
</head>
<body class="flex justify-center items-center min-h-screen">
    <!-- Loading GIF overlay (hidden by default) -->
    <div class="loading-gif" id="loadingGif">
        <img src="login.gif" alt="Loading...">
    </div>

    <div class="login-container w-full max-w-4xl bg-white rounded-xl p-8 mx-4">
        <div class="flex flex-col lg:flex-row items-center gap-12">
            <!-- Left Side - Branding -->
            <div class="w-full lg:w-1/2 flex flex-col items-center">
               
                <img src="logo.jpg" alt="BillBoard Logo" class="h-20 mb-6">
                <img src="accounts_display.gif" alt="Account Access" class="w-full max-w-xs rounded-lg mb-6">          
                <div class="handwriting text-center">
                    Login to <span class="brand-primary">Bill</span><span class="brand-secondary">Board</span>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="w-full lg:w-1/2">
                <div class="border-l border-gray-100 pl-8 lg:pl-12">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Faith Travels and Tours LTD</h1>
                    <p class="text-gray-500 mb-6">Please enter your credentials</p>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <?php echo htmlspecialchars($_SESSION['error']); 
                                unset($_SESSION['error']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="login_handler.php" method="POST" id="loginForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-5">
                            <label for="email" class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                            <input type="email" id="email" name="email" required 
                                class="input-field p-3 w-full border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition"
                                autocomplete="email" aria-describedby="email-help">
                            <div id="email-help" class="text-xs text-gray-500 mt-1">Enter your registered email address</div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="password" class="block text-sm font-medium text-gray-600 mb-1">Password</label>
                            <input type="password" id="password" name="password" required 
                                class="input-field p-3 w-full border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition"
                                autocomplete="current-password" minlength="8">
                        </div>
                        
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="remember-me" class="ml-2 block text-sm text-gray-600">Remember me</label>
                            </div>
                            <a href="forget_password.php" class="text-sm text-blue-500 hover:text-blue-700 hover:underline">Forgot Password?</a>
                        </div>
                        
                        <button type="submit" id="loginButton"
                            class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                            Sign In
                        </button>
                    </form>
<!--                     
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">New to BillBoard? 
                            <a href="register.php" class="text-blue-500 hover:text-blue-700 hover:underline">Create account</a>
                        </p>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Basic client-side validation
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                return;
            }
            
            // Show loading GIF
            document.getElementById('loadingGif').style.display = 'flex';
            
            // Form is valid, allow submission
            // No artificial delay - submit immediately after validation
        });
    </script>
</body>
</html>