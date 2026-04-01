<?php
include 'auth_check.php';
include 'db.php';

// Fetch company names for dropdown
$companyQuery = "SELECT DISTINCT PartyName FROM sales";
$companyResult = $conn->query($companyQuery);

// Fetch sales records
$where = "";
if (isset($_GET['company']) && !empty($_GET['company'])) {
    $company = $conn->real_escape_string($_GET['company']);
    $where .= " WHERE PartyName = '$company'";
}
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = $conn->real_escape_string($_GET['invoice']);
    $where .= ($where ? " AND" : " WHERE") . " invoice_number LIKE '%$invoice%'";
}
if (isset($_GET['pnr']) && !empty($_GET['pnr'])) {
    $pnr_ = $conn->real_escape_string($_GET['pnr']);
    $where .= ($where ? " AND" : " WHERE") . " PNR LIKE '%$pnr_%'";
}
if (isset($_GET['from_date']) && !empty($_GET['from_date']) && isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $from_date = $conn->real_escape_string($_GET['from_date']);
    $to_date = $conn->real_escape_string($_GET['to_date']);
    $where .= ($where ? " AND" : " WHERE") . " IssueDate BETWEEN '$from_date' AND '$to_date'";
}
if (!empty($where)) {
    $where .= " AND Remarks = 'Air Ticket Sale'";
} else {
    $where = " WHERE Remarks = 'Air Ticket Sale'";
}

$salesQuery = "SELECT * FROM sales" . $where;
$salesResult = $conn->query($salesQuery);

// Delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM sales WHERE SaleID=$id";
    if ($conn->query($deleteQuery) === TRUE) {
        echo "<script>alert('Record deleted successfully!'); window.location='invoice_list.php';</script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Process void request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['void_ticket'])) {
    $sale_id = intval($_POST['sale_id']);
    $void_charge = floatval($_POST['void_charge']);
    $net_price = floatval($_POST['net_price']);
    $notes = $conn->real_escape_string($_POST['notes']);
    
    // Fetch original sale data
    $originalQuery = "SELECT * FROM sales WHERE SaleID = $sale_id";
    $originalResult = $conn->query($originalQuery);
    $originalData = $originalResult->fetch_assoc();
    
    if ($originalData) {
        $profit = $void_charge - $net_price;
        
        // Update original record to mark as voided and update amounts
        $updateQuery = "UPDATE sales SET 
                        BillAmount = BillAmount - {$originalData['BillAmount']},
                        NetPayment = NetPayment - {$originalData['NetPayment']},
                        Profit = Profit - {$originalData['Profit']},
                        Remarks = 'Voided',
                        Notes = CONCAT(Notes, ' | VOIDED: {$notes}')
                        WHERE SaleID = $sale_id";
        
        // Insert void record
        $insertQuery = "INSERT INTO sales (
            section, PartyName, PassengerName, airlines, TicketRoute, TicketNumber, 
            Class, IssueDate, FlightDate, ReturnDate, PNR, BillAmount, NetPayment, 
            Profit, Source, system, PaymentStatus, PaidAmount, DueAmount, 
            PaymentMethod, BankName, SalesPersonName, invoice_number, Remarks, Notes
        ) VALUES (
            '{$originalData['section']}',
            '{$conn->real_escape_string($originalData['PartyName'])}',
            '{$conn->real_escape_string($originalData['PassengerName'])}',
            '{$conn->real_escape_string($originalData['airlines'])}',
            '{$conn->real_escape_string($originalData['TicketRoute'])}',
            '{$conn->real_escape_string($originalData['TicketNumber'])} VOID',
            '{$conn->real_escape_string($originalData['Class'])}',
            CURDATE(),
            '{$originalData['FlightDate']}',
            '{$originalData['ReturnDate']}',
            '{$conn->real_escape_string($originalData['PNR'])}',
            $void_charge,
            $net_price,
            $profit,
            '{$conn->real_escape_string($originalData['Source'])}',
            '{$conn->real_escape_string($originalData['system'])}',
            'Due',
            0.00,
            $void_charge,
            'Cash Payment',
            'Void Processing',
            '{$conn->real_escape_string($originalData['SalesPersonName'])}',
            CONCAT('VOID-', '{$originalData['invoice_number']}'),
            'Void Transaction',
            '{$notes}'
        )";
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update original record
            $conn->query($updateQuery);
            
            // Insert void record
            $conn->query($insertQuery);
            
            // Commit transaction
            $conn->commit();
            
            echo "<script>alert('Ticket voided successfully!'); window.location='invoice_list.php';</script>";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "<script>alert('Error voiding ticket: " . $conn->error . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="logo.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png">
    <title>Sales Records</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        th, td { 
            padding: 12px; 
            text-align: left; 
            font-size: 13px;
        }
        
        th { 
            background-color: #4a71ff; 
            color: white; 
            font-weight: 600;
        }
        
        .search-container { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 20px; 
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .search-container select, .search-container input { 
            padding: 10px; 
            width: 200px; 
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-container button {
            padding: 10px 20px;
            background: #4a71ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .search-container button:hover {
            background: #3a5fd9;
        }
        
        .export-btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .export-btn:hover {
            background: #218838;
        }
        
        .btn { 
            padding: 6px 12px; 
            border: none; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 12px; 
            border-radius: 4px;
            display: inline-block;
            margin: 2px 0;
            text-align: center;
        }
        
        .edit-btn { 
            background-color: #079320; 
            color: white; 
        }
        
        .delete-btn { 
            background-color: #d9534f; 
            color: white; 
        }
        
        .void-btn {
            background-color: #ff6b6b;
            color: white;
        }
        
        .btn-primary {
            background-color: #4a71ff;
            color: white;
        }
        
        .btn:hover { 
            opacity: 0.9; 
        }

        /* Alternating row colors */
        tr:nth-child(odd) {
            background-color: #f8f9ff; 
        }
        
        tr:nth-child(even) {
            background-color: #ffffff; 
        }

        /* Soft line separator between rows */
        tr {
            border-bottom: 1px solid #eaeaea;
        }

        tr:last-child {
            border-bottom: none;
        }
        
        .small-text {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
        }
        
        .highlight {
            color: #088910;
            font-weight: bold;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .success { background-color: #28a745; color: white; }
        .danger { background-color: #dc3545; color: white; }
        .warning { background-color: #ffc107; color: #212529; }
        .secondary { background-color: #6c757d; color: white; }
        .void { background-color: #ff6b6b; color: white; }
        
        .action-cell {
            min-width: 120px;
        }
        
        .export-container {
            text-align: center;
            margin: 15px 0;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            color: #333;
            margin: 0;
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .ticket-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4a71ff;
        }
        
        .ticket-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .calculation-result {
            background-color: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        
        .calculation-result p {
            margin: 5px 0;
            font-weight: bold;
        }
        
        .modal-buttons {
            text-align: right;
            margin-top: 20px;
        }
        
        .modal-buttons button {
            padding: 10px 20px;
            margin-left: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-confirm {
            background-color: #28a745;
            color: white;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        
        .void-indicator {
            color: #ff6b6b;
            font-weight: bold;
            font-size: 11px;
            display: block;
            margin-top: 3px;
        }
        
        @media (max-width: 1200px) {
            .container {
                overflow-x: auto;
            }
            
            .search-container {
                flex-direction: column;
                align-items: center;
            }
            
            .search-container select, .search-container input {
                width: 100%;
                max-width: 300px;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>

<body>

<?php include 'nav.php'  ?>

<div class="container">
    <h2>Sales Records</h2>
    
    <!-- Search Form -->
    <form method="GET" class="search-container">
        <select name="company">
            <option value="">Select Company</option>
            <?php while ($row = $companyResult->fetch_assoc()) : ?>
                <option value="<?= htmlspecialchars($row['PartyName']) ?>" 
                    <?= (isset($_GET['company']) && $_GET['company'] == $row['PartyName']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['PartyName']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        
        <input type="text" name="invoice" placeholder="Search Invoice Number" 
            value="<?= isset($_GET['invoice']) ? htmlspecialchars($_GET['invoice']) : '' ?>">

        <input type="text" name="pnr" placeholder="Search PNR" 
            value="<?= isset($_GET['pnr']) ? htmlspecialchars($_GET['pnr']) : '' ?>">
            
        <input type="date" name="from_date" placeholder="From Date" 
            value="<?= isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : '' ?>">
            
        <input type="date" name="to_date" placeholder="To Date" 
            value="<?= isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : '' ?>">
            
        <button type="submit">Search</button>
    </form>

    <!-- Export Button -->
    <div class="export-container">
        <?php
        $export_params = [];
        if (isset($_GET['company']) && !empty($_GET['company'])) {
            $export_params[] = "company=" . urlencode($_GET['company']);
        }
        if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
            $export_params[] = "from_date=" . urlencode($_GET['from_date']);
        }
        if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
            $export_params[] = "to_date=" . urlencode($_GET['to_date']);
        }
        $export_url = "export_invoice_excel.php";
        if (!empty($export_params)) {
            $export_url .= "?" . implode("&", $export_params);
        }
        ?>
        <a href="<?= $export_url ?>" class="export-btn">Export to Excel</a>
    </div>

    <!-- Void Modal -->
    <div id="voidModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Void Ticket</h3>
                <span class="close">&times;</span>
            </div>
            <form id="voidForm" method="POST">
                <input type="hidden" name="sale_id" id="void_sale_id">
                <input type="hidden" name="void_ticket" value="1">
                
                <div class="ticket-info" id="ticketDetails">
                    <!-- Ticket details will be loaded here -->
                </div>
                
                <div class="form-group">
                    <label for="void_charge">Void Charge (Sales Price):</label>
                    <input type="number" step="0.01" id="void_charge" name="void_charge" required 
                           placeholder="Enter void charge amount">
                </div>
                
                <div class="form-group">
                    <label for="net_price">Net Price:</label>
                    <input type="number" step="0.01" id="net_price" name="net_price" required 
                           placeholder="Enter net price">
                </div>
                
                <div class="calculation-result" id="profitCalculation" style="display: none;">
                    <p>Profit: <span id="calculated_profit">0.00</span></p>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" placeholder="Enter reason for void..." required></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" id="cancelVoid">Cancel</button>
                    <button type="submit" class="btn-confirm">Confirm Void</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Records Table -->
    <div class="result">
        <table>
            <tr>
                <th>Company Name</th>
                <th>Passenger Name</th>
                <th>Invoice Number</th>
                <th>Route</th>
                <th>Airlines</th>
                <th>PNR</th>
                <th>Ticket Number</th>
                <th>Issue Date</th>
                <th>Day Passes</th>
                <th>Payment Status</th>
                <th>Pricing</th>
                <th>Sales Person</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $salesResult->fetch_assoc()) : 
                $issue_date = new DateTime($row['IssueDate']);
                $today = new DateTime();
                $interval = $issue_date->diff($today);
                $day_passes = $interval->days;
                $deperture_date = new DateTime($row['FlightDate']);
                $return_date = new DateTime($row['ReturnDate']);
                $paidAmount = (float) $row['PaidAmount'];
                $paid = isset($row['PaidAmount']) ? (float) $row['PaidAmount'] : 0.00;
                $due = number_format($paid, 2, '.', '');
                $isVoided = strpos($row['TicketNumber'], 'VOID') !== false || $row['Remarks'] == 'Voided';
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['PartyName']) ?></td>
                    <td><?= htmlspecialchars($row['PassengerName']) ?></td>
                    <td>
                        <?= htmlspecialchars($row['invoice_number']) ?>
                        <?php if (!empty($row['invoice_number'])): ?>
                            <div>
                                <a href="redirect_reissue.php?id=<?= $row['SaleID'] ?>" class="btn btn-primary">Reissue</a>
                                <a href="redirect_refund.php?id=<?= $row['SaleID'] ?>" class="btn btn-primary">Refund</a>
                                <?php if (!$isVoided): ?>
                                    <a href="#" class="btn void-btn void-ticket-btn" 
                                       data-sale-id="<?= $row['SaleID'] ?>"
                                       data-passenger="<?= htmlspecialchars($row['PassengerName']) ?>"
                                       data-ticket="<?= htmlspecialchars($row['TicketNumber']) ?>"
                                       data-pnr="<?= htmlspecialchars($row['PNR']) ?>"
                                       data-route="<?= htmlspecialchars($row['TicketRoute']) ?>"
                                       data-airline="<?= htmlspecialchars($row['airlines']) ?>"
                                       data-selling="<?= $row['BillAmount'] ?>"
                                       data-net="<?= $row['NetPayment'] ?>">Void</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['TicketRoute']) ?></td>
                    <td>
                        <?= htmlspecialchars($row['airlines']) ?><br>
                        <span class="small-text">
                            <b>Issued From:</b> <span class="highlight"><?= htmlspecialchars($row['Source']) ?></span><br>
                            <b>System:</b> <span class="highlight"><?= htmlspecialchars($row['system']) ?></span>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['PNR']) ?></td>
                    <td>
                        <?= htmlspecialchars($row['TicketNumber']) ?>
                        <?php if ($isVoided): ?>
                            <span class="void-indicator">VOIDED</span>
                        <?php endif; ?>
                    </td>
                    <td><b>Issue Date : </b><?= htmlspecialchars($row['IssueDate']) ?> <br><b>Deperture : </b><?= htmlspecialchars($row['FlightDate']) ?><br><b>Return Date : </b><?= htmlspecialchars($row['ReturnDate']) ?></td>
                    <td><?= $day_passes ?> days</td>
                    <td>
                        <?php 
                        $statusClass = '';
                        switch($row['PaymentStatus']) {
                            case 'Paid': $statusClass = 'success'; break;
                            case 'Due': $statusClass = 'danger'; break;
                            case 'Partially Paid': $statusClass = 'warning'; break;
                            default: $statusClass = 'secondary';
                        }
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= substr($row['PaymentStatus'], 0, 1) ?></span><br>
                        <span class="small-text">
                            <b> Method:</b> <span class="highlight"><?= htmlspecialchars($row['PaymentMethod']) ?></span><br>
                            <b>Received in:</b> <span class="highlight"><?= htmlspecialchars($row['BankName']) ?></span><br>
                            <b>Received :</b> <span class="highlight"><?= htmlspecialchars($row['PaidAmount']) ?></span><br>
                            <b>Receive Date:</b> <span class="highlight"><?= htmlspecialchars($row['ReceivedDate']) ?></span>
                        </span>
                    </td>
                    <td>
                        <span class="small-text">
                            <b>Selling:</b> <?= number_format($row['BillAmount'], 2) ?><br>
                            <b>Net:</b> <?= number_format($row['NetPayment'], 2) ?><br>
                            <b>Profit:</b> <?= number_format($row['Profit'], 2) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['SalesPersonName']) ?></td>
                    <td class="action-cell">
                        <?php if (isset($row['SaleID'])): ?>
                            <a href="redirect_edit.php?id=<?php echo htmlspecialchars($row['SaleID']); ?>" class="btn edit-btn">
                                Edit
                            </a><br>
                            <a href="invoice_list.php?delete=<?php echo htmlspecialchars($row['SaleID']); ?>" class="btn delete-btn" 
                               onclick="return confirm('Are you sure you want to delete this record?')">
                                Delete
                            </a>
                            <form action="invoice_cart2.php" method="POST" style="margin-top: 5px;">
                                <input type="hidden" name="sell_id" value="<?= $row['SaleID'] ?>">
                                <button type="submit" class="btn btn-primary">Add to Invoice</button>
                            </form>
                        <?php else: ?>
                            <span style="color: red;">Error: No ID Found</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<script>
    // Void Modal functionality
    const modal = document.getElementById('voidModal');
    const closeBtn = document.getElementsByClassName('close')[0];
    const cancelBtn = document.getElementById('cancelVoid');
    const voidForm = document.getElementById('voidForm');
    const voidChargeInput = document.getElementById('void_charge');
    const netPriceInput = document.getElementById('net_price');
    const profitCalculation = document.getElementById('profitCalculation');
    const calculatedProfit = document.getElementById('calculated_profit');

    // Open modal when void button is clicked
    document.querySelectorAll('.void-ticket-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const saleId = this.getAttribute('data-sale-id');
            const passenger = this.getAttribute('data-passenger');
            const ticket = this.getAttribute('data-ticket');
            const pnr = this.getAttribute('data-pnr');
            const route = this.getAttribute('data-route');
            const airline = this.getAttribute('data-airline');
            const selling = this.getAttribute('data-selling');
            const net = this.getAttribute('data-net');
            
            // Set hidden sale ID
            document.getElementById('void_sale_id').value = saleId;
            
            // Display ticket details
            document.getElementById('ticketDetails').innerHTML = `
                <p><strong>Passenger:</strong> ${passenger}</p>
                <p><strong>Ticket Number:</strong> ${ticket}</p>
                <p><strong>PNR:</strong> ${pnr}</p>
                <p><strong>Route:</strong> ${route}</p>
                <p><strong>Airline:</strong> ${airline}</p>
                <p><strong>Original Selling:</strong> ${selling}</p>
                <p><strong>Original Net:</strong> ${net}</p>
            `;
            
            // Show modal
            modal.style.display = 'block';
        });
    });

    // Close modal
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    cancelBtn.onclick = function() {
        modal.style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Calculate profit when void charge or net price changes
    voidChargeInput.addEventListener('input', calculateProfit);
    netPriceInput.addEventListener('input', calculateProfit);

    function calculateProfit() {
        const voidCharge = parseFloat(voidChargeInput.value) || 0;
        const netPrice = parseFloat(netPriceInput.value) || 0;
        const profit = voidCharge - netPrice;
        
        if (voidCharge > 0 && netPrice > 0) {
            calculatedProfit.textContent = profit.toFixed(2);
            profitCalculation.style.display = 'block';
        } else {
            profitCalculation.style.display = 'none';
        }
    }

    // Confirm void before submitting
    voidForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const voidCharge = parseFloat(voidChargeInput.value);
        const netPrice = parseFloat(netPriceInput.value);
        const profit = voidCharge - netPrice;
        
        if (voidCharge <= 0 || netPrice <= 0) {
            alert('Please enter valid void charge and net price');
            return;
        }
        
        const confirmMsg = `Are you sure you want to void this ticket?\n\n` +
                          `Void Charge: ${voidCharge.toFixed(2)}\n` +
                          `Net Price: ${netPrice.toFixed(2)}\n` +
                          `Profit: ${profit.toFixed(2)}\n\n` +
                          `This will create a void transaction and update the original record.`;
        
        if (confirm(confirmMsg)) {
            this.submit();
        }
    });
</script>

</body>
</html>

<?php $conn->close(); ?>