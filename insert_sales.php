<?php
session_start();

$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    $profit = $_POST['BillAmount'] - $_POST['NetPayment'];
    $dueAmount = $_POST['BillAmount'] - $_POST['PaidAmount'];

    $sql = "INSERT INTO sales (
        PartyName, section, PassengerName, airlines, TicketRoute, TicketNumber,
        IssueDate, FlightDate, ReturnDate, PNR, BillAmount, NetPayment, Profit,
        PaymentStatus, PaidAmount, DueAmount, PaymentMethod, BankName, BranchName,
        AccountNumber, ReceivedDate, DepositDate, ClearingDate, SalesPersonName,
        Class, Source, system, Remarks, created_by_user_id
    ) VALUES (
        ?, 'agent', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Air Ticket Sale', ?
    )";

    $stmt = $conn->prepare($sql);

    // Type string: 27 characters: 9 s, 3 d, 1 s, 2 d, 11 s, 1 i = 9+3+1+2+11+1=27
    $stmt->bind_param(
        "sssssssssdddsddsssssssssssi",
        $_POST['AgentID'],          // PartyName
        $_POST['PassengerName'],    // PassengerName
        $_POST['airlines'],         // airlines
        $_POST['TicketRoute'],      // TicketRoute
        $_POST['TicketNumber'],     // TicketNumber
        $_POST['IssueDate'],        // IssueDate
        $_POST['FlightDate'],       // FlightDate
        $_POST['ReturnDate'],       // ReturnDate
        $_POST['PNR'],              // PNR
        $_POST['BillAmount'],       // BillAmount
        $_POST['NetPayment'],       // NetPayment
        $profit,                    // Profit
        $_POST['PaymentStatus'],    // PaymentStatus
        $_POST['PaidAmount'],       // PaidAmount
        $dueAmount,                 // DueAmount
        $_POST['PaymentMethod'],    // PaymentMethod
        $_POST['BankName'],         // BankName
        $_POST['BranchName'],       // BranchName
        $_POST['AccountNumber'],    // AccountNumber
        $_POST['ReceivedDate'],     // ReceivedDate
        $_POST['DepositDate'],      // DepositDate
        $_POST['ClearingDate'],     // ClearingDate
        $_POST['SalesPersonName'],  // SalesPersonName
        $_POST['Class'],            // Class
        $_POST['source_id'],        // Source
        $_POST['system'],           // system
        $created_by                 // created_by_user_id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Sales Record Inserted');</script>";
        header("Location: invoice_list.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>