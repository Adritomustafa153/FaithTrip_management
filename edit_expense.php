<?php
include 'db.php'; // adjust path as needed

$id = $_GET['id'] ?? null;
if (!$id) {
    die("No expense ID provided.");
}

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$expense = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];

    $update = $conn->prepare("UPDATE expenses SET expense_date = ?, category = ?, description = ?, amount = ? WHERE id = ?");
    $update->bind_param("sssdi", $date, $category, $description, $amount, $id);
    $update->execute();

    header("Location: view_expense.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Expense</title>

</head>
<body>

<?php include 'nav.php' ?>
    <h2>Edit Expense Record</h2>
    <form method="POST">
        Date: <input type="date" name="date" value="<?= $expense['expense_date'] ?>" required><br><br>
        Category: <input type="text" name="category" value="<?= htmlspecialchars($expense['category']) ?>" required><br><br>
        Description: <input type="text" name="description" value="<?= htmlspecialchars($expense['description']) ?>"><br><br>
        Amount: <input type="number" name="amount" step="0.01" value="<?= $expense['amount'] ?>" required><br><br>
        <button type="submit">Update Expense</button>
    </form>
    <p><a href="view_expense.php">← Back to Expenses</a></p>

</body>
</html>
