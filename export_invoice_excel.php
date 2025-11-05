<?php
include 'db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=invoice_export.xls");

$company_filter = $_GET['company'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$where = "WHERE Remarks = 'Air Ticket Sale'";
if (!empty($company_filter)) {
    $where .= " AND PartyName = '$company_filter'";
}
if (!empty($from_date) && !empty($to_date)) {
    $where .= " AND IssueDate BETWEEN '$from_date' AND '$to_date'";
}

$query = "
    SELECT s.PartyName, s.PassengerName, s.IssueDate, s.PaidAmount as debit, 
           s.BillAmount as credit, (s.BillAmount - IFNULL(s.PaidAmount, 0)) as balance,
           s.airlines, s.PNR, s.TicketRoute, s.TicketNumber
    FROM sales s
    $where
    ORDER BY s.IssueDate DESC
";

$result = mysqli_query($conn, $query);

echo "SL\tParty Name\tPassenger Name\tIssue Date\tDebit\tCredit\tBalance\tAirlines\tPNR\tRoute\tTicket Number\n";
$sl = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $debit = $row['debit'] ?? 0;
    $credit = $row['credit'] ?? 0;
    $balance = $row['balance'] ?? 0;
    
    echo "$sl\t{$row['PartyName']}\t{$row['PassengerName']}\t{$row['IssueDate']}\t{$debit}\t{$credit}\t{$balance}\t{$row['airlines']}\t{$row['PNR']}\t{$row['TicketRoute']}\t{$row['TicketNumber']}\n";
    $sl++;
}
?>