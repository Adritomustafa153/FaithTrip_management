<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
            /* margin: 0;  */
            /* padding: 20px;  */
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            /* max-width: 1400px; */
            /* margin: 0 auto; */
            background: white;
            /* padding: 20px; */
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .action-cell {
            min-width: 120px;
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
            
        <button type="submit">Search</button>
    </form>

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
                    <td><?= htmlspecialchars($row['TicketNumber']) ?></td>
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

</body>
</html>

<?php $conn->close(); ?>