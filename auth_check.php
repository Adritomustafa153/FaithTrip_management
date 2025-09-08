<?php
// ------------------------------
// Secure Session Initialization
// ------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,                     // 1 day (your original setting)
        'cookie_secure'   => !empty($_SERVER['HTTPS']), // Secure if HTTPS
        'cookie_httponly' => true,                      // Protect from JavaScript access
        'cookie_samesite' => 'Strict'                   // Prevent CSRF attacks
    ]);
}

// ------------------------------
// Optional: Restrict Access by IP
// ------------------------------
// Uncomment and add your allowed IPs
/*
$allowed_ips = ['203.76.200.50', '192.168.0.100'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!in_array($client_ip, $allowed_ips)) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied.");
}
*/

// ------------------------------
// Check if user is logged in
// ------------------------------
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['error'] = "Please login to access this page";
    header("Location: login.php");
    exit();
}

// ------------------------------
// Check for session timeout (30 minutes)
// ------------------------------
$inactive = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1"); // safer than using $_SESSION after destroy
    exit();
}

// ------------------------------
// Update last activity time
// ------------------------------
$_SESSION['last_activity'] = time();

// ------------------------------
// Regenerate Session ID (once per session)
// ------------------------------
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}
?>
