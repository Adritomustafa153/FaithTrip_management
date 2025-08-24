<?php
include 'db.php'; // This should define and initialize $conn

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and extract numeric values
    $billAmount = floatval($_POST['BillAmount']);
    $netPayment = floatval($_POST['NetPayment']);
    $paidAmount = floatval($_POST['PaidAmount']);

    $profit = $billAmount - $netPayment;
    $dueAmount = $billAmount - $paidAmount;

    // Prepare SQL
    $stmt = $conn->prepare("INSERT INTO sales 
        (
            section, PartyName, PassengerName, airlines, TicketRoute, TicketNumber, Class, IssueDate,
            FlightDate, ReturnDate, PNR, BillAmount, NetPayment, Profit, PaymentStatus, PaidAmount,
            DueAmount, PaymentMethod, BankName, BranchName, AccountNumber,
            ReceivedDate, DepositDate, ClearingDate, SalesPersonName, Source, system, Remarks
        ) 
        VALUES (
            'counter sell', 'counter sell', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Reissue'
        )");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "sssssssssdddsddssssssssss",
        $_POST['passenger_name'], $_POST['airlines'], $_POST['ticket_route'], $_POST['TicketNumber'],
        $_POST['Class'], $_POST['issueDate'], $_POST['journey_date'], $_POST['return_date'],
        $_POST['pnr'], $billAmount, $netPayment, $profit,
        $_POST['PaymentStatus'], $paidAmount, $dueAmount,
        $_POST['PaymentMethod'], $_POST['BankName'], $_POST['BranchName'], $_POST['AccountNumber'],
        $_POST['ReceivedDate'], $_POST['DepositDate'], $_POST['ClearingDate'], $_POST['sales_person'], $_POST['source'], $_POST['system']
    );

    if ($stmt->execute()) {
        echo "<script>alert('Reissue sale inserted successfully!'); window.location.href='reissue.php';</script>";
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
