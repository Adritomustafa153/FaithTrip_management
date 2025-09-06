<?php
include 'db.php';

// Fetch sales record by ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM sales WHERE SaleID = $id";
    $result = mysqli_query($conn, $query);
    $sale = mysqli_fetch_assoc($result);
    
    if (!$sale) {
        die("Record not found");
    }
} else {
    die("Invalid request");
}

// Fetch sources for the dropdown
$sources_query = "SELECT agency_name FROM sources";
$sources_result = mysqli_query($conn, $sources_query);

// Fetch systems for the dropdown
$systems_query = "SELECT system FROM iata_systems";
$systems_result = mysqli_query($conn, $systems_query);

// Fetch banks for the dropdown
$banks_query = "SELECT Bank_Name FROM banks";
$banks_result = mysqli_query($conn, $banks_query);

// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect all form data
    $PartyName = mysqli_real_escape_string($conn, $_POST['PartyName']);
    $PassengerName = mysqli_real_escape_string($conn, $_POST['PassengerName']);
    $airlines = mysqli_real_escape_string($conn, $_POST['airlines']);
    $TicketRoute = mysqli_real_escape_string($conn, $_POST['TicketRoute']);
    $TicketNumber = mysqli_real_escape_string($conn, $_POST['TicketNumber']);
    $Class = mysqli_real_escape_string($conn, $_POST['Class']);
    $IssueDate = mysqli_real_escape_string($conn, $_POST['IssueDate']);
    $FlightDate = mysqli_real_escape_string($conn, $_POST['FlightDate']);
    $ReturnDate = mysqli_real_escape_string($conn, $_POST['ReturnDate']);
    $PNR = mysqli_real_escape_string($conn, $_POST['PNR']);
    $BillAmount = floatval($_POST['BillAmount']);
    $NetPayment = floatval($_POST['NetPayment']);
    $Profit = floatval($_POST['Profit']);
    $Source = mysqli_real_escape_string($conn, $_POST['source_id']);
    $system = mysqli_real_escape_string($conn, $_POST['system']);
    $PaymentStatus = mysqli_real_escape_string($conn, $_POST['PaymentStatus']);
    $PaymentMethod = mysqli_real_escape_string($conn, $_POST['PaymentMethod']);
    $PaidAmount = floatval($_POST['PaidAmount']);
    $DueAmount = floatval($_POST['DueAmount']);
    $SalesPersonName = mysqli_real_escape_string($conn, $_POST['SalesPersonName']);
    $BankName = mysqli_real_escape_string($conn, $_POST['BankName']);
    $BranchName = mysqli_real_escape_string($conn, $_POST['BranchName']);
    $AccountNumber = mysqli_real_escape_string($conn, $_POST['AccountNumber']);
    $ReceivedDate = mysqli_real_escape_string($conn, $_POST['ReceivedDate']);
    $DepositDate = mysqli_real_escape_string($conn, $_POST['DepositDate']);
    $ClearingDate = mysqli_real_escape_string($conn, $_POST['ClearingDate']);
    
    // Update query
    $update_query = "UPDATE sales SET 
        PartyName = '$PartyName',
        PassengerName = '$PassengerName',
        airlines = '$airlines',
        TicketRoute = '$TicketRoute',
        TicketNumber = '$TicketNumber',
        Class = '$Class',
        IssueDate = '$IssueDate',
        FlightDate = '$FlightDate',
        ReturnDate = '$ReturnDate',
        PNR = '$PNR',
        BillAmount = $BillAmount,
        NetPayment = $NetPayment,
        Profit = $Profit,
        Source = '$Source',
        system = '$system',
        PaymentStatus = '$PaymentStatus',
        PaymentMethod = '$PaymentMethod',
        PaidAmount = $PaidAmount,
        DueAmount = $DueAmount,
        SalesPersonName = '$SalesPersonName',
        BankName = '$BankName',
        BranchName = '$BranchName',
        AccountNumber = '$AccountNumber',
        ReceivedDate = '$ReceivedDate',
        DepositDate = '$DepositDate',
        ClearingDate = '$ClearingDate'
        WHERE SaleID = $id";
    
    if (mysqli_query($conn, $update_query)) {
        echo "<script>alert('Record updated successfully!'); window.location='invoice_list.php';</script>";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.jpg">
    <title>Edit Sales Record</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 { 
            text-align: center; 
            color: #2c3e50; 
            margin-bottom: 25px; 
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        .form-row { 
            display: flex; 
            flex-wrap: wrap; 
            margin-bottom: 15px; 
        }
        .form-group { 
            flex: 1; 
            min-width: 250px; 
            margin: 0 10px 15px; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #333;
        }
        input, select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px;
            font-size: 14px;
        }
        .submit-btn { 
            background-color: #4a71ff; 
            color: white; 
            padding: 12px 25px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            margin-top: 15px;
        }
        .submit-btn:hover { 
            background-color: #3a5fd9; 
        }
        .hidden { 
            display: none; 
        }
        #suggestions { 
            position: absolute; 
            background: white; 
            border: 1px solid #ddd; 
            max-height: 150px; 
            overflow-y: auto; 
            z-index: 1000; 
            width: 300px;
        }
        .suggestion-item { 
            padding: 8px; 
            cursor: pointer; 
        }
        .suggestion-item:hover { 
            background-color: #f0f0f0; 
        }
        .bank-section {
            background-color: #f8f9ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #4a71ff;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include 'nav.php' ?>

<div class="container">
    <h2>Edit Sales Record</h2>
    <form action="" method="POST">
        <!-- Party Name -->
        <div class="form-row">
            <div class="form-group">
                <label for="PartyName">Company/Party Name:</label>
                <input type="text" name="PartyName" value="<?= htmlspecialchars($sale['PartyName']) ?>" required>
            </div>
        </div>

        <!-- Airline Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="airlines">Airlines Name</label>
                <input type="text" id="airlines" name="airlines" value="<?= htmlspecialchars($sale['airlines']) ?>" autocomplete="off" onkeyup="searchAirlines()">
                <input type="hidden" id="airline_code" name="airline_code" value="<? htmlspecialchars($sale['airline_code']) ?>">
                <div id="suggestions"></div>
            </div>
            <div class="form-group">
                <label for="PassengerName">Passenger Name:</label>
                <input type="text" name="PassengerName" value="<?= htmlspecialchars($sale['PassengerName']) ?>" required>
            </div>
        </div>

        <!-- Route and Ticket Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="TicketRoute">Ticket Route:</label>
                <input type="text" name="TicketRoute" value="<?= htmlspecialchars($sale['TicketRoute']) ?>" required>
            </div>
            <div class="form-group">
                <label for="TicketNumber">Ticket Number:</label>
                <input type="text" name="TicketNumber" value="<?= htmlspecialchars($sale['TicketNumber']) ?>" required>
            </div>
            <div class="form-group">
                <label for="Class">Seat Class:</label>
                <select name="Class" id="Class" required>
                    <option value="Economy" <?= $sale['Class'] == 'Economy' ? 'selected' : '' ?>>Economy Class</option>
                    <option value="Business" <?= $sale['Class'] == 'Business' ? 'selected' : '' ?>>Business Class</option>
                    <option value="First" <?= $sale['Class'] == 'First' ? 'selected' : '' ?>>First Class</option>
                    <option value="Premium" <?= $sale['Class'] == 'Premium' ? 'selected' : '' ?>>Premium Economy</option>
                </select>
            </div>
        </div>

        <!-- Date Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="IssueDate">Issue Date:</label>
                <input type="date" name="IssueDate" value="<?= $sale['IssueDate'] ?>" required>
            </div>
            <div class="form-group">
                <label for="FlightDate">Flight Date:</label>
                <input type="date" name="FlightDate" value="<?= $sale['FlightDate'] ?>" required>
            </div>
            <div class="form-group">
                <label for="ReturnDate">Return Date:</label>
                <input type="date" name="ReturnDate" value="<?= $sale['ReturnDate'] ?>">
            </div>
        </div>

        <!-- PNR and Financial Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="PNR">PNR:</label>
                <input type="text" name="PNR" value="<?= htmlspecialchars($sale['PNR']) ?>" required>
            </div>
            <div class="form-group">
                <label for="BillAmount">Bill Amount:</label>
                <input type="number" name="BillAmount" id="billAmount" value="<?= $sale['BillAmount'] ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="NetPayment">Net Payment:</label>
                <input type="number" name="NetPayment" id="netPayment" value="<?= $sale['NetPayment'] ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="Profit">Profit:</label>
                <input type="text" name="Profit" id="profit" value="<?= $sale['Profit'] ?>" readonly>
            </div>
        </div>

        <!-- Source and System -->
        <div class="form-row">
            <div class="form-group">
                <label for="source_id">Source (Agency Name)</label>
                <select name="source_id" id="source_id" required>
                    <option value="">Select Source</option>
                    <?php 
                    mysqli_data_seek($sources_result, 0);
                    while($row = mysqli_fetch_assoc($sources_result)): ?>
                        <option value="<?= $row['agency_name'] ?>" <?= $sale['Source'] == $row['agency_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['agency_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="system">System:</label>
                <select name="system" id="system" <?= !str_contains($sale['Source'] ?? '', 'IATA') ? 'disabled' : '' ?>>
                    <option value="">Select System</option>
                    <?php 
                    mysqli_data_seek($systems_result, 0);
                    while($system = mysqli_fetch_assoc($systems_result)): ?>
                        <option value="<?= $system['system'] ?>" <?= $sale['system'] == $system['system'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($system['system']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="PaymentStatus">Payment Status:</label>
                <select name="PaymentStatus" id="paymentStatus" required>
                    <option value="Paid" <?= $sale['PaymentStatus'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Partially Paid" <?= $sale['PaymentStatus'] == 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="Due" <?= $sale['PaymentStatus'] == 'Due' ? 'selected' : '' ?>>Due</option>
                </select>
            </div>
            <div class="form-group">
                <label for="PaymentMethod">Payment Method:</label>
                <select name="PaymentMethod" id="paymentMethod" required>
                    <option value="Cash Payment" <?= $sale['PaymentMethod'] == 'Cash Payment' ? 'selected' : '' ?>>Cash Payment</option>
                    <option value="Card Payment" <?= $sale['PaymentMethod'] == 'Card Payment' ? 'selected' : '' ?>>Card Payment</option>
                    <option value="Cheque Deposit" <?= $sale['PaymentMethod'] == 'Cheque Deposit' ? 'selected' : '' ?>>Cheque Deposit</option>
                    <option value="Bank Deposit" <?= $sale['PaymentMethod'] == 'Bank Deposit' ? 'selected' : '' ?>>Bank Deposit</option>
                    <option value="Cheque Clearing" <?= $sale['PaymentMethod'] == 'Cheque Clearing' ? 'selected' : '' ?>>Cheque Clearing</option>
                    <option value="Mobile Banking(nagad)" <?= $sale['PaymentMethod'] == 'Mobile Banking(nagad)' ? 'selected' : '' ?>>Mobile Banking (Nagad)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="PaidAmount">Paid Amount:</label>
                <input type="number" name="PaidAmount" id="paidAmount" value="<?= $sale['PaidAmount'] ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="DueAmount">Due Amount:</label>
                <input type="text" name="DueAmount" id="dueAmount" value="<?= $sale['DueAmount'] ?>" readonly>
            </div>
        </div>

        <!-- Sales Person -->
        <div class="form-row">
            <div class="form-group">
                <label for="SalesPersonName">Salesperson Name:</label>
                <input type="text" name="SalesPersonName" value="<?= htmlspecialchars($sale['SalesPersonName']) ?>" required>
            </div>
        </div>

        <!-- Bank Details (Conditional Display) -->
        <div id="bankDetails" class="<?= ($sale['PaymentStatus'] == 'Due') ? 'hidden' : '' ?> bank-section">
            <h3>Bank Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="BankName">Bank Name:</label>
                    <select name="BankName" id="BankName">
                        <option value="">Select Bank</option>
                        <?php 
                        mysqli_data_seek($banks_result, 0);
                        while($bank = mysqli_fetch_assoc($banks_result)): ?>
                            <option value="<?= $bank['Bank_Name'] ?>" <?= $sale['BankName'] == $bank['Bank_Name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bank['Bank_Name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="BranchName">Branch Name:</label>
                    <input type="text" name="BranchName" value="<?= htmlspecialchars($sale['BranchName']) ?>">
                </div>
                <div class="form-group">
                    <label for="AccountNumber">Account Number:</label>
                    <input type="text" name="AccountNumber" value="<?= htmlspecialchars($sale['AccountNumber']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="ReceivedDate">Received Date:</label>
                    <input type="date" name="ReceivedDate" value="<?= $sale['ReceivedDate'] ?>">
                </div>
                <div class="form-group">
                    <label for="DepositDate">Deposit Date:</label>
                    <input type="date" name="DepositDate" value="<?= $sale['DepositDate'] ?>">
                </div>
                <div class="form-group">
                    <label for="ClearingDate">Clearing Date:</label>
                    <input type="date" name="ClearingDate" value="<?= $sale['ClearingDate'] ?>">
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="form-row">
            <div class="form-group" style="text-align: center;">
                <button type="submit" class="submit-btn">Update Sale Record</button>
                <a href="invoice_list.php" style="margin-left: 15px; padding: 12px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Calculate initial profit and due amounts
    calculateProfit();
    calculateDueAmount();
    
    // Enable/disable system dropdown based on source selection
    $('#source_id').change(function() {
        var selectedSource = $(this).val();
        if (selectedSource && selectedSource.includes('IATA')) {
            $('#system').prop('disabled', false);
        } else {
            $('#system').prop('disabled', true).val('');
        }
    });
    
    // Calculate profit when bill amount or net payment changes
    $('#billAmount, #netPayment').on('input', calculateProfit);
    
    // Calculate due amount when paid amount changes
    $('#paidAmount').on('input', calculateDueAmount);
    
    // Show/hide bank details based on payment status
    $('#paymentStatus').change(function() {
        const status = $(this).val();
        if (status === 'Due') {
            $('#bankDetails').addClass('hidden');
        } else {
            $('#bankDetails').removeClass('hidden');
        }
    });
    
    // Also trigger on page load to set correct state
    $('#paymentStatus').trigger('change');
});

function calculateProfit() {
    const billAmount = parseFloat($('#billAmount').val()) || 0;
    const netPayment = parseFloat($('#netPayment').val()) || 0;
    const profit = billAmount - netPayment;
    $('#profit').val(profit.toFixed(2));
}

function calculateDueAmount() {
    const billAmount = parseFloat($('#billAmount').val()) || 0;
    const paidAmount = parseFloat($('#paidAmount').val()) || 0;
    const dueAmount = billAmount - paidAmount;
    $('#dueAmount').val(dueAmount.toFixed(2));
}

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