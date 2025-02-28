<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prepare the SQL statement with "Counter Sell" as the PartyName
    $stmt = $conn->prepare("INSERT INTO hotel 
        (PartyName, pessengerName, hotelName, country, address, issue_date,hotel_category,room_type,room_category, checkin_date, 
        checkout_date, reference_number, selling_price, net_price, profit, payment_status, paid_amount, 
        due_amount, payment_method, bank_name, deposit_date, 
        clearing_date, issued_by) 
        VALUES ('Counter Sell', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Calculate Profit and Due Amount
    $profit = $_POST['BillAmount'] - $_POST['NetPayment'];
    $dueAmount = $_POST['BillAmount'] - $_POST['PaidAmount'];

    // Bind parameters correctly
    $stmt->bind_param(
        "sssssssssssdddsddsssss", 
        $_POST['PassengerName'], $_POST['hotelName'], $_POST['country'], $_POST['address'],
        $_POST['IssueDate'],  $_POST['hotelCategory'],  $_POST['roomType'],  $_POST['roomCategory'], $_POST['checkindate'], $_POST['checkoutdate'], 
        $_POST['bookingId'], $_POST['BillAmount'], $_POST['NetPayment'], $profit, 
        $_POST['PaymentStatus'], $_POST['PaidAmount'], $dueAmount, 
        $_POST['PaymentMethod'], $_POST['BankName'], $_POST['DepositDate'], 
        $_POST['ClearingDate'], $_POST['SalesPersonName']
    );

    if ($stmt->execute()) {
        echo "<script>alert('Record deleted successfully!');</script>";
        header("Location: hotel_sales.php");

    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>