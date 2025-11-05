<?php 
require 'db.php';
require 'auth_check.php';
include 'iata_reminder.php';
// include 'iata_payments.php';

// Function to calculate today's flights count
function getTodaysFlightsCount($conn) {
    $today = date('Y-m-d');
    
    $query = "SELECT COUNT(*) as count 
              FROM sales 
              WHERE (
                    (FlightDate = ? AND FlightDate != '0000-00-00') 
                    OR 
                    (ReturnDate = ? AND ReturnDate != '0000-00-00')
              )
              AND Remarks IN ('Air Ticket Sale', 'Sell', 'Reissue')";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $today, $today);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    return $row['count'];
}

// Calculate the notification count
$notificationCount = getTodaysFlightsCount($conn);

   if ($iataReminder['show_reminder']): ?>
<?php endif; ?>
<?php 
// Initialize user image variable
$img_src = 'https://via.placeholder.com/40x40/cccccc/999999?text=USER';

if (isset($_SESSION['UserID'])) {
    $stmt = $conn->prepare("SELECT image FROM user WHERE UserID = ?");
    $stmt->bind_param("i", $_SESSION['UserID']);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_img);
    $stmt->fetch();
    $stmt->close();
    
    // Set the image source without echoing it
    if (!empty($user_img)) {
        $img_src = 'data:image/jpeg;base64,'.base64_encode($user_img);
    }
}

