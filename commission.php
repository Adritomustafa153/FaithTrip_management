<?php
require_once 'db.php';

// Initialize variables
$salesPerson = '';
$salesData = [];
$salesPersonInfo = [];
$monthlySales = [];
$companyMonthlySales = 0;
$companyYearlySales = 0;
$companyMonthlyProfit = 0;
$companyYearlyProfit = 0;
$commissionRate = 10; // Default commission rate (10%)

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sales_person'])) {
        $salesPerson = $_POST['sales_person'];
        
        // Get sales person info
        $stmt = $conn->prepare("SELECT * FROM sales_person WHERE name = ?");
        $stmt->bind_param("s", $salesPerson);
        $stmt->execute();
        $result = $stmt->get_result();
        $salesPersonInfo = $result->fetch_assoc();
        
        // Get all sales for this person
        $stmt = $conn->prepare("SELECT * FROM sales WHERE SalesPersonName = ?");
        $stmt->bind_param("s", $salesPerson);
        $stmt->execute();
        $result = $stmt->get_result();
        $salesData = $result->fetch_all(MYSQLI_ASSOC);
        
        // Calculate monthly sales data for charts
        $currentYear = date('Y');
        $currentMonth = date('m');
        
        $monthlySales = array_fill(1, 12, ['sales' => 0, 'profit' => 0]);
        
        foreach ($salesData as $sale) {
            $saleDate = new DateTime($sale['IssueDate']);
            $saleMonth = $saleDate->format('n');
            $monthlySales[$saleMonth]['sales'] += $sale['BillAmount'];
            $monthlySales[$saleMonth]['profit'] += $sale['Profit'];
        }
        
        // Calculate company totals for comparison charts
        // Current month company sales
        $firstDay = date('Y-m-01');
        $lastDay = date('Y-m-t');
        
        $stmt = $conn->prepare("SELECT SUM(BillAmount) as total_sales, SUM(Profit) as total_profit FROM sales WHERE IssueDate BETWEEN ? AND ?");
        $stmt->bind_param("ss", $firstDay, $lastDay);
        $stmt->execute();
        $result = $stmt->get_result();
        $companyMonthly = $result->fetch_assoc();
        $companyMonthlySales = $companyMonthly['total_sales'] ?? 0;
        $companyMonthlyProfit = $companyMonthly['total_profit'] ?? 0;
        
        // Current year company sales
        $firstDayYear = date('Y-01-01');
        $lastDayYear = date('Y-12-31');
        
        $stmt = $conn->prepare("SELECT SUM(BillAmount) as total_sales, SUM(Profit) as total_profit FROM sales WHERE IssueDate BETWEEN ? AND ?");
        $stmt->bind_param("ss", $firstDayYear, $lastDayYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $companyYearly = $result->fetch_assoc();
        $companyYearlySales = $companyYearly['total_sales'] ?? 0;
        $companyYearlyProfit = $companyYearly['total_profit'] ?? 0;
        
        // Get commission rate from POST if set
        if (isset($_POST['commission_rate'])) {
            $commissionRate = floatval($_POST['commission_rate']);
        }
    }
}

