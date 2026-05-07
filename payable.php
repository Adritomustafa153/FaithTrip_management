<?php
require 'vendor/autoload.php';
include 'db.php';
include 'auth_check.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Filters
$source_filter = $_GET['source'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$load_ledger = isset($_GET['load_ledger']) && $_GET['load_ledger'] == '1';

$source_condition = "";
$date_condition = "";

// Determine if we are showing refunds (special source)
$show_refunds = ($source_filter === 'Refunds');

if (!$show_refunds) {
    if (!empty($source_filter)) {
        $source_condition = "AND Source = '$source_filter'";
    }
} else {
    $source_condition = "";
}

if (!empty($from_date) && !empty($to_date)) {
    $date_condition = "AND IssueDate BETWEEN '$from_date' AND '$to_date'";
}

// Get list of sources (add "Refunds" as a special option)
$source_query = mysqli_query($conn, "SELECT DISTINCT agency_name FROM sources");
$sources = [];
while ($row = mysqli_fetch_assoc($source_query)) {
    $sources[] = $row['agency_name'];
}
$sources[] = 'Refunds';

// Forward balance (placeholder – replace with your actual query)
$forward_balance = 0;
if ($load_ledger && !$show_refunds && !empty($from_date)) {
    $forward_balance = 0; // TODO: replace with your original forward balance code
}

// ========== MAIN QUERY ==========
if ($show_refunds) {
    $query = "
        SELECT 
            'Refund' AS Source,
            s.IssueDate AS trans_date,
            s.TicketRoute,
            s.Airlines,
            s.PNR,
            s.TicketNumber,
            0 AS credit,
            s.refundtc AS debit,
            CONCAT('REFUND: ', IFNULL(s.Remarks, '')) AS remarks,
            'refund_payable' AS type,
            s.SaleID AS sale_id,
            s.PassengerName,
            s.PartyName,
            s.refundtc AS refund_amount,
            s.PaymentStatus,
            s.invoice_number
        FROM sales s
        WHERE s.Remarks = 'Refund'
            AND (s.PaymentStatus = 'Paid' OR s.PaymentStatus IS NULL)
            AND s.refundtc IS NOT NULL
            AND s.refundtc > 0
            $date_condition
    ";
} else {
    $query = "
        -- Regular Sales
        SELECT 
            s.Source,
            s.IssueDate AS trans_date,
            s.TicketRoute,
            s.Airlines,
            s.PNR,
            s.TicketNumber,
            s.NetPayment AS credit,
            0 AS debit,
            s.Remarks AS remarks,
            'sale' AS type,
            s.SaleID AS sale_id,
            s.PassengerName,
            s.PartyName,
            NULL AS refund_amount,
            s.PaymentStatus,
            s.invoice_number
        FROM sales s
        WHERE s.Remarks NOT IN ('Refund', 'Void Transaction', 'Voided', 'Reissue', 'Reissued') 
            $source_condition $date_condition

        UNION ALL

        -- Refunds
        SELECT 
            s.Source,
            s.IssueDate AS trans_date,
            s.TicketRoute,
            s.Airlines,
            s.PNR,
            s.TicketNumber,
            0 AS credit,
            s.refundtc AS debit,
            CONCAT('REFUND: ', IFNULL(s.Remarks, '')) AS remarks,
            'refund' AS type,
            s.SaleID AS sale_id,
            s.PassengerName,
            s.PartyName,
            s.refundtc,
            s.PaymentStatus,
            s.invoice_number
        FROM sales s
        WHERE s.Remarks = 'Refund' 
            $source_condition $date_condition
            AND s.refundtc > 0

        UNION ALL

        -- Reissue Charges
        SELECT 
            s.Source,
            s.IssueDate AS trans_date,
            s.TicketRoute,
            s.Airlines,
            s.PNR,
            CONCAT(s.TicketNumber, ' REISSUE') AS TicketNumber,
            s.NetPayment AS credit,
            0 AS debit,
            CONCAT('REISSUE CHARGE: ', IFNULL(s.Remarks, '')) AS remarks,
            'reissue' AS type,
            s.SaleID AS sale_id,
            s.PassengerName,
            s.PartyName,
            NULL,
            s.PaymentStatus,
            s.invoice_number
        FROM sales s
        WHERE s.Remarks = 'Reissue' 
            $source_condition $date_condition

        UNION ALL

        -- Reissue Reversals
        SELECT 
            s.Source,
            s.IssueDate AS trans_date,
            s.TicketRoute,
            s.Airlines,
            s.PNR,
            s.TicketNumber,
            0 AS credit,
            s.NetPayment AS debit,
            'REISSUE REVERSAL: Original sale reversed for reissue' AS remarks,
            'reissue_reversal' AS type,
            s.SaleID AS sale_id,
            s.PassengerName,
            s.PartyName,
            NULL,
            s.PaymentStatus,
            s.invoice_number
        FROM sales s
        WHERE s.Remarks = 'Reissued' 
            $source_condition $date_condition
            AND s.TicketNumber NOT LIKE '%REISSUE%'

        UNION ALL

        -- Void Transactions
        SELECT 
            s.Source,
            s.IssueDate AS trans_date,
            s.TicketRoute,
            s.Airlines,
            s.PNR,
            s.TicketNumber,
            0 AS credit,
            s.NetPayment AS debit,
            CONCAT('Void reversal with ', IFNULL(s.VoidCharge, 0), ' tk charge: ', IFNULL(s.Notes, '')) AS remarks,
            'void' AS type,
            s.SaleID AS sale_id,
            s.PassengerName,
            s.PartyName,
            NULL,
            s.PaymentStatus,
            s.invoice_number
        FROM sales s
        WHERE s.Remarks = 'Void Transaction' 
            $source_condition $date_condition

        UNION ALL

        -- Void Reversals
        SELECT 
            s.Source,
            s.IssueDate AS trans_date,
            s.TicketRoute,
            s.Airlines,
            s.PNR,
            s.TicketNumber,
            s.NetPayment AS credit,
            s.BillAmount AS debit,
            'VOID REVERSAL: Original sale & net payment reversed' AS remarks,
            'void_reversal' AS type,
            s.SaleID AS sale_id,
            s.PassengerName,
            s.PartyName,
            NULL,
            s.PaymentStatus,
            s.invoice_number
        FROM sales s
        WHERE s.Remarks = 'Voided' 
            $source_condition $date_condition
            AND s.TicketNumber NOT LIKE '%VOID%'
    ";
}

if ($load_ledger && !$show_refunds) {
    $query .= "
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
            IFNULL(p.remarks, '') AS remarks,
            'paid' AS type,
            NULL AS sale_id,
            '' AS PassengerName,
            '' AS PartyName,
            NULL,
            NULL AS PaymentStatus,
            p.invoice_no
        FROM paid p
        WHERE 1=1 $source_condition
        " . (!empty($from_date) && !empty($to_date) ? "AND p.payment_date BETWEEN '$from_date' AND '$to_date'" : "") . "
    ";
}

$query .= " ORDER BY trans_date ASC";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

// Calculate totals
$total_credit = 0;
$total_debit = 0;
if ($result && mysqli_num_rows($result) > 0) {
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_credit += floatval($row['credit']);
        $total_debit += floatval($row['debit']);
    }
    mysqli_data_seek($result, 0);
}
$period_balance = $total_credit - $total_debit;
$closing_balance = $show_refunds ? $total_debit : ($forward_balance + $period_balance);

