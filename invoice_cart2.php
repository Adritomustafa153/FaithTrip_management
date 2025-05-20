<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php'; // adjust path as needed

// Initialize cart
if (!isset($_SESSION['invoice_cart'])) {
    $_SESSION['invoice_cart'] = [];
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_id'])) {
    $sell_id = intval($_POST['sell_id']);
    if (!in_array($sell_id, $_SESSION['invoice_cart'])) {
        $_SESSION['invoice_cart'][] = $sell_id;
    }
}

// Handle item deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if (($key = array_search($delete_id, $_SESSION['invoice_cart'])) !== false) {
        unset($_SESSION['invoice_cart'][$key]);
    }
}

// Handle cart clearing
if (isset($_GET['clear_cart']) && $_GET['clear_cart'] == '1') {
    $_SESSION['invoice_cart'] = [];
}

// Fetch cart data
$sales = [];
if (!empty($_SESSION['invoice_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['invoice_cart']));
    $query = "SELECT * FROM sales WHERE SaleID IN ($id_list)";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="mb-4">Invoice Cart</h2>

        <div class="card p-3 mb-3">
<form method="POST" action="generate_invoice.php">
        <div class="row">
            <div class="col-md-4">
                <label>Type</label>
                <select id="clientType" class="form-select" name="clientType" required>
                    <option value="">Select Type</option>
                    <option value="company">Company</option>
                    <option value="agent">Agent</option>
                    <option value="passenger">Counter Sell</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>Name</label>
                <select id="clientName" class="form-select" name="ClientName" required></select>
            </div>
            <div class="col-md-4">
                <label>
                    Address <input type="checkbox" id="manualAddress" onchange="toggleManualAddress()"> Add Manually
                </label>
                <input type="text" id="address" name="address" class="form-control" required>
            </div>
            <div class="col-md-4" style="margin-top: 10px;">
                <label>
                    Email : <input type="text" id="email" name="client_email" readonly> <!-- Add this -->
                </label>
            </div>
        </div>
   
</div>


        <?php if (!empty($sales)): ?>
            <table class="table table-bordered table-striped">
                <thead class="bg-warning text-white">
                    <tr style="color: blue;">
                        <th>SL</th>
                        <th>Travelers</th>
                        <th>Flight Info</th>
                        <th>Ticket Info</th>
                        <th>Price</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                     <?php $total = 0; $serial = 1;  foreach ($sales as $row): ?>
                        <tr>
                            <td><?= $serial++ ?></td> <!-- Serial number -->
                            <td><?= htmlspecialchars($row['PassengerName']) ?></td>
                            <td>Travel Route : <b><?= htmlspecialchars($row['TicketRoute']) ?><br></b>
                            Airlines : <b><?= htmlspecialchars($row['airlines']) ?><br></b>
                            Deperture Date : <b><?= htmlspecialchars($row['FlightDate']) ?><br></b>
                            Return Date : <b><?= htmlspecialchars($row['ReturnDate']) ?><br></b>

                        </td>
                            <td>Ticket Number : <b><?= htmlspecialchars($row['TicketNumber']) ?><br></b>
                            PNR : <b><?= htmlspecialchars($row['PNR']) ?><br></b>
                            Ticket issue Date : <b><?= htmlspecialchars($row['IssueDate']) ?><br></b>
                            Seat Class : <b><?= htmlspecialchars($row['Class']) ?></b>

                        </td>
                            <td><?= number_format($row['BillAmount'], 2) ?></td>
                            <td>
                               
                            </td>
                            <td>
                                 <a href="invoice_cart2.php?delete_id=<?= $row['SaleID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?')">Delete</a>
                            </td>
                        </tr>
                            <?php $total += $row['BillAmount']; ?>
                    <?php endforeach; ?>
                                    <tr class="total-row">
                    <td colspan="4" class="text-end">Selling</td>
                    <td><?php echo number_format($total, 2); ?></td>
                </tr>
                <tr>
                    <?php $ait = $total * 0.003 ?>
                    <td colspan="4" class="text-end">AIT</td>
                    <td><?php echo number_format($ait, 2); ?></td>
                </tr>
                    <tr>
                    <?php $gt = $total + $ait ?>
                    <td colspan="4" class="text-end">Total</td>
                    <td><?php echo number_format($gt, 2); ?></td>
                </tr>
                </tbody>
            </table>

            <div class="d-flex justify-content-between mt-3">
                <a href="invoice_cart2.php?clear_cart=1" class="btn btn-warning" onclick="return confirm('Clear the entire cart?')">Clear Cart</a>
                <a href="invoice_list.php"><button type="button" style="font-size: 14px;border-radius: 8px;padding: 10px 20px;background-color:rgb(5, 200, 50);box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);">Home</button></a>
                <button type="submit" onclick="return confirm('Are you sure you want to generate the invoice?')" class="btn btn-primary">Generate Invoice</button>
            
            </div>
        <?php else: ?>
            <div class="alert alert-info">No items in the invoice cart.</div>
        <?php endif; ?>
    </div>

<script>
function toggleManualAddress() {
    document.getElementById('address').readOnly = !document.getElementById('manualAddress').checked;
}

document.getElementById('clientType').addEventListener('change', function () {
    let type = this.value;
    fetch(`fetch_names.php?type=${type}`)
        .then(res => res.json())
        .then(data => {
            let options = '<option value="">Select</option>';
            data.forEach(item => {
                options += `<option value="${item.name}" data-address="${item.address}" data-email="${item.email}">${item.name}</option>`;
            });
            document.getElementById('clientName').innerHTML = options;
        });
});

document.getElementById('clientName').addEventListener('change', function () {
    let selected = this.options[this.selectedIndex];
    let address = selected.getAttribute('data-address');
    let email = selected.getAttribute('data-email');

    if (!document.getElementById('manualAddress').checked) {
        document.getElementById('address').value = address;
    }
    document.getElementById('email').value = email || '';
});
</script>
 </form>
</body>
</html>