// Get all sales persons for autocomplete
$salesPersons = [];
$result = $conn->query("SELECT name FROM sales_person");
while ($row = $result->fetch_assoc()) {
    $salesPersons[] = $row['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Performance & Commission Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .highlight-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .summary-item {
            margin-bottom: 15px;
        }
        .summary-label {
            font-weight: 600;
            color: #6c757d;
        }
        .autocomplete {
            position: relative;
        }
        .autocomplete-items {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
        }
        .autocomplete-items div {
            padding: 10px;
            cursor: pointer;
            background-color: #fff; 
            border-bottom: 1px solid #d4d4d4; 
        }
        .autocomplete-items div:hover {
            background-color: #e9e9e9; 
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Sales Performance & Commission Calculator</h2>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <form method="POST" class="row g-3">
                    <div class="col-md-9 autocomplete">
                        <label for="sales_person" class="form-label">Sales Person</label>
                        <input type="text" class="form-control" id="sales_person" name="sales_person" 
                               value="<?php echo htmlspecialchars($salesPerson); ?>" 
                               placeholder="Type sales person name..." required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">View Sales</button>
                    </div>
                    
                    <?php if (!empty($salesPerson)): ?>
                    <div class="col-md-3">
                        <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                        <input type="number" class="form-control" id="commission_rate" name="commission_rate" 
                               value="<?php echo $commissionRate; ?>" step="0.1" min="0" max="100">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary">Update Rate</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if (!empty($salesPerson) && !empty($salesData)): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="dashboard-card">
                    <h4>Sales Summary for <?php echo htmlspecialchars($salesPerson); ?></h4>
                    <hr>
                                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="summary-item">
                                <div class="summary-label">Current Month Sales</div>
                                <div class="highlight-number">
                                    ৳<?php echo number_format($monthlySales[date('n')]['sales'], 2); ?>
                                </div>
                            </div>
                            
                            <div class="summary-item">
                                <div class="summary-label">Current Month Commission</div>
                                <div class="highlight-number">
                                    ৳<?php echo number_format($monthlySales[date('n')]['profit'] * $commissionRate / 100, 2); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="summary-item">
                                <div class="summary-label">Total Sales</div>
                                <div class="highlight-number">
                                    ৳<?php 
                                        $totalSales = array_sum(array_column($salesData, 'BillAmount'));
                                        echo number_format($totalSales, 2); 
                                    ?>
                                </div>
                            </div>
                            
                            <div class="summary-item">
                                <div class="summary-label">Total Commission</div>
                                <div class="highlight-number">
                                    ৳<?php 
                                        $totalProfit = array_sum(array_column($salesData, 'Profit'));
                                        echo number_format($totalProfit * $commissionRate / 100, 2); 
                                    ?>
                                </div>
                               
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h5>Sales Person Information</h5>
                        <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($salesPersonInfo['employee_id'] ?? 'N/A'); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($salesPersonInfo['phone'] ?? 'N/A'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($salesPersonInfo['email'] ?? 'N/A'); ?></p>
                        <p><strong>Joining Date:</strong> <?php echo htmlspecialchars($salesPersonInfo['joining_date'] ?? 'N/A'); ?></p>
                    </div>
                     <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#salesDetails" aria-expanded="false" aria-controls="salesDetails">
                    View Sales Details
                </button>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="dashboard-card">
                    <h4>Performance Charts</h4>
                    <hr>
                    
                    <div class="chart-container">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="monthlyImpactChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="yearlyImpactChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="monthlyProfitImpactChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="yearlyProfitImpactChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="commissionRateChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">

                
                <div class="collapse mt-3" id="salesDetails">
                    <div class="card card-body">
                        <h4>All Sales by <?php echo htmlspecialchars($salesPerson); ?></h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Issue Date</th>
                                        <th>Passenger</th>
                                        <th>Airline</th>
                                        <th>Route</th>
                                        <th>Bill Amount</th>
                                        <th>Profit</th>
                                        <th>Commission</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salesData as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['IssueDate']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['PassengerName']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['airlines']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['TicketRoute']); ?></td>
                                        <td>৳<?php echo number_format($sale['BillAmount'], 2); ?></td>
                                        <td>৳<?php echo number_format($sale['Profit'], 2); ?></td>
                                        <td>৳<?php echo number_format($sale['Profit'] * $commissionRate / 100, 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $sale['PaymentStatus'] === 'Paid' ? 'success' : 
                                                     ($sale['PaymentStatus'] === 'Partially Paid' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo htmlspecialchars($sale['PaymentStatus']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif (!empty($salesPerson)): ?>
        <div class="alert alert-warning mt-4">
            No sales data found for <?php echo htmlspecialchars($salesPerson); ?>.
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <script>
        // Autocomplete functionality
        function autocomplete(inp, arr) {
            inp.addEventListener("input", function(e) {
                var val = this.value;
                closeAllLists();
                if (!val) { return false; }
                
                var a = document.createElement("DIV");
                a.setAttribute("id", this.id + "autocomplete-list");
                a.setAttribute("class", "autocomplete-items");
                this.parentNode.appendChild(a);
                
                var matches = arr.filter(item => item.toLowerCase().includes(val.toLowerCase()));
                
                if (matches.length === 0) {
                    var b = document.createElement("DIV");
                    b.innerHTML = "<em>No matches found</em>";
                    a.appendChild(b);
                    return;
                }
                
                matches.slice(0, 5).forEach(match => {
                    var b = document.createElement("DIV");
                    b.innerHTML = "<strong>" + match.substr(0, val.length) + "</strong>";
                    b.innerHTML += match.substr(val.length);
                    b.innerHTML += "<input type='hidden' value='" + match + "'>";
                    b.addEventListener("click", function(e) {
                        inp.value = this.getElementsByTagName("input")[0].value;
                        closeAllLists();
                    });
                    a.appendChild(b);
                });
            });
            
            function closeAllLists(elmnt) {
                var x = document.getElementsByClassName("autocomplete-items");
                for (var i = 0; i < x.length; i++) {
                    if (elmnt != x[i] && elmnt != inp) {
                        x[i].parentNode.removeChild(x[i]);
                    }
                }
            }
            
            document.addEventListener("click", function(e) {
                closeAllLists(e.target);
            });
        }
        
        // Initialize autocomplete
        var salesPersons = <?php echo json_encode($salesPersons); ?>;
        autocomplete(document.getElementById("sales_person"), salesPersons);
    </script>
    
    <?php if (!empty($salesPerson) && !empty($salesData)): ?>
    <script>
        // Monthly Sales Chart
        const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
        const monthlySalesChart = new Chart(monthlySalesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Monthly Sales (BDT)',
                    data: [
                        <?php echo $monthlySales[1]['sales']; ?>,
                        <?php echo $monthlySales[2]['sales']; ?>,
                        <?php echo $monthlySales[3]['sales']; ?>,
                        <?php echo $monthlySales[4]['sales']; ?>,
                        <?php echo $monthlySales[5]['sales']; ?>,
                        <?php echo $monthlySales[6]['sales']; ?>,
                        <?php echo $monthlySales[7]['sales']; ?>,
                        <?php echo $monthlySales[8]['sales']; ?>,
                        <?php echo $monthlySales[9]['sales']; ?>,
                        <?php echo $monthlySales[10]['sales']; ?>,
                        <?php echo $monthlySales[11]['sales']; ?>,
                        <?php echo $monthlySales[12]['sales']; ?>
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Sales Performance'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '৳' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Monthly Impact Chart
        const monthlyImpactCtx = document.getElementById('monthlyImpactChart').getContext('2d');
        const monthlyImpactChart = new Chart(monthlyImpactCtx, {
            type: 'pie',
            data: {
                labels: ['<?php echo $salesPerson; ?>', 'Other Sales'],
                datasets: [{
                    data: [
                        <?php echo $monthlySales[date('n')]['sales']; ?>,
                        <?php echo max(1, $companyMonthlySales - $monthlySales[date('n')]['sales']); ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(254, 69, 217, 0.89)'
                    ],
                    borderColor: [
                        'rgba(14, 104, 165, 1)',
                        'rgba(73, 82, 74, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Sales Impact'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ৳${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Yearly Impact Chart
        const yearlyImpactCtx = document.getElementById('yearlyImpactChart').getContext('2d');
        const yearlyImpactChart = new Chart(yearlyImpactCtx, {
            type: 'pie',
            data: {
                labels: ['<?php echo $salesPerson; ?>', 'Other Sales'],
                datasets: [{
                    data: [
                        <?php echo array_sum(array_column($monthlySales, 'sales')); ?>,
                        <?php echo max(1, $companyYearlySales - array_sum(array_column($monthlySales, 'sales'))); ?>
                    ],
                    backgroundColor: [
                        'rgba(221, 255, 0, 0.96)',
                        'rgba(255, 128, 23, 1)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(201, 203, 207, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Yearly Sales Impact'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ৳${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Monthly Profit Impact Chart
        const monthlyProfitImpactCtx = document.getElementById('monthlyProfitImpactChart').getContext('2d');
        const monthlyProfitImpactChart = new Chart(monthlyProfitImpactCtx, {
            type: 'pie',
            data: {
                labels: ['<?php echo $salesPerson; ?>', 'Other Profit'],
                datasets: [{
                    data: [
                        <?php echo $monthlySales[date('n')]['profit']; ?>,
                        <?php echo max(1, $companyMonthlyProfit - $monthlySales[date('n')]['profit']); ?>
                    ],
                    backgroundColor: [
                        'rgba(16, 198, 13, 0.7)',
                        'rgba(27, 99, 244, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(201, 203, 207, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Profit Impact'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ৳${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Yearly Profit Impact Chart
        const yearlyProfitImpactCtx = document.getElementById('yearlyProfitImpactChart').getContext('2d');
        const yearlyProfitImpactChart = new Chart(yearlyProfitImpactCtx, {
            type: 'pie',
            data: {
                labels: ['<?php echo $salesPerson; ?>', 'Other Profit'],
                datasets: [{
                    data: [
                        <?php echo array_sum(array_column($monthlySales, 'profit')); ?>,
                        <?php echo max(1, $companyYearlyProfit - array_sum(array_column($monthlySales, 'profit'))); ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(201, 203, 207, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(201, 203, 207, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Yearly Profit Impact'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ৳${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Commission Rate Chart
        const commissionRateCtx = document.getElementById('commissionRateChart').getContext('2d');
        const commissionRateChart = new Chart(commissionRateCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Commission Earned (BDT)',
                    data: [
                        <?php echo $monthlySales[1]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[2]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[3]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[4]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[5]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[6]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[7]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[8]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[9]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[10]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[11]['profit'] * $commissionRate / 100; ?>,
                        <?php echo $monthlySales[12]['profit'] * $commissionRate / 100; ?>
                    ],
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Commission Earned (<?php echo $commissionRate; ?>% of Profit)'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '৳' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>