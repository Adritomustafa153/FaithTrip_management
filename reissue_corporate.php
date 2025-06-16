<?php
include 'db.php';
$sale_id = $_GET['sale_id'] ?? null;

if (!$sale_id) {
    echo "Sale ID not provided.";
    exit;
}

// Fetch sale details from database
$query = "SELECT * FROM sales WHERE SaleID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Sale record not found.";
    exit;
}


$row = $result->fetch_assoc();
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

        input[readonly], select[readonly] {
    background-color: #e0e0e0;
    color: #333;
    /* font-weight: bold; */
}


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
    <h2>Corporate Reissue Form</h2>
    <form action="reissue_corporate_sell.php" method="POST">
                <input type="hidden" name="sale_id" value="<? $row['SaleID '] ?>" >
        <!-- Row 1: Agent Name, Search, and Select Agent -->
        <div class="form-row">
            <div class="form-group">
                <label for="companyDropdown">Select Company:</label>
                <input type="text" name="partyname" value="<?= htmlspecialchars($row['PartyName']) ?>" readonly>

                    <!-- <option value="">Select Compa</option> -->
                </select>
            </div>
            <div class="form-group">
                    <label for="AccountNumber">Airlines Name</label>
                    <input type="text" name="airlines" value="<?= htmlspecialchars($row['airlines']) ?>" readonly>

            </div>
        </div>

        <!-- Row 2: Passenger Name, Ticket Route, and Ticket Number -->
        <div class="form-row">
            <div class="form-group">
                <label for="PassengerName">Passenger Name:</label>
                <input type="text" name="passengername" value="<?= htmlspecialchars($row['PassengerName']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="TicketRoute">Ticket Route:</label>
        <input type="text" name="ticket_route" value="<?= htmlspecialchars($row['TicketRoute']) ?>" readonly>
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
        <input type="date" name="FlightDate" value="<?= htmlspecialchars($row['FlightDate']) ?>">
            </div>
            <div class="form-group">
                <label for="ReturnDate">Return Date:</label>
        <input type="date" name="ReturnDate" value="<?= htmlspecialchars($row['ReturnDate']) ?>">
            </div>
        </div>

        <!-- Row 4: PNR, Bill Amount, and Net Payment -->
        <div class="form-row">
            <div class="form-group">
                <label for="PNR">PNR:</label>
        <input type="text" name="PNR" value="<?= htmlspecialchars($row['PNR']) ?>" readonly>
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
                <input type="text" name="DueAmount" id="dueAmount" >
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
                <button type="submit" class="submit-btn">Reissue</button>
            </div>
        </div>
        </div>
    </form>
</div>


 <!-- Insert part Ends here -->
</body>
</html>

<?php $conn->close(); ?>
