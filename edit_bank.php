<?php
include 'db.php';
include 'nav.php';

// Initialize variables
$id = $bank_name = $ac_title = $ac_number = $branch_name = $routing_number = $balance = "";
$logo = null;
$error = "";
$current_logo = null;

// Check if ID parameter is set
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid bank account ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: view_banks.php");
    exit();
}

$id = $_GET['id'];

// Fetch existing bank data
$stmt = $conn->prepare("SELECT * FROM banks WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['message'] = "Bank account not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: view_banks.php");
    exit();
}

$bank = $result->fetch_assoc();
$stmt->close();

// Populate form fields
$bank_name = $bank['Bank_Name'];
$ac_title = $bank['A/C_Title'];
$ac_number = $bank['A/C_Number'];
$branch_name = $bank['Branch_Name'];
$routing_number = $bank['Routing_Number'];
$balance = $bank['Balance'];
$current_logo = $bank['logo'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $bank_name = trim($_POST['bank_name']);
    $ac_title = trim($_POST['ac_title']);
    $ac_number = trim($_POST['ac_number']);
    $branch_name = trim($_POST['branch_name']);
    $routing_number = trim($_POST['routing_number']);
    $balance = trim($_POST['balance']);
    
    // Handle file upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $logo = file_get_contents($_FILES['logo']['tmp_name']);
        $logo_update = ", logo = ?";
    } else {
        $logo_update = "";
    }
    
    // Validate data
    if (empty($bank_name) || empty($ac_title) || empty($ac_number) || empty($branch_name) || empty($routing_number)) {
        $error = "Please fill in all required fields.";
    } else {
        // Prepare and bind
        if (!empty($logo_update)) {
            $stmt = $conn->prepare("UPDATE banks SET Bank_Name = ?, `A/C_Title` = ?, `A/C_Number` = ?, Branch_Name = ?, Routing_Number = ?, Balance = ? $logo_update WHERE id = ?");
            $stmt->bind_param("ssssisb", $bank_name, $ac_title, $ac_number, $branch_name, $routing_number, $balance, $logo, $id);
        } else {
            $stmt = $conn->prepare("UPDATE banks SET Bank_Name = ?, `A/C_Title` = ?, `A/C_Number` = ?, Branch_Name = ?, Routing_Number = ?, Balance = ? WHERE id = ?");
            $stmt->bind_param("ssssisi", $bank_name, $ac_title, $ac_number, $branch_name, $routing_number, $balance, $id);
        }
        
        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['message'] = "Bank account updated successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: view_banks.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
        
        // Close statement
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bank Account</title>
    <style>
        .required:after {
            content: " *";
            color: red;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .current-logo {
            width: 100px; /* Increased logo size */
            height: 100px; /* Increased logo size */
            object-fit: contain;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 5px;
            background-color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-edit"></i> Edit Bank Account</h2>
            <a href="view_banks.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Bank Accounts
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="bank_name" class="required">Bank Name</label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name" 
                               value="<?php echo htmlspecialchars($bank_name); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ac_title" class="required">Account Title</label>
                        <input type="text" class="form-control" id="ac_title" name="ac_title" 
                               value="<?php echo htmlspecialchars($ac_title); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="ac_number" class="required">Account Number</label>
                        <input type="text" class="form-control" id="ac_number" name="ac_number" 
                               value="<?php echo htmlspecialchars($ac_number); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="branch_name" class="required">Branch Name</label>
                        <input type="text" class="form-control" id="branch_name" name="branch_name" 
                               value="<?php echo htmlspecialchars($branch_name); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="routing_number" class="required">Routing Number</label>
                        <input type="number" class="form-control" id="routing_number" name="routing_number" 
                               value="<?php echo htmlspecialchars($routing_number); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="balance">Balance (৳)</label>
                        <input type="number" step="0.01" class="form-control" id="balance" name="balance" 
                               value="<?php echo htmlspecialchars($balance); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="logo">Bank Logo</label>
                    
                    <?php if (!empty($current_logo)): ?>
                        <div class="mb-2">
                            <p>Current Logo:</p>
                            <img src="data:image/png;base64,<?php echo base64_encode($current_logo); ?>" 
                                 class="current-logo" alt="Current logo">
                        </div>
                    <?php endif; ?>
                    
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="logo" name="logo" accept="image/*">
                        <label class="custom-file-label" for="logo">Choose new file (optional)</label>
                    </div>
                    <small class="form-text text-muted">Upload a new logo to replace the current one</small>
                </div>
                
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Update Bank Account
                    </button>
                    <a href="view_banks.php" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show the selected file name in the file input
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            var fileName = document.getElementById("logo").files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    </script>
</body>
</html>