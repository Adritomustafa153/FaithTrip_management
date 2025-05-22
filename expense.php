<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Register</title>
<style>
    body {
    font-family: Arial, sans-serif;
    background: #f8f9fa;
    /* padding: 20px; */
}

.container {
    max-width: 500px;
    margin: auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

form input, form textarea, form button {
    width: 100%;
    padding: 8px;
    margin-top: 8px;
    margin-bottom: 16px;
    border-radius: 4px;
    border: 1px solid #ccc;
}

form button {
    background-color: #28a745;
    color: white;
    border: none;
    cursor: pointer;
}

form button:hover {
    background-color: #218838;
}

#response {
    margin-top: 10px;
    color: green;
}

</style>
</head>
<body>

<?php include 'nav.php'  ?>
    <div class="container" style="margin: top 15px;">
        <h2 style="text-align:center;">Office Expense Register</h2>
        <form id="expenseForm">
            <label>Date:</label>
            <input type="date" name="expense_date" required><br>

            <label>Category:</label>
            <input type="text" name="category" placeholder="e.g., Stationery, Travel" required><br>

            <label>Description:</label>
            <textarea name="description" rows="3"></textarea><br>

            <label>Amount:</label>
            <input type="number" step="0.01" name="amount" required><br>

            <button type="submit">Submit</button>
            <p id="response"></p>
        </form>
    </div>

    <script>
        const form = document.getElementById('expenseForm');
        const response = document.getElementById('response');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(form);

            fetch('submit_expense.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                response.textContent = data;
                form.reset();
            })
            .catch(err => {
                response.textContent = "Error submitting data!";
                console.error(err);
            });
        });
    </script>
</body>
</html>
