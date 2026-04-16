<?php
session_start();
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    // Extract all POST values to simple variables
    $companyID       = $_POST['CompanyID'];
    $passengerName   = $_POST['PassengerName'];
    $airlines        = $_POST['airlines'];
    $ticketRoute     = $_POST['TicketRoute'];
    $ticketNumber    = $_POST['TicketNumber'];
    $issueDate       = $_POST['IssueDate'];
    $flightDate      = $_POST['FlightDate'];
    $returnDate      = $_POST['ReturnDate'];
    $pnr             = $_POST['PNR'];
    $billAmount      = $_POST['BillAmount'];
    $netPayment      = $_POST['NetPayment'];
    $paymentStatus   = $_POST['PaymentStatus'];
    $paidAmount      = $_POST['PaidAmount'];
    $paymentMethod   = $_POST['PaymentMethod'];
    $bankName        = $_POST['BankName'] ?? '';
    $branchName      = $_POST['BranchName'] ?? '';
    $accountNumber   = $_POST['AccountNumber'] ?? '';
    $receivedDate    = $_POST['ReceivedDate'] ?? '';
    $depositDate     = $_POST['DepositDate'] ?? '';
    $clearingDate    = $_POST['ClearingDate'] ?? '';
    $salesPersonName = $_POST['SalesPersonName'];
    $class           = $_POST['Class'];
    $sourceId        = $_POST['source_id'];
    $system          = $_POST['system'];

    $profit    = $billAmount - $netPayment;
    $dueAmount = $billAmount - $paidAmount;

    $stmt = $conn->prepare("INSERT INTO sales 
        (PartyName, section, PassengerName, airlines, TicketRoute, TicketNumber, IssueDate, FlightDate, ReturnDate, PNR,
         BillAmount, NetPayment, Profit, PaymentStatus, PaidAmount, DueAmount, PaymentMethod, BankName, BranchName, AccountNumber,
         ReceivedDate, DepositDate, ClearingDate, SalesPersonName, Class, Source, system, Remarks, created_by_user_id)
        VALUES (?, 'corporate', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Air Ticket Sale', ?)");

    // Dynamically build type string: 26 strings + 1 integer = 27 characters
    $typeString = str_repeat('s', 26) . 'i';

    $stmt->bind_param($typeString,
        $companyID, $passengerName, $airlines, $ticketRoute, $ticketNumber,
        $issueDate, $flightDate, $returnDate, $pnr,
        $billAmount, $netPayment, $profit,
        $paymentStatus, $paidAmount, $dueAmount,
        $paymentMethod,
        $bankName, $branchName, $accountNumber,
        $receivedDate, $depositDate, $clearingDate,
        $salesPersonName, $class, $sourceId, $system,
        $created_by
    );

    if ($stmt->execute()) {
        header('Location: invoice_list.php');
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>