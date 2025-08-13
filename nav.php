<?php 
require_once 'flight_reminder.php'; 
require_once 'iata_reminder.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
        <li class="nav-item dropdown">
        <a
          data-mdb-dropdown-init
          class="nav-link dropdown-toggle"
          href="#"
          id="navbarDropdownMenuLink"
          role="button"
          aria-expanded="false"
        >
          invoice List
        </a>

        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <li>
            <a class="dropdown-item" href="invoice_list.php">Sales</a>
          </li>
          <li>
            <a class="dropdown-item" href="reissue.php">Reissue</a>
          </li>
                    <li>
            <a class="dropdown-item" href="refund.php">Refund</a>
          </li>
        </ul>
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
          <a class="nav-link" href="summary.php">Sell Summary</a>
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
    Accounts
  </a>

  <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
    <li>
      <a class="dropdown-item" href="#">Summary</a>
    </li>
        <li>
      <a class="dropdown-item" href="payable.php">Payable</a>
    </li>
        <li>
      <a class="dropdown-item" href="receiveable.php">Receiveable</a>
    </li>
            <li>
      <a class="dropdown-item" href="paid.php">Paid</a>
    </li>
    <li>
      <a class="dropdown-item" href="commission.php">Sales Performance</a>
    </li>

    <!-- Nested Dropdown Start -->
    <li class="dropdown-submenu">
      <a class="dropdown-item dropdown-toggle" href="view_expense.php">Expense</a>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="expense.php">Insert Expense</a></li>
        <li><a class="dropdown-item" href="view_expense.php">View Expense</a></li>
      </ul>
    </li>
    <!-- Nested Dropdown End -->

  </ul>
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
            <a class="dropdown-item" href="add_passenger.php">Counter</a>
          </li>
          <li>
            <a class="dropdown-item" href="corporate_insert.php">Corporate</a>
          </li>
                      <li>
            <a class="dropdown-item" href="insert_agent.php">Agents</a>
          </li>
                    <li>
            <a class="dropdown-item" href="passenger_list.php">Passenger List</a>
          </li>
            <li>
            <a class="dropdown-item" href="insert_sources.php">Sourcing</a>
          </li>
                      <li>
            <a class="dropdown-item" href="add_sales_person.php">Sales Person</a>
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
      <a class="text-reset me-3" href="invoice_cart2.php">
        <i class="fas fa-shopping-cart"></i>
      </a>

<!-- Notifications -->
<!-- Notifications -->
<!-- <div class="dropdown">
    <a
        data-mdb-dropdown-init
        class="text-reset me-3 dropdown-toggle hidden-arrow"
        href="#"
        id="navbarDropdownMenuLink"
        role="button"
        aria-expanded="false"
    >
        <i class="fas fa-bell"></i>
        <?php if (isset($notificationCount) && $notificationCount > 0): ?>
            <span class="badge rounded-pill badge-notification bg-danger">
                <?php echo $notificationCount; ?>
            </span>
        <?php endif; ?>
    </a>
    <ul
        class="dropdown-menu dropdown-menu-end"
        aria-labelledby="navbarDropdownMenuLink"
    >
        <?php if (isset($notificationCount) && $notificationCount > 0): ?>
            <li>
                <a class="dropdown-item" href="todays_flights.php">
                    <?php echo $notificationCount; ?> flight(s) today
                </a>
            </li>
        <?php else: ?>
            <li>
                <a class="dropdown-item" href="#">No flights today</a>
            </li>
        <?php endif; ?>
    </ul>
</div> -->


<!-- In your notifications dropdown -->
<div class="dropdown">
    <a class="text-reset me-3 dropdown-toggle hidden-arrow"
       href="#"
       id="notificationsDropdown"
       role="button"
       data-mdb-dropdown-init
       aria-expanded="false">
        <i class="fas fa-bell"></i>
        <?php 
        $totalNotifications = 0;
        if (isset($notificationCount) && $notificationCount > 0) $totalNotifications += $notificationCount;
        if ($iataReminder['show_reminder']) $totalNotifications += 1;
        
        if ($totalNotifications > 0): ?>
            <span class="badge rounded-pill badge-notification bg-danger">
                <?php echo $totalNotifications; ?>
            </span>
        <?php endif; ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-end"
        aria-labelledby="notificationsDropdown">
        <?php if (isset($notificationCount) && $notificationCount > 0): ?>
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
                    if (date('j') >= 10 && date('j') <= 15) {
                        echo number_format($iataReminder['first_period'], 2) . ' ('.$iataReminder['period'].')';
                    } else {
                        echo number_format($iataReminder['second_period'], 2) . ' ('.$iataReminder['period'].')';
                    }
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
    <!-- MDB -->
    <script type="text/javascript" src="js/mdb.umd.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript"></script>
</body>

</html>