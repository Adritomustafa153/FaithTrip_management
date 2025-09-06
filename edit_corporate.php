<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if ID parameter is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    die("Error: No record ID provided.");
}

$sale_id = intval($_GET['sale_id']);

// Fetch the existing sale record
$sale_query = "SELECT * FROM sales WHERE SaleID = $sale_id";
$sale_result = $conn->query($sale_query);

if ($sale_result->num_rows === 0) {
    die("Error: Record not found.");
}

$sale_data = $sale_result->fetch_assoc();

// Fetch company names for dropdown
$companyQuery = "SELECT DISTINCT PartyName FROM sales";
$companyResult = $conn->query($companyQuery);

// Fetch sources for dropdown
$sources_query = "SELECT agency_name FROM sources";
$sources_result = mysqli_query($conn, $sources_query);

// Fetch systems for dropdown
$systems_query = "SELECT system FROM iata_systems";
$systems_result = mysqli_query($conn, $systems_query);

// Fetch bank names for dropdown
$banks_query = "SELECT Bank_Name FROM banks";
$banks_result = mysqli_query($conn, $banks_query);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $partyName = $conn->real_escape_string($_POST['PartyName']);
    $passengerName = $conn->real_escape_string($_POST['PassengerName']);
    $airlines = $conn->real_escape_string($_POST['airlines']);
    $ticketRoute = $conn->real_escape_string($_POST['TicketRoute']);
    $ticketNumber = $conn->real_escape_string($_POST['TicketNumber']);
    $class = $conn->real_escape_string($_POST['Class']);
    $issueDate = $conn->real_escape_string($_POST['IssueDate']);
    $flightDate = $conn->real_escape_string($_POST['FlightDate']);
    $returnDate = $conn->real_escape_string($_POST['ReturnDate']);
    $pnr = $conn->real_escape_string($_POST['PNR']);
    $billAmount = floatval($_POST['BillAmount']);
    $netPayment = floatval($_POST['NetPayment']);
    $profit = floatval($_POST['Profit']);
    $source = $conn->real_escape_string($_POST['source_id']);
    $system = $conn->real_escape_string($_POST['system']);
    $paymentStatus = $conn->real_escape_string($_POST['PaymentStatus']);
    $paymentMethod = $conn->real_escape_string($_POST['PaymentMethod']);
    $paidAmount = floatval($_POST['PaidAmount']);
    $dueAmount = floatval($_POST['DueAmount']);
    $salesPersonName = $conn->real_escape_string($_POST['SalesPersonName']);
    $bankName = $conn->real_escape_string($_POST['BankName']);
    $receivedDate = $conn->real_escape_string($_POST['ReceivedDate']);
    $depositDate = $conn->real_escape_string($_POST['DepositDate']);
    $clearingDate = $conn->real_escape_string($_POST['ClearingDate']);
    
    // Update query
    $update_query = "UPDATE sales SET 
        PartyName = '$partyName',
        PassengerName = '$passengerName',
        airlines = '$airlines',
        TicketRoute = '$ticketRoute',
        TicketNumber = '$ticketNumber',
        Class = '$class',
        IssueDate = '$issueDate',
        FlightDate = '$flightDate',
        ReturnDate = '$returnDate',
        PNR = '$pnr',
        BillAmount = $billAmount,
        NetPayment = $netPayment,
        Profit = $profit,
        Source = '$source',
        system = '$system',
        PaymentStatus = '$paymentStatus',
        PaymentMethod = '$paymentMethod',
        PaidAmount = $paidAmount,
        DueAmount = $dueAmount,
        SalesPersonName = '$salesPersonName',
        BankName = '$bankName',
        ReceivedDate = '$receivedDate',
        DepositDate = '$depositDate',
        ClearingDate = '$clearingDate'
        WHERE SaleID = $sale_id";
    
    if ($conn->query($update_query)) {
        echo "<script>alert('Record updated successfully!'); window.location='invoice_list.php';</script>";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sales Record</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
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
            gap: 15px;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .submit-btn {
            background-color: #4a71ff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            display: block;
            margin: 20px auto;
            width: 200px;
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
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            width: calc(100% - 2px);
            box-sizing: border-box;
        }
        
        .suggestion-item {
            padding: 8px 10px;
            cursor: pointer;
        }
        
        .suggestion-item:hover {
            background-color: #f0f0f0;
        }
        
        select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .back-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background-color: #5a6268;
        }
        
        .payment-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #4a71ff;
        }
        
        .payment-section h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <button class="back-btn" onclick="window.location.href='invoice_list.php'">← Back to Sales Records</button>
    
    <h2>Edit Sales Record (ID: <?php echo $sale_id; ?>)</h2>
    <form method="POST">
        <!-- Row 1: Company Name, Airlines -->
        <div class="form-row">
            <div class="form-group">
                <label for="PartyName">Company Name:</label>
                <select name="PartyName" id="PartyName" required>
                    <option value="">Select Company</option>
                    <?php 
                    $companyResult->data_seek(0); // Reset pointer
                    while ($row = $companyResult->fetch_assoc()) : ?>
                        <option value="<?= htmlspecialchars($row['PartyName']) ?>" 
                            <?= ($sale_data['PartyName'] == $row['PartyName']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['PartyName']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="airlines">Airlines Name</label>
                <input type="text" id="airlines" name="airlines" value="<?= htmlspecialchars($sale_data['airlines']) ?>" autocomplete="off" onkeyup="searchAirlines()">
                <input type="hidden" id="airline_code" name="airline_code">
                <input type="hidden" id="airline_logo_url" name="airline_logo_url">
                <div id="suggestions"></div>
            </div>
        </div>

        <!-- Row 2: Passenger Name, Ticket Route, and Ticket Number -->
        <div class="form-row">
            <div class="form-group">
                <label for="PassengerName">Passenger Name:</label>
                <input type="text" name="PassengerName" value="<?= htmlspecialchars($sale_data['PassengerName']) ?>" required>
            </div>
            <div class="form-group">
                <label for="TicketRoute">Ticket Route:</label>
                <input type="text" name="TicketRoute" value="<?= htmlspecialchars($sale_data['TicketRoute']) ?>" required>
            </div>
            <div class="form-group">
                <label for="TicketNumber">Ticket Number:</label>
                <input type="text" name="TicketNumber" value="<?= htmlspecialchars($sale_data['TicketNumber']) ?>" required>
            </div>
        </div>

        <!-- Row 3: Issue Date, Flight Date, and Return Date -->
        <div class="form-row">
            <div class="form-group">
                <label for="IssueDate">Issue Date:</label>
                <input type="date" name="IssueDate" value="<?= htmlspecialchars($sale_data['IssueDate']) ?>" required>
            </div>
            <div class="form-group">
                <label for="FlightDate">Flight Date:</label>
                <input type="date" name="FlightDate" value="<?= htmlspecialchars($sale_data['FlightDate']) ?>" required>
            </div>
            <div class="form-group">
                <label for="ReturnDate">Return Date:</label>
                <input type="date" name="ReturnDate" value="<?= htmlspecialchars($sale_data['ReturnDate']) ?>">
            </div>
        </div>

        <!-- Row 4: PNR, Bill Amount, Net Payment, and Source -->
        <div class="form-row">
            <div class="form-group">
                <label for="PNR">PNR:</label>
                <input type="text" name="PNR" value="<?= htmlspecialchars($sale_data['PNR']) ?>" required>
            </div>
            <div class="form-group">
                <label for="BillAmount">Bill Amount:</label>
                <input type="number" name="BillAmount" id="billAmount" value="<?= htmlspecialchars($sale_data['BillAmount']) ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="NetPayment">Net Payment:</label>
                <input type="number" name="NetPayment" id="netPayment" value="<?= htmlspecialchars($sale_data['NetPayment']) ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="source_id">Source (Agency Name)</label>
                <select name="source_id" id="source_id" class="form-control" required>
                    <option value="">Select Source</option>
                    <?php 
                    mysqli_data_seek($sources_result, 0);
                    while($row = mysqli_fetch_assoc($sources_result)): ?>
                        <option value="<?= $row['agency_name']; ?>" 
                            <?= ($sale_data['Source'] == $row['agency_name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['agency_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <!-- Row 5: Profit, Payment Status, Payment Method, and System -->
        <div class="form-row">
            <div class="form-group">
                <label for="Profit">Profit:</label>
                <input type="text" name="Profit" id="profit" value="<?= htmlspecialchars($sale_data['Profit']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="PaymentStatus">Payment Status:</label>
                <select name="PaymentStatus" id="paymentStatus" required>
                    <option value="Paid" <?= ($sale_data['PaymentStatus'] == 'Paid') ? 'selected' : '' ?>>Paid</option>
                    <option value="Partially Paid" <?= ($sale_data['PaymentStatus'] == 'Partially Paid') ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="Due" <?= ($sale_data['PaymentStatus'] == 'Due') ? 'selected' : '' ?>>Due</option>
                </select>
            </div>
            <div class="form-group">
                <label for="PaymentMethod">Payment Method:</label>
                <select name="PaymentMethod" id="paymentMethod" required>
                    <option value="Cash Payment" <?= ($sale_data['PaymentMethod'] == 'Cash Payment') ? 'selected' : '' ?>>Cash Payment</option>
                    <option value="Card Payment" <?= ($sale_data['PaymentMethod'] == 'Card Payment') ? 'selected' : '' ?>>Card Payment</option>
                    <option value="Cheque Deposit" <?= ($sale_data['PaymentMethod'] == 'Cheque Deposit') ? 'selected' : '' ?>>Cheque Deposit</option>
                    <option value="Bank Deposit" <?= ($sale_data['PaymentMethod'] == 'Bank Deposit') ? 'selected' : '' ?>>Bank Deposit</option>
                    <option value="Cheque Clearing" <?= ($sale_data['PaymentMethod'] == 'Cheque Clearing') ? 'selected' : '' ?>>Cheque Clearing</option>
                    <option value="Mobile Banking(nagad)" <?= ($sale_data['PaymentMethod'] == 'Mobile Banking(nagad)') ? 'selected' : '' ?>>Mobile Banking (Nagad)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="system">System:</label>
                <select name="system" id="system" disabled required>
                    <option value="">Select System</option>
                    <?php 
                    mysqli_data_seek($systems_result, 0);
                    while($system = mysqli_fetch_assoc($systems_result)): ?>
                        <option value="<?= $system['system']; ?>" 
                            <?= ($sale_data['system'] == $system['system']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($system['system']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <!-- Row 6: Paid Amount, Due Amount, Salesperson, and Class -->
        <div class="form-row">
            <div class="form-group">
                <label for="PaidAmount">Paid Amount:</label>
                <input type="number" name="PaidAmount" id="paidAmount" value="<?= htmlspecialchars($sale_data['PaidAmount']) ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="DueAmount">Due Amount:</label>
                <input type="text" name="DueAmount" id="dueAmount" value="<?= htmlspecialchars($sale_data['DueAmount']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="SalesPersonName">Salesperson Name:</label>
                <input type="text" name="SalesPersonName" value="<?= htmlspecialchars($sale_data['SalesPersonName']) ?>" required>
            </div>
            <div class="form-group">
                <label for="Class">Seat Class:</label>
                <select name="Class" id="seat" required>
                    <option value="Economy" <?= ($sale_data['Class'] == 'Economy') ? 'selected' : '' ?>>Economy Class</option>
                    <option value="Business" <?= ($sale_data['Class'] == 'Business') ? 'selected' : '' ?>>Business Class</option>
                    <option value="First" <?= ($sale_data['Class'] == 'First') ? 'selected' : '' ?>>First Class</option>
                    <option value="Premium" <?= ($sale_data['Class'] == 'Premium') ? 'selected' : '' ?>>Premium Economy</option>
                    <option value="Business + Economy" <?= ($sale_data['Class'] == 'Business + Economy') ? 'selected' : '' ?>>Business + Economy</option>
                </select>
            </div>
        </div>

        <!-- Payment Details Section (shown when Payment Status is Paid or Partially Paid) -->
        <div id="paymentDetails" class="payment-section <?= (in_array($sale_data['PaymentStatus'], ['Paid', 'Partially Paid'])) ? '' : 'hidden' ?>">
            <h3>Payment Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="BankName">Bank Name:</label>
                    <select name="BankName" id="BankName">
                        <option value="">Select Bank</option>
                        <?php 
                        mysqli_data_seek($banks_result, 0);
                        while($bank = mysqli_fetch_assoc($banks_result)): ?>
                            <option value="<?= $bank['Bank_Name']; ?>" 
                                <?= ($sale_data['BankName'] == $bank['Bank_Name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bank['Bank_Name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ReceivedDate">Received Date:</label>
                    <input type="date" name="ReceivedDate" value="<?= htmlspecialchars($sale_data['ReceivedDate']) ?>">
                </div>
                <div class="form-group">
                    <label for="DepositDate">Deposit Date:</label>
                    <input type="date" name="DepositDate" value="<?= htmlspecialchars($sale_data['DepositDate']) ?>">
                </div>
                <div class="form-group">
                    <label for="ClearingDate">Clearing Date:</label>
                    <input type="date" name="ClearingDate" value="<?= htmlspecialchars($sale_data['ClearingDate']) ?>">
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="form-row">
            <div class="form-group">
                <button type="submit" class="submit-btn">Update Sale</button>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Initially disable the system dropdown
    $('#system').prop('disabled', true);
    
    // Check if source should enable system dropdown
    var selectedSource = $('#source_id').val();
    if (selectedSource && selectedSource.includes('IATA')) {
        $('#system').prop('disabled', false);
    }
    
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
    
    // Show/hide payment details based on payment status
    $('#paymentStatus').change(function() {
        var status = $(this).val();
        if (['Paid', 'Partially Paid'].includes(status)) {
            $('#paymentDetails').removeClass('hidden');
        } else {
            $('#paymentDetails').addClass('hidden');
        }
    });
    
    // Show/hide bank details based on payment method
    $('#paymentMethod').change(function() {
        var method = $(this).val();
        if (['Card Payment', 'Cheque Deposit', 'Bank Deposit', 'Cheque Clearing'].includes(method)) {
            $('#paymentDetails').removeClass('hidden');
        } else if ($('#paymentStatus').val() !== 'Paid' && $('#paymentStatus').val() !== 'Partially Paid') {
            $('#paymentDetails').addClass('hidden');
        }
    });
    
    // Trigger change event on page load to set initial visibility
    $('#paymentStatus').trigger('change');
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
    document.getElementById('airline_logo_url').value = airline.logo;
    document.getElementById('suggestions').innerHTML = "";
}
</script>

</body>
</html>

<?php $conn->close(); ?>