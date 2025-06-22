<?php
include 'db.php';

// Filters
$source_filter = $_GET['source'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$source_condition = "";
$date_condition = "";

if (!empty($source_filter)) {
    $source_condition = "AND Source = '$source_filter'";
}
if (!empty($from_date) && !empty($to_date)) {
    $date_condition = "AND IssueDate BETWEEN '$from_date' AND '$to_date'";
}

// Get list of sources
$source_query = mysqli_query($conn, "SELECT DISTINCT agency_name FROM sources");
$sources = [];
while ($row = mysqli_fetch_assoc($source_query)) {
    $sources[] = $row['agency_name'];
}

// Combine sales and payments into a single query using UNION
$query = "
    SELECT 
        s.Source,
        s.IssueDate AS trans_date,
        s.TicketRoute,
        s.Airlines,
        s.PNR,
        s.TicketNumber,
        s.NetPayment AS credit,
        0 AS debit,
        '' AS remarks,
        'sale' AS type
    FROM sales s
    WHERE 1=1 $source_condition $date_condition

    UNION ALL

    SELECT 
        p.Source,
        p.payment_date AS trans_date,
        '' AS TicketRoute,
        '' AS Airlines,
        '' AS PNR,
        '' AS TicketNumber,
        0 AS credit,
        p.amount AS debit,
        p.remarks,
        'paid' AS type
    FROM paid p
    WHERE 1=1 $source_condition
    " . (!empty($from_date) && !empty($to_date) ? "AND p.payment_date BETWEEN '$from_date' AND '$to_date'" : "") . "

    ORDER BY trans_date ASC
";

$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Payable (Unified View)</title>
    <style>
        body { font-family: Arial;}
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: justify; font-family: 'Times New Roman', Times, serif;font-size: 15px;}
        th { background-color: #f2f2f2; }
        .filter-section { margin-bottom: 20px; }
        .total-section { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="filter-section">
    <form method="GET" action="">
        <label>Source:</label>
        <select name="source">
            <option value="">All</option>
            <?php foreach ($sources as $src): ?>
                <option value="<?= $src ?>" <?= $src == $source_filter ? 'selected' : '' ?>><?= $src ?></option>
            <?php endforeach; ?>
        </select>

        <label>From:</label>
        <input type="date" name="from_date" value="<?= $from_date ?>">

        <label>To:</label>
        <input type="date" name="to_date" value="<?= $to_date ?>">

        <button type="submit">Search</button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>SL</th>
            <th>Date</th>
            <th>Type</th>
            <th>Source</th>
            <th>Route</th>
            <th>Airlines</th>
            <th>PNR</th>
            <th>Ticket Number</th>
            <th>Debit (Paid)</th>
            <th>Credit (Bill)</th>
            <th>Balance</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sl = 1;
        $balance = 0;

        while ($row = mysqli_fetch_assoc($result)):
            $debit = floatval($row['debit']);
            $credit = floatval($row['credit']);
            $balance += $credit - $debit;
        ?>
        <tr>
            <td><?= $sl++ ?></td>
            <td><?= $row['trans_date'] ?></td>
            <td><?= ucfirst($row['type']) ?></td>
            <td><?= $row['Source'] ?></td>
            <td><?= $row['TicketRoute'] ?></td>
            <td><?= $row['Airlines'] ?></td>
            <td><?= $row['PNR'] ?></td>
            <td><?= $row['TicketNumber'] ?></td>
            <td><?= number_format($debit, 2) ?></td>
            <td><?= number_format($credit, 2) ?></td>
            <td><?= number_format($balance, 2) ?></td>
            <td><?= $row['remarks'] ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<table>
            <tr>
            <td style="text-align: right;background: #e9ecef"><strong>Total Payable Balance: </strong></td>
            <td><b><?= number_format($balance, 2) ?> Taka</b></td>
        </tr>
</table>
</body>
</html>
