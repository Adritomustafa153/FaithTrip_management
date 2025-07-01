<?php
session_start();
if (!isset($_SESSION['invoice_sent'])) {
    header("Location: invoice_list.php");
    exit;
}
$invoice_file = $_SESSION['invoice_file'] ?? '';
$invoice_email = $_SESSION['invoice_email'] ?? '';
unset($_SESSION['invoice_sent']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mail Sent Successfully</title>
    <style>
        body {
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        .message {
            font-size: 22px;
            margin-top: 20px;
            color:rgb(25, 98, 255);
        }
    </style>
</head>
<body>
    <img src="gif/mail3.gif" alt="Mail Sent" width="120" />
    <div class="message">Invoice sent to <strong><?php echo htmlspecialchars($invoice_email); ?></strong></div>

    <script>
        // Redirect after 3 seconds
        setTimeout(() => {
            window.location.href = "invoice_list.php";
        }, 3000);
    </script>
</body>
</html>
