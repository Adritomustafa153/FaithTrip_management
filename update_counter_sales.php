<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $sale_id = $_POST['SaleID'];
    $section = $_POST['section'];
    $party_name = $_POST['PartyName'];
    $passenger_name = $_POST['PassengerName'];
    $airlines = $_POST['airlines'];
    $ticket_route = $_POST['TicketRoute'];
    $ticket_number = $_POST['TicketNumber'];
    $class = $_POST['Class'];
    $issue_date = $_POST['IssueDate'];
    $flight_date = $_POST['FlightDate'];
    $return_date = $_POST['ReturnDate'];
    $pnr = $_POST['PNR'];
    $bill_amount = $_POST['BillAmount'];
    $net_payment = $_POST['NetPayment'];
    $profit = $_POST['Profit'];
    $source = $_POST['source_id'];
    $system = $_POST['system'];
    $payment_status = $_POST['PaymentStatus'];
    $paid_amount = $_POST['PaidAmount'];
    $due_amount = $_POST['DueAmount'];
    $payment_method = $_POST['PaymentMethod'];
    $bank_name = $_POST['BankName'];
    $branch_name = $_POST['BranchName'];
    $account_number = $_POST['AccountNumber'];
    $received_date = $_POST['ReceivedDate'];
    $deposit_date = $_POST['DepositDate'];
    $clearing_date = $_POST['ClearingDate'];
    $sales_person = $_POST['SalesPersonName'];
    $remarks = $_POST['Remarks'];
    $refund_date = $_POST['refund_date'];
    
    // Calculate days passed from issue date
    $days_passed = null;
    if (!empty($issue_date)) {
        $issue_date_obj = new DateTime($issue_date);
        $current_date = new DateTime();
        $days_passed = $current_date->diff($issue_date_obj)->format('%a');
    }
    
    // Update query
    $query = "UPDATE sales SET 
        section = '$section',
        PartyName = " . (($section == 'counter') ? "NULL" : "'$party_name'") . ",
        PassengerName = '$passenger_name',
        airlines = '$airlines',
        TicketRoute = '$ticket_route',
        TicketNumber = '$ticket_number',
        Class = '$class',
        IssueDate = '$issue_date',
        DaysPassedFromIssue = $days_passed,
        FlightDate = '$flight_date',
        ReturnDate = " . (empty($return_date) ? "NULL" : "'$return_date'") . ",
        PNR = '$pnr',
        BillAmount = $bill_amount,
        NetPayment = $net_payment,
        Profit = $profit,
        Source = '$source',
        system = '$system',
        PaymentStatus = '$payment_status',
        PaidAmount = $paid_amount,
        DueAmount = $due_amount,
        PaymentMethod = '$payment_method',
        BankName = " . (empty($bank_name) ? "NULL" : "'$bank_name'") . ",
        BranchName = " . (empty($branch_name) ? "NULL" : "'$branch_name'") . ",
        AccountNumber = " . (empty($account_number) ? "NULL" : "'$account_number'") . ",
        ReceivedDate = " . (empty($received_date) ? "NULL" : "'$received_date'") . ",
        DepositDate = " . (empty($deposit_date) ? "NULL" : "'$deposit_date'") . ",
        ClearingDate = " . (empty($clearing_date) ? "NULL" : "'$clearing_date'") . ",
        SalesPersonName = '$sales_person',
        Remarks = '$remarks',
        refund_date = " . (empty($refund_date) ? "NULL" : "'$refund_date'") . "
        WHERE SaleID = $sale_id";
    
    if (mysqli_query($conn, $query)) {
        echo "Record updated successfully. <a href='edit_sales.php'>Edit another record</a>";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}

$conn->close();
?>