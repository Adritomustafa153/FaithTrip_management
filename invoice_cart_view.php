<?php
// invoice_cart_view.php
include 'db.php';

$sales = [];
$total = 0;
$ait = 0;
$gt = 0;

if (!empty($_SESSION['invoice_cart'])) {
    $id_list = implode(",", array_map('intval', $_SESSION['invoice_cart']));
    $query = "SELECT * FROM sales WHERE SaleID IN ($id_list)";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
        $total += $row['BillAmount'];
    }
    $ait = $total * 0.003;
    $gt = $total + $ait;
}
?>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>SL</th>
            <th>Passenger</th>
            <th>Flight Info</th>
            <th>Ticket Info</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
    <?php $serial = 1; foreach ($sales as $row): ?>
        <tr>
            <td><?= $serial++ ?></td>
            <td><?= htmlspecialchars($row['PassengerName']) ?></td>
            <td>
                Route: <?= htmlspecialchars($row['TicketRoute']) ?><br>
                Airline: <?= htmlspecialchars($row['airlines']) ?><br>
                Departure: <?= htmlspecialchars($row['FlightDate']) ?><br>
                Return: <?= htmlspecialchars($row['ReturnDate']) ?>
            </td>
            <td>
                Ticket No: <?= htmlspecialchars($row['TicketNumber']) ?><br>
                PNR: <?= htmlspecialchars($row['PNR']) ?><br>
                Issued: <?= htmlspecialchars($row['IssueDate']) ?><br>
                Seat Class: <?= htmlspecialchars($row['PNR']) ?>
            </td>
            <td><?= number_format($row['BillAmount'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
        <tr>
            <td colspan="4" align="right">AIT</td>
            <td><?= number_format($ait, 2) ?></td>
        </tr>
        <tr>
            <td colspan="4" align="right"><b>Total</b></td>
            <td><b><?= number_format($gt, 2) ?></b></td>
        </tr>
    </tbody>
</table>
