<?php
include('db.php');

$filter = $_GET['filter'] ?? 'monthly';
$whereClause = "";

if ($filter === 'monthly') {
    $whereClause = "WHERE MONTH(IssueDate) = MONTH(CURDATE()) AND YEAR(IssueDate) = YEAR(CURDATE())";
} elseif ($filter === 'yearly') {
    $whereClause = "WHERE YEAR(IssueDate) = YEAR(CURDATE())";
}

$salesQuery = "SELECT section, SUM(BillAmount) AS total FROM sales $whereClause GROUP BY section";
$salesResult = mysqli_query($conn, $salesQuery);

$salesData = ['Agent' => 0, 'Counter' => 0, 'Corporate' => 0];
while ($row = mysqli_fetch_assoc($salesResult)) {
    $key = ucfirst(strtolower($row['section']));
    if (array_key_exists($key, $salesData)) {
        $salesData[$key] = (float)$row['total'];
    }
}

$expenseQuery = "SELECT DATE_FORMAT(expense_date, '%b') AS month, SUM(amount) AS total FROM expenses GROUP BY month ORDER BY MIN(expense_date)";
$expenseResult = mysqli_query($conn, $expenseQuery);

$expenseMonths = [];
$expenseTotals = [];

while ($row = mysqli_fetch_assoc($expenseResult)) {
    $expenseMonths[] = $row['month'];
    $expenseTotals[] = (float)$row['total'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background:rgb(255, 255, 255);
            padding: 0px;
            margin: 0;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .filter-box {
            margin-bottom: 20px;
        }
        .charts-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0;
            
        }
        .chart-container {
            width: 45%;
            text-align: center;
        }
        canvas {
            display: block;
            margin: 0 auto;
            width: 50% !important;
            height: 280px !important;
            border-radius: 1px;
        }
        select {
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <?php include 'nav.php' ?>
    <div class="dashboard-header">
        <h2>Sales Dashboard</h2>
        <div class="filter-box">
            <label for="salesFilter">Filter by:</label>
            <select id="salesFilter" onchange="updateDashboard()">
                <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                <option value="total" <?= $filter === 'total' ? 'selected' : '' ?>>Total</option>
            </select>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-container">
            <h3>Sales by Section (Pie Chart)</h3>
            <canvas id="salesPieChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Expenses by Month (Bar Graph)</h3>
            <canvas id="expenseBarChart"></canvas>
        </div>
    </div>

    <script>
    function updateDashboard() {
        const filter = document.getElementById('salesFilter').value;
        window.location.href = `dashboard.php?filter=${filter}`;
    }

    const salesPieCtx = document.getElementById('salesPieChart').getContext('2d');
    new Chart(salesPieCtx, {
        type: 'pie',
        data: {
            labels: ['Agent', 'Counter', 'Corporate'],
            datasets: [{
                data: [<?= $salesData['Agent'] ?>, <?= $salesData['Counter'] ?>, <?= $salesData['Corporate'] ?>],
                backgroundColor: [
                    'rgba(8, 180, 42, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgb(255, 64, 0)'
                ],
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    const expenseBarCtx = document.getElementById('expenseBarChart').getContext('2d');
    new Chart(expenseBarCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($expenseMonths) ?>,
            datasets: [{
                label: 'Expenses',
                data: <?= json_encode($expenseTotals) ?>,
                backgroundColor: 'rgb(0, 64, 255)',
                borderColor: 'rgb(99, 255, 255)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '৳' + value;
                        }
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
    </script>
</body>
</html>
