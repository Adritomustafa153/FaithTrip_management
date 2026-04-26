<?php
session_start();
require_once __DIR__ . '/../access_control.php';
requirePermission('user.manage');

$conn = getDbConnection();

$roles = [];
$roleRes = $conn->query("SELECT role_name FROM roles ORDER BY role_name");
if ($roleRes && $roleRes->num_rows) {
    while ($r = $roleRes->fetch_assoc()) $roles[] = $r['role_name'];
} else {
    $roles = ['super_admin', 'admin_master', 'accounts', 'sales', 'reservation', 'test'];
}

$userName = $email = $dob = $nid = '';
$role = 'sales';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = trim($_POST['userName']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $dob = $_POST['dob'];
    $nid = trim($_POST['nid']);
    $password = $_POST['password'];
    $confirm = $_POST['confirmPassword'];

    // Validations
    if (empty($userName)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    else {
        $chk = $conn->prepare("SELECT UserID FROM user WHERE email = ?");
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) $errors[] = "Email already taken";
        $chk->close();
    }
    if (empty($password)) $errors[] = "Password is required";
    else {
        if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
        if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must contain an uppercase letter";
        if (!preg_match('/[a-z]/', $password)) $errors[] = "Password must contain a lowercase letter";
        if (preg_match_all('/\d/', $password) < 2) $errors[] = "Password must contain at least two numbers";
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $errors[] = "Password must contain a special character";
    }
    if ($password !== $confirm) $errors[] = "Passwords do not match";

    // ========== FIXED IMAGE UPLOAD – saves to accounts/uploads/ ==========
    $imagePath = '';
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Target folder: faithtrip/accounts/uploads/
        $uploadDir = __DIR__ . '/uploads/';   // current directory (accounts/) + /uploads/
        
        // Create folder if missing
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $fileName = 'user_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // Store path relative to the accounts/ folder
                $imagePath = 'uploads/' . $fileName;
            } else {
                $errors[] = "Failed to move uploaded file. Check folder permissions (accounts/uploads/ must be writable).";
            }
        } else {
            $errors[] = "Only JPG, JPEG, PNG, GIF allowed.";
        }
    }
    // ================================================================

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO user (UserName, role, email, DateOfBirth, NIDNumber, Password, image) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss", $userName, $role, $email, $dob, $nid, $hashed, $imagePath);
        if ($stmt->execute()) {
            $success = "User created successfully!";
            $userName = $email = $dob = $nid = '';
        } else $errors[] = "Database error: " . $stmt->error;
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; }
        .card { border-radius: 1rem; }
        .profile-img-container { width: 120px; height: 120px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; margin: 0 auto; cursor: pointer; overflow: hidden; border: 3px solid white; box-shadow: 0 0.2rem 0.5rem rgba(0,0,0,0.1); }
        .rule-item { font-size: 0.85rem; margin-bottom: 0.3rem; color: #6c757d; }
        .rule-item.valid { color: #28a745; }
        .rule-item i { width: 1.2rem; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <i class="fas fa-user-plus fa-2x"></i>
                    <h3 class="mb-0">Create New User</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" id="userForm">
                        <div class="text-center mb-4">
                            <label for="imageInput" class="profile-img-container">
                                <i class="fas fa-camera fa-3x text-secondary" id="defaultIcon"></i>
                                <img id="previewImage" src="#" style="display:none; width:100%; height:100%; object-fit:cover;">
                            </label>
                            <input type="file" id="imageInput" name="image" accept="image/*" class="d-none">
                            <div class="text-muted small mt-1">Click to upload profile picture</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="userName" class="form-control" value="<?= htmlspecialchars($userName) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email) ?>" autocomplete="off" required>
                                <div id="emailStatus" class="small mt-1"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($dob) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">NID Number</label>
                                <input type="text" name="nid" class="form-control" value="<?= htmlspecialchars($nid) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                        </div>

                        <div class="mt-3 bg-light p-3 rounded">
                            <small class="text-muted d-block mb-2">Password must meet:</small>
                            <div id="ruleLength" class="rule-item"><i class="far fa-circle"></i> Minimum 8 characters</div>
                            <div id="ruleUpper" class="rule-item"><i class="far fa-circle"></i> One uppercase letter</div>
                            <div id="ruleLower" class="rule-item"><i class="far fa-circle"></i> One lowercase letter</div>
                            <div id="ruleNumber" class="rule-item"><i class="far fa-circle"></i> At least two numbers</div>
                            <div id="ruleSpecial" class="rule-item"><i class="far fa-circle"></i> One special character</div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                            <div id="matchMsg" class="text-danger small mt-1"></div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" id="submitBtn" class="btn btn-primary btn-lg" disabled>Create User</button>
                            <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview
const imgInput = document.getElementById('imageInput');
const preview = document.getElementById('previewImage');
const defaultIcon = document.getElementById('defaultIcon');
imgInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            preview.src = ev.target.result;
            preview.style.display = 'block';
            defaultIcon.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        defaultIcon.style.display = 'block';
    }
});

