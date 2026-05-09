<?php
// edit.php
include 'db_connection.php'; // adjust to your connection file

$sale_id = $_GET['sale_id'] ?? $_GET['id'] ?? null;

if (!$sale_id) {
    die("Sale ID not provided.");
}

$query = "SELECT * FROM sales WHERE SaleID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Sale record not found.");
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Sell</title>
    <style>
        /* Original CSS from your edit.php (kept exactly as before) */
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[readonly] { background-color: #f0f0f0; }
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; border-radius: 20px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2); }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; border-radius: 20px; }
        th { background-color: rgb(74, 113, 255); color: white; border-radius: 5px; }
        .search-container { display: flex; gap: 10px; margin-bottom: 20px; border-radius: 15px; }
        .search-container select, .search-container input { padding: 8px; width: 200px; }
        .btn { padding: 5px 10px; border: none; cursor: pointer; text-decoration: none; font-size: 12px; padding: 4px 8px; }
        .edit-btn { background-color: rgb(7, 147, 32); color: white; }
        .delete-btn { background-color: #d9534f; color: white; }
        .btn:hover { opacity: 0.8; }
    </style>
    <link rel="stylesheet" href="agents_manual_insert.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="manualinsert.js" defer></script>
</head>
<?php include 'nav.php'; ?>
<body>
    <div style="display: flex; justify-content: center; margin-top: 30px">
        <h2>Edit Sell</h2>
    </div>
    <div class="container">
        <form action="update_sale.php" method="post">
            <input type="hidden" name="sale_id" value="<?= htmlspecialchars($row['SaleID']) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Passenger Name:</label>
                    <input type="text" name="passenger_name" value="<?= htmlspecialchars($row['PassengerName'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Ticket Route:</label>
                    <input type="text" name="ticket_route" value="<?= htmlspecialchars($row['TicketRoute'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Issue Date:</label>
                    <input type="date" name="issueDate" id="issueDate" value="<?= htmlspecialchars($row['IssueDate'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Airlines Name:</label>
                    <input type="text" name="airlines" value="<?= htmlspecialchars($row['airlines'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>PNR:</label>
                    <input type="text" name="pnr" value="<?= htmlspecialchars($row['PNR'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Ticket Number:</label>
                    <input type="text" name="TicketNumber" value="<?= htmlspecialchars($row['TicketNumber'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Journey Date:</label>
                    <input type="date" name="journey_date" value="<?= htmlspecialchars($row['FlightDate'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Return Date:</label>
                    <input type="date" name="return_date" value="<?= htmlspecialchars($row['ReturnDate'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Bill Amount:</label>
                    <input type="number" name="BillAmount" id="billAmount" value="<?= htmlspecialchars($row['BillAmount'] ?? 0) ?>" step="0.01">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Net Payment:</label>
                    <input type="number" name="NetPayment" id="NetPayment" value="<?= htmlspecialchars($row['NetPayment'] ?? 0) ?>" step="0.01">
                </div>
                <div class="form-group">
                    <label>Profit:</label>
                    <input type="text" name="Profit" id="Profit" value="<?= htmlspecialchars($row['Profit'] ?? 0) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Sales Person:</label>
                    <input type="text" name="sales_person" value="<?= htmlspecialchars($row['SalesPersonName'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Payment Status:</label>
                    <select name="PaymentStatus" id="paymentStatus" required>
                        <option value="Paid" <?= ($row['PaymentStatus'] ?? '') == 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Partially Paid" <?= ($row['PaymentStatus'] ?? '') == 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                        <option value="DUE" <?= ($row['PaymentStatus'] ?? '') == 'DUE' ? 'selected' : '' ?>>DUE</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Method:</label>
                    <select name="PaymentMethod" id="paymentMethod" required>
                        <option value="Cash Payment" <?= ($row['PaymentMethod'] ?? '') == 'Cash Payment' ? 'selected' : '' ?>>Cash Payment</option>
                        <option value="Card Payment" <?= ($row['PaymentMethod'] ?? '') == 'Card Payment' ? 'selected' : '' ?>>Card Payment</option>
                        <option value="Cheque Deposit" <?= ($row['PaymentMethod'] ?? '') == 'Cheque Deposit' ? 'selected' : '' ?>>Cheque Deposit</option>
                        <option value="Bank Deposit" <?= ($row['PaymentMethod'] ?? '') == 'Bank Deposit' ? 'selected' : '' ?>>Bank Deposit</option>
                        <option value="Cheque Clearing" <?= ($row['PaymentMethod'] ?? '') == 'Cheque Clearing' ? 'selected' : '' ?>>Cheque Clearing</option>
                        <option value="Mobile Banking(nagad)" <?= ($row['PaymentMethod'] ?? '') == 'Mobile Banking(nagad)' ? 'selected' : '' ?>>Mobile Banking (Nagad)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Seat Class:</label>
                    <select name="Class" id="seat" required>
                        <option value="Economy" <?= ($row['Class'] ?? '') == 'Economy' ? 'selected' : '' ?>>Economy Class</option>
                        <option value="Business" <?= ($row['Class'] ?? '') == 'Business' ? 'selected' : '' ?>>Business Class</option>
                        <option value="First" <?= ($row['Class'] ?? '') == 'First' ? 'selected' : '' ?>>First Class</option>
                        <option value="Premium" <?= ($row['Class'] ?? '') == 'Premium' ? 'selected' : '' ?>>Premium Economy</option>
                    </select>
                </div>
            </div>

            <div id="bankDetails" class="hidden">
                <div class="form-row">
                    <div class="form-group">
                        <label>Bank Name:</label>
                        <select name="BankName" id="bankDropdown">
                            <option value="">Select Bank</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Received Date:</label>
                        <input type="date" name="ReceivedDate" value="<?= htmlspecialchars($row['ReceivedDate'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Deposit Date:</label>
                        <input type="date" name="DepositDate" value="<?= htmlspecialchars($row['DepositDate'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Clearing Date:</label>
                        <input type="date" name="ClearingDate" value="<?= htmlspecialchars($row['ClearingDate'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Paid Amount:</label>
                    <input type="number" name="PaidAmount" id="paidAmount" value="<?= htmlspecialchars($row['PaidAmount'] ?? 0) ?>" step="0.01">
                </div>
                <div class="form-group">
                    <label>Due Amount:</label>
                    <input type="text" name="DueAmount" id="dueAmount" value="<?= htmlspecialchars($row['DueAmount'] ?? 0) ?>" readonly>
                </div>
            </div>

            <div class="form-row submit-button-wrapper">
                <div class="form-group">
                    <button type="submit" class="submit-btn">Update Sale</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function calculateProfit() {
            let billAmount = parseFloat(document.getElementById('billAmount').value) || 0;
            let netPayment = parseFloat(document.getElementById('NetPayment').value) || 0;
            let profit = billAmount - netPayment;
            document.getElementById('Profit').value = profit.toFixed(2);
        }

        function calculateDue() {
            let billAmount = parseFloat(document.getElementById('billAmount').value) || 0;
            let paidAmount = parseFloat(document.getElementById('paidAmount').value) || 0;
            let dueAmount = billAmount - paidAmount;
            document.getElementById('dueAmount').value = dueAmount.toFixed(2);
        }

        document.getElementById('billAmount').addEventListener('input', function() {
            calculateProfit();
            calculateDue();
        });
        document.getElementById('NetPayment').addEventListener('input', calculateProfit);
        document.getElementById('paidAmount').addEventListener('input', calculateDue);

        // Initial calculations on page load
        calculateProfit();
        calculateDue();
    </script>
</body>
</html>