<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $system = $_POST['system'];
    $pcc = $_POST['pcc'];
    $url = $_POST['url'];
    $user = $_POST['user'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("INSERT INTO iata_systems (system, PCC, url, user, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $system, $pcc, $url, $user, $password);

    if ($stmt->execute()) {
        $success = "IATA System added successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add IATA System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .card-header h3 {
            color: white !important;
            margin-bottom: 0;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .password-input-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
            color: var(--secondary-color);
            z-index: 2;
        }
        
        .password-input-container input {
            padding-right: 35px;
        }
        
        #loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 9999;
            text-align: center;
            padding-top: 20%;
        }
        
        #loading img {
            width: 100px;
            height: 100px;
        }
        
        #loading p {
            color: white;
            margin-top: 20px;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="fas fa-network-wired me-2"></i>Add New IATA System</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $success ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="iataForm">
                            <!-- Row 1 -->
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <label for="system" class="form-label">System Name</label>
                                    <input type="text" class="form-control" id="system" name="system" required placeholder="e.g., Sabre, Galileo">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="pcc" class="form-label">PCC Code</label>
                                    <input type="text" class="form-control" id="pcc" name="pcc" required placeholder="e.g., ABCD1234">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="url" class="form-label">System URL</label>
                                    <input type="url" class="form-control" id="url" name="url" required placeholder="https://system.url.com">
                                </div>
                            </div>
                            
                            <!-- Row 2 -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="user" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="user" name="user" required placeholder="Enter username">
                                </div>
                                <div class="col-md-6 mb-3 password-input-container">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required placeholder="Enter password">
                                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="btn btn-primary px-4 py-2" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Save System
                                </button>
                                <button type="reset" class="btn btn-outline-secondary px-4 py-2">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading GIF Container -->
    <div id="loading">
        <img src="gds.gif" alt="Loading...">
        <p>Processing your request...</p>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form submission with 2-second loading GIF
        document.getElementById('iataForm').addEventListener('submit', function(e) {
            // Prevent immediate form submission
            e.preventDefault();
            
            // Show loading GIF
            const loadingElement = document.getElementById('loading');
            loadingElement.style.display = 'block';
            
            // Disable submit button and change text
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            
            // Set timeout for 2 seconds before submitting
            setTimeout(() => {
                // Submit the form after delay
                this.submit();
            }, 2000);
        });
    </script>
</body>
</html>