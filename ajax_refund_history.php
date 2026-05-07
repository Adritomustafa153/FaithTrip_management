<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

$sale_id = intval($_GET['sale_id'] ?? 0);
if ($sale_id <= 0) { echo "<p>Invalid request</p>"; exit; }

$stmt = $conn->prepare("SELECT invoice_number FROM sales WHERE SaleID = ? AND Remarks = 'Refund'");
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
if (!$row) { echo "<p>Refund not found</p>"; exit; }

$invoice_no = $row['invoice_number'];
$payments = $conn->query("SELECT payment_date, amount, payment_method, remarks FROM paid WHERE invoice_no = '$invoice_no' AND remarks LIKE '%Refund payment%' ORDER BY payment_date DESC");
if ($payments->num_rows == 0) {
    echo "<p>No payment history</p>";
} else {
    echo "<table border='1' cellpadding='5' style='width:100%; border-collapse:collapse;'>
            <tr><th>Date</th><th>Amount</th><th>Method</th><th>Remarks</th></tr>";
    while ($p = $payments->fetch_assoc()) {
        echo "<tr>
                <td>{$p['payment_date']}</td>
                <td>" . number_format($p['amount'],2) . "</td>
                <td>{$p['payment_method']}</td>
                <td>{$p['remarks']}</td>
              </tr>";
    }
    echo "</table>";
}
?>