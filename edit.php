<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = intval($_GET['id']);
$salesQuery = "SELECT * FROM sales WHERE SaleID=$id";
$result = $conn->query($salesQuery);
$row = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $company = $_POST['company'];
    $invoice = $_POST['invoice'];
    $pessenger_name = $_POST['name'];
    $status = $_POST['status'];
    $price = $_POST['price'];
    $pnr = $_POST['date'];
    $ticket_number = $_POST['ticket'];

    $updateQuery = "UPDATE sales SET PartyName='$company', invoice_number='$invoice', 
                    PassengerName='$pessenger_name', PaymentStatus='$status', TicketNumber='$ticket_number', BillAmount='$price', PNR='$pnr' WHERE SaleID ='$id'";

    if ($conn->query($updateQuery) === TRUE) {
        echo "<script>alert('Record updated successfully!'); window.location='invoice_list.php';</script>";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>

<form method="POST">
    <label for="">Company: </label><input type="text" name="company" placeholder="Company Name"value="<?= htmlspecialchars($row['PartyName']) ?>" required><br>
    <label for="">Invoice Number: </label><input type="text" name="invoice" placeholder="Invoice Number" value="<?= htmlspecialchars($row['invoice_number']) ?>" required><br>
    <label for="">Pessenger Name: </label><input type="text" name="name" placeholder="Passenger Name" value="<?= htmlspecialchars($row['PassengerName']) ?>" required><br>
    <label for="">Payment Status: </label><input type="text" name="status" placeholder="Payment Status" value="<?= htmlspecialchars($row['PaymentStatus']) ?>" required><br>
    <label for="">Bill: </label><input type="number" step="0.01" name="price" placeholder="Amount " value="<?= htmlspecialchars($row['BillAmount']) ?>" required><br>
    <label for="">PNR: </label><input type="text" name="date" placeholder="PNR" value=" <?= htmlspecialchars($row['PNR']) ?>" required><br>
    <label for="">Ticket Number: </label><input type="text" name="ticket" placeholder="Ticket Number" value="<?= htmlspecialchars($row['TicketNumber']) ?>" required><br>
    
    <button type="submit" name="update">Update</button>
</form>

<?php $conn->close(); ?>
