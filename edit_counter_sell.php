<?php
include 'auth_check.php';
include 'db.php';

// Check if ID is provided - handle both 'id' and 'sale_id' parameters
$id = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
} elseif (isset($_GET['sale_id']) && !empty($_GET['sale_id'])) {
    $id = intval($_GET['sale_id']);
}

if (!$id) {
    die("Error: No record ID provided");
}

// Fetch the record to edit
$query = "SELECT * FROM sales WHERE SaleID = $id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Error: Record not found");
}

$record = mysqli_fetch_assoc($result);

// Fetch salesperson names for dropdown (excluding empty values)
$salespersonQuery = "SELECT DISTINCT SalesPersonName FROM sales WHERE SalesPersonName IS NOT NULL AND SalesPersonName != '' AND SalesPersonName != '0'";
$salespersonResult = mysqli_query($conn, $salespersonQuery);

// Fetch sources for the dropdown
$sources_query = "SELECT agency_name FROM sources";
$sources_result = mysqli_query($conn, $sources_query);

// Fetch systems for the dropdown
$systems_query = "SELECT system FROM iata_systems";
$systems_result = mysqli_query($conn, $systems_query);

// Fetch banks for the dropdown
$banks_query = "SELECT Bank_Name FROM banks";
$banks_result = mysqli_query($conn, $banks_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
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
    $BankName = mysqli_real_escape_string($conn, $_POST['BankName']);
    $ReceivedDate = mysqli_real_escape_string($conn, $_POST['ReceivedDate']);
    $DepositDate = mysqli_real_escape_string($conn, $_POST['DepositDate']);
    $ClearingDate = mysqli_real_escape_string($conn, $_POST['ClearingDate']);
    $SalesPersonName = mysqli_real_escape_string($conn, $_POST['SalesPersonName']);
    
    // Update query
    $updateQuery = "UPDATE sales SET 
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
        BankName = '$BankName',
        ReceivedDate = '$ReceivedDate',
        DepositDate = '$DepositDate',
        ClearingDate = '$ClearingDate',
        SalesPersonName = '$SalesPersonName'
        WHERE SaleID = $id";
    
    if (mysqli_query($conn, $updateQuery)) {
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
    <link rel="icon" href="logo.png">
    <title>Edit Sales Record</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eaeaea;
            font-weight: 600;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .form-group {
            flex: 1;
            min-width: 220px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            background-color: white;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: #4a71ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 113, 255, 0.1);
        }
        
        .hidden {
            display: none;
        }
        
        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }
        
        .submit-btn {
            background: linear-gradient(to right, #4a71ff, #6a8aff);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(74, 113, 255, 0.25);
        }
        
        .submit-btn:hover {
            background: linear-gradient(to right, #3a5fd9, #5a7ae9);
            box-shadow: 0 6px 15px rgba(74, 113, 255, 0.35);
            transform: translateY(-2px);
        }
        
        .cancel-btn {
            background: linear-gradient(to right, #6c757d, #868e96);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-align: center;
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.25);
        }
        
        .cancel-btn:hover {
            background: linear-gradient(to right, #5a6268, #727b84);
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.35);
            transform: translateY(-2px);
            color: white;
        }
        
        #suggestions {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            width: 300px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 6px;
        }
        
        .suggestion-item {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .suggestion-item:hover {
            background-color: #f0f5ff;
        }
        
        .error-message {
            color: #d9534f;
            text-align: center;
            padding: 20px;
            font-size: 18px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            margin: 20px auto;
            max-width: 600px;
        }
        
        .payment-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #4a71ff;
        }
        
        @media (max-width: 768px) {
            .form-group {
                min-width: 100%;
            }
            
            .button-container {
                flex-direction: column;
            }
            
            .submit-btn, .cancel-btn {
                width: 100%;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include 'nav.php' ?>

<div class="container">
    <h1>Edit Sales Record</h1>
    
    <form action="" method="POST">
        <!-- Company Name and Passenger Name -->
        <div class="form-row">
            <div class="form-group">
                <label for="PartyName">Company Name:</label>
                <input type="text" name="PartyName" value="<?= htmlspecialchars($record['PartyName']) ?>" required>
            </div>
            <div class="form-group">
                <label for="PassengerName">Passenger Name:</label>
                <input type="text" name="PassengerName" value="<?= htmlspecialchars($record['PassengerName']) ?>" required>
            </div>
        </div>

        <!-- Ticket Route and Ticket Number -->
        <div class="form-row">
            <div class="form-group">
                <label for="TicketRoute">Ticket Route:</label>
                <input type="text" name="TicketRoute" value="<?= htmlspecialchars($record['TicketRoute']) ?>" required>
            </div>
            <div class="form-group">
                <label for="TicketNumber">Ticket Number:</label>
                <input type="text" name="TicketNumber" value="<?= htmlspecialchars($record['TicketNumber']) ?>" required>
            </div>
        </div>
        
        <!-- Airlines selection -->
        <div class="form-row">
            <div class="form-group">
                <label for="airlines">Airlines Name:</label>
                <input type="text" id="airlines" name="airlines" value="<?= htmlspecialchars($record['airlines']) ?>" autocomplete="on" onkeyup="searchAirlines()">
                <input type="hidden" id="airline_code" name="airline_code">
                <div id="suggestions"></div>
            </div>
        </div>

        <!-- Dates -->
        <div class="form-row">
            <div class="form-group">
                <label for="IssueDate">Issue Date:</label>
                <input type="date" name="IssueDate" value="<?= $record['IssueDate'] ?>" required>
            </div>
            <div class="form-group">
                <label for="FlightDate">Flight Date:</label>
                <input type="date" name="FlightDate" value="<?= $record['FlightDate'] ?>" required>
            </div>
            <div class="form-group">
                <label for="ReturnDate">Return Date:</label>
                <input type="date" name="ReturnDate" value="<?= $record['ReturnDate'] ?>">
            </div>
        </div>

        <!-- PNR, Bill Amount, and Net Payment -->
        <div class="form-row">
            <div class="form-group">
                <label for="PNR">PNR:</label>
                <input type="text" name="PNR" value="<?= htmlspecialchars($record['PNR']) ?>" required>
            </div>
            <div class="form-group">
                <label for="BillAmount">Bill Amount:</label>
                <input type="number" name="BillAmount" id="billAmount" value="<?= $record['BillAmount'] ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="NetPayment">Net Payment:</label>
                <input type="number" name="NetPayment" id="netPayment" value="<?= $record['NetPayment'] ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="source_id">Source (Agency Name)</label>
                <select name="source_id" id="source_id" class="form-control" required>
                    <option value="">Select Source</option>
                    <?php 
                    if ($sources_result) {
                        mysqli_data_seek($sources_result, 0);
                        while($row = mysqli_fetch_assoc($sources_result)): 
                    ?>
                        <option value="<?= $row['agency_name']; ?>" 
                            <?= $record['Source'] == $row['agency_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['agency_name']); ?>
                        </option>
                    <?php 
                        endwhile; 
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Profit, Payment Status, and Payment Method -->
        <div class="form-row">
            <div class="form-group">
                <label for="Profit">Profit:</label>
                <input type="text" name="Profit" id="profit" value="<?= $record['Profit'] ?>" step="0.01" readonly>
            </div>
            <div class="form-group">
                <label for="PaymentStatus">Payment Status:</label>
                <select name="PaymentStatus" id="paymentStatus" required>
                    <option value="Paid" <?= $record['PaymentStatus'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Partially Paid" <?= $record['PaymentStatus'] == 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="Due" <?= $record['PaymentStatus'] == 'Due' ? 'selected' : '' ?>>Due</option>
                </select>
            </div>
            <div class="form-group">
                <label for="PaymentMethod">Payment Method:</label>
                <select name="PaymentMethod" id="paymentMethod" required>
                    <option value="Cash Payment" <?= $record['PaymentMethod'] == 'Cash Payment' ? 'selected' : '' ?>>Cash Payment</option>
                    <option value="Card Payment" <?= $record['PaymentMethod'] == 'Card Payment' ? 'selected' : '' ?>>Card Payment</option>
                    <option value="Cheque Deposit" <?= $record['PaymentMethod'] == 'Cheque Deposit' ? 'selected' : '' ?>>Cheque Deposit</option>
                    <option value="Bank Deposit" <?= $record['PaymentMethod'] == 'Bank Deposit' ? 'selected' : '' ?>>Bank Deposit</option>
                    <option value="Cheque Clearing" <?= $record['PaymentMethod'] == 'Cheque Clearing' ? 'selected' : '' ?>>Cheque Clearing</option>
                    <option value="Mobile Banking(nagad)" <?= $record['PaymentMethod'] == 'Mobile Banking(nagad)' ? 'selected' : '' ?>>Mobile Banking (Nagad)</option>
                </select>
            </div>
            <!-- System dropdown -->
            <div class="form-group">
                <label for="system">System:</label>
                <select name="system" id="system" <?= !str_contains($record['Source'] ?? '', 'IATA') ? 'disabled' : '' ?> required>
                    <option value="">Select System</option>
                    <?php 
                    if ($systems_result) {
                        mysqli_data_seek($systems_result, 0);
                        while($system = mysqli_fetch_assoc($systems_result)): 
                    ?>
                        <option value="<?= $system['system']; ?>" 
                            <?= $record['system'] == $system['system'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($system['system']); ?>
                        </option>
                    <?php 
                        endwhile; 
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Paid Amount, Due Amount, and Salesperson Name -->
        <div class="form-row">
            <div class="form-group">
                <label for="PaidAmount">Paid Amount:</label>
                <input type="number" name="PaidAmount" id="paidAmount" value="<?= $record['PaidAmount'] ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="DueAmount">Due Amount:</label>
                <input type="text" name="DueAmount" id="dueAmount" value="<?= $record['DueAmount'] ?>" step="0.01" readonly>
            </div>
            <div class="form-group">
                <label for="SalesPersonName">Salesperson Name:</label>
                <select name="SalesPersonName" id="SalesPersonName" required>
                    <option value="">Select Salesperson</option>
                    <?php 
                    if ($salespersonResult) {
                        while ($salesperson = mysqli_fetch_assoc($salespersonResult)) : 
                    ?>
                        <option value="<?= htmlspecialchars($salesperson['SalesPersonName']) ?>" 
                            <?= $record['SalesPersonName'] == $salesperson['SalesPersonName'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($salesperson['SalesPersonName']) ?>
                        </option>
                    <?php 
                        endwhile; 
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="Class">Seat Class:</label>
                <select name="Class" id="seat" required>
                    <option value="Economy" <?= $record['Class'] == 'Economy' ? 'selected' : '' ?>>Economy Class</option>
                    <option value="Business" <?= $record['Class'] == 'Business' ? 'selected' : '' ?>>Business Class</option>
                    <option value="First" <?= $record['Class'] == 'First' ? 'selected' : '' ?>>First Class</option>
                    <option value="Premium" <?= $record['Class'] == 'Premium' ? 'selected' : '' ?>>Premium Economy</option>
                </select>
            </div>
        </div>

        <!-- Bank Details (Hidden by Default) -->
        <div id="bankDetails" class="<?= $record['PaymentStatus'] == 'Due' ? 'hidden' : 'payment-details' ?>">
            <h3 style="margin-top: 0; color: #2c3e50;">Payment Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="BankName">Bank Name:</label>
                    <select name="BankName" id="BankName">
                        <option value="">Select Bank</option>
                        <?php 
                        if ($banks_result) {
                            while ($bank = mysqli_fetch_assoc($banks_result)) : 
                        ?>
                            <option value="<?= htmlspecialchars($bank['Bank_Name']) ?>" 
                                <?= $record['BankName'] == $bank['Bank_Name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bank['Bank_Name']) ?>
                            </option>
                        <?php 
                            endwhile; 
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ReceivedDate">Received Date:</label>
                    <input type="date" name="ReceivedDate" value="<?= $record['ReceivedDate'] ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="DepositDate">Deposit Date:</label>
                    <input type="date" name="DepositDate" value="<?= $record['DepositDate'] ?>">
                </div>
                <div class="form-group">
                    <label for="ClearingDate">Clearing Date:</label>
                    <input type="date" name="ClearingDate" value="<?= $record['ClearingDate'] ?>">
                </div>
            </div>
        </div>

        <!-- Submit and Cancel Buttons -->
        <div class="button-container">   
            <button type="submit" class="submit-btn">Update Sale</button>
            <a href="invoice_list.php" class="cancel-btn">Cancel</a>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Calculate profit and due amount on page load
    calculateProfit();
    calculateDueAmount();
    
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
    $('#billAmount, #netPayment').on('input', calculateProfit);
    
    // Calculate due amount when paid amount changes
    $('#paidAmount').on('input', calculateDueAmount);
    
    // Show/hide bank details based on payment status
    $('#paymentStatus').change(function() {
        var status = $(this).val();
        if (status === 'Due') {
            $('#bankDetails').addClass('hidden');
        } else {
            $('#bankDetails').removeClass('hidden').addClass('payment-details');
        }
    });
    
    // Trigger change event on page load to set initial state
    $('#paymentStatus').trigger('change');
});

function calculateProfit() {
    var billAmount = parseFloat($('#billAmount').val()) || 0;
    var netPayment = parseFloat($('#netPayment').val()) || 0;
    var profit = billAmount - netPayment;
    $('#profit').val(profit.toFixed(2));
}

function calculateDueAmount() {
    var billAmount = parseFloat($('#billAmount').val()) || 0;
    var paidAmount = parseFloat($('#paidAmount').val()) || 0;
    var dueAmount = billAmount - paidAmount;
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

<?php $conn->close(); ?>