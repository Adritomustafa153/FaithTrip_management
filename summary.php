<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2980b9;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            /* background-color: var(--primary); */
            color: black;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        header h1 {
            margin: 0;
            text-align: center;
            font-weight: 500;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        select, button {
            padding: 10px 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        
        select {
            min-width: 150px;
        }
        
        button {
            background-color: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: var(--secondary);
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card-sells {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }
        .summary-card-profit {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--success);
        }
        .summary-card-transcations {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--success);
        }
        .summary-card-reissue {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--warning);
        }
        .summary-card-refund {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--danger);
        }
        
        .summary-card-sells h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 18px;
        }
        .summary-card-profit h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 18px;
        }
        .summary-card-transcations h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 18px;
        }
        .summary-card-reissue h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 18px;
        }
        .summary-card-refund h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 18px;
        }
        
        .summary-value {
            font-size: 16px;
            font-weight: 600;
            margin: 10px 0;
        }

        
        
        .summary-label {
            color: #666;
            font-size: 14px;
        }
        
        .chart-container {
            height: 400px;
            margin-bottom: 30px;
        }
        
        .details-btn {
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }
        
        tr:hover {
            background-color: #f5f7fa;
        }
        
        .sell { color: var(--success); }
        .reissue { color: var(--info); }
        .refund { color: var(--danger); }
        
        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-section {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
<?php include 'nav.php'; ?>
    <header>
        <div class="container">
            <h1>Sales Summary Dashboard</h1>
        </div>
    </header>
    
    <main class="container">
        <div class="dashboard-card">
            <h2>Sales Overview</h2>
            
            <div class="filter-section">
                <select id="timePeriod">
                    <option value="monthly">Monthly Report</option>
                    <option value="yearly">Yearly Report</option>
                </select>
                
                <select id="monthSelect">
                    <?php
                    $months = [
                        '01' => 'January', '02' => 'February', '03' => 'March',
                        '04' => 'April', '05' => 'May', '06' => 'June',
                        '07' => 'July', '08' => 'August', '09' => 'September',
                        '10' => 'October', '11' => 'November', '12' => 'December'
                    ];
                    
                    $currentMonth = date('m');
                    foreach ($months as $num => $name) {
                        $selected = ($num == $currentMonth) ? 'selected' : '';
                        echo "<option value='$num' $selected>$name</option>";
                    }
                    ?>
                </select>
                
                <select id="yearSelect">
                    <?php
                    $currentYear = date('Y');
                    for ($year = $currentYear; $year >= 2020; $year--) {
                        $selected = ($year == $currentYear) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
                
                <button id="generateReport">Generate Report</button>
            </div>
            
            <div class="summary-cards">
                <div class="summary-card-sells">
                    <h3>Total Sales</h3>
                    <div class="summary-value" id="totalSales">0.00</div>
                    <div class="summary-label">All transactions (Sell + Reissue - Refund)</div>
                </div>
                
                <div class="summary-card-profit">
                    <h3>Total Profit</h3>
                    <div class="summary-value" id="totalProfit">0.00</div>
                    <div class="summary-label">Profit from all transactions</div>
                </div>
                
                <div class="summary-card-transcations">
                    <h3>Sell Transactions</h3>
                    <div class="summary-value sell" id="sellCount">0</div>
                    <div class="summary-label">Total Sell records</div>
                </div>
                
                <div class="summary-card-reissue">
                    <h3>Reissue Transactions</h3>
                    <div class="summary-value reissue" id="reissueCount">0</div>
                    <div class="summary-label">Total Reissue records</div>
                </div>
                
                <div class="summary-card-refund">
                    <h3>Refund Transactions</h3>
                    <div class="summary-value refund" id="refundCount">0</div>
                    <div class="summary-label">Total Refund records</div>
                </div>
            </div>
            
            <button id="viewDetails" class="details-btn">View Details</button>
            
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        
        <div class="dashboard-card hidden" id="detailsSection">
            <h2>Transaction Details</h2>
            <div id="detailsContent"></div>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            // Initialize chart
            const ctx = document.getElementById('salesChart').getContext('2d');
            let salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Sales Amount',
                            data: [],
                            backgroundColor: 'rgba(0, 122, 41, 0.82)',
                            borderColor: 'rgba(0,122, 41, 0.82)',
                            borderWidth: 1
                        },
                        {
                            label: 'Profit',
                            data: [],
                            backgroundColor: 'rgba(0, 5, 160, 0.7)',
                            borderColor: 'rgba(0,5, 160, 0.7)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Toggle time period
            $('#timePeriod').change(function() {
                if ($(this).val() === 'yearly') {
                    $('#monthSelect').hide();
                } else {
                    $('#monthSelect').show();
                }
                generateReport();
            });
            
            // Generate report
            $('#generateReport').click(generateReport);
            
            // View details
            $('#viewDetails').click(function() {
                const period = $('#timePeriod').val();
                const month = $('#monthSelect').val();
                const year = $('#yearSelect').val();
                
                $.ajax({
                    url: 'get_details.php',
                    type: 'GET',
                    data: {
                        period: period,
                        month: month,
                        year: year
                    },
                    success: function(response) {
                        $('#detailsContent').html(response);
                        $('#detailsSection').removeClass('hidden');
                        $('html, body').animate({
                            scrollTop: $('#detailsSection').offset().top
                        }, 500);
                    }
                });
            });
            
            // Load initial report
            generateReport();
            
            function generateReport() {
                const period = $('#timePeriod').val();
                const month = $('#monthSelect').val();
                const year = $('#yearSelect').val();
                
                $.ajax({
                    url: 'get_report.php',
                    type: 'GET',
                    data: {
                        period: period,
                        month: month,
                        year: year
                    },
                    dataType: 'json',
                    success: function(data) {
                        // Update summary cards
                        $('#totalSales').text(data.total_sales.toLocaleString('en-US', {
                            style: 'currency',
                            currency: 'BDT',
                            minimumFractionDigits: 2
                        }));
                        
                        $('#totalProfit').text(data.total_profit.toLocaleString('en-US', {
                            style: 'currency',
                            currency: 'BDT',
                            minimumFractionDigits: 2
                        }));
                        
                        $('#sellCount').text(data.sell_count);
                        $('#reissueCount').text(data.reissue_count);
                        $('#refundCount').text(data.refund_count);
                        
                        // Update chart
                        updateChart(data.chart_data);
                    }
                });
            }
            
            function updateChart(chartData) {
                salesChart.data.labels = chartData.labels;
                salesChart.data.datasets[0].data = chartData.sales;
                salesChart.data.datasets[1].data = chartData.profit;
                salesChart.update();
            }
        });
    </script>
</body>
</html>