<?php
session_start();
require_once 'access_control.php';
requireLogin();

$conn = getDbConnection();
$userId = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT UserID, UserName, email, DateOfBirth, NIDNumber, `Password`,role, image FROM user WHERE UserID = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || empty($user['Password'])) {
    die("User record or password hash not found. Please contact admin.");
}

$success_message = '';
$error_message = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid security token. Please refresh and try again.";
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $dob = $_POST['dob'];
        $nid = trim($_POST['nid']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Basic validation
        if (empty($username)) $error_message = "Username is required";
        elseif (empty($email)) $error_message = "Email is required";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error_message = "Invalid email format";

        $password_error = '';
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $password_error = "Current password is required to set a new password";
            } elseif (!password_verify($current_password, $user['Password'])) {
                $password_error = "Current password is incorrect";
            } elseif (strlen($new_password) < 8) {
                $password_error = "New password must be at least 8 characters";
            } elseif (!preg_match('/[A-Z]/', $new_password)) {
                $password_error = "New password must contain at least one uppercase letter";
            } elseif (!preg_match('/[a-z]/', $new_password)) {
                $password_error = "New password must contain at least one lowercase letter";
            } elseif (preg_match_all('/\d/', $new_password) < 2) {
                $password_error = "New password must contain at least two numbers";
            } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
                $password_error = "New password must contain at least one special character";
            } elseif ($new_password !== $confirm_password) {
                $password_error = "New passwords do not match";
            }
        }

        if ($password_error) {
            $error_message = $password_error;
        } elseif (empty($error_message)) {
            // Image upload
            $image_path = $user['image'];
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed)) {
                    $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                    $target_file = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                        if (!empty($user['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['image'])) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . $user['image']);
                        }
                        $image_path = '/uploads/' . $filename;
                    } else {
                        $error_message = "Failed to upload image";
                    }
                } else {
                    $error_message = "Only JPG, PNG, GIF allowed";
                }
            }

            if (empty($error_message)) {
                $hashed = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : null;
                if ($hashed) {
                    $sql = "UPDATE user SET UserName = ?, email = ?, DateOfBirth = ?, NIDNumber = ?, image = ?, `Password` = ? WHERE UserID = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssi", $username, $email, $dob, $nid, $image_path, $hashed, $userId);
                } else {
                    $sql = "UPDATE user SET UserName = ?, email = ?, DateOfBirth = ?, NIDNumber = ?, image = ? WHERE UserID = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssi", $username, $email, $dob, $nid, $image_path, $userId);
                }

                if ($stmt->execute()) {
                    $_SESSION['user_name'] = $username;
                    $_SESSION['user_email'] = $email;
                    $success_message = "Profile updated successfully!";
                    // Update local user array
                    $user['UserName'] = $username;
                    $user['email'] = $email;
                    $user['DateOfBirth'] = $dob;
                    $user['NIDNumber'] = $nid;
                    $user['image'] = $image_path;
                    if ($hashed) $user['Password'] = $hashed;
                } else {
                    $error_message = "Error updating profile: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// ---- Flexible nav.php inclusion ----
$nav_path = __DIR__ . '/nav.php';
if (!file_exists($nav_path)) {
    $nav_path = __DIR__ . '/../nav.php'; // try parent folder
}
if (!file_exists($nav_path)) {
    die("nav.php not found. Please ensure nav.php is in the same folder as profile.php or in the parent folder.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .form-container { max-width: 800px; margin: 0 auto; }
        .rule-item { font-size: 0.85rem; margin-bottom: 0.3rem; color: #6c757d; }
        .rule-item.valid { color: #28a745; }
        .rule-item i { width: 1.2rem; }
    </style>
</head>
<body>
    <?php include $nav_path; ?>
    <div class="container form-container mt-4">
        <h1 class="mb-4">Edit Profile</h1>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <?php if (!empty($user['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['image'])): ?>
                        <img src="<?= htmlspecialchars($user['image']) ?>" class="profile-img mb-3" id="profileImagePreview">
                    <?php else: ?>
                        <div id="defaultAvatar" class="default-avatar mb-3" style="margin: 0 auto;">
                            <?= strtoupper(substr($user['UserName'], 0, 1)) ?>
                        </div>
                        <img id="profileImagePreview" class="profile-img mb-3" style="display:none;">
                    <?php endif; ?>
                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                    <small class="text-muted">Max 2MB (JPG, PNG, GIF)</small>
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['UserName']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($user['DateOfBirth']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NID Number</label>
                        <input type="text" name="nid" class="form-control" value="<?= htmlspecialchars($user['NIDNumber']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" name="role" class="form-control" value="<?= htmlspecialchars($user['role']) ?>">
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <h5 class="mb-3">Change Password</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">New Password</label>
                    <input type="password" id="newPassword" name="new_password" class="form-control">
                    <div class="mt-2 bg-light p-2 rounded small">
                        <div id="ruleLength" class="rule-item"><i class="far fa-circle"></i> Minimum 8 characters</div>
                        <div id="ruleUpper" class="rule-item"><i class="far fa-circle"></i> One uppercase letter</div>
                        <div id="ruleLower" class="rule-item"><i class="far fa-circle"></i> One lowercase letter</div>
                        <div id="ruleNumber" class="rule-item"><i class="far fa-circle"></i> At least two numbers</div>
                        <div id="ruleSpecial" class="rule-item"><i class="far fa-circle"></i> One special character</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirm_password" class="form-control">
                    <div id="matchMsg" class="text-danger small mt-1"></div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" id="submitBtn" class="btn btn-primary me-2">Save Changes</button>
                <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        const pass = document.getElementById('newPassword');
        const confirmPass = document.getElementById('confirmPassword');
        const matchMsg = document.getElementById('matchMsg');
        const submitBtn = document.getElementById('submitBtn');

        function updateRules(pwd) {
            const len = pwd.length >= 8;
            const upper = /[A-Z]/.test(pwd);
            const lower = /[a-z]/.test(pwd);
            const number = (pwd.match(/\d/g) || []).length >= 2;
            const special = /[!@#$%^&*(),.?":{}|<>]/.test(pwd);
            document.getElementById('ruleLength').innerHTML = (len ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>') + ' Minimum 8 characters';
            document.getElementById('ruleUpper').innerHTML = (upper ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>') + ' One uppercase letter';
            document.getElementById('ruleLower').innerHTML = (lower ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>') + ' One lowercase letter';
            document.getElementById('ruleNumber').innerHTML = (number ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>') + ' At least two numbers';
            document.getElementById('ruleSpecial').innerHTML = (special ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>') + ' One special character';
            return len && upper && lower && number && special;
        }

        function checkMatch() {
            if (confirmPass.value === '') { matchMsg.innerText = ''; return true; }
            if (pass.value !== confirmPass.value) { matchMsg.innerText = 'Passwords do not match'; return false; }
            matchMsg.innerText = ''; return true;
        }

        function enableSubmit() {
            if (pass.value === '') {
                submitBtn.disabled = false;
                return;
            }
            const passOk = updateRules(pass.value);
            const matchOk = checkMatch();
            submitBtn.disabled = !(passOk && matchOk);
        }

        pass.addEventListener('input', enableSubmit);
        confirmPass.addEventListener('input', enableSubmit);
        enableSubmit();

        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const preview = document.getElementById('profileImagePreview');
                    if (preview) {
                        preview.src = ev.target.result;
                        preview.style.display = 'block';
                        const defaultAvatar = document.getElementById('defaultAvatar');
                        if (defaultAvatar) defaultAvatar.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>