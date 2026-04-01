<?php
// Database connection
include 'db.php';
include 'auth_check.php';

// Insert logic
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $AgentName = trim($_POST['AgentName']);
    $ShopName = trim($_POST['ShopName']);
    $ShopAddress = trim($_POST['ShopAddress']);
    $Email = trim($_POST['Email']);
    $PhoneNumber = trim($_POST['PhoneNumber']);
    $NID = trim($_POST['NID']);
    $TradeLicense = trim($_POST['TradeLicense']);
    $BIN = trim($_POST['BIN']);
    $TIN = trim($_POST['TIN']);
    $DateOfBirth = $_POST['DateOfBirth'];

    // Validate required field
    if (empty($AgentName)) {
        $message = "Error: Agent Name is required.";
    } else {
        // Handle file uploads safely
        $image = null;
        $logo = null;
        
        if (isset($_FILES['Image']['tmp_name']) && !empty($_FILES['Image']['tmp_name']) && $_FILES['Image']['error'] == 0) {
            $image = file_get_contents($_FILES['Image']['tmp_name']);
        }
        
        if (isset($_FILES['Logo']['tmp_name']) && !empty($_FILES['Logo']['tmp_name']) && $_FILES['Logo']['error'] == 0) {
            $logo = file_get_contents($_FILES['Logo']['tmp_name']);
        }

        // Handle null values for images
        if ($image === null) $image = '';
        if ($logo === null) $logo = '';

        // Generate Agent ID using database sequence
        $currentYearMonth = date('Ym');
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get and increment the sequence number
            $seq_stmt = $conn->prepare("SELECT next_sequence FROM agent_sequence WHERE year_month = ? FOR UPDATE");
            $seq_stmt->bind_param("s", $currentYearMonth);
            $seq_stmt->execute();
            $seq_result = $seq_stmt->get_result();
            
            if ($seq_result->num_rows > 0) {
                $seq_row = $seq_result->fetch_assoc();
                $next_sequence = $seq_row['next_sequence'];
                
                // Generate Agent ID
                $AgentsID = "AGT-" . $currentYearMonth . str_pad($next_sequence, 3, '0', STR_PAD_LEFT);
                
                // Increment sequence for next use
                $update_stmt = $conn->prepare("UPDATE agent_sequence SET next_sequence = next_sequence + 1 WHERE year_month = ?");
                $update_stmt->bind_param("s", $currentYearMonth);
                $update_stmt->execute();
                
            } else {
                // First entry for this month
                $insert_stmt = $conn->prepare("INSERT INTO agent_sequence (year_month, next_sequence) VALUES (?, 2)");
                $insert_stmt->bind_param("s", $currentYearMonth);
                $insert_stmt->execute();
                $AgentsID = "AGT-" . $currentYearMonth . "001";
            }
            
            // Now insert the agent
            $stmt = $conn->prepare("INSERT INTO agents 
                (AgentsID, AgentName, ShopName, ShopAddress, Email, PhoneNumber, NID, TradeLicense, BIN, TIN, Image, Logo, DateOfBirth) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssssssss", 
                $AgentsID, $AgentName, $ShopName, $ShopAddress, $Email, $PhoneNumber, 
                $NID, $TradeLicense, $BIN, $TIN, $image, $logo, $DateOfBirth
            );

            if ($stmt->execute()) {
                $conn->commit();
                $message = "Agent inserted successfully! Agent ID: <strong>" . $AgentsID . "</strong>";
            } else {
                throw new Exception($stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            
            // Fallback: Simple timestamp-based ID
            $fallback_id = "AGT-" . date('YmdHis') . rand(100, 999);
            
            $fallback_stmt = $conn->prepare("INSERT INTO agents 
                (AgentsID, AgentName, ShopName, ShopAddress, Email, PhoneNumber, NID, TradeLicense, BIN, TIN, Image, Logo, DateOfBirth) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $fallback_stmt->bind_param("sssssssssssss", 
                $fallback_id, $AgentName, $ShopName, $ShopAddress, $Email, $PhoneNumber, 
                $NID, $TradeLicense, $BIN, $TIN, $image, $logo, $DateOfBirth
            );

            if ($fallback_stmt->execute()) {
                $message = "Agent inserted successfully with fallback ID: <strong>" . $fallback_id . "</strong>";
            } else {
                $message = "All insertion methods failed: " . $fallback_stmt->error;
            }
            
            $fallback_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert New Agent - FaithTrip</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .header h2 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .form-container {
            padding: 30px;
        }

        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ecf0f1;
        }

        .section-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 0.95rem;
        }

        .required::after {
            content: " *";
            color: #e74c3c;
        }

        input, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        textarea {
            resize: vertical;
            min-height: 70px;
        }

        input[type="file"] {
            padding: 8px;
            background: #f8f9fa;
        }

        .button-container {
            text-align: center;
            margin-top: 30px;
        }

        .submit-btn {
            background: #27ae60;
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .submit-btn:hover {
            background: #219a52;
        }

        .form-note {
            text-align: center;
            margin-top: 10px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #0c5460;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
            
            .form-group {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
    <div class="header">
        <h2>Add New Agent</h2>
        <p>Complete the form below to register a new agent</p>
    </div>

    <div class="form-container">
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <strong>Agent ID Format:</strong> AGT-YYYYMM001 (Automatically generated)<br>
            <small>Example: AGT-202511001, AGT-202511002, etc. Sequence resets each month.</small>
        </div>

        <form method="POST" enctype="multipart/form-data" id="agentForm">
            <div class="form-section">
                <h3 class="section-title">Personal Information</h3>
                <div class="row">
                    <div class="form-group">
                        <label for="AgentName" class="required">Agent Full Name</label>
                        <input type="text" id="AgentName" name="AgentName" required 
                               value="<?php echo isset($_POST['AgentName']) ? htmlspecialchars($_POST['AgentName']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="DateOfBirth">Date of Birth</label>
                        <input type="date" id="DateOfBirth" name="DateOfBirth"
                               value="<?php echo isset($_POST['DateOfBirth']) ? $_POST['DateOfBirth'] : ''; ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="Email">Email Address</label>
                        <input type="email" id="Email" name="Email"
                               value="<?php echo isset($_POST['Email']) ? htmlspecialchars($_POST['Email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="PhoneNumber">Phone Number</label>
                        <input type="text" id="PhoneNumber" name="PhoneNumber"
                               value="<?php echo isset($_POST['PhoneNumber']) ? htmlspecialchars($_POST['PhoneNumber']) : ''; ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="NID">National ID (NID)</label>
                        <input type="text" id="NID" name="NID"
                               value="<?php echo isset($_POST['NID']) ? htmlspecialchars($_POST['NID']) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Business Information</h3>
                <div class="row">
                    <div class="form-group">
                        <label for="ShopName">Shop Name</label>
                        <input type="text" id="ShopName" name="ShopName"
                               value="<?php echo isset($_POST['ShopName']) ? htmlspecialchars($_POST['ShopName']) : ''; ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="ShopAddress">Shop Address</label>
                        <textarea id="ShopAddress" name="ShopAddress"><?php echo isset($_POST['ShopAddress']) ? htmlspecialchars($_POST['ShopAddress']) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Legal Documents</h3>
                <div class="row">
                    <div class="form-group">
                        <label for="TradeLicense">Trade License</label>
                        <input type="text" id="TradeLicense" name="TradeLicense"
                               value="<?php echo isset($_POST['TradeLicense']) ? htmlspecialchars($_POST['TradeLicense']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="BIN">BIN Number</label>
                        <input type="text" id="BIN" name="BIN"
                               value="<?php echo isset($_POST['BIN']) ? htmlspecialchars($_POST['BIN']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="TIN">TIN Number</label>
                        <input type="text" id="TIN" name="TIN"
                               value="<?php echo isset($_POST['TIN']) ? htmlspecialchars($_POST['TIN']) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Upload Files</h3>
                <div class="row">
                    <div class="form-group">
                        <label for="Image">Agent Photo</label>
                        <input type="file" id="Image" name="Image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="Logo">Business Logo</label>
                        <input type="file" id="Logo" name="Logo" accept="image/*">
                    </div>
                </div>
            </div>

            <div class="button-container">
                <button type="submit" class="submit-btn">Add Agent</button>
                <p class="form-note">Fields marked with * are required</p>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('agentForm').addEventListener('submit', function(e) {
        const agentName = document.getElementById('AgentName').value.trim();
        if (!agentName) {
            e.preventDefault();
            alert('Please enter the agent name.');
            document.getElementById('AgentName').focus();
        }
    });

    // File size validation
    document.getElementById('Image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.size > 5 * 1024 * 1024) {
            alert('Image file size should be less than 5MB');
            e.target.value = '';
        }
    });

    document.getElementById('Logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.size > 5 * 1024 * 1024) {
            alert('Logo file size should be less than 5MB');
            e.target.value = '';
        }
    });
</script>

</body>
</html>