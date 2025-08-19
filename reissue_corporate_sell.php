<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and prepare numeric inputs
    $billAmount = floatval($_POST['BillAmount']);
    $netPayment = floatval($_POST['NetPayment']);
    $paidAmount = floatval($_POST['PaidAmount']);

    $profit = $billAmount - $netPayment;
    $dueAmount = $billAmount - $paidAmount;

    $stmt = $conn->prepare("INSERT INTO sales (
        section, PartyName, PassengerName, airlines, TicketRoute, TicketNumber, Class,
        IssueDate, FlightDate, ReturnDate, PNR,
        BillAmount, NetPayment, Profit, PaymentStatus, PaidAmount, DueAmount,
        PaymentMethod, BankName,
        ReceivedDate, DepositDate, ClearingDate,
        SalesPersonName, Source, invoice_number, Remarks
    ) VALUES (
        'Corporate', ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, ?, 'Reissue'
    )");

    $stmt->bind_param(
        "ssssssssssdddsddssssssss",
        $_POST['partyname'],
        $_POST['passengername'],
        $_POST['airlines'],
        $_POST['ticket_route'],
        $_POST['TicketNumber'],
        $_POST['Class'],
        $_POST['IssueDate'],
        $_POST['FlightDate'],
        $_POST['ReturnDate'],
        $_POST['PNR'],
        $billAmount,
        $netPayment,
        $profit,
        $_POST['PaymentStatus'],
        $paidAmount,
        $dueAmount,
        $_POST['PaymentMethod'],
        $_POST['BankName'],
        $_POST['ReceivedDate'],
        $_POST['DepositDate'],
        $_POST['ClearingDate'],
        $_POST['SalesPersonName'],
        $_POST['source'],
        $_POST['invoice_number']
    );

    if ($stmt->execute()) {
        echo "<script>alert('Reissue sale inserted successfully!'); window.location.href='invoice_list.php';</script>";
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