// Calculate total notifications
$totalNotifications = 0;
if ($notificationCount > 0) $totalNotifications += $notificationCount;
if ($iataReminder['show_reminder']) $totalNotifications += 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation</title>
    <!-- MDB icon -->
    <link rel="https://portal.faithtrip.net/companyLogo/JD0aa1748681597.jpg" href="img/mdb-favicon.ico" type="image/x-icon" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <!-- Google Fonts Roboto -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" />
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb.min.css" />
    <style>
        .dropdown-submenu {
            position: relative;
        }
        
        .dropdown-submenu .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -1px;
        }
        
        .dropdown-submenu:hover .dropdown-menu {
            display: block;
        }
        
        .default-avatar {
            background-color: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-weight: bold;
            border-radius: 50%;
        }
    </style>
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
        <img src="logo.jpg" height="30" alt="MDB Logo" loading="lazy" />
      </a>
      <!-- Left links -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">Home</a>
        </li>
        
        <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            Auto Insert
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="agents_auto_insert.php">Agents</a></li>
            <li><a class="dropdown-item" href="corporate_auto_insert.php">Corporate</a></li>
            <li><a class="dropdown-item" href="counter_auto_insert.php">Counter Sell</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            Manual Insert
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="manual_insert.php">Agents</a></li>
            <li><a class="dropdown-item" href="corporate_manual_insert.php">Corporate</a></li>
            <li><a class="dropdown-item" href="counter_sell_manual_insert.php">Counter Sell</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="combined_cart_search.php">Generate Invoice</a>
        </li>
        
        <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            invoice List
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink"> 
             <li><a class="dropdown-item" href="all_invoice.php">All invoices</a></li>
            <!-- <li><a class="dropdown-item" href="invoice_list.php">Sales</a></li>
            <li><a class="dropdown-item" href="reissue.php">Reissue</a></li>
            <li><a class="dropdown-item" href="refund.php">Refund</a></li> -->
          </ul>
        </li>

        <!-- <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            Sales Flow
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="hotel_sales.php">Hotel</a></li>
            <li><a class="dropdown-item" href="">Visa Processing</a></li>
            <li><a class="dropdown-item" href="">Tour Package</a></li>
          </ul>
        </li> -->

          <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            Sales Record
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="summary.php">Sale Summary</a></li>
            <li><a class="dropdown-item" href="invoice_list.php">Ticket Sales</a></li>
            <li><a class="dropdown-item" href="reissue.php">Ticket Reissue</a></li>
            <li><a class="dropdown-item" href="refund.php">Ticket Refund</a></li>
            <li><a class="dropdown-item" href="hotel_sales.php">Hotel</a></li>
            <li><a class="dropdown-item" href="">Visa Processing</a></li>
            <li><a class="dropdown-item" href="">Tour Package</a></li>
          </ul>
        </li>
        
        <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            Accounts
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="#">Summary</a></li>
            <li><a class="dropdown-item" href="payable.php">Payable</a></li>
            
            <li><a class="dropdown-item" href="paid.php">Paid</a></li>
            <li><a class="dropdown-item" href="receiveable.php">Receiveable</a></li>
            <li><a class="dropdown-item" href="received_payments.php">Received Payments</a></li>
             <!-- <li><a class="dropdown-item" href="payment_history.php">Payment History</a></li> -->
            <li><a class="dropdown-item" href="commission.php">Sales Performance</a></li>
            <li class="dropdown-submenu">
              <a class="dropdown-item dropdown-toggle" href="view_expense.php">Expense</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="expense.php">Insert Expense</a></li>
                <li><a class="dropdown-item" href="view_expense.php">View Expense</a></li>
              </ul>
            </li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            Finance & Banking
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="#">Balance Summary</a></li>
            <li><a class="dropdown-item" href="view_banks.php">Bank Accounts</a></li>
            <li><a class="dropdown-item" href="banking_management.php">Balance Inquery</a></li>
            <li><a class="dropdown-item" href="manage_loans.php">Loan Management</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            Human Resource
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="#">Employee Management</a></li>
            <li><a class="dropdown-item" href="#">Leave Management</a></li>
            <li><a class="dropdown-item" href="#">Salary Calculation</a></li>
            <li><a class="dropdown-item" href="#">Attandance Inquery</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a data-mdb-dropdown-init class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
            Add
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="add_passenger.php">Counter</a></li>
            <li><a class="dropdown-item" href="view_corporates.php">Corporate</a></li>
            <li><a class="dropdown-item" href="insert_agent.php">Agents</a></li>
            <li><a class="dropdown-item" href="passenger_list.php">Passenger List</a></li>
            <li><a class="dropdown-item" href="insert_sources.php">Sourcing</a></li>
            <li><a class="dropdown-item" href="add_sales_person.php">Sales Person</a></li>
            <li><a class="dropdown-item" href="users.php">User</a></li>
            <li><a class="dropdown-item" href="view_iata_system.php">IATA System</a></li>
          </ul>
        </li>
      </ul>
      <!-- Left links -->
    </div>
    <!-- Collapsible wrapper -->

    <!-- Right elements -->
    <div class="d-flex align-items-center">
      <!-- Icon -->
      <a class="text-reset me-3" href="invoice_cart2.php">
        <i class="fas fa-shopping-cart"></i>
      </a>

      <!-- Notifications -->
      <div class="dropdown">
        <a class="text-reset me-3 dropdown-toggle hidden-arrow"
           href="#"
           id="notificationsDropdown"
           role="button"
           data-mdb-dropdown-init
           aria-expanded="false">
            <i class="fas fa-bell"></i>
            <?php if ($totalNotifications > 0): ?>
                <span class="badge rounded-pill badge-notification bg-danger">
                    <?php echo $totalNotifications; ?>
                </span>
            <?php endif; ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end"
            aria-labelledby="notificationsDropdown">
            <?php if ($notificationCount > 0): ?>
                <li>
                    <a class="dropdown-item" href="todays_flights.php">
                        <i class="fas fa-plane me-2"></i>
                        <?php echo $notificationCount; ?> flight(s) today
                    </a>
                </li>
            <?php endif; ?>
            
           <?php if ($iataReminder['show_reminder']): ?>
    <li>
        <a class="dropdown-item" href="iata_payments.php">
            <i class="fas fa-money-bill-wave me-2"></i>
            IATA Payment Due: 
            <?php 
            // Safely display the amount with fallback
            $displayAmount = 0;
            if (isset($iataReminder['amount']) && $iataReminder['amount'] > 0) {
                $displayAmount = $iataReminder['amount'];
            } elseif (isset($iataReminder['first_period']) && $iataReminder['first_period'] > 0) {
                $displayAmount = $iataReminder['first_period'];
            } elseif (isset($iataReminder['second_period']) && $iataReminder['second_period'] > 0) {
                $displayAmount = $iataReminder['second_period'];
            }
            
            echo number_format($displayAmount, 2) . ' (' . ($iataReminder['period'] ?? 'Period') . ')'; 
            ?>
        </a>
    </li>
<?php endif; ?>
            
            <?php if ($totalNotifications === 0): ?>
                <li>
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-check-circle me-2"></i>
                        No notifications
                    </a>
                </li>
            <?php endif; ?>
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
            src="<?php echo $img_src; ?>"
            class="rounded-circle"
            height="40"
            width="40"
            alt="User Profile"
            loading="lazy"
            style="object-fit: cover;"
          />
        </a>
        <ul
          class="dropdown-menu dropdown-menu-end"
          aria-labelledby="navbarDropdownMenuAvatar"
        >
          <li>
            <a class="dropdown-item" href="my_profile.php">My profile</a>
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

<!-- MDB -->
<script type="text/javascript" src="js/mdb.umd.min.js"></script>
<!-- Custom scripts -->
<script type="text/javascript">
    // Initialize MDB dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        const dropdowns = document.querySelectorAll('[data-mdb-dropdown-init]');
        dropdowns.forEach(dropdown => {
            new mdb.Dropdown(dropdown);
        });
    });
</script>
</body>
</html>