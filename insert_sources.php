<?php
include 'db.php'; // database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $agency_name = $_POST['agency_name'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $contact_person = $_POST['contact_person'];
    $phone_number = $_POST['phone_number'];
    $iata_number = $_POST['iata_number'];

    $sql = "INSERT INTO sources (agency_name, address, email, contact_person, phone_number, iata_number) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $agency_name, $address, $email, $contact_person, $phone_number, $iata_number);

    if ($stmt->execute()) {
        echo "<script>alert('Source added successfully.'); window.location.href='insert_sources.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insert Source</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f9f9f9;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        form {
            width: 90%;
            max-width: 800px;
            margin: auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 10px 8px;
        }

        label {
            font-weight: bold;
            display: inline-block;
            min-width: 140px;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"] {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background: #218838;
        }

        .center {
            text-align: center;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'nav.php' ?>
<div class="container" style="margin-top: 35px; margin-bottom: 20px;">
    <h2 >Add New Source</h2>

<form method="POST" action="">
    <table>
        <tr>
            <td><label for="agency_name">Agency Name*</label></td>
            <td><input type="text" name="agency_name" id="agency_name" required></td>
        </tr>
        <tr>
            <td><label for="address">Address</label></td>
            <td><textarea name="address" id="address" rows="2"></textarea></td>
        </tr>
        <tr>
            <td><label for="email">Email</label></td>
            <td><input type="email" name="email" id="email"></td>
        </tr>
        <tr>
            <td><label for="contact_person">Contact Person</label></td>
            <td><input type="text" name="contact_person" id="contact_person"></td>
        </tr>
        <tr>
            <td><label for="phone_number">Phone Number</label></td>
            <td><input type="text" name="phone_number" id="phone_number"></td>
        </tr>
        <tr>
            <td><label for="iata_number">IATA Number</label></td>
            <td><input type="text" name="iata_number" id="iata_number"></td>
        </tr>
    </table>

    <div class="center">
        <input type="submit" value="Add Source">
    </div>
</form>
</div>


</body>
</html>
