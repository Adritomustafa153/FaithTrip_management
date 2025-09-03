<?php
include 'db.php';
include 'nav.php';

// Check if ID parameter is set
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid bank account ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: view_banks.php");
    exit();
}

$id = $_GET['id'];

// Check if bank exists
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

// Process deletion if confirmed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
    $stmt = $conn->prepare("DELETE FROM banks WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Bank account deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting bank account: " . $stmt->error;
        $_SESSION['message_type'] = "danger";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: view_banks.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Bank Account</title>
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .bank-details {
            background-color: white;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning-icon {
            font-size: 4rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-trash-alt"></i> Delete Bank Account</h2>
            <a href="view_banks.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Bank Accounts
            </a>
        </div>

        <div class="confirmation-container text-center">
            <div class="warning-icon mb-3">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h3>Are you sure you want to delete this bank account?</h3>
            <p class="text-muted">This action cannot be undone.</p>
            
            <div class="bank-details text-left">
                <h4>Account Details:</h4>
                <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($bank['Bank_Name']); ?></p>
                <p><strong>Account Title:</strong> <?php echo htmlspecialchars($bank['A/C_Title']); ?></p>
                <p><strong>Account Number:</strong> <?php echo htmlspecialchars($bank['A/C_Number']); ?></p>
                <p><strong>Balance:</strong> ৳ <?php echo number_format($bank['Balance'], 2); ?></p>
            </div>
            
            <form method="POST">
                <button type="submit" name="confirm_delete" value="1" class="btn btn-danger btn-lg">
                    <i class="fas fa-trash"></i> Yes, Delete Account
                </button>
                <a href="view_banks.php" class="btn btn-secondary btn-lg">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>