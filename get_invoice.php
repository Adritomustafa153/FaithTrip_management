<?php
include 'db.php';
$id = $_GET['id'];
$invoice = $conn->query("SELECT * FROM invoices WHERE invoice_id = $id")->fetch_assoc();
$items = $conn->query("SELECT s.* FROM invoice_items ii JOIN sales s ON s.SaleID = ii.sell_id WHERE ii.invoice_id = $id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?= $invoice['invoice_number'] ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .invoice-box { padding: 30px; background: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,.15); margin: auto; max-width: 800px; }
        .travel-logo { width: 120px; }
    </style>
</head>
<body>
<div class="invoice-box">
    <div class="text-center">
        <img src="https://portal.faithtrip.net/companyLogo/gZdfl1728121001.jpg" alt="Travel Agency" class="travel-logo">
        <h2>Faith Travels and Tours LTD</h2>
        <p>Abedin Tower(level 5), Road 17, 35 Kamal Ataturk Avenue, Banani, Dhaka 1213 | info@faithtrip.net | +8809647649044, 01896459490</p>
        <hr>
        <h3>Invoice #<?= $invoice['invoice_number'] ?></h3>
        <p>Date: <?= $invoice['date'] ?></p>
    </div>
    <table class="table table-bordered mt-4">
        <thead><tr><th>SL</th><th>Travelers</th><th>Flight Info</th><th>Ticket Info</th><th>Price</th></tr></thead>
        <tbody>
            <?php $total = 0; $serial = 1; while($row = $items->fetch_assoc()): ?>      
            <tr>
                <td><?= $serial++ ?></td> <!-- Serial number -->
                <td><?= $row['description'] ?></td>
                <td><?= $row['amount']; $total += $row['amount']; ?></td>
            </tr>
            <?php endwhile; ?>
            <tr><td colspan="2">Total</td><td><?= $total ?></td></tr>
        </tbody>
    </table>
</div>
</body>
</html>
