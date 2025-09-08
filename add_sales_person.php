<?php
// Database connection
include 'auth_check.php';
include 'db.php';

// Initialize variables
$name = $address = $phone = $email = $nid = $employee_id = $joining_date = $date_of_birth = "";
$error = $success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $name = sanitize_input($_POST["name"]);
    $address = sanitize_input($_POST["address"]);
    $phone = sanitize_input($_POST["phone"]);
    $email = sanitize_input($_POST["email"]);
    $nid = sanitize_input($_POST["nid"]);
    $employee_id = sanitize_input($_POST["employee_id"]);
    $joining_date = sanitize_input($_POST["joining_date"]);
    $date_of_birth = sanitize_input($_POST["date_of_birth"]);
    
    // Validate required fields
    if (empty($name) || empty($address) || empty($phone) || empty($email) || empty($nid) || empty($joining_date)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check for duplicate email, NID, or employee_id with specific counts
        $check_sql = "SELECT 
            SUM(email = ?) as email_count,
            SUM(nid = ?) as nid_count,
            SUM(employee_id = ?) as employee_id_count
        FROM sales_person 
        WHERE email = ? OR nid = ? OR employee_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ssssss", $email, $nid, $employee_id, $email, $nid, $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['email_count'] > 0) {
            $error = "A sales person with the same email already exists.";
        } elseif ($row['nid_count'] > 0) {
            $error = "A sales person with the same NID already exists.";
        } elseif (!empty($employee_id) && $row['employee_id_count'] > 0) {
            $error = "A sales person with the same employee ID already exists.";
        } else {
            // Insert data into database
            $insert_sql = "INSERT INTO sales_person (name, address, phone, email, nid, employee_id, joining_date, date_of_birth) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssssss", $name, $address, $phone, $email, $nid, $employee_id, $joining_date, $date_of_birth);
            
            if ($stmt->execute()) {
                $success = "Sales person added successfully!";
                // Clear form
                $name = $address = $phone = $email = $nid = $employee_id = $joining_date = $date_of_birth = "";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sales Person</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;

        }
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        .form-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="form-container">
                    <div class="form-header">
                        <h2 class="text-center">Add New Sales Person</h2>
                        <p class="text-center text-muted">Fill in the details below to add a new sales person</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row mb-3">
                            <label for="name" class="col-sm-3 col-form-label required-field">Full Name</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="address" class="col-sm-3 col-form-label required-field">Address</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $address; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="phone" class="col-sm-3 col-form-label required-field">Phone</label>
                            <div class="col-sm-9">
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>" required>
                                <small class="text-muted">Format: +880 1XXX-XXXXXX</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="email" class="col-sm-3 col-form-label required-field">Email</label>
                            <div class="col-sm-9">
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="nid" class="col-sm-3 col-form-label required-field">National ID (NID)</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="nid" name="nid" value="<?php echo $nid; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="employee_id" class="col-sm-3 col-form-label">Employee ID</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?php echo $employee_id; ?>">
                                <small class="text-muted">Optional field</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="joining_date" class="col-sm-3 col-form-label required-field">Joining Date</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control" id="joining_date" name="joining_date" value="<?php echo $joining_date; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="date_of_birth" class="col-sm-3 col-form-label">Date of Birth</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo $date_of_birth; ?>">
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-person-plus"></i> Add Sales Person
                                </button>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="text-center mb-4">
                    <a href="list_sales_persons.php" class="btn btn-link">
                        <i class="bi bi-list-ul"></i> View All Sales Persons
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Form validation script -->
    <script>
        // Client-side form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = document.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    
                    // Add error message if not already present
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                        const error = document.createElement('div');
                        error.className = 'error-message';
                        error.textContent = 'This field is required';
                        field.parentNode.insertBefore(error, field.nextSibling);
                    }
                } else {
                    field.classList.remove('is-invalid');
                    // Remove error message if exists
                    if (field.nextElementSibling && field.nextElementSibling.classList.contains('error-message')) {
                        field.nextElementSibling.remove();
                    }
                }
            });
            
            // Validate email format
            const emailField = document.getElementById('email');
            if (emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
                isValid = false;
                emailField.classList.add('is-invalid');
                if (!emailField.nextElementSibling || !emailField.nextElementSibling.classList.contains('error-message')) {
                    const error = document.createElement('div');
                    error.className = 'error-message';
                    error.textContent = 'Please enter a valid email address';
                    emailField.parentNode.insertBefore(error, emailField.nextSibling);
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
<?php
// Close connection only after everything is done
if (isset($conn)) {
    $conn->close();
}
?>