<?php
include 'db.php';
include 'auth_check.php';

$sql = "SELECT * FROM passengers ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Passenger List</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to right, #bdc3c7, #2c3e50);
      min-height: 100vh;
      /* padding: 2rem; */
      color: #fff;
    }
    .container h1 {
      text-align: center;
      margin-bottom: 2rem;
      margin-top: 10px;
      font-weight: 700;
      color: #f1f1f1;
    }
    .card {
      border: none;
      margin-bottom: 15px;
      border-radius: 10px;
      transition: transform 0.2s ease;
    }
    .card:hover {
      transform: scale(1.01);
    }
    .bg-dark-row {
      background-color: rgba(33, 37, 41, 0.8);
    }
    .bg-light-row {
      background-color: rgba(255, 255, 255, 0.1);
    }
    .edit-btn {
      background-color: #3498db;
      color: #fff;
      font-weight: 500;
    }
    .edit-btn:hover {
      background-color: #2980b9;
    }
    .row-label {
      font-weight: 600;
    }
  </style>

   <!-- MDB icon -->
 <link rel="icon" href="https://portal.faithtrip.net/companyLogo/JD0aa1748681597.jpg" type="image/x-icon" />
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
    <h1>Passenger List</h1>

    <?php
    $i = 0;
    if ($result->num_rows > 0):
      while ($row = $result->fetch_assoc()):
        $bgClass = $i % 2 == 0 ? 'bg-gray-row' : 'bg-light-row';
    ?>
      <div class="card <?= $bgClass ?>">
        <div class="card-body row">
          <div class="col-md-2"><span class="row-label">Name:</span> <?= htmlspecialchars($row['name']) ?></div>
          <div class="col-md-2"><span class="row-label">DOB:</span> <?= $row['date_of_birth'] ?></div>
          <div class="col-md-2"><span class="row-label">Passport:</span> <?= htmlspecialchars($row['passport_number']) ?></div>
          <div class="col-md-2"><span class="row-label">Expiry:</span> <?= $row['passport_expiry'] ?></div>
          <div class="col-md-2"><span class="row-label">Email:</span> <?= htmlspecialchars($row['PassengerEmail']) ?></div>
          <div class="col-md-2 text-end">
            <a href="edit_passenger.php?id=<?= $row['id'] ?>" class="btn edit-btn btn-sm">Edit</a>
          </div>
        </div>
      </div>
    <?php
        $i++;
      endwhile;
    else:
      echo '<div class="alert alert-warning text-center">No passengers found.</div>';
    endif;

    $conn->close();
    ?>
  </div>

</body>

</html>
