<!-- <?php
$host = '127.0.0.1';
$db   = 'faithtrip_accounts';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?> -->

<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'faithtrip_accounts');
define('DB_USER', 'root'); // Change as needed
define('DB_PASS', ''); // Change as needed

// PHPMailer configuration
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP server
define('SMTP_USER', 'faithtrip.net@gmail.com'); // Change to your email
define('SMTP_PASS', 'hprnbfnzkywrymqw'); // Change to your email password
define('SMTP_SECURE', 'tls');
define('SMTP_PORT', 587);

// Website configuration
define('BASE_URL', 'http://localhost/faithtrip/accounts/'); // Change to your base URL

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
