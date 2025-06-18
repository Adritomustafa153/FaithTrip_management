<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // $partyName = $_POST['agentname'];
    
    // Step 1: Lookup AgentID from agents table
    // $agentStmt = $conn->prepare("SELECT AgentsID FROM agents WHERE AgentName = ?");
    // $agentStmt->bind_param("s", $partyName);
    // $agentStmt->execute();
    // $agentResult = $agentStmt->get_result();

    // if ($agentResult->num_rows === 0) {
    //     echo "<script>alert('Agent not found. Please check the Party Name.'); history.back();</script>";
    //     exit;
    // }

    // $agentRow = $agentResult->fetch_assoc();
    // $agentID = $agentRow['AgentsID'];

    // Step 2: Sanitize numeric values
    $billAmount = floatval($_POST['BillAmount']);
    $netPayment = floatval($_POST['NetPayment']);
    $paidAmount = floatval($_POST['PaidAmount']);
    $profit = $billAmount - $netPayment;
    $dueAmount = $billAmount - $paidAmount;

    // Step 3: Insert into sales
    $stmt = $conn->prepare("INSERT INTO sales (
        section, PartyName, PassengerName, airlines, TicketRoute, TicketNumber, Class,
        IssueDate, FlightDate, ReturnDate, PNR,
        BillAmount, NetPayment, Profit, PaymentStatus, PaidAmount, DueAmount,
        PaymentMethod, BankName, BranchName, AccountNumber,
        ReceivedDate, DepositDate, ClearingDate,
        SalesPersonName, Remarks
    ) VALUES (
        'Agent', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, 'Reissue'
    )");

    $stmt->bind_param(
        "ssssssssssdddsddssssssss",
        $_POST['agentname'],
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
        $_POST['BranchName'],
        $_POST['AccountNumber'],
        $_POST['ReceivedDate'],
        $_POST['DepositDate'],
        $_POST['ClearingDate'],
        $_POST['SalesPersonName'],
      
    );

    if ($stmt->execute()) {
        echo "<script>alert('Agent reissue inserted successfully!'); window.location.href='invoice_list.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $agentStmt->close();
}

$conn->close();
?>
