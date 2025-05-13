<?php
$host = 'localhost';
$user = 'root'; // change if needed
$password = ''; // change if needed
$db = 'faithtrip_accounts';

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = intval($_POST['id']);
  $name = $conn->real_escape_string($_POST['name']);
  $dob = $_POST['dob'];
  $passport = $conn->real_escape_string($_POST['passport']);
  $expiry = $_POST['expiry'];
  $email = $conn->real_escape_string($_POST['email']);

  $sql = "UPDATE passengers SET 
            name = '$name',
            date_of_birth = '$dob',
            passport_number = '$passport',
            passport_expiry = '$expiry',
            email = '$email'
          WHERE id = $id";

  if ($conn->query($sql) === TRUE) {
    header("Location: passenger_list.php?success=1");
    exit();
  } else {
    echo "Error updating record: " . $conn->error;
  }
} else {
  echo "Invalid request.";
}

$conn->close();
?>
