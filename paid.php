<?php
include 'db.php';

// Fetch sources
$sources_result = mysqli_query($conn, "SELECT id, agency_name FROM Sources");

// Filters
$source_id = $_GET['source_id'] ?? '';
$from_date = $_GET['from'] ?? '';
$to_date = $_GET['to'] ?? '';
$invoice_no = $_GET['invoice_no'] ?? '';

// Build WHERE clause
$where = "WHERE 1";
if (!empty($source_id)) $where .= " AND p.source = '$source_id'";
if (!empty($from_date) && !empty($to_date)) $where .= " AND p.payment_date BETWEEN '$from_date' AND '$to_date'";
if (!empty($invoice_no)) $where .= " AND p.invoice_no LIKE '%$invoice_no%'";

// Fetch paid data
$query = "
    SELECT p.*, s.agency_name
    FROM paid p
    LEFT JOIN Sources s ON p.source = s.id
    $where
    ORDER BY p.payment_date DESC
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Paid Summary</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; color: #333; }
        .filter-div, .summary-div, .table-div { margin-bottom: 30px; }
        table { border-collapse: collapse; width: 100%; margin-left: 10px; margin-right: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #e9ecef; }
        .receipt-link { color: #007bff; text-decoration: none; }
        .receipt-link:hover { text-decoration: underline; }
        .actions button {
            margin: 2px;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .actions .edit-btn { background-color:rgb(25, 199, 43); color: #000; }
        .actions .delete-btn { background-color: #dc3545; color: #fff; }
        button:hover { opacity: 0.9; }
        .filter-div form { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 20px;margin-left: 125px;}
        .filter-div label { font-weight: 600; }
        .filter-div select, .filter-div input[type="date"], .filter-div input[type="text"] {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .filter-div button {
            background-color: #007bff;
            color: #fff;
            padding: 6px 14px;
            border: none;
            border-radius: 4px;
        }
        .filter-div button:hover {
            background-color: #0056b3;
        }
        .loading-btn { position: relative; }
        .loading-btn:disabled::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top: 2px solid #333;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
    <script>
        function confirmSuccess() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success')) {
                alert("✅ Payment inserted successfully.");
            }
        }

        function handleSearch(btn) {
            btn.disabled = true;
            btn.classList.add('loading-btn');
            btn.form.submit();
        }

        window.onload = confirmSuccess;
    </script>
</head>
<body>
<?php include 'nav.php' ?>
<h3 style="text-align : center;margin-top:30px;">Paid invoices List</h3>
<div class="filter-div">
    <form method="GET" action="paid.php">
        <label>Source:</label>
        <select name="source_id" id="sourceSelect">
            <option value="">All Sources</option>
            <?php while ($row = mysqli_fetch_assoc($sources_result)): ?>
                <option value="<?= $row['agency_name'] ?>" <?= $source_id == $row['agency_name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['agency_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>From:</label>
        <input type="date" name="from" value="<?= $from_date ?>">

        <label>To:</label>
        <input type="date" name="to" value="<?= $to_date ?>">

        <label>Invoice No:</label>
        <input type="text" name="invoice_no" placeholder="Search Invoice" value="<?= htmlspecialchars($invoice_no) ?>">

        <button type="submit" onclick="handleSearch(this)">Search</button>
        <button type="button" onclick="window.location.href='insert_paid.php'">Insert</button>
        <button type="button" onclick="window.location.href='export_paid.php?source_id=<?= $source_id ?>&from=<?= $from_date ?>&to=<?= $to_date ?>&invoice_no=<?= $invoice_no ?>'">Export to Excel</button>
    </form>
</div>

<div class="table-div">
    <table>
        <thead>
            <tr>
                <th>SL</th>
                <th>Date</th>
                <th>Source</th>
                <th>Invoice No</th>
                <th>Receipt</th>
                <th>Transaction ID</th>
                <th>Payment Method</th>
                <th>Amount</th>
                <th>Remarks</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sl = 1;
        $total = 0;
        while ($row = mysqli_fetch_assoc($result)):
            $total += floatval($row['amount']);
        ?>
            <tr>
                <td><?= $sl++ ?></td>
                <td><?= htmlspecialchars($row['payment_date']) ?></td>
                <td><?= htmlspecialchars($row['source']) ?></td>
                <td><?= htmlspecialchars($row['invoice_no']) ?></td>
                <td>
                    <?php if (!empty($row['receipt'])): ?>
                        <a href="uploads/receipts/<?= htmlspecialchars($row['receipt']) ?>" target="_blank" class="receipt-link">Attachment</a>
                    <?php else: ?>
                        No Image
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                <td><?= number_format($row['amount'], 2) ?></td>
                <td><?= htmlspecialchars($row['remarks']) ?></td>
                <td class="actions">
                    <button class="edit-btn" onclick="location.href='edit_paid.php?id=<?= $row['id'] ?>'">Edit</button>
                    <button class="delete-btn" onclick="if(confirm('Are you sure?')) location.href='delete_paid.php?id=<?= $row['id'] ?>'">Delete</button>
                </td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="7" style="text-align: right;background: #e9ecef"><strong>Total</strong></td>
            <td><strong><?= number_format($total, 2) ?></strong></td>
            <td colspan="2"></td>
        </tr>
        </tbody>
    </table>
</div>

</body>
</html>