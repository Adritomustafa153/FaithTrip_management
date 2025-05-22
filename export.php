<?php
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

// Set headers to force Excel file download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=expenses_{$year}_{$month}.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch expenses
$sql = "SELECT * FROM expenses WHERE YEAR(expense_date) = ? AND MONTH(expense_date) = ? ORDER BY expense_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$result = $stmt->get_result();

// Calculate total
$total_sql = "SELECT SUM(amount) AS total FROM expenses WHERE YEAR(expense_date) = ? AND MONTH(expense_date) = ?";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param("ii", $year, $month);
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total = $total_result['total'];

// Output table as HTML (Excel will parse it)
echo "<table border='1'>";
echo "<tr><th colspan='4'><h3>Expense Report - $month/$year</h3></th></tr>";
echo "<tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$row['expense_date']}</td>
        <td>{$row['category']}</td>
        <td>{$row['description']}</td>
        <td>" . number_format($row['amount'], 2) . "</td>
    </tr>";
}

echo "<tr>
    <td colspan='3'><strong>Total</strong></td>
    <td><strong>" . number_format($total, 2) . "</strong></td>
</tr>";

echo "</table>";
?>
