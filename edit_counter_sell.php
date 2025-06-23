<?php
include 'db.php';

$sale_id = $_GET['sale_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle update submission
    $PassengerName = $_POST['PassengerName'];
    $TicketRoute = $_POST['TicketRoute'];
    $TicketNumber = $_POST['TicketNumber'];
    $airlines = $_POST['airlines'];
    $IssueDate = $_POST['IssueDate'];
    $FlightDate = $_POST['FlightDate'];
    $ReturnDate = $_POST['ReturnDate'];
    $PNR = $_POST['PNR'];
    $BillAmount = $_POST['BillAmount'];
    $NetPayment = $_POST['NetPayment'];
    $Profit = $_POST['Profit'];
    $PaymentStatus = $_POST['PaymentStatus'];
    $PaymentMethod = $_POST['PaymentMethod'];
    $PaidAmount = $_POST['PaidAmount'];
    $DueAmount = $_POST['DueAmount'];
    $SalesPersonName = $_POST['SalesPersonName'];
    $SaleID = $_POST['SaleID'];
    $Class = $_POST['Class'];
    $source = $_POST['source_id'];

    $stmt = $conn->prepare("UPDATE sales SET PassengerName=?, TicketRoute=?, TicketNumber=?, airlines=?, IssueDate=?, FlightDate=?, ReturnDate=?, PNR=?, BillAmount=?, NetPayment=?, Profit=?, PaymentStatus=?, PaymentMethod=?, PaidAmount=?, DueAmount=?, SalesPersonName=?,Class=?,Source=? WHERE SaleID=?");
    $stmt->bind_param("ssssssssddddssdsi", $PassengerName, $TicketRoute, $TicketNumber, $airlines, $IssueDate, $FlightDate, $ReturnDate, $PNR, $BillAmount, $NetPayment, $Profit, $PaymentStatus, $PaymentMethod, $PaidAmount, $DueAmount, $SalesPersonName,$Class,$source, $SaleID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Sale updated successfully.'); window.location.href='counter_sell_list.php';</script>";
        exit;
    } else {
        echo "<script>alert('No changes made or update failed.');</script>";
    }
}

if (!$sale_id || !is_numeric($sale_id)) {
    echo "Invalid SaleID.";
    exit;
}

// Fetch the existing data securely
$stmt = $conn->prepare("SELECT * FROM sales WHERE SaleID = ? LIMIT 1");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "SaleID not found.";
    exit;
}

$sale = $result->fetch_assoc();
$sources_query = "SELECT agency_name FROM sources";
$sources_result = mysqli_query($conn, $sources_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sale</title>
    <link rel="stylesheet" href="agents_manual_insert.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="manualinsert.js" defer></script>
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
</head>
<body>
<?php include 'nav.php'; ?>

<div style="display: flex; justify-content:center; margin-top:15px">
    <h1>Edit Counter Sale</h1>
</div>

<div class="container">
    <form method="POST">
        <input type="hidden" name="SaleID" value="<?= htmlspecialchars($sale_id) ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="PassengerName">Passenger Name:</label>
                <input type="text" name="PassengerName" value="<?= htmlspecialchars($sale['PassengerName']) ?>" required>
            </div>
            <div class="form-group">
                <label for="TicketRoute">Ticket Route:</label>
                <input type="text" name="TicketRoute" value="<?= htmlspecialchars($sale['TicketRoute']) ?>" required>
            </div>
            <div class="form-group">
                <label for="TicketNumber">Ticket Number:</label>
                <input type="text" name="TicketNumber" value="<?= htmlspecialchars($sale['TicketNumber']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <label for="AccountNumber">Airlines Name :</label>
            <input type="text" id="airlines" name="airlines" value="<?= htmlspecialchars($sale['airlines']) ?>" autocomplete="on" onkeyup="searchAirlines()">
                    <!-- <input type="text" id="airlines" name="airlines" autocomplete="off" onkeyup="searchAirlines()"> -->
                    <input type="hidden" id="airline_code" name="airline_code">
                    <input type="hidden" id="airline_logo_url" name="airline_logo_url">
                    <div id="suggestions"></div>
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

        <div class="form-row">
            <div class="form-group">
                <label for="IssueDate">Issue Date:</label>
                <input type="date" name="IssueDate" value="<?= htmlspecialchars($sale['IssueDate']) ?>" required>
            </div>
            <div class="form-group">
                <label for="FlightDate">Flight Date:</label>
                <input type="date" name="FlightDate" value="<?= htmlspecialchars($sale['FlightDate']) ?>" required>
            </div>
            <div class="form-group">
                <label for="ReturnDate">Return Date:</label>
                <input type="date" name="ReturnDate" value="<?= htmlspecialchars($sale['ReturnDate']) ?>">
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


        <div class="form-row">
            <div class="form-group">
                <label for="PNR">PNR:</label>
                <input type="text" name="PNR" value="<?= htmlspecialchars($sale['PNR']) ?>" required>
            </div>
            <div class="form-group">
                <label for="BillAmount">Bill Amount:</label>
                <input type="number" step="0.01" name="BillAmount" id="billAmount" value="<?= htmlspecialchars($sale['BillAmount']) ?>" required>
            </div>
            <div class="form-group">
                <label for="NetPayment">Net Payment:</label>
                <input type="number" step="0.01" name="NetPayment" id="netPayment" value="<?= htmlspecialchars($sale['NetPayment']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="Profit">Profit:</label>
                <input type="text" name="Profit" id="profit" value="<?= htmlspecialchars($sale['Profit']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="PaymentStatus">Payment Status:</label>
                <select name="PaymentStatus" id="paymentStatus" required>
                    <option value="Paid" <?= $sale['PaymentStatus'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Partially Paid" <?= $sale['PaymentStatus'] == 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="DUE" <?= $sale['PaymentStatus'] == 'DUE' ? 'selected' : '' ?>>DUE</option>
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
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="PaidAmount">Paid Amount:</label>
                <input type="number" name="PaidAmount" value="<?= htmlspecialchars($sale['PaidAmount']) ?>" required>
            </div>
            <div class="form-group">
                <label for="DueAmount">Due Amount:</label>
                <input type="text" name="DueAmount" value="<?= htmlspecialchars($sale['DueAmount']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="SalesPersonName">Salesperson Name:</label>
                <input type="text" name="SalesPersonName" value="<?= htmlspecialchars($sale['SalesPersonName']) ?>" required>
            </div>
                                    <div class="form-group">
    <label for="source_id">Source (Agency Name)</label>
    <select name="source_id" id="source_id" class="form-control" required>
        <option value="">Select Source</option>
        <?php while($row = mysqli_fetch_assoc($sources_result)): ?>
            <option value="<?= $row['agency_name']; ?>"><?= htmlspecialchars($row['agency_name']); ?></option>
        <?php endwhile; ?>
    </select>
</div>
        </div>

        <div class="form-row submit-button-wrapper">   
        <div class="form-row">
            <div class="form-group">
                <button type="submit" class="submit-btn">Update</button>
            </div>
        </div>
        </div>
    </form>
</div>
</body>
</html>