<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "faithtrip_accounts";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$userName = $email = $dob = $nid = $password = $image = "";
$role = "User"; // Default role
$errors = [];
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $userName = trim($_POST["userName"]);
    $email = trim($_POST["email"]);
    $role = $_POST["role"];
    $dob = $_POST["dob"];
    $nid = trim($_POST["nid"]);
    $password = trim($_POST["password"]);
    $confirmPassword = trim($_POST["confirmPassword"]);
    
    // Validate inputs
    if (empty($userName)) {
        $errors[] = "User Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // Process image upload if no errors
    if (empty($errors)) {
        // Handle image upload
        $imagePath = "";
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
            $targetDir = "uploads/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = basename($_FILES["image"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow certain file formats
            $allowedTypes = array('jpg', 'png', 'jpeg', 'gif');
            if (in_array($fileType, $allowedTypes)) {
                // Upload file to server
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                    $imagePath = $targetFilePath;
                } else {
                    $errors[] = "Sorry, there was an error uploading your file.";
                }
            } else {
                $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO user (UserName, role, email, DateOfBirth, NIDNumber, Password, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $userName, $role, $email, $dob, $nid, $hashedPassword, $imagePath);
        
        // Execute the statement
        if ($stmt->execute()) {
            $success = "User added successfully!";
            // Clear form
            $userName = $email = $dob = $nid = $password = "";
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
        
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
    <title>Add New User</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-header {
            background-color: #4e73df;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #d1d3e2;
        }
        .form-control:focus, .form-select:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        .btn-primary {
            background-color: #4e73df;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #3a5bbf;
        }
        .error {
            color: #e74a3b;
            font-size: 0.875rem;
        }
        .success {
            color: #1cc88a;
            font-size: 1rem;
            font-weight: 600;
        }
        .profile-image-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 3px solid #e3e6f0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .profile-image-container:hover {
            border-color: #bac8f3;
        }
        .profile-image {
            max-width: 100%;
            max-height: 100%;
            display: none;
        }
        .upload-icon {
            font-size: 3rem;
            color: #d1d3e2;
        }
        #imageLabel {
            display: block;
            text-align: center;
            cursor: pointer;
        }
        .required-field::after {
            content: " *";
            color: #e74a3b;
        }
    </style>
</head>
<body>
    <?php include 'nav.php' ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="fas fa-user-plus me-2"></i>Add New User</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-4 text-center">
                                <label for="image" id="imageLabel">
                                    <div class="profile-image-container">
                                        <i class="fas fa-user-circle upload-icon"></i>
                                        <img id="profileImage" class="profile-image" src="#" alt="Profile Image">
                                    </div>
                                    <span class="text-muted">Click to upload profile image</span>
                                </label>
                                <input type="file" class="form-control d-none" id="image" name="image" accept="image/*">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="userName" class="form-label required-field">Full Name</label>
                                    <input type="text" class="form-control" id="userName" name="userName" value="<?php echo htmlspecialchars($userName); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label required-field">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="role" class="form-label required-field">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="User" <?php echo $role === 'User' ? 'selected' : ''; ?>>User</option>
                                        <option value="Admin" <?php echo $role === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="Accounts" <?php echo $role === 'Accounts' ? 'selected' : ''; ?>>Accounts</option>
                                        <option value="Sales" <?php echo $role === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                        <option value="Management" <?php echo $role === 'Management' ? 'selected' : ''; ?>>Management</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($dob); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="nid" class="form-label">NID Number</label>
                                    <input type="text" class="form-control" id="nid" name="nid" value="<?php echo htmlspecialchars($nid); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label required-field">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirmPassword" class="form-label required-field">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordMatch" class="error"></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-user-plus me-2"></i>Add User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profileImage = document.getElementById('profileImage');
                    profileImage.src = e.target.result;
                    profileImage.style.display = 'block';
                    document.querySelector('.upload-icon').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPassword = document.getElementById('confirmPassword');
            const icon = this.querySelector('i');
            if (confirmPassword.type === 'password') {
                confirmPassword.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                confirmPassword.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Password match validation
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchMessage = document.getElementById('passwordMatch');
            
            if (confirmPassword !== '') {
                if (password !== confirmPassword) {
                    matchMessage.textContent = 'Passwords do not match';
                    document.getElementById('submitBtn').disabled = true;
                } else {
                    matchMessage.textContent = '';
                    document.getElementById('submitBtn').disabled = false;
                }
            } else {
                matchMessage.textContent = '';
                document.getElementById('submitBtn').disabled = false;
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                document.getElementById('passwordMatch').textContent = 'Passwords do not match';
            }
        });
    </script>
</body>
</html>