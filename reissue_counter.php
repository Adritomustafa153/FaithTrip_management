<?php
// reissue_counter_sell.php

include 'db_connection.php'; // Adjust if your DB connection file has a different name

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
<html>
<head>
    <title>Reissue Counter Sell</title>
    <style>
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[readonly] { background-color: #f0f0f0; }        body { font-family: Arial, sans-serif; margin: 20px; }
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
    <script src="manualinsert.js" defer></script>
    

    
</head>
<?php include 'nav.php' ?>
<body>
    <div style="display: flex;justify-content:center;margin-top:30px">
           <h2> Reissue Counter Sell</h2>
    </div>
    <div class="container" >
 
    <form action="process_reissue_counter.php" method="post">
        <input type="hidden" name="sale_id" value="<? $row['SaleID '] ?>" >

<div class="form-row">
    <div class="form-group">
        <label>Passenger Name:</label>
        <input type="text" name="passenger_name" value="<?= htmlspecialchars($row['PassengerName']) ?>" readonly>
    </div>
       <div class="form-group">
         <label>Ticket Route:</label>
        <input type="text" name="ticket_route" value="<?= htmlspecialchars($row['TicketRoute']) ?>" readonly>
       </div>
                   <div class="form-group">
        <label>Issue Date:</label>
        <input type="date" name="issueDate"  id="issueDate">
            </div>
       
        <!-- <div class="form-group">
                    <label>Ticket Number:</label>
        <input type="text" name="ticket_number" value="<?= htmlspecialchars($row['TicketNumber']) ?>" readonly>
        </div> -->
</div>
        

               <div class="form-row">
            <div class="form-group">
        <label>Airlines Name:</label>
        <input type="text" name="airlines" value="<?= htmlspecialchars($row['airlines']) ?>" readonly>
            </div>
            <div class="form-group">
        <label>PNR:</label>
        <input type="text" name="pnr" value="<?= htmlspecialchars($row['PNR']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="TicketNumber">Ticket Number:</label>
                <input type="text" name="TicketNumber" required>
            </div>
        </div>

                <div class="form-row">
            <div class="form-group">
        <label>Journey Date:</label>
        <input type="date" name="journey_date" value="<?= htmlspecialchars($row['FlightDate']) ?>">
            </div>
            <div class="form-group">
        <label>Return Date:</label>
        <input type="date" name="return_date" value="<?= htmlspecialchars($row['ReturnDate']) ?>">
            </div>
            <div class="form-group">
        <label>Bill Amount:</label>
        <input type="number" name="BillAmount" id="billAmount" value="<?= htmlspecialchars($row['BillAmount']) ?>" step="0.01">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
        <label>Net Payment:</label>
        <input type="number" name="NetPayment"  id="NetPayment" value="<?= htmlspecialchars($row['NetPayment']) ?>" step="0.01">
            </div>

            <div class="form-group">
                <label for="Profit">Profit:</label>
                
               <input type="text" name="Profit" id="Profit" readonly><br>
            </div>
                        <div class="form-group">
        <label>Sales Person:</label>
        <input type="text" name="sales_person" value="<?= htmlspecialchars($row['SalesPersonName']) ?>" readonly>
            </div>
        </div>
        <div class="form-row">
                    <div class="form-group">
                <label for="PaymentStatus">Payment Status:</label>
                <select name="PaymentStatus" id="paymentStatus" required>
                    <option value="Paid">Paid</option>
                    <option value="Partially Paid">Partially Paid</option>
                    <option value="DUE">DUE</option>
                </select>


                
                </div>
                <div>
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
                </div>
                <div class="form-group">
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
            <!-- <div class="form-group">
                <label for="salespersonDropdown">Salesperson Name:</label>
                <select name="SalesPersonName" id="salespersonDropdown" required>
                    <option value="">Select Salesperson</option>
                </select>
            </div> -->

        </div>
        
        <div class="form-row submit-button-wrapper">   
        <div class="form-row">
            <div class="form-group">
                <button type="submit" class="submit-btn">Reissue</button>
            </div>
        </div>
        </div>

        <script>
    // Function to calculate profit
    function calculateProfit() {
        let billAmount = parseFloat(document.getElementById('billAmount').value) || 0;
        let netPayment = parseFloat(document.getElementById('NetPayment').value) || 0;
        let profit = billAmount - netPayment;
        document.getElementById('Profit').value = profit.toFixed(2);
    }


    // Attach listeners
    document.getElementById('billAmount').addEventListener('input', calculateProfit);
    document.getElementById('NetPayment').addEventListener('input', calculateProfit);


    
</script>

    </form>
    </div>
</body>
</html>
