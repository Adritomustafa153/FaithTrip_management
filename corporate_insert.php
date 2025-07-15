<?php
// Database connection
include 'db.php'; 

// Initialize variables
$message = "";
$companyName = $staffName = $designation = $staffDateOfBirth = $passportNumber = "";
$passportExpiryDate = $companyAddress = $companyEmail = $personAddress = "";
$companyPhoneNumber = $personPhoneNumber = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $companyName = sanitizeInput($_POST['companyName']);
    $staffName = sanitizeInput($_POST['staffName']);
    $designation = sanitizeInput($_POST['designation']);
    $staffDateOfBirth = $_POST['staffDateOfBirth'];
    $passportNumber = sanitizeInput($_POST['passportNumber']);
    $passportExpiryDate = $_POST['passportExpiryDate'];
    $companyAddress = sanitizeInput($_POST['companyAddress']);
    $companyEmail = sanitizeInput($_POST['companyEmail']);
    $personAddress = sanitizeInput($_POST['personAddress']);
    $companyPhoneNumber = sanitizeInput($_POST['companyPhoneNumber']);
    $personPhoneNumber = sanitizeInput($_POST['personPhoneNumber']);
    
    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO companyprofile (CompanyName, StaffName, Designation, StaffDateOfBirth, PassportNumber, PassportExpiryDate, CompanyAddress, CompanyEmail, PersonAddress, CompanyPhoneNumber, PersonPhoneNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $companyName, $staffName, $designation, $staffDateOfBirth, $passportNumber, $passportExpiryDate, $companyAddress, $companyEmail, $personAddress, $companyPhoneNumber, $personPhoneNumber);
    
    // Execute the statement
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>New record created successfully</div>";
        // Clear form fields
        $companyName = $staffName = $designation = $staffDateOfBirth = $passportNumber = "";
        $passportExpiryDate = $companyAddress = $companyEmail = $personAddress = "";
        $companyPhoneNumber = $personPhoneNumber = "";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    
    $stmt->close();
}

// Function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile Insert</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;

        }
        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: #343a40;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            font-weight: 500;
        }
        .form-group-row {
            margin-bottom: 15px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-container">
                    <div class="form-header">
                        <h2>Add New Company Profile</h2>
                        <p class="text-muted">Fill in the details below to add a new company profile</p>
                    </div>
                    
                    <?php echo $message; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <!-- Row 1: Company Information -->
                        <div class="row form-group-row">
                            <div class="col-md-4 mb-3">
                                <label for="companyName" class="form-label required-field">Company Name</label>
                                <input type="text" class="form-control" id="companyName" name="companyName" value="<?php echo $companyName; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="companyEmail" class="form-label required-field">Company Email</label>
                                <input type="email" class="form-control" id="companyEmail" name="companyEmail" value="<?php echo $companyEmail; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="companyPhoneNumber" class="form-label">Company Phone</label>
                                <input type="text" class="form-control" id="companyPhoneNumber" name="companyPhoneNumber" value="<?php echo $companyPhoneNumber; ?>">
                            </div>
                        </div>
                        
                        <!-- Row 2: Company Address -->
                        <div class="row form-group-row">
                            <div class="col-12 mb-3">
                                <label for="companyAddress" class="form-label">Company Address</label>
                                <textarea class="form-control" id="companyAddress" name="companyAddress" rows="2"><?php echo $companyAddress; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Row 3: Staff Information -->
                        <div class="row form-group-row">
                            <div class="col-md-4 mb-3">
                                <label for="staffName" class="form-label">Staff Name(s)</label>
                                <textarea class="form-control" id="staffName" name="staffName" rows="2"><?php echo $staffName; ?></textarea>
                                <small class="text-muted">Multiple names separated by commas</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="designation" class="form-label">Designation</label>
                                <input type="text" class="form-control" id="designation" name="designation" value="<?php echo $designation; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="personPhoneNumber" class="form-label">Person Phone</label>
                                <input type="text" class="form-control" id="personPhoneNumber" name="personPhoneNumber" value="<?php echo $personPhoneNumber; ?>">
                            </div>
                        </div>
                        
                        <!-- Row 4: Personal Information -->
                        <div class="row form-group-row">
                            <div class="col-md-4 mb-3">
                                <label for="staffDateOfBirth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="staffDateOfBirth" name="staffDateOfBirth" value="<?php echo $staffDateOfBirth; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="passportNumber" class="form-label">Passport Number</label>
                                <input type="text" class="form-control" id="passportNumber" name="passportNumber" value="<?php echo $passportNumber; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="passportExpiryDate" class="form-label">Passport Expiry</label>
                                <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" value="<?php echo $passportExpiryDate; ?>">
                            </div>
                        </div>
                        
                        <!-- Row 5: Person Address -->
                        <div class="row form-group-row">
                            <div class="col-12 mb-3">
                                <label for="personAddress" class="form-label">Person Address</label>
                                <textarea class="form-control" id="personAddress" name="personAddress" rows="2"><?php echo $personAddress; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="row form-group-row">
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary btn-submit">Add Company Profile</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>