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

// New query for monthly sales vs profit
$monthlySalesProfitQuery = "SELECT 
    DATE_FORMAT(IssueDate, '%b') AS month,
    SUM(BillAmount) AS total_sales,
    SUM(Profit) AS total_profit
FROM sales
WHERE YEAR(IssueDate) = YEAR(CURDATE())
GROUP BY month
ORDER BY MONTH(IssueDate)";

$monthlySalesProfitResult = mysqli_query($conn, $monthlySalesProfitQuery);

$monthlyLabels = [];
$monthlySales = [];
$monthlyProfit = [];

while ($row = mysqli_fetch_assoc($monthlySalesProfitResult)) {
    $monthlyLabels[] = $row['month'];
    $monthlySales[] = (float)$row['total_sales'];
    $monthlyProfit[] = (float)$row['total_profit'];
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
            margin-bottom: 30px;
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
        .charts-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
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

    <div class="charts-container">
        <div class="chart-container">
            <h4>Sales by Section</h4>
            <canvas id="salesPieChart"></canvas>
        </div>
        <div class="chart-container">
            <h4>Expenses by Month</h4>
            <canvas id="expenseBarChart"></canvas>
        </div>
        <div class="chart-container" style="margin-top: 20px;">
            <h4>Monthly Sales vs Profit</h4>
            <canvas id="salesProfitChart"></canvas>
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

    // New chart for monthly sales vs profit
    const salesProfitCtx = document.getElementById('salesProfitChart').getContext('2d');
    new Chart(salesProfitCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [
                {
                    label: 'Sales',
                    data: <?= json_encode($monthlySales) ?>,

                                        backgroundColor: 'rgba(0, 255, 89, 0.66)',
                    borderColor: 'rgba(0, 255, 89, 0.66)',
                    borderWidth: 1
                },
                {
                    label: 'Profit',
                    data: <?= json_encode($monthlyProfit) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 0.7)',
                    borderWidth: 1
                }
            ]
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
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
</body>
</html>