// Email duplicate check
const emailField = document.getElementById('email');
const emailStatus = document.getElementById('emailStatus');
let emailTimer = null;
function updateEmailStatus() {
    const email = emailField.value.trim();
    if (email === '') { emailStatus.innerHTML = ''; enableSubmit(); return; }
    if (emailTimer) clearTimeout(emailTimer);
    emailTimer = setTimeout(() => {
        fetch('check_email.php?email=' + encodeURIComponent(email))
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    emailStatus.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Email already taken';
                    disableSubmit();
                } else {
                    emailStatus.innerHTML = '<i class="fas fa-check-circle text-success"></i> Email available';
                    enableSubmit();
                }
            })
            .catch(() => { emailStatus.innerHTML = ''; enableSubmit(); });
    }, 500);
}
emailField.addEventListener('input', updateEmailStatus);

// Password strength rules
const pass = document.getElementById('password');
const confirmPass = document.getElementById('confirmPassword');
const matchMsg = document.getElementById('matchMsg');
const submitBtn = document.getElementById('submitBtn');

function updateRules(pwd) {
    const len = pwd.length >= 8;
    const upper = /[A-Z]/.test(pwd);
    const lower = /[a-z]/.test(pwd);
    const number = (pwd.match(/\d/g) || []).length >= 2;
    const special = /[!@#$%^&*(),.?":{}|<>]/.test(pwd);
    document.getElementById('ruleLength').innerHTML = len ? '<i class="fas fa-check-circle"></i> Minimum 8 characters' : '<i class="far fa-circle"></i> Minimum 8 characters';
    document.getElementById('ruleUpper').innerHTML = upper ? '<i class="fas fa-check-circle"></i> One uppercase letter' : '<i class="far fa-circle"></i> One uppercase letter';
    document.getElementById('ruleLower').innerHTML = lower ? '<i class="fas fa-check-circle"></i> One lowercase letter' : '<i class="far fa-circle"></i> One lowercase letter';
    document.getElementById('ruleNumber').innerHTML = number ? '<i class="fas fa-check-circle"></i> At least two numbers' : '<i class="far fa-circle"></i> At least two numbers';
    document.getElementById('ruleSpecial').innerHTML = special ? '<i class="fas fa-check-circle"></i> One special character' : '<i class="far fa-circle"></i> One special character';
    return len && upper && lower && number && special;
}

function checkMatch() {
    if (confirmPass.value === '') { matchMsg.innerText = ''; return true; }
    if (pass.value !== confirmPass.value) { matchMsg.innerText = 'Passwords do not match'; return false; }
    matchMsg.innerText = ''; return true;
}

function disableSubmit() { submitBtn.disabled = true; }
function enableSubmit() {
    const emailOk = !emailStatus.innerHTML.includes('already taken');
    const passOk = updateRules(pass.value);
    const matchOk = checkMatch();
    submitBtn.disabled = !(emailOk && passOk && matchOk && confirmPass.value !== '');
}

pass.addEventListener('input', function() { updateRules(this.value); enableSubmit(); });
confirmPass.addEventListener('input', function() { checkMatch(); enableSubmit(); });
enableSubmit();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>