// Excel export – replace with your actual code
if (isset($_GET['export'])) {
    // ... your export logic ...
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payable</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 100%; margin: 0 auto; padding: 20px; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow-x: auto; }
        h2 { color: #2a5885; text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .filter-section { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .filter-group { display: flex; align-items: center; gap: 8px; }
        label { font-weight: 600; color: #495057; min-width: 60px; }
        select, input[type="date"] { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; background-color: white; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-search { background-color: #2a5885; color: white; }
        .btn-search:hover { background-color: #1e3c6d; }
        .btn-export { background-color: #28a745; color: white; }
        .btn-export:hover { background-color: #218838; }

        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th, td { padding: 8px 6px; text-align: left; border: 1px solid #dee2e6; word-wrap: break-word; overflow-wrap: break-word; }
        
        /* Column widths – dynamic based on whether actions column is shown */
        th:nth-child(1), td:nth-child(1) { width: 40px; }
        th:nth-child(2), td:nth-child(2) { width: 90px; }
        th:nth-child(3), td:nth-child(3) { width: 100px; }
        th:nth-child(4), td:nth-child(4) { width: 120px; }
        th:nth-child(5), td:nth-child(5) { width: 150px; }
        th:nth-child(6), td:nth-child(6) { width: 120px; }
        th:nth-child(7), td:nth-child(7) { width: 100px; }
        th:nth-child(8), td:nth-child(8) { width: 90px; }
        th:nth-child(9), td:nth-child(9) { width: 110px; }
        th:nth-child(10), td:nth-child(10) { width: 100px; text-align: right; }
        th:nth-child(11), td:nth-child(11) { width: 100px; text-align: right; }
        <?php if ($load_ledger && !$show_refunds): ?>
        th:nth-child(12), td:nth-child(12) { width: 100px; text-align: right; }
        th:nth-child(13), td:nth-child(13) { width: 200px; }
        <?php elseif ($show_refunds): ?>
        /* Refunds view has an extra Actions column */
        th:nth-child(12), td:nth-child(12) { width: 120px; } /* Actions */
        th:nth-child(13), td:nth-child(13) { width: 200px; } /* Remarks */
        <?php else: ?>
        th:nth-child(12), td:nth-child(12) { width: 200px; }
        <?php endif; ?>

        th { background-color: #2a5885; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .total-section { margin-top: 20px; padding: 10px; background-color: #e9ecef; border-radius: 4px; text-align: right; font-weight: 600; }
        .forward-balance-section { margin-top: 15px; padding: 10px; background-color: #e8f0f8; border-radius: 4px; border-left: 4px solid #2a5885; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .refund-row { background-color: #fff8e7 !important; }
        .balance-positive { color: #28a745; font-weight: bold; }
        .balance-negative { color: #dc3545; font-weight: bold; }

        /* Button styles */
        .btn-pay, .btn-history {
            display: inline-block;
            padding: 4px 8px;
            margin: 2px;
            font-size: 11px;
            font-weight: bold;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-pay { background-color: #28a745; color: white; }
        .btn-pay:hover { background-color: #218838; }
        .btn-history { background-color: #17a2b8; color: white; }
        .btn-history:hover { background-color: #138496; }

        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 8px; }
        .close, .close-history { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover, .close-history:hover { color: black; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-success { background-color: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }

        @media (max-width: 1200px) { table { font-size: 11px; } th, td { padding: 5px 4px; } }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <h2>Payable Party List</h2>
    
    <div class="filter-section">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label for="source">Source:</label>
                <select name="source" id="source">
                    <option value="">All Sources</option>
                    <?php foreach ($sources as $src): ?>
                        <option value="<?= htmlspecialchars($src) ?>" <?= $src == $source_filter ? 'selected' : '' ?>>
                            <?= htmlspecialchars($src) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="from_date">From:</label>
                <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
            </div>
            <div class="filter-group">
                <label for="to_date">To:</label>
                <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="load_ledger" id="load_ledger" value="1" <?= $load_ledger ? 'checked' : '' ?>>
                <label for="load_ledger" style="min-width: auto;">Load Ledger</label>
            </div>
            <button type="submit" class="btn btn-search">Search</button>
            <button type="submit" name="export" value="1" class="btn btn-export">Export to Excel</button>
        </form>
    </div>

    <?php if ($load_ledger && !$show_refunds && !empty($from_date) && $forward_balance != 0): ?>
    <div class="forward-balance-section">
        <strong>Forward Balance (as of <?= htmlspecialchars($from_date) ?>):</strong> 
        <span class="<?= $forward_balance >= 0 ? 'balance-positive' : 'balance-negative' ?>">
            <?= number_format($forward_balance, 2) ?> Taka
        </span>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Passenger/Party</th>
                    <th>Route</th>
                    <th>Airlines</th>
                    <th>PNR</th>
                    <th>Ticket No</th>
                    <th class="text-right">Debit (Amount)</th>
                    <th class="text-right">Credit</th>
                    <?php if ($load_ledger && !$show_refunds): ?>
                    <th class="text-right">Balance</th>
                    <?php endif; ?>
                    <?php if ($show_refunds): ?>
                    <th>Actions</th>
                    <?php endif; ?>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sl = 1;
                $balance = $forward_balance;
                
                if ($load_ledger && !$show_refunds && !empty($from_date) && $forward_balance != 0) {
                    ?>
                    <tr style="background-color: #e8f0f8; font-weight: bold;">
                        <td></td>
                        <td><?= htmlspecialchars($from_date) ?></td>
                        <td>Forward Balance</td>
                        <td><?= htmlspecialchars($source_filter ?: 'ALL') ?></td>
                        <td></td><td></td><td></td><td></td><td></td>
                        <td class="text-right"></td>
                        <td class="text-right"></td>
                        <?php if ($load_ledger && !$show_refunds): ?>
                        <td class="text-right <?= $forward_balance >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                            <?= number_format($forward_balance, 2) ?>
                        </td>
                        <?php endif; ?>
                        <td>Opening Balance</td>
                        <td></td>
                    </tr>
                    <?php
                }
                
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)):
                        $debit = floatval($row['debit']);
                        $credit = floatval($row['credit']);
                        if ($load_ledger && !$show_refunds) {
                            $balance += $credit - $debit;
                        }
                        $rowClass = ($show_refunds && $row['type'] == 'refund_payable') ? 'refund-row' : '';
                        $displayType = ($show_refunds) ? 'Refund Payable' : ucfirst($row['type']);
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td><?= $sl++ ?></td>
                        <td><?= htmlspecialchars($row['trans_date']) ?></td>
                        <td><?= $displayType ?></td>
                        <td><?= $show_refunds ? 'Refunds' : htmlspecialchars($row['Source']) ?></td>
                        <td><?= $show_refunds ? htmlspecialchars($row['PartyName'] . ' / ' . $row['PassengerName']) : htmlspecialchars($row['PartyName'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['TicketRoute']) ?></td>
                        <td><?= htmlspecialchars($row['Airlines']) ?></td>
                        <td><?= htmlspecialchars($row['PNR']) ?></td>
                        <td><?= htmlspecialchars($row['TicketNumber']) ?></td>
                        <td class="text-right"><?= number_format($debit, 2) ?></td>
                        <td class="text-right"><?= number_format($credit, 2) ?></td>
                        <?php if ($load_ledger && !$show_refunds): ?>
                        <td class="text-right <?= $balance >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                            <?= number_format($balance, 2) ?>
                        </td>
                        <?php endif; ?>
                        <?php if ($show_refunds): ?>
                        <td class="actions">
                            <?php if ($row['type'] == 'refund_payable'): ?>
                                <button class="btn-pay" 
                                        data-sale-id="<?= $row['sale_id'] ?>" 
                                        data-amount="<?= $row['refund_amount'] ?>" 
                                        data-invoice="<?= htmlspecialchars($row['invoice_number']) ?>">Pay</button>
                                <button class="btn-history" data-sale-id="<?= $row['sale_id'] ?>">History</button>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($row['remarks']) ?></td>
                    </tr>
                <?php endwhile;
                } else {
                    $colspan = ($load_ledger && !$show_refunds) ? 13 : ($show_refunds ? 13 : 12);
                    echo "<tr><td colspan='$colspan' style='text-align: center;'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0 || ($load_ledger && !empty($from_date))): ?>
    <div class="total-section">
        <div><strong>Period Balance:</strong> <?= number_format($period_balance, 2) ?> Taka</div>
        <?php if ($load_ledger && !$show_refunds && !empty($from_date)): ?>
        <div><strong>Forward Balance:</strong> <?= number_format($forward_balance, 2) ?> Taka</div>
        <div><strong>Closing Balance:</strong> <?= number_format($closing_balance, 2) ?> Taka</div>
        <?php elseif ($show_refunds): ?>
        <div><strong>Total Refunds Payable:</strong> <?= number_format($closing_balance, 2) ?> Taka</div>
        <?php else: ?>
        <div><strong>Total Payable Balance:</strong> <?= number_format($period_balance, 2) ?> Taka</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal for Payment -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Record Refund Payment</h3>
        <form id="paymentForm" method="POST" action="ajax_refund_payment.php">
            <input type="hidden" name="sale_id" id="modal_sale_id">
            <div class="form-group">
                <label>Invoice No:</label>
                <input type="text" name="invoice_no" id="modal_invoice" readonly style="background:#e9ecef;">
            </div>
            <div class="form-group">
                <label>Refund Amount Due:</label>
                <input type="text" id="modal_due" readonly style="background:#e9ecef;">
            </div>
            <div class="form-group">
                <label>Payment Amount:</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
            <div class="form-group">
                <label>Payment Date:</label>
                <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Payment Method:</label>
                <select name="payment_method" required>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" rows="2"></textarea>
            </div>
            <button type="submit" class="btn-success">Submit Payment</button>
        </form>
    </div>
</div>

<!-- Modal for History -->
<div id="historyModal" class="modal">
    <div class="modal-content" style="width: 70%;">
        <span class="close-history">&times;</span>
        <h3>Payment History</h3>
        <div id="historyContent">Loading...</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Pay button
    $('.btn-pay').click(function() {
        var saleId = $(this).data('sale-id');
        var amount = $(this).data('amount');
        var invoice = $(this).data('invoice');
        $('#modal_sale_id').val(saleId);
        $('#modal_invoice').val(invoice);
        $('#modal_due').val(parseFloat(amount).toFixed(2));
        $('#paymentModal').show();
    });

    // History button
    $('.btn-history').click(function() {
        var saleId = $(this).data('sale-id');
        $('#historyModal').show();
        $('#historyContent').html('<p>Loading...</p>');
        $.get('ajax_refund_history.php?sale_id=' + saleId, function(data) {
            $('#historyContent').html(data);
        }).fail(function() {
            $('#historyContent').html('<p style="color:red">Error loading history</p>');
        });
    });

    // Close modals
    $('.close, .close-history').click(function() {
        $('#paymentModal, #historyModal').hide();
    });
    $(window).click(function(event) {
        if ($(event.target).is('#paymentModal, #historyModal')) {
            $('#paymentModal, #historyModal').hide();
        }
    });

    // Payment form submit via AJAX
    $('#paymentForm').submit(function(e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function(response) {
            if (response.success) {
                alert('Payment recorded successfully');
                $('#paymentModal').hide();
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'Unknown error'));
            }
        }, 'json').fail(function() {
            alert('Server error');
        });
    });
});
</script>
</body>
</html>