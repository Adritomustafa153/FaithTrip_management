<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch company names for dropdown
$companyQuery = "SELECT DISTINCT partyName FROM hotel";
$companyResult = $conn->query($companyQuery);

// Fetch sales records
$where = "";
if (isset($_GET['company']) && !empty($_GET['company'])) {
    $company = $conn->real_escape_string($_GET['company']);
    $where .= " WHERE partyName = '$company'";
}
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = $conn->real_escape_string($_GET['invoice']);
    $where .= ($where ? " AND" : " WHERE") . " invoice_number LIKE '%$invoice%'";
}
if (isset($_GET['booking_id']) && !empty($_GET['booking_id'])) {
    $pnr_ = $conn->real_escape_string($_GET['booking_id']);
    $where .= ($where ? " AND" : " WHERE") . " reference_number LIKE '%$pnr_%'";
}

$salesQuery = "SELECT * FROM hotel" . $where;
$salesResult = $conn->query($salesQuery);

// Delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM hotel WHERE id=$id";
    if ($conn->query($deleteQuery) === TRUE) {
        echo "<script>alert('Record deleted successfully!'); window.location='hotel_sales.php';</script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Records</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; border-radius: 20px;border-collapse: collapse;box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);}
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; border-radius: 20px;}
        th { background-color:rgb(74, 113, 255); color: white; border-radius: 5px; }
        .search-container { display: flex; gap: 10px; margin-bottom: 20px; border-radius: 15px;}
        .search-container select, .search-container input { padding: 8px; width: 200px; }
        .btn { padding: 5px 10px; border: none; cursor: pointer; text-decoration: none; font-size: 12px; padding: 4px 8px }
        .edit-btn { background-color:rgb(7, 147, 32); color: white; }
        .delete-btn { background-color: #d9534f; color: white; }
        .btn:hover { opacity: 0.8; }

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

 <!-- Start your project here-->
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-body-tertiary" style="background-color: #e3f2fd";>
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
<!-- Navbar -->


<h2 style="margin-top: 10px; text-align:center">Sales Records</h2>
<div style="display:flex; justify-content: center;">
<!-- Search Form -->
<form method="GET" class="search-container" >
    <select name="company">
        <option value="">Select All</option>
        <?php while ($row = $companyResult->fetch_assoc()) : ?>
            <option value="<?= htmlspecialchars($row['partyName']) ?>" 
                <?= (isset($_GET['company']) && $_GET['company'] == $row['partyName']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['partyName']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <input type="text" name="invoice" placeholder="Search Invoice Number" 
        value="<?= isset($_GET['invoice']) ? htmlspecialchars($_GET['invoice']) : '' ?>">

    <input type="text" name="booking_id" placeholder="Search Booking ID" 
        value="<?= isset($_GET['booking_id']) ? htmlspecialchars($_GET['booking_id']) : '' ?>">
    <button type="submit">Search</button><br>
    <button type="button" onclick="location.href='add_sales_corporate.php'">Add Sales (Corporate)</button>
    <button type="button" onclick="location.href='add_hotel_sales_agents.php'">Add Sales (Agents)</button>
    <button type="button" onclick="location.href='add_hotel_sales_counter.php'">Add Sales (Counter Sales)</button>
</form>

</div>
<!-- Sales Records Table -->
 <div class="result">
<table>
    <tr>
        <th style="font-size: 12px;">Company Name</th>
        <th style="font-size: 12px;">Hotel Name</th>
        <th style="font-size: 12px;">Invoice Number</th>
        <th style="font-size: 12px;">Pessenger Number</th>
        <th style="font-size: 12px;">Check-In Date</th>
        <th style="font-size: 12px;">Check-Out Date</th>
        <th style="font-size: 12px;">Issue Date</th>
        <th style="font-size: 12px;">Day Passes</th>
        <th style="font-size: 12px;">Payment Status</th>
        <th style="font-size: 12px;">Selling Price</th>
        <th style="font-size: 12px;">Sells Person</th>
        <th style="font-size: 12px;">Modify</th>
    </tr>
    <?php while ($row = $salesResult->fetch_assoc()) : 
        $issue_date = new DateTime($row['issue_date']);
        $today = new DateTime();
        $interval = $issue_date->diff($today);
        $day_passes = $interval->days;
        ?>
        <tr>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['partyName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['hotelName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['invoice_number']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['pessengerName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['checkin_date']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['checkout_date']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['issue_date']) ?></td>
            <td style="font-size: 12px;"><?= $day_passes ?> days</td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['payment_status']) ?></td>
            <td style="font-size: 12px;">BDT<?= number_format($row['selling_price'], 2) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['issued_by']) ?></td>
            <td>
    <?php if (isset($row['id'])): ?>
        <a href="edit.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn edit-btn" >
            <i class="fas fa-edit"></i> Edit
        </a><br>
        <a href="hotel_sales.php?delete=<?php echo htmlspecialchars($row['id']); ?>" class="btn delete-btn" 
           onclick="return confirm('Are you sure you want to delete this record?')">
            <i class="fas fa-trash"></i> Delete
            
        </a>
    <?php else: ?>
        <span style="color: red;">Error: No ID Found</span>
    <?php endif; ?>
</td>

        </tr>
    <?php endwhile; ?>
</table>
<script type="text/javascript" src="js/mdb.umd.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript"></script>
</body>
<script src="invoice_list.js"></script>

</html>

<?php $conn->close(); ?>
