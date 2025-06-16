<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
    <link rel="stylesheet" href="agents_manual_insert.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="companySearch.js" defer></script>

</head>
<body>

 <!-- Start your project here-->
<!-- Navbar -->
<?php include 'nav.php';  ?>
<!-- Navbar -->
<div style="display: flex;justify-content:center;margin-top:15px">
<h1 style="font-family:Arial, Helvetica, sans-serif">Insert Sales</h1>
</div>

<!-- insert part is here -->
<div class="container">
    <h2>Corporate Sales Entry Form</h2>
    <form action="manual_insert_corporate_sell.php" method="POST">
        <!-- Row 1: Agent Name, Search, and Select Agent -->
        <div class="form-row">
            <div class="form-group">
                <label for="companysearch">Company Search:</label>
                <input type="text" id="companysearch" placeholder="Search By Company Name">
            </div>
            <div class="form-group">
                <label for="companyDropdown">Select Company:</label>
                <select name="CompanyID" id="companyDropdown" required>
                    <!-- <option value="">Select Compa</option> -->
                </select>
            </div>
            <div class="form-group">
                    <label for="AccountNumber">Airlines Name</label>
                    <input type="text" id="airlines" name="airlines" autocomplete="off" onkeyup="searchAirlines()">
                    <input type="hidden" id="airline_code" name="airline_code">
                    <input type="hidden" id="airline_logo_url" name="airline_logo_url">
                    <div id="suggestions"></div>
                    <!-- <button type="submit">Submit</button> -->
            </div>
            <script>
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
            document.getElementById('airline_logo').src = airline.logo;
            document.getElementById('suggestions').innerHTML = "";
        }
    </script>
        </div>

        <!-- Row 2: Passenger Name, Ticket Route, and Ticket Number -->
        <div class="form-row">
            <div class="form-group">
                <label for="PassengerName">Passenger Name:</label>
                <input type="text" name="PassengerName" required>
            </div>
            <div class="form-group">
                <label for="TicketRoute">Ticket Route:</label>
                <input type="text" name="TicketRoute" required>
            </div>
            <div class="form-group">
                <label for="TicketNumber">Ticket Number:</label>
                <input type="text" name="TicketNumber" required>
            </div>
        </div>

        <!-- Row 3: Issue Date, Flight Date, and Return Date -->
        <div class="form-row">
            <div class="form-group">
                <label for="IssueDate">Issue Date:</label>
                <input type="date" name="IssueDate" required>
            </div>
            <div class="form-group">
                <label for="FlightDate">Flight Date:</label>
                <input type="date" name="FlightDate" required>
            </div>
            <div class="form-group">
                <label for="ReturnDate">Return Date:</label>
                <input type="date" name="ReturnDate">
            </div>
        </div>

        <!-- Row 4: PNR, Bill Amount, and Net Payment -->
        <div class="form-row">
            <div class="form-group">
                <label for="PNR">PNR:</label>
                <input type="text" name="PNR" required>
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
                                    <div class="form-group">
                <label for="PaymentMethod">Seat Class:</label>
                <select name="Class" id="seat" required>
                    <option value="Economy">Economy Class</option>
                    <option value="Business">Business Class</option>
                    <option value="First">First Class</option>
                    <option value="Premium">Premium Economy</option>
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
</body>
</html>

<?php $conn->close(); ?>
