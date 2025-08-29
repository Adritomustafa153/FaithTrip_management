<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RHC Meter - IATA Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --iata-blue: #0033a0;
            --iata-light-blue: #0099d7;
            --iata-gray: #f2f2f2;
            --iata-dark-gray: #666666;
            --iata-green: #00a651;
            --iata-yellow: #ffcc00;
            --iata-orange: #ff9900;
            --iata-red: #ed1c24;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .rhc-container {
            width: 380px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .rhc-header {
            background: var(--iata-blue);
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        
        .rhc-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .rhc-body {
            padding: 20px;
        }
        
        .period-info {
            text-align: center;
            margin-bottom: 15px;
            color: var(--iata-dark-gray);
            font-size: 13px;
            padding: 8px;
            background: var(--iata-gray);
            border-radius: 6px;
        }
        
        .percentage-display {
            text-align: center;
            margin: 15px 0 20px;
            position: relative;
        }
        
        .percentage-value {
            font-size: 52px;
            font-weight: 800;
            color: var(--iata-blue);
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .percentage-label {
            font-size: 16px;
            color: var(--iata-dark-gray);
            font-weight: 500;
        }
        
        .rhc-details {
            background: var(--iata-gray);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .detail-label {
            font-size: 13px;
            color: var(--iata-dark-gray);
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--iata-blue);
        }
        
        .progress-container {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 12px 0;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 5px;
            background: linear-gradient(to right, 
                var(--iata-green) 0%, 
                var(--iata-green) 25%, 
                var(--iata-yellow) 25%, 
                var(--iata-yellow) 50%, 
                var(--iata-orange) 50%, 
                var(--iata-orange) 75%, 
                var(--iata-red) 75%, 
                var(--iata-red) 100%);
        }
        
        .progress-markers {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 4px;
        }
        
        .marker {
            width: 1px;
            height: 5px;
            background: var(--iata-dark-gray);
            position: relative;
        }
        
        .marker-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 9px;
            color: var(--iata-dark-gray);
            margin-top: 2px;
        }
        
        .indicator {
            position: absolute;
            top: -22px;
            transform: translateX(-50%);
            font-weight: bold;
            color: var(--iata-blue);
            font-size: 14px;
        }
        
        .refresh-btn {
            background: var(--iata-blue);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 13px;
        }
        
        .refresh-btn:hover {
            background: #00257a;
        }
        
        .refresh-btn i {
            margin-right: 4px;
            font-size: 12px;
        }
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 160px;
        }
        
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(0, 51, 160, 0.1);
            border-left-color: var(--iata-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .risk-low { color: var(--iata-green); }
        .risk-medium { color: var(--iata-yellow); }
        .risk-high { color: var(--iata-orange); }
        .risk-critical { color: var(--iata-red); }
        
        .payment-info {
            background: #e8f4ff;
            border-left: 3px solid var(--iata-blue);
            padding: 10px;
            border-radius: 4px;
            margin-top: 12px;
            font-size: 12px;
        }
        
        .payment-due {
            font-weight: bold;
            color: var(--iata-blue);
            font-size: 13px;
        }
        
        .rhc-update {
            text-align: center;
            color: var(--iata-dark-gray);
            font-size: 12px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="rhc-container">
        <div class="rhc-header">
            <h2>RHC Amount</h2>
        </div>
        
        <div class="rhc-body">
            <div class="period-info">
                Current Period: <span id="current-period">16 Aug 2025 - 29 Aug 2025</span><br>
                Remittance Frequency: Fortnightly
            </div>
            
            <div id="loading" class="loading">
                <div class="spinner"></div>
            </div>
            
            <div id="dashboard-content" style="display: none;">
                <div class="percentage-display">
                    <div class="percentage-value risk-low" id="percentage-value">0%</div>
                    <div class="percentage-label">Percentage usage</div>
                    
                    <div class="progress-container">
                        <div class="progress-bar" id="progress-bar"></div>
                    </div>
                    
                    <div class="progress-markers">
                        <div class="marker" style="left: 0%;"><span class="marker-label">0%</span></div>
                        <div class="marker" style="left: 25%;"><span class="marker-label">25%</span></div>
                        <div class="marker" style="left: 50%;"><span class="marker-label">50%</span></div>
                        <div class="marker" style="left: 75%;"><span class="marker-label">75%</span></div>
                        <div class="marker" style="left: 90%;"><span class="marker-label">90%</span></div>
                    </div>
                    
                    <div class="indicator" id="indicator">⬇</div>
                </div>
                
                <div class="rhc-details">
                    <div class="detail-row">
                        <span class="detail-label">Current usage</span>
                        <span class="detail-value" id="current-usage">BDT 0</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">RHC Limit</span>
                        <span class="detail-value">BDT 10,000,000</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Remaining Balance (90% of RHC)</span>
                        <span class="detail-value" id="remaining-balance">BDT 9,000,000</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Paid Amount (This Month)</span>
                        <span class="detail-value" id="paid-amount">BDT 0</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Fortnight Payment Due</span>
                        <span class="detail-value" id="fortnight-payment">BDT 0</span>
                    </div>
                </div>
                
                <div class="payment-info">
                    Next Payment Due: <span id="payment-due-date" class="payment-due">30 Aug 2025</span><br>
                    <span id="payment-description">For tickets issued in Fortnight 1</span>
                </div>
                
                <div class="text-center mt-3">
                    <button class="refresh-btn" id="refresh-btn">
                        <i class="fas fa-sync-alt"></i>Refresh Data
                    </button>
                </div>
                
                <div class="rhc-update">
                    Last Updated: <span id="last-updated">Tue, 26 Aug 2025</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Constants - 90% of 1,00,00,000
            const RHC_LIMIT = 10000000;
            const RHC_LIMITP = RHC_LIMIT*0.90;
            
            // Function to format currency
            function formatCurrency(amount) {
                return 'BDT ' + amount.toLocaleString('en-IN');
            }
            
            // Function to update the dashboard
            function updateDashboard(data) {
                // Hide loading, show content
                document.getElementById('loading').style.display = 'none';
                document.getElementById('dashboard-content').style.display = 'block';
                
                // Calculate percentage based on current usage (capped at 90%)
                const percentage = Math.min(90, (data.currentUsage / RHC_LIMIT * 100)).toFixed(0);
                const remainingBalance = Math.max(0, RHC_LIMITP - data.currentUsage);
                
                // Update the display
                document.getElementById('percentage-value').textContent = percentage + '%';
                document.getElementById('current-usage').textContent = formatCurrency(data.currentUsage);
                document.getElementById('remaining-balance').textContent = formatCurrency(remainingBalance);
                document.getElementById('paid-amount').textContent = formatCurrency(data.paidThisMonth);
                document.getElementById('fortnight-payment').textContent = formatCurrency(data.fortnightPayment);
                document.getElementById('payment-due-date').textContent = data.paymentDueDate;
                document.getElementById('payment-description').textContent = data.paymentDescription;
                
                // Update progress bar (capped at 90%)
                const progressPercentage = Math.min(90, percentage);
                document.getElementById('progress-bar').style.width = progressPercentage + '%';
                document.getElementById('indicator').style.left = progressPercentage + '%';
                
                // Update risk color based on percentage
                const percentageElement = document.getElementById('percentage-value');
                percentageElement.className = 'percentage-value '; // Reset classes
                
                if (percentage < 25) {
                    percentageElement.classList.add('risk-low');
                } else if (percentage < 50) {
                    percentageElement.classList.add('risk-medium');
                } else if (percentage < 75) {
                    percentageElement.classList.add('risk-high');
                } else {
                    percentageElement.classList.add('risk-critical');
                }
                
                // Update last updated time
                const now = new Date();
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                
                const day = days[now.getDay()];
                const dateNum = now.getDate();
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                
                document.getElementById('last-updated').textContent = 
                    `${day}, ${dateNum} ${month} ${year}`;
            }
            
            // Function to fetch data from server
            function fetchData() {
                // Show loading, hide content
                document.getElementById('loading').style.display = 'flex';
                document.getElementById('dashboard-content').style.display = 'none';
                
                // Fetch data from server
                fetch('get_rhc_data1.php')
                    .then(response => response.json())
                    .then(data => {
                        updateDashboard(data);
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        // Fallback to static data if server fails
                        updateDashboard({
                            currentUsage: 1602911,
                            paidThisMonth: 884009,
                            fortnightPayment: 4500000,
                            paymentDueDate: "30 Aug 2025",
                            paymentDescription: "For tickets issued in Fortnight 1"
                        });
                    });
            }
            
            // Set up the current period display (fortnightly)
            function setCurrentPeriod() {
                const now = new Date();
                const currentDate = now.getDate();
                const currentMonth = now.getMonth();
                const currentYear = now.getFullYear();
                
                // Calculate start and end of current fortnight
                let startDate, endDate;
                if (currentDate <= 15) {
                    startDate = 1;
                    endDate = 15;
                } else {
                    startDate = 16;
                    // Get last day of current month
                    endDate = new Date(currentYear, currentMonth + 1, 0).getDate();
                }
                
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                
                document.getElementById('current-period').textContent = 
                    `${startDate} ${months[currentMonth]} ${currentYear} - ${endDate} ${months[currentMonth]} ${currentYear}`;
            }
            
            // Initial data fetch
            fetchData();
            setCurrentPeriod();
            
            // Refresh button functionality
            document.getElementById('refresh-btn').addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Refreshing...';
                this.disabled = true;
                
                fetchData();
                
                // Re-enable button after a short delay
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Data';
                    this.disabled = false;
                }, 2000);
            });
        });
    </script>
</body>
</html>