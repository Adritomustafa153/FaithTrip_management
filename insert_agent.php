<?php
// Database connection
include 'db.php';
include 'auth_check.php';

// Insert logic
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $AgentName = $_POST['AgentName'];
    $ShopName = $_POST['ShopName'];
    $ShopAddress = $_POST['ShopAddress'];
    $Email = $_POST['Email'];
    $PhoneNumber = $_POST['PhoneNumber'];
    $NID = $_POST['NID'];
    $TradeLicense = $_POST['TradeLicense'];
    $BIN = $_POST['BIN'];
    $TIN = $_POST['TIN'];
    $DateOfBirth = $_POST['DateOfBirth'];

    $image = addslashes(file_get_contents($_FILES['Image']['tmp_name']));
    $logo = addslashes(file_get_contents($_FILES['Logo']['tmp_name']));

    $stmt = $conn->prepare("INSERT INTO agents 
        (AgentName, ShopName, ShopAddress, Email, PhoneNumber, NID, TradeLicense, BIN, TIN, Image, Logo, DateOfBirth) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssss", $AgentName, $ShopName, $ShopAddress, $Email, $PhoneNumber, $NID, $TradeLicense, $BIN, $TIN, $image, $logo, $DateOfBirth);

    if ($stmt->execute()) {
        $message = "Agent inserted successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Insert Agent</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            /* 
            background-color: #eef1f4;
            display: flex;
            justify-content: center; */

        }

        .container {
              margin-top: 40px;
            width: 100%;
            max-width: 960px;
        }

        h2 {
            margin-top: 30px;
            text-align: center;
            color: #333;
        }

        form {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
        }

        label {
            display: block;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 7px;
            margin-top: 4px;
            border: 1px solid #bbb;
            border-radius: 4px;
        }

        button {
            display: block;
            margin: 0 auto;
            padding: 10px 25px;
            background-color: teal;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            align-items: center;
        }
        .button-container {
    display: flex;
    justify-content: center;
    margin-top: 50px;
     border-radius: 20px;
}

        .message {
            text-align: center;
            margin: 20px 0;
            font-weight: bold;
            color: green;
        }
    </style>
</head>
<body>
<?php include 'nav.php';?>
<div class="container">

    <h2>Insert New Agent</h2>

    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="form-group">
                <label>Agent Name</label>
                <input type="text" name="AgentName" required>
            </div>
            <div class="form-group">
                <label>Shop Name</label>
                <input type="text" name="ShopName">
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Shop Address</label>
                <textarea name="ShopAddress"></textarea>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="Email">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="PhoneNumber">
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>NID</label>
                <input type="text" name="NID">
            </div>
            <div class="form-group">
                <label>Trade License</label>
                <input type="text" name="TradeLicense">
            </div>
            <div class="form-group">
                <label>BIN</label>
                <input type="text" name="BIN">
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>TIN</label>
                <input type="text" name="TIN">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="DateOfBirth">
            </div>
            <div class="form-group">
                <label>Agent Image</label>
                <input type="file" name="Image" accept="image/*">
            </div>
            <div class="form-group">
                <label>Agent Logo</label>
                <input type="file" name="Logo" accept="image/*">
            </div>
        </div>

<div class="button-container">
    <button type="submit">Insert Agent</button>
</div>

    </form>
</div>

</body>
</html>
