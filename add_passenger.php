<!DOCTYPE html>
<html>
<head>
  <title>Add Passenger</title>
  <style>
    body { font-family: Arial; background: #f4f4f4; padding: 20px; }
    form { background: white; padding: 20px; border-radius: 10px; max-width: 400px; margin: auto; }
    input, button { width: 100%; padding: 10px; margin: 10px 0; }
  </style>
</head>
<body>
  <h2>Add Passenger</h2>
  <form method="POST" action="add_passenger.php">
    <input type="text" name="pname" placeholder="Passenger Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="date" name="dob" placeholder="Date of Birth" required>
    <input type="text" name="passport_number" placeholder="Passport Number" required>
    <input type="date" name="passport_expiry" required>
    <button type="submit">Save</button>
  </form>
</body>
</html>


<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "passport_db");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    // Use null coalescing operator to avoid notices (if needed)
    $name = $_POST['pname'] ?? '';
    $email = $_POST['email'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $passport = $_POST['passport_number'] ?? '';
    $expiry = $_POST['passport_expiry'] ?? '';

    $stmt = $conn->prepare("INSERT INTO passengers (name, email, date_of_birth, passport_number, passport_expiry) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $dob, $passport, $expiry);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "Passenger added successfully. <a href='add_passenger.php'>Go Back</a>";
}
?>
