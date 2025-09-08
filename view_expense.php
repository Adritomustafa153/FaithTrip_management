<?php
include 'db.php';
include 'auth_check.php';

// Initialize year and month for filtering
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Initialize start and end date for date range filtering
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build the SQL query based on filters
if (!empty($start_date) && !empty($end_date)) {
    // Date range filter
    $sql = "SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
} else {
    // Month/Year filter
    $sql = "SELECT * FROM expenses WHERE YEAR(expense_date) = ? AND MONTH(expense_date) = ? ORDER BY expense_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $year, $month);
}

$stmt->execute();
$result = $stmt->get_result();
$total_amount = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="logo.jpg">
    <title>Expense Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-top: 20px;
        }

        form {
            text-align: center;
            margin: 15px 0;
        }

        input[type="date"],
        input[type="number"] {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin: 0 5px;
        }

        button,
        a.button,
        a.export-btn {
            padding: 6px 14px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            margin-left: 10px;
            font-size: 14px;
        }

        button:hover,
        a.button:hover,
        a.export-btn:hover {
            background-color: #0056b3;
        }

        .top-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin: 10px 0;
        }

        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .action-links a {
            margin: 0 5px;
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 4px;
            color: white;
        }

        .edit-link {
            background-color: #28a745;
        }

        .edit-link:hover {
            background-color: #218838;
        }

        .delete-link {
            background-color: #dc3545;
        }

        .delete-link:hover {
            background-color: #c82333;
        }

        a.export-btn {
            background-color: #17a2b8;
        }

        a.export-btn:hover {
            background-color: #138496;
        }

        .insert-btn {
            background-color: #6f42c1;
        }

        .insert-btn:hover {
            background-color: #5a32a3;
        }
        
        .notification {
            padding: 10px;
            margin: 10px auto;
            width: 80%;
            text-align: center;
            border-radius: 5px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<?php include 'nav.php' ?>

<!-- Display success/error messages -->
<?php
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $type = isset($_GET['success']) && $_GET['success'] == 'true' ? 'success' : 'error';
    echo "<div class='notification $type'>$message</div>";
}
?>

<!-- Date Range Filter -->
<form method="GET">
    From: <input type="date" name="start_date" value="<?= $start_date ?>">
    To: <input type="date" name="end_date" value="<?= $end_date ?>">
    <button type="submit">Filter</button>
    <?php
    $params = http_build_query([
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    ?>
    <a href="export.php?<?= $params ?>" target="_blank" class="export-btn">Export to Excel</a>
</form>

<!-- Month/Year Filter + Insert -->
<div class="top-bar">
    <form method="GET">
        Year: <input type="number" name="year" value="<?= $year ?>" min="2000" max="2100">
        Month: <input type="number" name="month" value="<?= $month ?>" min="1" max="12">
        <button type="submit">Filter</button>
        <a href="export.php?year=<?= $year ?>&month=<?= $month ?>" target="_blank" class="export-btn">Export to Excel</a>
    </form>

    <button onclick="window.location.href='expense.php'" class="insert-btn">Insert Expense</button>
</div>

<!-- Table Display -->
<h2>Expense Records</h2>
<table>
    <tr style="text-align:center;">
        <th>Date</th>
        <th>Category</th>
        <th>Description</th>
        <th>Amount</th>
        <th>Action</th>
    </tr>
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['expense_date'] ?></td>
            <td><?= $row['category'] ?></td>
            <td><?= $row['description'] ?></td>
            <td>&#2547;<?= number_format($row['amount'], 2) ?></td>
            <td class="action-links">
                <a href="edit_expense.php?id=<?= $row['id'] ?>" class="edit-link">Edit</a>
                <a href="#" class="delete-link" onclick="confirmDelete(<?= $row['id'] ?>)">Delete</a>
            </td>
        </tr>
        <?php $total_amount += $row['amount']; // accumulate ?>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" style="text-align: center;">No expenses found for the selected filter.</td>
        </tr>
    <?php endif; ?>
    <?php if ($result->num_rows > 0): ?>
    <tr style="font-weight:bold; background-color:#f2f2f2;">
        <td colspan="3" style="text-align:right;">Total:</td>
        <td>&#2547;<?= number_format($total_amount, 2) ?></td>
        <td></td>
    </tr>
    <?php endif; ?>
</table>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this expense?')) {
        window.location.href = 'delete_expense.php?id=' + id;
    }
}
</script>

</body>
</html>