<?php
include 'db_connection.php'; // Your DB connection

// Handle update request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['sale_id'])) {
    $sale_id = $_POST['sale_id'];
    $passenger_name = $_POST['passenger_name'];
    $ticket_route = $_POST['ticket_route'];
    $ticket_number = $_POST['TicketNumber'];
    $airlines = $_POST['airlines'];
    $pnr = $_POST['pnr'];
    $issueDate = $_POST['issueDate'];
    $journey_date = $_POST['journey_date'];
    $return_date = $_POST['return_date'];
    $bill_amount = $_POST['BillAmount'];
    $net_payment = $_POST['NetPayment'];
    $profit = $_POST['Profit'];
    $sales_person = $_POST['sales_person'];
    $payment_status = $_POST['PaymentStatus'];
    $payment_method = $_POST['PaymentMethod'];
    $seat_class = $_POST['Class'];
    $paid_amount = $_POST['PaidAmount'];
    $due_amount = $_POST['DueAmount'];
    $bank_name = $_POST['BankName'] ?? null;
    $received_date = $_POST['ReceivedDate'] ?? null;
    $deposit_date = $_POST['DepositDate'] ?? null;
    $clearing_date = $_POST['ClearingDate'] ?? null;
    $remarks = 'Sell';

    $stmt = $conn->prepare("
        UPDATE sales SET 
            PassengerName = ?, TicketRoute = ?, TicketNumber = ?, Airlines = ?, PNR = ?, 
            IssueDate = ?, FlightDate = ?, ReturnDate = ?, BillAmount = ?, NetPayment = ?, 
            Profit = ?, SalesPersonName = ?, PaymentStatus = ?, PaymentMethod = ?, Class = ?, 
            PaidAmount = ?, DueAmount = ?, BankName = ?, ReceivedDate = ?, DepositDate = ?, 
            ClearingDate = ?
        WHERE SaleID = ?
    ");

    $stmt->bind_param("sssssssddddssssddsssssi", 
        $passenger_name, $ticket_route, $ticket_number, $airlines, $pnr,
        $issueDate, $journey_date, $return_date, $bill_amount, $net_payment,
        $profit, $sales_person, $payment_status, $payment_method, $seat_class,
        $paid_amount, $due_amount, $bank_name, $received_date, $deposit_date, 
        $clearing_date, $remarks, $sale_id
    );

    if ($stmt->execute()) {
        echo "<script>alert('✅ information updated successfully.'); window.location.href='counter_sell_list.php';</script>";
        exit;
    } else {
        echo "❌ Update failed: " . $stmt->error;
    }
    $stmt->close();
}
