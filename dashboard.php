<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "faithtrip_accounts";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Corporate Sales
$sql_corporate = "SELECT SUM(s.BillAmount) AS CorporateSales FROM sales s INNER JOIN companyprofile c ON s.PartyName = c.CompanyName";
$result_corporate = $conn->query($sql_corporate);
$corporate_sales = ($result_corporate->num_rows > 0) ? $result_corporate->fetch_assoc()['CorporateSales'] : 0;

// Fetch Agent Sales - Fixing Query
$sql_agents = "SELECT SUM(s.BillAmount) AS AgentSales FROM sales s INNER JOIN agents a ON s.PartyName = a.AgentName";
$result_agents = $conn->query($sql_agents);
$agent_sales = ($result_agents->num_rows > 0) ? $result_agents->fetch_assoc()['AgentSales'] : 0;

// Fetch Counter Sales
$sql_counter = "SELECT SUM(BillAmount) AS CounterSales FROM sales WHERE PartyName NOT IN (SELECT CompanyName FROM companyprofile UNION SELECT AgentName FROM agents)";
$result_counter = $conn->query($sql_counter);
$counter_sales = ($result_counter->num_rows > 0) ? $result_counter->fetch_assoc()['CounterSales'] : 0;

// $conn->close();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            display: flex;
            /* justify-content: space-around; */
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 20px;
            justify-content: center;
        }
        canvas {
            width: 300px !important;
            height: 300px !important;
        }
    </style>
</head>
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
            <a class="dropdown-item" href="hotel_sales.php">Hote</a>
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
          Add
        </a>

        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <li>
            <a class="dropdown-item" href="add_passenger.php">Add Counter Passenger</a>
          </li>
          <li>
            <a class="dropdown-item" href="#">Add Corporate Passenger</a>
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



<!-- Diagram Part starts here -->

<div class="chart-container">
        <div>
            <h2>Sales Distribution</h2>
            <canvas id="salesDistributionChart"></canvas>
        </div>
    </div>
    
    <script>
        // Sales Distribution (Pie Chart)
        const salesDistributionCtx = document.getElementById('salesDistributionChart').getContext('2d');
        new Chart(salesDistributionCtx, {
            type: 'pie',
            data: {
                labels: ['Corporate Sales', 'Agent Sales', 'Counter Sales'],
                datasets: [{
                    data: [<?php echo $corporate_sales; ?>, <?php echo $agent_sales; ?>, <?php echo $counter_sales; ?>],
                    backgroundColor: ['red', 'blue', 'green']
                }]
            }
        });
    </script>
<!-- Diagram part ends here -->





<script type="text/javascript" src="js/mdb.umd.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript"></script>
</body>
</html>

<?php $conn->close(); ?>
