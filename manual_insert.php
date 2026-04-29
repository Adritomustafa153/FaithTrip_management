<?php
include 'auth_check.php';
include 'db.php';
$sources_query = "SELECT agency_name FROM sources";
$sources_result = mysqli_query($conn, $sources_query);

// Fetch systems for the dropdown
$systems_query = "SELECT system FROM iata_systems";
$systems_result = mysqli_query($conn, $systems_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="logo.jpg">
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
        select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.7;
        }
    </style>

    <link rel="stylesheet" href="agents_manual_insert.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="manualinsert.js" defer></script>

</head>
<body>

<!-- Start your project here-->
<?php include 'nav.php' ?>
<div style="display: flex;justify-content:center;margin-top:15px">
<h1 style="font-family:Arial, Helvetica, sans-serif">Insert Sales</h1>
</div>

<!-- insert part is here -->
<div class="container">
    <h2>Sales Entry Form</h2>
    <form action="insert_sales.php" method="POST">
        <!-- Row 1: Agent Name, Search, and Select Agent -->
        <div class="form-row">
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
            <div class="form-group">
                <label for="AccountNumber">Airlines Name</label>
                <input type="text" id="airlines" name="airlines" autocomplete="off" onkeyup="searchAirlines()">
                <input type="hidden" id="airline_code" name="airline_code">
                <div id="suggestions"></div>
            </div>
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
            <div class="form-group">
                <label for="source_id">Source (Agency Name)</label>
                <select name="source_id" id="source_id" class="form-control" required>
                    <option value="">Select Source</option>
                    <?php 
                    // Reset pointer and loop through sources again
                    mysqli_data_seek($sources_result, 0);
                    while($row = mysqli_fetch_assoc($sources_result)): ?>
                        <option value="<?= $row['agency_name']; ?>"><?= htmlspecialchars($row['agency_name']); ?></option>
                    <?php endwhile; ?>
                </select>
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
            <div class="form-group">
                <label for="system">System:</label>
                <select name="system" id="system" disabled required>
                    <option value="">Select System</option>
                    <?php while($system = mysqli_fetch_assoc($systems_result)): ?>
                        <option value="<?= $system['system']; ?>"><?= htmlspecialchars($system['system']); ?></option>
                    <?php endwhile; ?>
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
                <div class="form-group">
                    <label for="BranchName">Branch Name:</label>
                    <input type="text" name="BranchName">
                </div>
                <div class="form-group">
                    <label for="AccountNumber">Account Number:</label>
                    <input type="text" name="AccountNumber">
                </div>
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

<script>
$(document).ready(function() {
    // Initially disable the system dropdown
    $('#system').prop('disabled', true);
    
    // When source changes
    $('#source_id').change(function() {
        var selectedSource = $(this).val();
        
        // Check if source contains "IATA" (case-sensitive)
        if (selectedSource.includes('IATA')) {
            $('#system').prop('disabled', false);
        } else {
            $('#system').prop('disabled', true).val('');
        }
    });
    
    // Calculate profit when bill amount or net payment changes
    $('#billAmount, #netPayment').on('input', function() {
        var billAmount = parseFloat($('#billAmount').val()) || 0;
        var netPayment = parseFloat($('#netPayment').val()) || 0;
        var profit = billAmount - netPayment;
        $('#profit').val(profit.toFixed(2));
    });
    
    // Calculate due amount when paid amount changes
    $('#paidAmount').on('input', function() {
        var billAmount = parseFloat($('#billAmount').val()) || 0;
        var paidAmount = parseFloat($('#paidAmount').val()) || 0;
        var dueAmount = billAmount - paidAmount;
        $('#dueAmount').val(dueAmount.toFixed(2));
    });
});

// Airline search functionality
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
</script>

</body>
</html>

<?php $conn->close(); ?>