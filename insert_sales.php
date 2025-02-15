<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("INSERT INTO sales 
        (PartyName, PassengerName,airlines, TicketRoute, TicketNumber, IssueDate, FlightDate, ReturnDate, PNR, BillAmount, NetPayment, Profit, PaymentStatus, 
        PaidAmount, DueAmount, PaymentMethod, BankName, BranchName, AccountNumber, ReceivedDate, DepositDate, ClearingDate, SalesPersonName)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $profit = $_POST['BillAmount'] - $_POST['NetPayment'];
    $dueAmount = $_POST['BillAmount'] - $_POST['PaidAmount'];

    $stmt->bind_param("sssssssssdddsddssssssss", 
        $_POST['AgentID'], $_POST['PassengerName'], $_POST['airlines'],$_POST['TicketRoute'], $_POST['TicketNumber'], $_POST['IssueDate'], $_POST['FlightDate'], $_POST['ReturnDate'], $_POST['PNR'], $_POST['BillAmount'], $_POST['NetPayment'], $profit, $_POST['PaymentStatus'], $_POST['PaidAmount'], $dueAmount, $_POST['PaymentMethod'], $_POST['BankName'], $_POST['BranchName'], $_POST['AccountNumber'], $_POST['ReceivedDate'], $_POST['DepositDate'], $_POST['ClearingDate'], $_POST['SalesPersonName']);

    if ($stmt->execute()) {
        echo "<script>alert('Sales Record Updated');</script>";
        header("Location: invoice_list.php");
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
