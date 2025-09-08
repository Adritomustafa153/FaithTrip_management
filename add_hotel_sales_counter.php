<?php
include 'auth_check.php';
include 'db.php';
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
    <!-- Manual Insert links -->
    <link rel="stylesheet" href="agents_manual_insert.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="manualinsert.js" defer></script>

</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    $("#bookingId").on("input", function () {
        let bookingId = $(this).val().trim();

        if (bookingId.length > 0) {
            $.ajax({
                url: "check_booking_id.php",
                type: "POST",
                data: { bookingId: bookingId },
                dataType: "json",
                success: function (response) {
                    if (response.exists) {
                        $("#bookingIdWarning").show();
                    } else {
                        $("#bookingIdWarning").hide();
                    }
                },
                error: function () {
                    console.error("Error checking Booking ID.");
                }
            });
        } else {
            $("#bookingIdWarning").hide();
        }
    });

    $("#salesForm").on("submit", function (event) {
        if ($("#bookingIdWarning").is(":visible")) {
            event.preventDefault(); // Prevent form submission
            alert("Booking ID already exists. Please enter a unique ID.");
        }
    });
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
<div style="display: flex;justify-content:center;margin-top:15px">
<h1 style="font-family:Arial, Helvetica, sans-serif">Insert Sales</h1>
</div>

