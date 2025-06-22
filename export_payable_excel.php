<?php
include 'db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=payable_export.xls");

$source_filter = $_GET['source'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$where = "WHERE 1=1";
if (!empty($source_filter)) {
    $where .= " AND s.SourceName = '$source_filter'";
}
if (!empty($from_date) && !empty($to_date)) {
    $where .= " AND s.IssueDate BETWEEN '$from_date' AND '$to_date'";
}

$query = "
    SELECT s.SourceName, s.Route, s.PNR, s.TicketNumber, s.IssueDate, s.Amount AS credit, s.Remarks,
           IFNULL(p.amount, 0) AS debit
    FROM sales s
    LEFT JOIN paid p ON s.InvoiceNo = p.invoice_no
    $where
    ORDER BY s.IssueDate DESC
";

$result = mysqli_query($conn, $query);

echo "SL\tSource\tRoute\tPNR\tTicket\tDay Passes\tDebit\tCredit\tBalance\tRemarks\n";
$sl = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $issue_date = new DateTime($row['IssueDate']);
    $now = new DateTime();
    $days_passed = $now->diff($issue_date)->days;
    $balance = $row['credit'] - $row['debit'];
    echo "$sl\t{$row['SourceName']}\t{$row['Route']}\t{$row['PNR']}\t{$row['TicketNumber']}\t{$days_passed}\t{$row['debit']}\t{$row['credit']}\t{$balance}\t{$row['Remarks']}\n";
    $sl++;
}
