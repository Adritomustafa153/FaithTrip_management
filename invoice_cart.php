<?php
session_start();
include 'db.php'; // Update this path if your DB connection file is named differently

// Initialize cart session if not set
if (!isset($_SESSION['invoice_cart'])) {
    $_SESSION['invoice_cart'] = [];
}

// Add item to invoice cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_id'])) {
    $sell_id = intval($_POST['sell_id']);
    if (!in_array($sell_id, $_SESSION['invoice_cart'])) {
        $_SESSION['invoice_cart'][] = $sell_id;
    }
}

// Prepare to fetch sales data
$sell_data = [];
if (!empty($_SESSION['invoice_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['invoice_cart']));
    $query = "SELECT * FROM sales WHERE SaleID IN ($id_list)";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sell_data[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice Cart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f5f7fa;
        }
        .invoice-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(92, 95, 122, 0.1);
        }
        .total-row {
            font-weight: bold;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
<div class="invoice-container">
    <h2 class="mb-4 text-center">Invoice Preview</h2>

    <?php if (!empty($sell_data)): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr class="bg-success">
                    <th>SL</th>
                    <th>Traveler Name</th>
                    <th>Flight Info</th>
                    <th>Ticket Info</th>
                    <th>Price</th>
                    <th>Remarks</th>
                    <th>Total</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php $total = 0; foreach ($sell_data as $row): ?>
                    <tr>
                        <td><?php echo $row['SaleID']; ?></td>
                        <td><?php echo htmlspecialchars($row['PassengerName']); ?></td>
                        <td><?php echo htmlspecialchars($row['airlines']); ?>
                        <?php echo htmlspecialchars($row['TicketRoute']); ?><br>
                        <?php echo htmlspecialchars($row['FlightDate']); ?><br>
                        <?php echo htmlspecialchars($row['ReturnDate']); ?>
                    </td>

                        <td><?php echo htmlspecialchars($row['TicketNumber']); ?><br>
                        <?php echo htmlspecialchars($row['IssueDate']); ?><br>
                        <?php echo htmlspecialchars($row['PNR']); ?>
                    </td>
                        <td><?php echo number_format($row['BillAmount'], 2); ?></td>
                    </tr>
                    <?php $total += $row['BillAmount']; ?>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2" class="text-end">Total</td>
                    <td><?php echo number_format($total, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="text-end mt-4">
            <button class="btn btn-success" onclick="confirmInvoice()">Generate Invoice</button>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">No items in the invoice cart.</div>
    <?php endif; ?>
</div>

<script>
function confirmInvoice() {
    if (confirm("Are you sure you want to generate this invoice?")) {
        window.location.href = "generate_invoice.php";
    }
}
</script>
</body>
</html>
