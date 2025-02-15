<!-- login_handler.php -->
<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'faithtrip_accounts');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to check user credentials
    $sql = "SELECT * FROM user WHERE email = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Store user data in session
        $_SESSION['UserId'] = $user['UserId'];
        $_SESSION['UserName'] = $user['UserName'];
        $_SESSION['image'] = $user['image']; // Ensure profile_image stores file path
   
        // Successful login
        //echo "<script>alert('Login successful!');</script>";
    //    header("Location: dashboard.php?user_id=". $user_id); // Redirect to dashboard page
       header("Location: dashboard.php");
    } else {
        // Invalid credentials
        echo "<script>alert('Invalid email or password.');</script>";
        header("Location: login.php");
    }

    $stmt->close();
    $conn->close();
}
?>