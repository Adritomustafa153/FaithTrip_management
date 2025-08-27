<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RHC Meter - IATA Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .rhc-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .rhc-body {
            padding: 25px;
        }
        
        .percentage-display {
            text-align: center;
            margin: 20px 0 25px;
            position: relative;
        }
        
        .percentage-value {
            font-size: 62px;
            font-weight: 800;
            color: var(--iata-blue);
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .percentage-label {
            font-size: 18px;
            color: var(--iata-dark-gray);
            font-weight: 500;
        }
        
        .rhc-details {
            background: var(--iata-gray);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .detail-label {
            font-size: 14px;
            color: var(--iata-dark-gray);
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--iata-blue);
        }
        
        .period-info {
            text-align: center;
            margin-bottom: 20px;
            color: var(--iata-dark-gray);
            font-size: 14px;
        }
        
        .rhc-update {
            text-align: center;
            color: var(--iata-dark-gray);
            font-size: 13px;
            margin-top: 20px;
        }
        
        .risk-low { color: var(--iata-green); }
        .risk-medium { color: var(--iata-yellow); }
        .risk-high { color: var(--iata-orange); }
        .risk-critical { color: var(--iata-red); }
        
        .progress-container {
            height: 12px;
            background: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 6px;
            background: linear-gradient(to right, 
                var(--iata-green) 0%, 
                var(--iata-green) 25%, 
                var(--iata-yellow) 25%, 
                var(--iata-yellow) 50%, 
                var(--iata-orange) 50%, 
                var(--iata-orange) 75%, 
                var(--iata-red) 75%, 
                var(--iata-red) 100%);
            width: 19%;
        }
        
        .progress-markers {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 5px;
        }
        
        .marker {
            width: 1px;
            height: 6px;
            background: var(--iata-dark-gray);
            position: relative;
        }
        
        .marker-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: var(--iata-dark-gray);
            margin-top: 3px;
        }
        
        .indicator {
            position: absolute;
            top: -25px;
            left: 19%;
            transform: translateX(-50%);
            font-weight: bold;
            color: var(--iata-blue);
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
                Current Period: 16 Aug 2025 - 29 Aug 2025<br>
                Remittance Frequency: Fortnightly
            </div>
            
            <div class="percentage-display">
                <div class="percentage-value risk-low">19%</div>
                <div class="percentage-label">Percentage usage</div>
                
                <div class="progress-container">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                
                <div class="progress-markers">
                    <div class="marker" style="left: 0%;"><span class="marker-label">0%</span></div>
                    <div class="marker" style="left: 25%;"><span class="marker-label">25%</span></div>
                    <div class="marker" style="left: 50%;"><span class="marker-label">50%</span></div>
                    <div class="marker" style="left: 75%;"><span class="marker-label">75%</span></div>
                    <div class="marker" style="left: 100%;"><span class="marker-label">100%</span></div>
                </div>
                
                <div class="indicator" id="indicator">⬇</div>
            </div>
            
            <div class="rhc-details">
                <div class="detail-row">
                    <span class="detail-label">Current usage</span>
                    <span class="detail-value">BDT 1,871,742</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">RHC Limit</span>
                    <span class="detail-value">BDT 10,000,000</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Remaining Balance</span>
                    <span class="detail-value">BDT 8,128,258</span>
                </div>
            </div>
            
            <div class="rhc-update">
                Last Updated: Tue, 26 Aug 2025
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // For demonstration purposes, we're using static data
            // In a real implementation, you would fetch this from the server
            
            const RHC_LIMIT = 10000000;
            const CURRENT_USAGE = 1871742;
            
            // Calculate percentage
            const percentage = (CURRENT_USAGE / RHC_LIMIT * 100).toFixed(0);
            
            // Update display with static data
            document.querySelector('.percentage-value').textContent = percentage + '%';
            document.getElementById('progress-bar').style.width = percentage + '%';
            document.getElementById('indicator').style.left = percentage + '%';
            
            // Update risk color based on percentage
            const percentageElement = document.querySelector('.percentage-value');
            if (percentage < 25) {
                percentageElement.className = 'percentage-value risk-low';
            } else if (percentage < 50) {
                percentageElement.className = 'percentage-value risk-medium';
            } else if (percentage < 75) {
                percentageElement.className = 'percentage-value risk-high';
            } else {
                percentageElement.className = 'percentage-value risk-critical';
            }
            
            // Simulate live updates (in a real implementation, this would come from the server)
            setInterval(() => {
                const now = new Date();
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                
                const day = days[now.getDay()];
                const dateNum = now.getDate();
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                
                document.querySelector('.rhc-update').textContent = 
                    `Last Updated: ${day}, ${dateNum} ${month} ${year}`;
            }, 60000);
        });
    </script>
</body>
</html>