<?php
session_start();
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    $stmt = $conn->prepare("INSERT INTO sales 
        (PartyName, section, PassengerName, TicketRoute, TicketNumber, IssueDate, FlightDate, ReturnDate, 
         PNR, BillAmount, NetPayment, Profit, PaymentStatus, PaidAmount, DueAmount, 
         PaymentMethod, BankName, ReceivedDate, DepositDate, 
         ClearingDate, SalesPersonName, airlines, Class, Remarks, Source, system, created_by_user_id) 
        VALUES ('Counter Sell', 'counter', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Air Ticket Sale', ?, ?, ?)");

    $profit = $_POST['BillAmount'] - $_POST['NetPayment'];
    $dueAmount = $_POST['BillAmount'] - $_POST['PaidAmount'];

    $stmt->bind_param(
        "sssssssdddsddssssssssssi",
        $_POST['PassengerName'],
        $_POST['TicketRoute'],
        $_POST['TicketNumber'],
        $_POST['IssueDate'],
        $_POST['FlightDate'],
        $_POST['ReturnDate'],
        $_POST['PNR'],
        $_POST['BillAmount'],
        $_POST['NetPayment'],
        $profit,
        $_POST['PaymentStatus'],
        $_POST['PaidAmount'],
        $dueAmount,
        $_POST['PaymentMethod'],
        $_POST['BankName'],
        $_POST['ReceivedDate'],
        $_POST['DepositDate'],
        $_POST['ClearingDate'],
        $_POST['SalesPersonName'],
        $_POST['airlines'],
        $_POST['Class'],
        $_POST['source_id'],
        $_POST['system'],
        $created_by
    );

    if ($stmt->execute()) {
        header("Location: counter_sell_manual_insert.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>