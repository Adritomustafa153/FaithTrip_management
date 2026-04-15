<?php
session_start();
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    $stmt = $conn->prepare("INSERT INTO sales 
        (PartyName, section, PassengerName, airlines, TicketRoute, TicketNumber, IssueDate, FlightDate, ReturnDate, PNR,
         BillAmount, NetPayment, Profit, PaymentStatus, PaidAmount, DueAmount, PaymentMethod, BankName, BranchName, AccountNumber,
         ReceivedDate, DepositDate, ClearingDate, SalesPersonName, Class, Source, system, Remarks, created_by_user_id)
        VALUES (?, 'corporate', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Air Ticket Sale', ?)");

    $profit = $_POST['BillAmount'] - $_POST['NetPayment'];
    $dueAmount = $_POST['BillAmount'] - $_POST['PaidAmount'];

    $stmt->bind_param("sssssssssdddsddssssssssssi",
        $_POST['CompanyID'],
        $_POST['PassengerName'],
        $_POST['airlines'],
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
        $_POST['BranchName'],
        $_POST['AccountNumber'],
        $_POST['ReceivedDate'],
        $_POST['DepositDate'],
        $_POST['ClearingDate'],
        $_POST['SalesPersonName'],
        $_POST['Class'],
        $_POST['source_id'],
        $_POST['system'],
        $created_by
    );

    if ($stmt->execute()) {
        echo "Sale recorded successfully!";
        header('Location: invoice_list.php');
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>