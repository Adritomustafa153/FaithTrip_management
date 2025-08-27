<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("INSERT INTO sales 
        (PartyName,section, PassengerName, airlines, TicketRoute, TicketNumber, IssueDate, FlightDate, ReturnDate, PNR,
         BillAmount, NetPayment, Profit, PaymentStatus, PaidAmount, DueAmount, PaymentMethod, BankName, BranchName, AccountNumber,
          ReceivedDate, DepositDate, ClearingDate, SalesPersonName, Class,Source,system,Remarks)
        VALUES (?, 'corporate',?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Air Ticket Sale')");

    $profit = $_POST['BillAmount'] - $_POST['NetPayment'];
    $dueAmount = $_POST['BillAmount'] - $_POST['PaidAmount'];

    $stmt->bind_param("sssssssssdddsddsssssssssss", 
        $_POST['CompanyID'], $_POST['PassengerName'],$_POST['airlines'], $_POST['TicketRoute'], $_POST['TicketNumber'], 
        $_POST['IssueDate'], $_POST['FlightDate'], $_POST['ReturnDate'], $_POST['PNR'], $_POST['BillAmount'], $_POST['NetPayment'],
         $profit, $_POST['PaymentStatus'], $_POST['PaidAmount'], $dueAmount, $_POST['PaymentMethod'], $_POST['BankName'],
          $_POST['BranchName'], $_POST['AccountNumber'], $_POST['ReceivedDate'], $_POST['DepositDate'], $_POST['ClearingDate'], 
          $_POST['SalesPersonName'], $_POST['Class'], $_POST['source_id'],$_POST['system'] );

    if ($stmt->execute()) {
        echo "Sale recorded successfully!";
        header('invoice_list.php');
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
