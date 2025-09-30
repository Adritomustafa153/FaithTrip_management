<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fare Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .calculator-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.15);
            padding: 30px;
        }
        .calculator-title {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: bold;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        .base-fare-container {
            text-align: right;
        }
        .tax-container {
            text-align: left;
        }
        .result-container {
            background-color: #e9f7ef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid #27ae60;
        }
        .result-label {
            font-weight: 600;
            color: #27ae60;
        }
        .btn-calculate {
            width: 100%;
            margin-top: 25px;
            background-color: #3498db;
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 6px;
            transition: all 0.3s;
        }
        .btn-calculate:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .input-group-text {
            background-color: #f8f9fa;
            color: #495057;
        }
        .tax-column {
            border-right: 1px dashed #dee2e6;
            padding-right: 30px;
        }
        .calculation-column {
            padding-left: 30px;
        }
        .section-title {
            font-weight: 600;
            color: #3498db;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .form-control {
            border-radius: 5px;
            border: 1px solid #ced4da;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .result-value {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.05rem;
        }
        @media (max-width: 768px) {
            .tax-column {
                border-right: none;
                border-bottom: 1px dashed #dee2e6;
                padding-right: 15px;
                padding-bottom: 30px;
                margin-bottom: 30px;
            }
            .calculation-column {
                padding-left: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="calculator-container">
        <h2 class="calculator-title">Fare Calculator</h2>
        
        <div class="row">
            <!-- Left Column - Tax Inputs -->
            <div class="col-md-6 tax-column">
                <h5 class="section-title">Tax Details</h5>
                
                <form id="fareCalculatorForm">
                    <!-- Base Fare -->
                    <div class="row mb-3">
                        <div class="col-md-6 base-fare-container">
                            <label for="baseFare" class="form-label">Base Fare</label>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="number" class="form-control" id="baseFare" step="0.01" min="0" value="356417" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Commission -->
                    <div class="row mb-3">
                        <div class="col-md-6 base-fare-container">
                            <label for="commission" class="form-label">Commission (%)</label>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="number" class="form-control" id="commission" step="0.01" min="0" max="100" value="7">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tax Fields -->
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="bd" class="form-label">BD (Embarkation Fee)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="bd" step="0.01" min="0" value="500">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="ut" class="form-label">UT (Travel Tax)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="ut" step="0.01" min="0" value="6000">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="ow" class="form-label">OW (Excise Duty Tax)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="ow" step="0.01" min="0" value="4000">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="e5" class="form-label">E5 (Value Added Tax on Embarkation Fees)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="e5" step="0.01" min="0" value="443">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="gb" class="form-label">GB (Air Passenger Duty)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="gb" step="0.01" min="0" value="35460">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="ub" class="form-label">UB (Passenger Service Charge)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="ub" step="0.01" min="0" value="8491">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="yr" class="form-label">YR (Fuel Charges)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="yr" step="0.01" min="0" value="4597">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="p7" class="form-label">P7 (P7)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="p7" step="0.01" min="0" value="1225">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 tax-container">
                            <label for="p8" class="form-label">P8 (Passenger Security Fee)</label>
                        </div>
                        <div class="col-md-6">
                            <input type="number" class="form-control tax-input" id="p8" step="0.01" min="0" value="1225">
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Right Column - Calculations -->
            <div class="col-md-6 calculation-column">
                <h5 class="section-title">Calculations</h5>
                
                <!-- Calculation Results -->
                <div class="result-container">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="result-label">Total Tax:</span>
                        </div>
                        <div class="col-md-6">
                            <span id="totalTax" class="result-value">61,941.00</span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="result-label">Total Fare:</span>
                        </div>
                        <div class="col-md-6">
                            <span id="totalFare" class="result-value">418,358.00</span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="result-label">Commission:</span>
                        </div>
                        <div class="col-md-6">
                            <span id="commissionAmount" class="result-value">24,949.19</span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="result-label">AIT (0.3%):</span>
                        </div>
                        <div class="col-md-6">
                            <span id="ait" class="result-value">1,234.25</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <span class="result-label">Net Payment:</span>
                        </div>
                        <div class="col-md-6">
                            <span id="netPayment" class="result-value">394,643.05</span>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-primary btn-calculate" id="calculateBtn">Calculate</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('calculateBtn').addEventListener('click', function() {
            calculateFare();
        });
        
        // Add event listeners to all input fields to recalculate when values change
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', calculateFare);
        });
        
        function formatNumber(num) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        }
        
        function calculateFare() {
            // Get base fare value
            const baseFare = parseFloat(document.getElementById('baseFare').value) || 0;
            
            // Get commission percentage
            const commissionPercentage = parseFloat(document.getElementById('commission').value) || 0;
            
            // Calculate commission amount
            const commissionAmount = baseFare * (commissionPercentage / 100);
            
            // Get all tax values
            const bd = parseFloat(document.getElementById('bd').value) || 0;
            const ut = parseFloat(document.getElementById('ut').value) || 0;
            const ow = parseFloat(document.getElementById('ow').value) || 0;
            const e5 = parseFloat(document.getElementById('e5').value) || 0;
            const gb = parseFloat(document.getElementById('gb').value) || 0;
            const ub = parseFloat(document.getElementById('ub').value) || 0;
            const yr = parseFloat(document.getElementById('yr').value) || 0;
            const p7 = parseFloat(document.getElementById('p7').value) || 0;
            const p8 = parseFloat(document.getElementById('p8').value) || 0;
            
            // Calculate total tax
            const totalTax = bd + ut + ow + e5 + gb + ub + yr + p7 + p8;
            
            // Calculate total fare
            const totalFare = baseFare + totalTax;
            
            // Calculate AIT
            const ait = (totalFare - (bd + ut + e5)) * 0.003;
            
            // Calculate net payment
            const netPayment = (baseFare - commissionAmount) + totalTax + ait;
            
            // Update the display with calculated values
            document.getElementById('totalTax').textContent = formatNumber(totalTax);
            document.getElementById('totalFare').textContent = formatNumber(totalFare);
            document.getElementById('commissionAmount').textContent = formatNumber(commissionAmount);
            document.getElementById('ait').textContent = formatNumber(ait);
            document.getElementById('netPayment').textContent = formatNumber(netPayment);
        }
        
        // Initialize calculation on page load
        window.addEventListener('load', calculateFare);
    </script>
</body>
</html>