<!-- insert part is here -->
<div class="container">
    <h2>Counter Sales Entry</h2>
    <form action="hotel_insert_counter_sell.php" method="POST">

        <!-- <div class="form-row">
            <div class="form-group">
                <label for="agentSearch">Agent Name:</label>
                <input type="text" id="agentSearch" placeholder="Search Agent (Name/ID)">
            </div>
            <div class="form-group">
                <label for="agentDropdown">Select Agent:</label>
                <select name="AgentID" id="agentDropdown" required>
                    <option value="">Select Agent</option>
                </select>
            </div>
        </div> -->

        <!-- Row 2: Passenger Name, Ticket Route, and Ticket Number -->
        <div class="form-row">
            <div class="form-group">
                <label for="PassengerName">Passenger Name:</label>
                <input type="text" name="PassengerName" required>
            </div>
            <div class="form-group">
                <label for="TicketRoute">Hotel Name:</label>
                <input type="text" name="hotelName" required>
            </div>
            <div class="form-group">
                <label for="TicketNumber">Country:</label>
                <input type="text" name="country" required>
            </div>
        </div>
        <!-- Row for airlines selection search box -->
        <div class="form-row">
            <label for="AccountNumber">Hotel Address :</label>
            <input type="text" name="address" required>
                    <!-- <input type="text" id="airlines" name="airlines" autocomplete="on" onkeyup="searchAirlines()">
                    <input type="hidden" id="airline_code" name="airline_code">
                    <div id="suggestions"></div> -->
            <!-- <script>
        function searchAirlines() {
            let input = document.getElementById('airlines').value;
            let suggestionsBox = document.getElementById('suggestions');
            if (input.length < 1) {
                suggestionsBox.innerHTML = "";
                return;
            }

            fetch(`fetch_airlines.php?query=${input}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = "";
                    data.forEach(item => {
                        let div = document.createElement('div');
                        div.classList.add('suggestion-item');
                        div.textContent = `${item.code} - ${item.name}`;
                        div.onclick = () => selectAirline(item);
                        suggestionsBox.appendChild(div);
                    });
                });
        }

        function selectAirline(airline) {
            document.getElementById('airlines').value = airline.name;
            document.getElementById('airline_code').value = airline.code;
            document.getElementById('suggestions').innerHTML = "";
        }
    </script> -->
        </div>

        <!-- Row 3: Issue Date, Flight Date, and Return Date -->
        <div class="form-row">
            <div class="form-group">
                <label for="IssueDate">Issue Date:</label>
                <input type="date" name="IssueDate" required>
            </div>
            <div class="form-group">
                <label for="FlightDate">Check-In Date:</label>
                <input type="date" name="checkindate" required>
            </div>
            <div class="form-group">
                <label for="ReturnDate">Check-Out Date:</label>
                <input type="date" name="checkoutdate">
            </div>
        </div>


        <!-- Row : Room Details Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="PNR">Hotel Category:</label>
                <select name="hotelCategory" id="hotelCategory" required>
                    <option value="1star">1 Star</option>
                    <option value="2star">2 Star</option>
                    <option value="3star">3 Star</option>
                    <option value="4star">4 Star</option>
                    <option value="5star">5 Star</option>
                </select>
            </div>
            <div class="form-group">
                <label for="roomType">Room type:</label>
                <select name="roomType" id="roomType" required>
                    <option value="roomonly">Room Only</option>
                    <option value="breakfast">Breakfast</option>
                    <option value="halhboard">Half Board</option>

                </select>
            </div>
            <div class="form-group">
                <label for="roomCategory">Room Category:</label>
                <input type="text" name="roomCategory" id="netPayment" required>
            </div>
        </div>



        <!-- Row 4: PNR, Bill Amount, and Net Payment -->
        <div class="form-row">
            <div class="form-group">
                <label for="PNR">Booking ID:</label>
                <input type="text" name="bookingId" id="bookingId" required>
                <span id="bookingIdWarning" style="color: red; display: none;">This Booking ID already exists!</span>
            </div>
            <div class="form-group">
                <label for="BillAmount">Bill Amount:</label>
                <input type="number" name="BillAmount" id="billAmount" required>
            </div>
            <div class="form-group">
                <label for="NetPayment">Net Payment:</label>
                <input type="number" name="NetPayment" id="netPayment" required>
            </div>
        </div>

        <!-- Row 5: Profit, Payment Status, and Payment Method -->
        <div class="form-row">
            <div class="form-group">
                <label for="Profit">Profit:</label>
                <input type="text" name="Profit" id="profit" readonly>
            </div>
            <div class="form-group">
                <label for="PaymentStatus">Payment Status:</label>
                <select name="PaymentStatus" id="paymentStatus" required>
                    <option value="Paid">Paid</option>
                    <option value="Partially Paid">Partially Paid</option>
                    <option value="DUE">DUE</option>
                </select>
            </div>
            <div class="form-group">
                <label for="PaymentMethod">Payment Method:</label>
                <select name="PaymentMethod" id="paymentMethod" required>
                    <option value="Cash Payment">Cash Payment</option>
                    <option value="Card Payment">Card Payment</option>
                    <option value="Cheque Deposit">Cheque Deposit</option>
                    <option value="Bank Deposit">Bank Deposit</option>
                    <option value="Cheque Clearing">Cheque Clearing</option>
                    <option value="Mobile Banking(nagad)">Mobile Banking (Nagad)</option>
                </select>
            </div>
        </div>

        <!-- Row 6: Paid Amount, Due Amount, and Salesperson Name -->
        <div class="form-row">
            <div class="form-group">
                <label for="PaidAmount">Paid Amount:</label>
                <input type="number" name="PaidAmount" id="paidAmount" required>
            </div>
            <div class="form-group">
                <label for="DueAmount">Due Amount:</label>
                <input type="text" name="DueAmount" id="dueAmount" readonly>
            </div>
            <div class="form-group">
                <label for="salespersonDropdown">Salesperson Name:</label>
                <select name="SalesPersonName" id="salespersonDropdown" required>
                    <option value="">Select Salesperson</option>
                </select>
            </div>
        </div>

        <!-- Row 7: Bank Details (Hidden by Default) -->
        <div id="bankDetails" class="hidden">
            <div class="form-row">
                <div class="form-group">
                    <label for="BankName">Bank Name:</label>
                    <select name="BankName" id="bankDropdown">
                        <option value="">Select Bank</option>
                    </select>
                </div>
                <!-- <div class="form-group">
                    <label for="BranchName">Branch Name:</label>
                    <input type="text" name="BranchName">
                </div> -->
                <!-- <div class="form-group">
                    <label for="AccountNumber">Account Number:</label>
                    <input type="text" name="AccountNumber">
                </div> -->
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="ReceivedDate">Received Date:</label>
                    <input type="date" name="ReceivedDate">
                </div>
                <div class="form-group">
                    <label for="DepositDate">Deposit Date:</label>
                    <input type="date" name="DepositDate">
                </div>
                <div class="form-group">
                    <label for="ClearingDate">Clearing Date:</label>
                    <input type="date" name="ClearingDate">
                </div>
            </div>
        </div>

        <!-- Row 8: Submit Button -->
        <div class="form-row submit-button-wrapper">   
        <div class="form-row">
            <div class="form-group">
                <button type="submit" class="submit-btn">Submit Sale</button>
            </div>
        </div>
        </div>
    </form>
</div>


 <!-- Insert part Ends here -->



<script type="text/javascript" src="js/mdb.umd.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript"></script>
</body>
</html>

<?php $conn->close(); ?>