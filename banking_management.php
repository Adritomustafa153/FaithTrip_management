<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Transaction Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: #2c3e50;
        }
        
        .logo h1 {
            font-size: 2rem;
            color: #2c3e50;
        }
        
        .controls {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 1.2rem;
            color: #2c3e50;
        }
        
        .balance {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .positive {
            color: #2ecc71;
        }
        
        .negative {
            color: #e74c3c;
        }
        
        .transaction-list {
            list-style: none;
            margin-top: 15px;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .transaction-info {
            display: flex;
            flex-direction: column;
        }
        
        .transaction-desc {
            font-weight: 600;
        }
        
        .transaction-date {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .transaction-amount {
            font-weight: 700;
        }
        
        .content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        thead {
            background: #2c3e50;
            color: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 150px;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            text-align: center;
        }
        
        .stat-item {
            padding: 15px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }
        
        .pagination button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .pagination button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
        }
        
        .notification.success {
            background: #2ecc71;
        }
        
        .notification.error {
            background: #e74c3c;
        }
        
        .taka-icon {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <?php
    // Include database connection and navbar
    include 'db.php';
    include 'nav.php';
    
    // Initialize variables
    $banks = [];
    $transactions = [];
    $transaction_types = [];
    $total_balance = 0;
    $total_income = 0;
    $total_expenses = 0;
    
    // First, check if transactions table exists and create it if not
    $table_check = $conn->query("SHOW TABLES LIKE 'transactions'");
    if ($table_check->num_rows == 0) {
        // Create transactions table if it doesn't exist
        $create_table = "CREATE TABLE transactions (
            transaction_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            bank_id INT(11) NOT NULL,
            type_id INT(11) NOT NULL,
            transaction_date DATE NOT NULL,
            description TEXT DEFAULT NULL,
            amount DECIMAL(15,2) NOT NULL,
            balance_after DECIMAL(15,2) NOT NULL,
            reference_number VARCHAR(100) DEFAULT NULL,
            category VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bank_id) REFERENCES banks(id),
            FOREIGN KEY (type_id) REFERENCES transaction_types(type_id)
        )";
        
        if ($conn->query($create_table)) {
            // Insert sample data
            $sample_data = [
                [1, 1, '2024-08-28', 'Receive', 4250.00, 4250.00, null, 'receive'],
                [1, 2, '2024-08-26', 'Payment', 185.30, 4064.70, null, 'payment'],
                [2, 2, '2024-08-25', 'Transfer', 147.85, 147.85, null, 'transfer'],
                [3, 2, '2024-08-20', 'Withdraw', 89.99, 89.99, null, 'withdraw'],
                [1, 1, '2024-08-15', 'deposit', 325.75, 4390.45, null, 'deposit']
            ];
            
            foreach ($sample_data as $data) {
                $insert = "INSERT INTO transactions (bank_id, type_id, transaction_date, description, amount, balance_after, reference_number, category) 
                          VALUES ($data[0], $data[1], '$data[2]', '$data[3]', $data[4], $data[5], '$data[6]', '$data[7]')";
                $conn->query($insert);
            }
        }
    }
    
    // Fetch banks data
    $bank_query = "SELECT * FROM banks";
    $bank_result = $conn->query($bank_query);
    
    if ($bank_result->num_rows > 0) {
        while($row = $bank_result->fetch_assoc()) {
            $banks[] = $row;
            $total_balance += $row['Balance'];
        }
    }
    
    // Fetch transaction types
    $type_query = "SELECT * FROM transaction_types";
    $type_result = $conn->query($type_query);
    
    if ($type_result->num_rows > 0) {
        while($row = $type_result->fetch_assoc()) {
            $transaction_types[$row['type_id']] = $row['type_name'];
        }
    }
    
    // Fetch transactions
    $transaction_query = "SELECT t.*, b.Bank_Name, tt.type_name 
                         FROM transactions t 
                         JOIN banks b ON t.bank_id = b.id 
                         JOIN transaction_types tt ON t.type_id = tt.type_id
                         ORDER BY t.transaction_date DESC LIMIT 5";
    
    $transaction_result = $conn->query($transaction_query);
    if ($transaction_result && $transaction_result->num_rows > 0) {
        while($row = $transaction_result->fetch_assoc()) {
            $transactions[] = $row;
            
            if ($row['type_name'] == 'Deposit') {
                $total_income += $row['amount'];
            } else {
                $total_expenses += $row['amount'];
            }
        }
    }
    
    // Handle form submission
    $notification = "";
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $bank_id = $_POST['bank_id'];
        $type_id = $_POST['type_id'];
        $amount = $_POST['amount'];
        $date = $_POST['date'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        
        // Get the current balance for the selected bank
        $balance_query = "SELECT Balance FROM banks WHERE id = ?";
        $stmt_balance = $conn->prepare($balance_query);
        $stmt_balance->bind_param("i", $bank_id);
        $stmt_balance->execute();
        $stmt_balance->bind_result($current_balance);
        $stmt_balance->fetch();
        $stmt_balance->close();
        
        // Calculate new balance
        if ($type_id == 1) { // Assuming type_id 1 is Deposit
            $balance_after = $current_balance + $amount;
        } else {
            $balance_after = $current_balance - $amount;
        }
        
        // Insert the transaction into the database
        $insert_query = "INSERT INTO transactions (bank_id, type_id, transaction_date, description, amount, balance_after, category) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("iissdds", $bank_id, $type_id, $date, $description, $amount, $balance_after, $category);
            
            if ($stmt->execute()) {
                $notification = "<div class='notification success'>Transaction added successfully!</div>";
                
                // Update bank balance
                $update_balance = "UPDATE banks SET Balance = ? WHERE id = ?";
                $stmt2 = $conn->prepare($update_balance);
                if ($stmt2) {
                    $stmt2->bind_param("di", $balance_after, $bank_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
                
                // Refresh page to show updated data
                echo "<meta http-equiv='refresh' content='2'>";
            } else {
                $notification = "<div class='notification error'>Error adding transaction: " . $conn->error . "</div>";
            }
            $stmt->close();
        } else {
            $notification = "<div class='notification error'>Error preparing statement: " . $conn->error . "</div>";
        }
    }
    
    // Handle date filter
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
    ?>
    
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-landmark"></i>
                <h1>Bank Transaction Manager</h1>
            </div>
            <div class="controls">
                <button class="btn btn-primary" onclick="refreshData()"><i class="fas fa-sync-alt"></i> Refresh</button>
                <button class="btn btn-success" onclick="exportData()"><i class="fas fa-download"></i> Export</button>
            </div>
        </header>
        
        <?php echo $notification; ?>
        
        <div class="dashboard">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Total Balance</h2>
                    <i class="fas fa-wallet fa-2x"></i>
                </div>
                <div class="balance positive">৳<?php echo number_format($total_balance, 2); ?></div>
                <div class="stats">
                    <div class="stat-item">
                        <div class="stat-value positive">৳<?php echo number_format($total_income, 2); ?></div>
                        <div class="stat-label">Income</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value negative">৳<?php echo number_format($total_expenses, 2); ?></div>
                        <div class="stat-label">Expenses</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class='card-title'>Recent Transactions</h2>
                    <i class='fas fa-list fa-2x'></i>
                </div>
                <ul class='transaction-list'>
                    <?php foreach ($transactions as $transaction): ?>
                    <li class='transaction-item'>
                        <div class='transaction-info'>
                            <div class='transaction-desc'><?php echo $transaction['description']; ?></div>
                            <div class='transaction-date'><?php echo $transaction['transaction_date']; ?></div>
                        </div>
                        <div class='transaction-amount <?php echo $transaction['type_name'] == 'Deposit' ? 'positive' : 'negative'; ?>'>
                            <?php echo $transaction['type_name'] == 'Deposit' ? '+' : '-'; ?>৳<?php echo number_format($transaction['amount'], 2); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Bank Accounts</h2>
                    <i class="fas fa-university fa-2x"></i>
                </div>
                <ul class="transaction-list">
                    <?php foreach ($banks as $bank): ?>
                    <li class="transaction-item">
                        <div class="transaction-info">
                            <div class="transaction-desc"><?php echo $bank['Bank_Name']; ?> (<?php echo $bank['A/C_Title']; ?>)</div>
                            <div class="transaction-date"><?php echo substr($bank['A/C_Number'], -4); ?></div>
                        </div>
                        <div class="transaction-amount">৳<?php echo number_format($bank['Balance'], 2); ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Add Transaction</h2>
                </div>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="bank_id">Bank Account</label>
                        <select class="form-control" id="bank_id" name="bank_id" required>
                            <?php foreach ($banks as $bank): ?>
                            <option value="<?php echo $bank['id']; ?>">
                                <?php echo $bank['Bank_Name']; ?> (<?php echo substr($bank['A/C_Number'], -4); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type_id">Transaction Type</label>
                        <select class="form-control" id="type_id" name="type_id" required>
                            <?php foreach ($transaction_types as $id => $name): ?>
                            <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter amount" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" class="form-control" id="description" name="description" placeholder="Enter description" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="receive">Receive</option>
                            <option value="payment">Payment</option>
                            <option value="transfer">Transfer</option>
                            <option value="withdraw">Withdraw</option>
                            <!-- <option value="shopping">Shopping</option> -->
                            <option value="deposit">Deposit</option>
                            <!-- <option value="other">Other</option> -->
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-plus-circle"></i> Add Transaction
                    </button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Transaction History</h2>
                </div>
                
                <div class="filters">
                    <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <select class="filter-select" name="bank_filter">
                            <option value="all">All Banks</option>
                            <?php foreach ($banks as $bank): ?>
                            <option value="<?php echo $bank['id']; ?>" <?php echo (isset($_GET['bank_filter']) && $_GET['bank_filter'] == $bank['id']) ? 'selected' : ''; ?>>
                                <?php echo $bank['Bank_Name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select class="filter-select" name="type_filter">
                            <option value="all">All Types</option>
                            <?php foreach ($transaction_types as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo (isset($_GET['type_filter']) && $_GET['type_filter'] == $id) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select class="filter-select" name="date_filter">
                            <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="7" <?php echo $date_filter == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $date_filter == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $date_filter == '90' ? 'selected' : ''; ?>>Last 3 Months</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <button type="button" class="btn btn-danger" onclick="window.location.href=window.location.pathname">Clear Filters</button>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Bank</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Build filtered transaction query
                        $filter_query = "SELECT t.*, b.Bank_Name, tt.type_name 
                                        FROM transactions t 
                                        JOIN banks b ON t.bank_id = b.id 
                                        JOIN transaction_types tt ON t.type_id = tt.type_id
                                        WHERE 1=1";
                        
                        if (isset($_GET['bank_filter']) && $_GET['bank_filter'] != 'all') {
                            $filter_query .= " AND t.bank_id = " . intval($_GET['bank_filter']);
                        }
                        
                        if (isset($_GET['type_filter']) && $_GET['type_filter'] != 'all') {
                            $filter_query .= " AND t.type_id = " . intval($_GET['type_filter']);
                        }
                        
                        if (isset($_GET['date_filter']) && $_GET['date_filter'] != 'all') {
                            $days = intval($_GET['date_filter']);
                            $filter_query .= " AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
                        }
                        
                        $filter_query .= " ORDER BY t.transaction_date DESC";
                        
                        $filtered_result = $conn->query($filter_query);
                        
                        if ($filtered_result && $filtered_result->num_rows > 0) {
                            while($transaction = $filtered_result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $transaction['transaction_date']; ?></td>
                            <td><?php echo $transaction['description']; ?></td>
                            <td><?php echo $transaction['Bank_Name']; ?></td>
                            <td><?php echo $transaction['type_name']; ?></td>
                            <td class="<?php echo $transaction['type_name'] == 'Deposit' ? 'positive' : 'negative'; ?>">
                                <?php echo $transaction['type_name'] == 'Deposit' ? '+' : '-'; ?>৳<?php echo number_format($transaction['amount'], 2); ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center;'>No transactions found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class="pagination">
                    <button disabled>Previous</button>
                    <button class="btn btn-primary">1</button>
                    <button>2</button>
                    <button>3</button>
                    <button>Next</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set default date to today
        document.getElementById('date').valueAsDate = new Date();
        
        // Refresh data function
        function refreshData() {
            window.location.reload();
        }
        
        // Export data function
        function exportData() {
            alert('Export functionality would be implemented here. Data would be exported to CSV format.');
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const amount = document.getElementById('amount').value;
            const description = document.getElementById('description').value;
            
            if (!amount || amount <= 0 || !description) {
                e.preventDefault();
                alert('Please fill in all required fields with valid values');
                return;
            }
        });
    </script>
</body>
</html>