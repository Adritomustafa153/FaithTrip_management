<!DOCTYPE html>
<html>
<head>
  <title>Add Passenger</title>


  <style>
    /* body { font-family: Arial; background: #f4f4f4; padding: 20px; }
    form { background: white; padding: 20px; border-radius: 10px; max-width: 400px; margin: auto; }
    input, button { width: 100%; padding: 10px; margin: 10px 0; } */

    body {
      background: linear-gradient(to right, #83a4d4, #b6fbff);
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      /* display: flex;
      align-items: center;
      justify-content: center; */
    }

    .card {
      margin-top: 20px;
      border-radius: 1rem;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }

    .form-control:focus {
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    .form-label {
      font-weight: 500;
    }

    h2 {
      font-weight: 700;
      color: #2c3e50;
    }

    .btn-custom {
      background-color: #2c3e50;
      color: #fff;
    }

    .btn-custom:hover {
      background-color: #1a252f;
    }

  </style>
</head>
<body>

<?php include 'nav.php' ?>
  <!-- <h2>Add Passenger</h2>
  <form method="POST" action="add_passenger.php">
    <input type="text" name="pname" placeholder="Passenger Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="date" name="dob" placeholder="Date of Birth" required>
    <input type="text" name="passport_number" placeholder="Passport Number" required>
    <input type="date" name="passport_expiry" required>
    <button type="submit">Save</button>
  </form> -->


    <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card p-4">
          <h2 class="text-center mb-4">Add Passenger Details</h2>
          <form action="add_passenger.php" method="POST">
            
            <div class="mb-3">
              <label for="name" class="form-label">Passenger Name</label>
              <input type="text" class="form-control" id="name" name="pname" required>
            </div>

            <div class="mb-3">
              <label for="dob" class="form-label">Date of Birth</label>
              <input type="date" class="form-control" id="dob" name="dob" required>
            </div>

            <div class="mb-3">
              <label for="passport" class="form-label">Passport Number</label>
              <input type="text" class="form-control" id="passport" name="passport" required>
            </div>

            <div class="mb-3">
              <label for="expiry" class="form-label">Passport Expiry Date</label>
              <input type="date" class="form-control" id="expiry" name="expiry" required>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="email" name="email">
            </div>

            <div class="d-flex justify-content-between mt-4">
              <button type="submit" class="btn btn-custom px-4">Submit</button>
              <a href="edit_passenger.php" class="btn btn-outline-primary">Edit Passenger</a>
              <button type="reset" class="btn btn-outline-secondary px-4">Reset</button>
            </div>



          </form>
        </div>
      </div>
    </div>
  </div>


</body>
</html>


<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    // Use null coalescing operator to avoid notices (if needed)
    $name = $_POST['pname'] ?? '';
    $email = $_POST['email'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $passport = $_POST['passport'] ?? '';
    $expiry = $_POST['expiry'] ?? '';

    $stmt = $conn->prepare("INSERT INTO passengers (name, PassengerEmail, date_of_birth, passport_number, passport_expiry) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $dob, $passport, $expiry);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "Passenger added successfully. <a href='add_passenger.php'>Go Back</a>";
}
?>
