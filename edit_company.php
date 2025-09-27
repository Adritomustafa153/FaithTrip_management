<?php
// Database connection (db.php)
$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'faithtrip_accounts';
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Simple authentication check (auth_check.php)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if ID parameter is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No company ID provided.";
    $_SESSION['msg_type'] = "danger";
    header("location: view_corporates.php");
    exit();
}

$id = $_GET['id'];

// Fetch company data
$result = $mysqli->query("SELECT * FROM companyprofile WHERE CompanyID = $id");
$company = $result->fetch_assoc();

// Check if company exists
if (!$company) {
    $_SESSION['message'] = "Company not found.";
    $_SESSION['msg_type'] = "danger";
    header("location: view_corporates.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input data
    $companyName = $mysqli->real_escape_string($_POST['companyName']);
    $staffName = $mysqli->real_escape_string($_POST['staffName']);
    $designation = $mysqli->real_escape_string($_POST['designation']);
    $staffDateOfBirth = $mysqli->real_escape_string($_POST['staffDateOfBirth']);
    $passportNumber = $mysqli->real_escape_string($_POST['passportNumber']);
    $passportExpiryDate = $mysqli->real_escape_string($_POST['passportExpiryDate']);
    $companyAddress = $mysqli->real_escape_string($_POST['companyAddress']);
    $companyEmail = $mysqli->real_escape_string($_POST['companyEmail']);
    $personAddress = $mysqli->real_escape_string($_POST['personAddress']);
    $companyPhoneNumber = $mysqli->real_escape_string($_POST['companyPhoneNumber']);
    $personPhoneNumber = $mysqli->real_escape_string($_POST['personPhoneNumber']);
    
    // Handle empty dates
    if (empty($staffDateOfBirth)) $staffDateOfBirth = NULL;
    if (empty($passportExpiryDate)) $passportExpiryDate = NULL;
    
    // Update query
    $updateQuery = "UPDATE companyprofile SET 
                    CompanyName = '$companyName',
                    StaffName = '$staffName',
                    Designation = '$designation',
                    StaffDateOfBirth = " . (is_null($staffDateOfBirth) ? "NULL" : "'$staffDateOfBirth'") . ",
                    PassportNumber = '$passportNumber',
                    PassportExpiryDate = " . (is_null($passportExpiryDate) ? "NULL" : "'$passportExpiryDate'") . ",
                    CompanyAddress = '$companyAddress',
                    CompanyEmail = '$companyEmail',
                    PersonAddress = '$personAddress',
                    CompanyPhoneNumber = '$companyPhoneNumber',
                    PersonPhoneNumber = '$personPhoneNumber'
                    WHERE CompanyID = $id";
    
    if ($mysqli->query($updateQuery)) {
        $_SESSION['message'] = "Company profile updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("location: view_corporates.php");
        exit();
    } else {
        $error = "Error updating record: " . $mysqli->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company - FaithTrip Accounts</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-top: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .navbar-custom {
            background-color: #2c3e50;
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: #ecf0f1;
        }
        .navbar-custom .nav-link:hover {
            color: #3498db;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

    <div class="container-fluid">
        <div class="form-container">
            <div class="page-header">
                <h2><i class="fas fa-edit me-2"></i> Edit Company Profile</h2>
                <a href="view_corporates.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="mb-3">Company Information</h4>
                        
                        <div class="mb-3">
                            <label for="companyName" class="form-label required-field">Company Name</label>
                            <input type="text" class="form-control" id="companyName" name="companyName" 
                                   value="<?php echo htmlspecialchars($company['CompanyName']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="companyAddress" class="form-label">Company Address</label>
                            <textarea class="form-control" id="companyAddress" name="companyAddress" rows="3"><?php echo htmlspecialchars($company['CompanyAddress']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="companyEmail" class="form-label">Company Email</label>
                            <input type="email" class="form-control" id="companyEmail" name="companyEmail" 
                                   value="<?php echo htmlspecialchars($company['CompanyEmail']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="companyPhoneNumber" class="form-label">Company Phone Number</label>
                            <input type="tel" class="form-control" id="companyPhoneNumber" name="companyPhoneNumber" 
                                   value="<?php echo htmlspecialchars($company['CompanyPhoneNumber']); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4 class="mb-3">Staff Information</h4>
                        
                        <div class="mb-3">
                            <label for="staffName" class="form-label required-field">Staff Name</label>
                            <input type="text" class="form-control" id="staffName" name="staffName" 
                                   value="<?php echo htmlspecialchars($company['StaffName']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" class="form-control" id="designation" name="designation" 
                                   value="<?php echo htmlspecialchars($company['Designation']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="staffDateOfBirth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="staffDateOfBirth" name="staffDateOfBirth" 
                                   value="<?php echo ($company['StaffDateOfBirth'] && $company['StaffDateOfBirth'] != '0000-00-00') ? $company['StaffDateOfBirth'] : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="personAddress" class="form-label">Personal Address</label>
                            <textarea class="form-control" id="personAddress" name="personAddress" rows="3"><?php echo htmlspecialchars($company['PersonAddress']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="personPhoneNumber" class="form-label">Personal Phone Number</label>
                            <input type="tel" class="form-control" id="personPhoneNumber" name="personPhoneNumber" 
                                   value="<?php echo htmlspecialchars($company['PersonPhoneNumber']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h4 class="mb-3">Passport Information</h4>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="passportNumber" class="form-label">Passport Number</label>
                            <input type="text" class="form-control" id="passportNumber" name="passportNumber" 
                                   value="<?php echo htmlspecialchars($company['PassportNumber']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="passportExpiryDate" class="form-label">Passport Expiry Date</label>
                            <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" 
                                   value="<?php echo ($company['PassportExpiryDate'] && $company['PassportExpiryDate'] != '0000-00-00') ? $company['PassportExpiryDate'] : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="view_corporates.php" class="btn btn-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Company</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>