<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch company names for dropdown
$companyQuery = "SELECT DISTINCT PartyName FROM sales";
$companyResult = $conn->query($companyQuery);

// Fetch sales records
$where = "";
if (isset($_GET['company']) && !empty($_GET['company'])) {
    $company = $conn->real_escape_string($_GET['company']);
    $where .= " WHERE PartyName = '$company'";
}
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = $conn->real_escape_string($_GET['invoice']);
    $where .= ($where ? " AND" : " WHERE") . " invoice_number LIKE '%$invoice%'";
}
if (isset($_GET['pnr']) && !empty($_GET['pnr'])) {
    $pnr_ = $conn->real_escape_string($_GET['pnr']);
    $where .= ($where ? " AND" : " WHERE") . " PNR LIKE '%$pnr_%'";
}

$salesQuery = "SELECT * FROM sales" . $where;
$salesResult = $conn->query($salesQuery);

// Delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM sales WHERE SaleID=$id";
    if ($conn->query($deleteQuery) === TRUE) {
        echo "<script>alert('Record deleted successfully!'); window.location='invoice_list.php';</script>";
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
    <!-- <style>
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
        .hr-lines:before{
         content:" ";
         display: block;
         height: 2px;
         width: 100%;
         position: absolute;
         top: 50%;
         left: 0;
         background: grey;
}

    </style> -->
    <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 20px; 
        border-radius: 20px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    }
    th, td { 
        padding: 10px; 
        border-radius: 5px;
        text-align: left; 
    }
    th { 
        background-color: rgb(74, 113, 255); 
        color: white; 
    }
    .search-container { 
        display: flex; 
        gap: 10px; 
        margin-bottom: 20px; 
        border-radius: 15px;
    }
    .search-container select, .search-container input { 
        padding: 8px; 
        width: 200px; 
    }
    .btn { 
        padding: 5px 10px; 
        border: none; 
        cursor: pointer; 
        text-decoration: none; 
        font-size: 12px; 
        padding: 4px 8px 
    }
    .edit-btn { background-color: rgb(7, 147, 32); color: white; }
    .delete-btn { background-color: #d9534f; color: white; }
    .btn:hover { opacity: 0.8; }

    /* Alternating row colors */
    tr:nth-child(odd) {
        background-color:rgb(238, 241, 255); /* Light grey */
    }
    tr:nth-child(even) {
        background-color: #ffffff; /* White */
    }

    /* Soft line separator between rows */
    tr {
        border-bottom: 1px solid #ddd;
    }

    tr:last-child {
        border-bottom: none;
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
<script>
  document.addEventListener("DOMContentLoaded", function () {
      let cart = JSON.parse(localStorage.getItem("cart")) || [];

      function updateCartCount() {
          document.getElementById("cart-count").textContent = cart.length;
      }

      function addToCart(item) {
          cart.push(item);
          localStorage.setItem("cart", JSON.stringify(cart));
          updateCartCount();
      }

      document.querySelectorAll(".add-to-cart").forEach(button => {
          button.addEventListener("click", function () {
              let passenger = {
                  name: this.dataset.name,
                  price: this.dataset.price,
                  route: this.dataset.route,
                  journey: this.dataset.journey,
                  return: this.dataset.return,
                  pnr: this.dataset.pnr,
                  ticket: this.dataset.ticket,
                  paid: this.dataset.paid,
                  due: this.dataset.due
              };
              addToCart(passenger);
              alert("Added to Cart!");
          });
      });

      updateCartCount();
  });
</script>

<body>

 <!-- Start your project here-->
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
            <a class="dropdown-item" href="">Visa</a>
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
      </ul>
      <!-- Left links -->
    </div>
    <!-- Collapsible wrapper -->

    <!-- Right elements -->
    <div class="d-flex align-items-center">
      <!-- Icon -->
      <a class="text-reset me-3" href="cart.php">
  <i class="fas fa-shopping-cart"></i>
  <span id="cart-count" class="badge bg-danger">0</span>
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
            <option value="<?= htmlspecialchars($row['PartyName']) ?>" 
                <?= (isset($_GET['company']) && $_GET['company'] == $row['PartyName']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['PartyName']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <input type="text" name="invoice" placeholder="Search Invoice Number" 
        value="<?= isset($_GET['invoice']) ? htmlspecialchars($_GET['invoice']) : '' ?>">

    <input type="text" name="pnr" placeholder="Search PNR" 
        value="<?= isset($_GET['PNR']) ? htmlspecialchars($_GET['PNR']) : '' ?>">
    <button type="submit">Search</button>
</form>
</div>
<!-- Sales Records Table -->
 <div class="result">
<table>
    <tr>
        <th style="font-size: 12px;">Company Name</th>
        <th style="font-size: 12px;">Pessenger Name</th>
        <th style="font-size: 12px;">Invoice Number</th>
        <th style="font-size: 12px;">Route</th>
        <th style="font-size: 12px;">PNR</th>
        <th style="font-size: 12px;">Ticket Number</th>
        <th style="font-size: 12px;">Issue Date</th>
        <th style="font-size: 12px;">Day Passes</th>
        <th style="font-size: 12px;">Payment Status</th>
        <th style="font-size: 12px;">Selling Price</th>
        <th style="font-size: 12px;">Sells Person</th>
        <th style="font-size: 12px;">Modify</th>
    </tr>
    <?php while ($row = $salesResult->fetch_assoc()) : 
        $issue_date = new DateTime($row['IssueDate']);
        $today = new DateTime();
        $interval = $issue_date->diff($today);
        $day_passes = $interval->days;
        $deperture_date = new DateTime($row['FlightDate']);
        $return_date = new DateTime($row['ReturnDate']);
        $paidAmount = (float) $row['PaidAmount']; // Ensure it's a float
        // $paid = number_format($paidAmount, 2, '.', ''); // Format as decimal(10,2)
        // $due = number_format($row['BillAmount'], 2) - $paid;
        $paid = isset($row['PaidAmount']) ? (float) $row['PaidAmount'] : 0.00; // Convert safely to float
        $due = number_format($paid, 2, '.', ''); // Format as decimal(10,2)
        ?>
        <tr>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['PartyName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['PassengerName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['invoice_number']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['TicketRoute']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['PNR']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['TicketNumber']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['IssueDate']) ?></td>
            <td style="font-size: 12px;"><?= $day_passes ?> days</td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['PaymentStatus']) ?></td>
            <td style="font-size: 12px;">BDT<?= number_format($row['BillAmount'], 2) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['SalesPersonName']) ?></td>
            <td>
    <?php if (isset($row['SaleID'])): ?>
        <a href="edit.php?id=<?php echo htmlspecialchars($row['SaleID']); ?>" class="btn edit-btn" >
            <i class="fas fa-edit"></i> Edit
        </a><br>
        <a href="invoice_list.php?delete=<?php echo htmlspecialchars($row['SaleID']); ?>" class="btn delete-btn" 
           onclick="return confirm('Are you sure you want to delete this record?')">
            <i class="fas fa-trash"></i> Delete
            
        </a>
        <button class="add-to-cart" 
        data-name="<?php $row['PassengerName'] ?>" 
        data-price="<?php $row['BillAmount'] ?>"
        data-route="<?php $row['TicketRoute'] ?>"
        data-journey="<?php $row['FlightDate'] ?>"
        data-return="<?php $row['ReturnDate'] ?>"
        data-pnr="<?php $row['PNR'] ?>"
        data-ticket="<?php $row['TicketNumber'] ?>"
        data-paid="<?php echo $paid ?>"
        data-due="<?php echo $due ?>">
  Add to Cart
</button>
<!-- 
        <a href="cart.php?id=<?php echo htmlspecialchars($row['SaleID']); ?>" class="btn edit-btn" >
        <i class="fas fa-cart-plus"> Invoice</i>        
        </a> -->
        <!-- <a href="cart.php">
        <button onclick="addToCart(<?php echo $product_id; ?>, '<?php echo $product_name; ?>', <?php echo $price; ?>)">
    Add to Invoice
        </button>

        </a> -->

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
