<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prepare the SQL statement with "Counter Sell" as the PartyName
    $stmt = $conn->prepare("INSERT INTO sales 
        (PartyName,section, PassengerName, TicketRoute, TicketNumber, IssueDate, FlightDate, ReturnDate, 
        PNR, BillAmount, NetPayment, Profit, PaymentStatus, PaidAmount, DueAmount, 
        PaymentMethod, BankName, ReceivedDate, DepositDate, 
        ClearingDate, SalesPersonName,airlines,Class,Remarks,Source,system) 
        VALUES ('Counter Sell','counter', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Sell', ?, ?)");

    // Calculate Profit and Due Amount
    $profit = $_POST['BillAmount'] - $_POST['NetPayment'];
    $dueAmount = $_POST['BillAmount'] - $_POST['PaidAmount'];

    // Bind parameters correctly
    $stmt->bind_param(
        "sssssssdddsddssssssssss", 
        $_POST['PassengerName'], $_POST['TicketRoute'], $_POST['TicketNumber'], 
        $_POST['IssueDate'], $_POST['FlightDate'], $_POST['ReturnDate'], 
        $_POST['PNR'], $_POST['BillAmount'], $_POST['NetPayment'], $profit, 
        $_POST['PaymentStatus'], $_POST['PaidAmount'], $dueAmount, 
        $_POST['PaymentMethod'], $_POST['BankName'],
         $_POST['ReceivedDate'], $_POST['DepositDate'], 
        $_POST['ClearingDate'], $_POST['SalesPersonName'],
        $_POST['airlines'],$_POST['Class'],$_POST['source_id'],$_POST['system']
    );

    if ($stmt->execute()) {
        echo "<script>alert('Record deleted successfully!');</script>";
        header("Location: counter_sell_manual_insert.php");

    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>