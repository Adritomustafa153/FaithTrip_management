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
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-body-tertiary">
  <!-- Container wrapper -->
  <div class="container-fluid">
    <!-- Toggle button -->
    <button
      data-mdb-collapse-init
      class="navbar-toggler"
      type="button"
      data-mdb-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
      <i class="fas fa-bars"></i>
    </button>

    <!-- Collapsible wrapper -->
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <!-- Navbar brand -->
      <a class="navbar-brand mt-2 mt-lg-0" href="#">
        <img
          src="logo.jpg"
          height="30"
          alt="MDB Logo"
          loading="lazy"
        />
      </a>
      <!-- Left links -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">Home</a>
        </li>
        <a
          data-mdb-dropdown-init
          class="nav-link dropdown-toggle"
          href="#"
          id="navbarDropdownMenuLink"
          role="button"
          aria-expanded="false"
        >
          Auto Insert
        </a>
        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <li>
            <a class="dropdown-item" href="agents_auto_insert.php">Agents</a>
          </li>
          <li>
            <a class="dropdown-item" href="corporate_auto_insert.php">Corporate</a>
          </li>
          <li>
            <a class="dropdown-item" href="counter_auto_insert.php">Counter Sell</a>
          </li>
        </ul>


        <li class="nav-item dropdown">
        <a
          data-mdb-dropdown-init
          class="nav-link dropdown-toggle"
          href="#"
          id="navbarDropdownMenuLink"
          role="button"
          aria-expanded="false"
        >
          Manual Insert
        </a>

        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <li>
            <a class="dropdown-item" href="manual_insert.php">Agents</a>
          </li>
          <li>
            <a class="dropdown-item" href="corporate_manual_insert.php">Corporate</a>
          </li>
          <li>
            <a class="dropdown-item" href="counter_sell_manual_insert.php">Counter Sell</a>
          </li>
        </ul>
      </li>


        <li class="nav-item">
          <a class="nav-link" href="#">Generate Invoice</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="invoice_list.php">Invoice List</a>
        </li>



        <li class="nav-item dropdown">
        <a
          data-mdb-dropdown-init
          class="nav-link dropdown-toggle"
          href="#"
          id="navbarDropdownMenuLink"
          role="button"
          aria-expanded="false"
        >
          Sales Flow
        </a>
        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <li>
            <a class="dropdown-item" href="hotel_sales.php">Hotel</a>
          </li>
          <li>
            <a class="dropdown-item" href="">Visa Processing</a>
          </li>
          <li>
            <a class="dropdown-item" href="">Tour Package</a>
          </li>
        </ul>
      </li>


        <li class="nav-item">
          <a class="nav-link" href="#">Sell Summary</a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="#">Due Bills</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="">Accounts</a>
        </li>

                <li class="nav-item dropdown">
        <a
          data-mdb-dropdown-init
          class="nav-link dropdown-toggle"
          href="#"
          id="navbarDropdownMenuLink"
          role="button"
          aria-expanded="false"
        >
          Add Passengers
        </a>

        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <li>
            <a class="dropdown-item" href="add_passenger.php">Counter</a>
          </li>
          <li>
            <a class="dropdown-item" href="#">Corporate</a>
          </li>
                    <li>
            <a class="dropdown-item" href="passenger_list.php">Passenger List</a>
          </li>
        </ul>
      </li>

      </ul>
      <!-- Left links -->
    </div>
    <!-- Collapsible wrapper -->

    <!-- Right elements -->
    <div class="d-flex align-items-center">
      <!-- Icon -->
      <a class="text-reset me-3" href="#">
        <i class="fas fa-shopping-cart"></i>
      </a>

      <!-- Notifications -->
      <div class="dropdown">
        <a
          data-mdb-dropdown-init
          class="text-reset me-3 dropdown-toggle hidden-arrow"
          href="#"
          id="navbarDropdownMenuLink"
          role="button"
          aria-expanded="false"
        >
          <i class="fas fa-bell"></i>
          <span class="badge rounded-pill badge-notification bg-danger">1</span>
        </a>
        <ul
          class="dropdown-menu dropdown-menu-end"
          aria-labelledby="navbarDropdownMenuLink"
        >
          <li>
            <a class="dropdown-item" href="#">Some news</a>
          </li>
          <li>
            <a class="dropdown-item" href="#">Another news</a>
          </li>
          <li>
            <a class="dropdown-item" href="#">Something else here</a>
          </li>
        </ul>
      </div>
      <!-- Avatar -->
      <div class="dropdown">
        <a
          data-mdb-dropdown-init
          class="dropdown-toggle d-flex align-items-center hidden-arrow"
          href="#"
          id="navbarDropdownMenuAvatar"
          role="button"
          aria-expanded="false"
        >
          <img
            src="https://mdbcdn.b-cdn.net/img/new/avatars/2.webp"
            class="rounded-circle"
            height="25"
            alt="Black and White Portrait of a Man"
            loading="lazy"
          />
        </a>
        <ul
          class="dropdown-menu dropdown-menu-end"
          aria-labelledby="navbarDropdownMenuAvatar"
        >
          <li>
            <a class="dropdown-item" href="#">My profile</a>
          </li>
          <li>
            <a class="dropdown-item" href="#">Settings</a>
          </li>
          <li>
            <a class="dropdown-item" href="logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
    <!-- Right elements -->
  </div>
  <!-- Container wrapper -->
</nav>
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

  
<script type="text/javascript" src="js/mdb.umd.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript"></script>
  <!-- Bootstrap JS CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
