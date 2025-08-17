<?php
require_once 'auth_check.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'faithtrip_accounts');

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM user WHERE UserId = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $dob = $_POST['dob'];
    $nid = trim($_POST['nid']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate current password if changing password
    if (!empty($new_password)) {
        if ($current_password !== $user['Password']) { // In production, use password_verify()
            $error_message = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords don't match";
        }
    }

    // Handle image upload
    $image_path = $user['image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $filename;

        // Validate image
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($check !== false && move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            // Delete old image if it exists
            if (!empty($user['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['image'])) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $user['image']);
            }
            $image_path = '/uploads/' . $filename;
        }
    }

    // Update database if no errors
    if (empty($error_message)) {
        // Determine if we're updating password
        $password_update = !empty($new_password) ? ", Password = ?" : "";
        $sql = "UPDATE user SET 
                UserName = ?, 
                email = ?, 
                DateOfBirth = ?, 
                NIDNumber = ?, 
                image = ? 
                $password_update 
                WHERE UserId = ?";

        $stmt = $conn->prepare($sql);
        
        if (!empty($new_password)) {
            $stmt->bind_param('ssssssi', $username, $email, $dob, $nid, $image_path, $new_password, $_SESSION['user_id']);
        } else {
            $stmt->bind_param('sssssi', $username, $email, $dob, $nid, $image_path, $_SESSION['user_id']);
        }

        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['user_name'] = $username;
            $_SESSION['user_email'] = $email;
            
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $user['UserName'] = $username;
            $user['email'] = $email;
            $user['DateOfBirth'] = $dob;
            $user['NIDNumber'] = $nid;
            $user['image'] = $image_path;
        } else {
            $error_message = "Error updating profile: " . $conn->error;
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
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .default-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
            font-weight: bold;
            border: 5px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container form-container mt-4">
        <h1 class="mb-4">Edit Profile</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <?php if (!empty($user['image'])): ?>
                        <img src="<?php echo htmlspecialchars($user['image']); ?>" 
                             class="profile-img mb-3" 
                             id="profileImagePreview"
                             onerror="this.onerror=null;this.src='';this.style.display='none';document.getElementById('defaultAvatar').style.display='flex';">
                    <?php endif; ?>
                    <div id="defaultAvatar" class="default-avatar mb-3" style="<?php echo empty($user['image']) ? '' : 'display: none;'; ?> margin: 0 auto;">
                        <?php echo strtoupper(substr($user['UserName'], 0, 1)); ?>
                    </div>
                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                    <small class="text-muted">Max 2MB (JPG, PNG)</small>
                </div>
                
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user['UserName']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" 
                               value="<?php echo htmlspecialchars($user['DateOfBirth']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="nid" class="form-label">NID Number</label>
                        <input type="text" class="form-control" id="nid" name="nid" 
                               value="<?php echo htmlspecialchars($user['NIDNumber']); ?>">
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <h5 class="mb-3">Change Password</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password">
                </div>
                <div class="col-md-4">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password">
                </div>
                <div class="col-md-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary me-2">Save Changes</button>
                <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('profileImagePreview');
                    if (preview) {
                        preview.src = event.target.result;
                        preview.style.display = 'block';
                        document.getElementById('defaultAvatar').style.display = 'none';
                    } else {
                        // Create new preview if it doesn't exist
                        const img = document.createElement('img');
                        img.id = 'profileImagePreview';
                        img.className = 'profile-img mb-3';
                        img.src = event.target.result;
                        img.style.display = 'block';
                        img.onerror = function() {
                            this.style.display = 'none';
                            document.getElementById('defaultAvatar').style.display = 'flex';
                        };
                        document.querySelector('.col-md-4.text-center').insertBefore(img, document.getElementById('profile_image'));
                        document.getElementById('defaultAvatar').style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>