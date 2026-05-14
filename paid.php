<?php
include 'db.php';
include 'auth_check.php';

// Fetch sources
$sources_result = mysqli_query($conn, "SELECT id, agency_name FROM Sources");
$sources_list = [];
while ($row = mysqli_fetch_assoc($sources_result)) {
    $sources_list[] = $row;
}

// Filters
$source_id = $_GET['source_id'] ?? '';
$from_date = $_GET['from'] ?? '';
$to_date = $_GET['to'] ?? '';
$invoice_no = $_GET['invoice_no'] ?? '';
$refund_filter = $_GET['refund_only'] ?? '';

// Build WHERE clause
$where = "WHERE 1";
if (!empty($source_id)) $where .= " AND p.source = '$source_id'";
if (!empty($from_date) && !empty($to_date)) $where .= " AND p.payment_date BETWEEN '$from_date' AND '$to_date'";
if (!empty($invoice_no)) $where .= " AND p.invoice_no LIKE '%$invoice_no%'";
if ($refund_filter == '1') $where .= " AND p.remarks LIKE '%Refund payment%'";

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Paid Summary | Invoice Manager</title>
    <!-- Google Font & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: #1e293b;
            line-height: 1.5;
        }

        /* main container */
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1.5rem 2rem;
        }

        /* page header */
        .page-header {
            margin-bottom: 1.75rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.3px;
        }
        .page-header p {
            color: #5b6e8c;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        /* summary cards */
        .stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03), 0 1px 2px rgba(0, 0, 0, 0.05);
            padding: 1.2rem 1.5rem;
            flex: 1;
            min-width: 160px;
            transition: all 0.2s ease;
            border: 1px solid #eef2f6;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -12px rgba(0, 0, 0, 0.1);
        }
        .stat-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            color: #5b6e8c;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0f2b3d;
            line-height: 1.2;
        }
        .stat-sub {
            font-size: 0.75rem;
            color: #7c8ba0;
            margin-top: 0.4rem;
        }

        /* filter card */
        .filter-card {
            background: white;
            border-radius: 1.25rem;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.02), 0 0 0 1px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
            padding: 1.5rem 1.8rem;
            border: 1px solid #eef2f6;
            transition: all 0.2s;
        }
        .filter-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 1.2rem;
            row-gap: 1.5rem;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 140px;
        }
        .filter-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            letter-spacing: 0.3px;
        }
        .filter-group input, .filter-group select {
            background: #f9fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.6rem 1rem;
            font-family: 'Inter', monospace;
            font-size: 0.85rem;
            transition: 0.2s;
            outline: none;
            color: #0f172a;
        }
        .filter-group input:focus, .filter-group select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
            background: white;
        }
        .btn-group {
            display: flex;
            gap: 0.7rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #1f2a44;
            border: 1px solid #e2e8f0;
        }
        .btn-primary {
            background: #1e3a8a;
            color: white;
            border: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn-primary:hover {
            background: #1e40af;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -8px #1e3a8a;
        }
        .btn-outline {
            border: 1px solid #cbd5e1;
            background: white;
        }
        .btn-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }
        .btn-success {
            background: #059669;
            color: white;
            border: none;
        }
        .btn-success:hover {
            background: #047857;
        }
        .btn-warning {
            background: #d97706;
            color: white;
            border: none;
        }
        .btn-warning:hover {
            background: #b45309;
        }

        /* table wrapper */
        .table-wrapper {
            background: white;
            border-radius: 1.25rem;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.02), 0 0 0 1px rgba(0, 0, 0, 0.02);
            overflow-x: auto;
            border: 1px solid #edf2f7;
            padding: 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            min-width: 880px;
        }
        .data-table th {
            text-align: left;
            padding: 1rem 1rem;
            background-color: #f8fafc;
            font-weight: 600;
            color: #1e293b;
            border-bottom: 1px solid #e9edf2;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .data-table td {
            padding: 1rem 1rem;
            border-bottom: 1px solid #f0f2f5;
            vertical-align: middle;
            color: #1f2a44;
        }
        .data-table tr:hover td {
            background-color: #fefce8;
        }
        .receipt-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eef2ff;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #1e3a8a;
            text-decoration: none;
            transition: 0.2s;
        }
        .receipt-link i {
            font-size: 0.7rem;
        }
        .receipt-link:hover {
            background: #d9e6ff;
            color: #0f2b6d;
        }
        .no-image {
            color: #94a3b8;
            font-size: 0.75rem;
            font-style: italic;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .edit-btn, .delete-btn {
            border: none;
            background: transparent;
            font-size: 0.75rem;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .edit-btn {
            background: #e6f7ec;
            color: #15803d;
        }
        .edit-btn:hover {
            background: #c2e9ce;
            transform: scale(0.97);
        }
        .delete-btn {
            background: #fee2e2;
            color: #b91c1c;
        }
        .delete-btn:hover {
            background: #fecaca;
        }
        .footer-total td {
            background: #f1f5f9;
            font-weight: 700;
            border-top: 1px solid #e2e8f0;
        }
        .empty-row td {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .loading-btn:disabled {
            opacity: 0.7;
            position: relative;
            cursor: not-allowed;
        }
        .loading-btn:disabled::after {
            content: "";
            position: absolute;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.6);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
            position: relative;
            display: inline-block;
            top: 2px;
        }
        @media (max-width: 760px) {
            .dashboard-container {
                padding: 1rem;
            }
            .filter-group {
                width: 100%;
            }
            .btn-group {
                width: 100%;
                justify-content: flex-start;
            }
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
<?php include 'nav.php'; ?>
<div class="dashboard-container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-receipt" style="margin-right: 10px; color:#2c5282;"></i> Paid Invoices</h1>
            <p>Track and manage all payments, refunds & receipts</p>
        </div>
    </div>

    <?php
    // Compute totals for summary cards (same filtered data)
    $total_amount = 0;
    $record_count = 0;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_amount += floatval($row['amount']);
        $record_count++;
    }
    mysqli_data_seek($result, 0);
    ?>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-title"><i class="fas fa-chart-line"></i> Total Amount</div>
            <div class="stat-number">৳ <?= number_format($total_amount, 2) ?></div>
            <div class="stat-sub">filtered transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-title"><i class="fas fa-file-invoice-dollar"></i> Total Invoices</div>
            <div class="stat-number"><?= $record_count ?></div>
            <div class="stat-sub">paid entries</div>
        </div>
        <div class="stat-card">
            <div class="stat-title"><i class="fas fa-building"></i> Sources</div>
            <div class="stat-number"><?= count($sources_list) ?></div>
            <div class="stat-sub">active agencies</div>
        </div>
    </div>

    <!-- FILTER CARD -->
    <div class="filter-card">
        <form method="GET" action="paid.php" style="width: 100%;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label><i class="fas fa-store"></i> Source</label>
                    <select name="source_id">
                        <option value="">All Sources</option>
                        <?php foreach ($sources_list as $src): ?>
                            <option value="<?= htmlspecialchars($src['agency_name']) ?>" <?= $source_id == $src['agency_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($src['agency_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="far fa-calendar-alt"></i> From Date</label>
                    <input type="date" name="from" value="<?= $from_date ?>">
                </div>
                <div class="filter-group">
                    <label><i class="far fa-calendar-check"></i> To Date</label>
                    <input type="date" name="to" value="<?= $to_date ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-hashtag"></i> Invoice No</label>
                    <input type="text" name="invoice_no" placeholder="Search invoice" value="<?= htmlspecialchars($invoice_no) ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Payment Type</label>
                    <select name="refund_only">
                        <option value="">All Payments</option>
                        <option value="1" <?= $refund_filter == '1' ? 'selected' : '' ?>>Refund Payments Only</option>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary" onclick="handleSearch(this)"><i class="fas fa-search"></i> Search</button>
                    <button type="button" class="btn btn-outline" onclick="window.location.href='insert_paid.php'"><i class="fas fa-plus-circle"></i> Insert</button>
                    <button type="button" class="btn btn-success" onclick="window.location.href='export_paid.php?source_id=<?= urlencode($source_id) ?>&from=<?= urlencode($from_date) ?>&to=<?= urlencode($to_date) ?>&invoice_no=<?= urlencode($invoice_no) ?>&refund_only=<?= urlencode($refund_filter) ?>'"><i class="fas fa-file-excel"></i> Export</button>
                    <button type="button" class="btn btn-warning" onclick="window.location.href='paid.php'"><i class="fas fa-undo-alt"></i> Reset</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABLE CARD -->
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#SL</th><th>Date</th><th>Source</th><th>Invoice No</th><th>Receipt</th>
                    <th>Transaction ID</th><th>Payment Method</th><th>Amount (৳)</th><th>Remarks</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sl = 1;
            $display_total = 0;
            $has_rows = false;
            while ($row = mysqli_fetch_assoc($result)):
                $has_rows = true;
                $display_total += floatval($row['amount']);
                $refund_badge = (stripos($row['remarks'], 'Refund payment') !== false) ? true : false;
            ?>
                <tr>
                    <td><?= $sl++ ?></td>
                    <td><?= htmlspecialchars($row['payment_date']) ?></td>
                    <td><?= htmlspecialchars($row['source']) ?></td>
                    <td><strong><?= htmlspecialchars($row['invoice_no']) ?></strong></td>
                    <td>
                        <?php if (!empty($row['receipt'])): ?>
                            <a href="uploads/receipts/<?= htmlspecialchars($row['receipt']) ?>" target="_blank" class="receipt-link">
                                <i class="fas fa-paperclip"></i> Attachment
                            </a>
                        <?php else: ?>
                            <span class="no-image"><i class="far fa-file"></i> No file</span>
                        <?php endif; ?>
                    </td>
                    <td><code style="font-size:0.75rem;"><?= htmlspecialchars($row['transaction_id']) ?></code></td>
                    <td>
                        <span style="background:#eef2ff; padding:0.2rem 0.6rem; border-radius:20px; font-size:0.7rem;">
                            <?= htmlspecialchars($row['payment_method']) ?>
                        </span>
                    </td>
                    <td style="font-weight:600;"><?= number_format($row['amount'], 2) ?></td>
                    <td>
                        <?php if($refund_badge): ?>
                            <span style="background:#fff1f0; color:#b91c1c; padding:0.2rem 0.6rem; border-radius:50px; font-size:0.7rem;">
                                <i class="fas fa-undo-alt"></i> <?= htmlspecialchars($row['remarks']) ?>
                            </span>
                        <?php else: ?>
                            <?= htmlspecialchars($row['remarks']) ?: '—' ?>
                        <?php endif; ?>
                    </td>
                    <td class="action-buttons">
                        <button class="edit-btn" onclick="location.href='edit_paid.php?id=<?= $row['id'] ?>'"><i class="fas fa-edit"></i> Edit</button>
                        <button class="delete-btn" onclick="if(confirm('⚠️ Are you sure you want to delete this payment record?')) location.href='delete_paid.php?id=<?= $row['id'] ?>'"><i class="fas fa-trash-alt"></i> Del</button>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if(!$has_rows): ?>
                <tr class="empty-row">
                    <td colspan="10">
                        <i class="fas fa-inbox" style="font-size: 2rem; opacity: 0.5; display: block; margin-bottom: 8px;"></i>
                        No paid records found. Adjust filters or add new payment.
                    </td>
                </tr>
            <?php else: ?>
                <tr class="footer-total">
                    <td colspan="7" style="text-align: right; font-weight: 700;">GRAND TOTAL</td>
                    <td style="font-weight: 800; font-size:1rem;">৳ <?= number_format($display_total, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>