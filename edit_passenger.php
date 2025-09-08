<?php
include 'auth_check.php';
include 'db.php';

if (!isset($_GET['id'])) {
  die("No ID provided.");
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM passengers WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
  die("Passenger not found.");
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Passenger</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #83a4d4, #b6fbff);
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      /* display: flex;
      align-items: center;
      justify-content: center; */
    }
    .card { border-radius: 1rem; box-shadow: 0 8px 16px rgba(0,0,0,0.15); margin-top: 50px; }
    .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
    .form-label { font-weight: 500; }
    h2 { font-weight: 700; color: #2c3e50; }
    .btn-custom { background-color:rgb(155, 155, 156); color: #fff; }
    .btn-custom:hover { background-color:rgb(100, 100, 100); }
  </style>
   <!-- MDB icon -->
 <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <!-- Google Fonts Roboto -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
    />
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card p-4">
        <h2 class="text-center mb-4">Edit Passenger Information</h2>
        <form action="update_passenger.php" method="POST">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">

          <div class="mb-3">
            <label class="form-label">Passenger Name</label>
            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control" name="date_of_birth" value="<?= $row['date_of_birth'] ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Passport Number</label>
            <input type="text" class="form-control" name="passport" value="<?= htmlspecialchars($row['passport_number']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Passport Expiry Date</label>
            <input type="date" class="form-control" name="expiry" value="<?= $row['passport_expiry'] ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['PassengerEmail']) ?>">
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button type="submit" class="btn btn-custom px-4">Update</button>
            <a href="add_passenger.php" class="btn btn-outline-secondary px-4">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> -->

<!-- <script type="text/javascript" src="js/mdb.umd.min.js"></script> -->
    <!-- Custom scripts -->
    <!-- <script type="text/javascript"></script> -->
</body>
</html>
