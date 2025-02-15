<?php
$mysqli = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$password = "Adrito153";
$stored_hash = "$2y$10$47kpMkL/T2kU8i8ZFnQ1wOzP9SfqXlsiAt2O8DeLPe3CqEpO/z.qu";

if (password_verify($password, $stored_hash)) {
    echo "Password is correct!";
} else {
    echo "Incorrect password!";
}

?>
