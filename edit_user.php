<?php
session_start();
require_once __DIR__ . '/access_control.php';
requirePermission('user.manage');

$conn = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: users.php'); exit; }

// Fetch user data
$stmt = $conn->prepare("SELECT UserID, UserName, email, role, DateOfBirth, NIDNumber, image FROM user WHERE UserID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { header('Location: users.php'); exit; }

// ----- Fetch roles: try 'roles' table first, else fallback to distinct from 'user' -----
$roles = [];
$tableExists = $conn->query("SHOW TABLES LIKE 'roles'")->num_rows > 0;
if ($tableExists) {
    $rolesQuery = $conn->query("SELECT role_name FROM roles ORDER BY role_name");
    while ($row = $rolesQuery->fetch_assoc()) $roles[] = $row['role_name'];
}
// If no roles table or empty, get distinct roles from user table
if (empty($roles)) {
    $rolesQuery = $conn->query("SELECT DISTINCT role FROM user WHERE role IS NOT NULL AND role != '' ORDER BY role");
    while ($row = $rolesQuery->fetch_assoc()) $roles[] = $row['role'];
}
// Final fallback (hardcoded roles)
if (empty($roles)) $roles = ['super_admin', 'admin_master', 'accounts', 'sales', 'reservation', 'test'];

$errors = [];
$success = '';
$newPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = trim($_POST['userName']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $dob = $_POST['dob'];
    $nid = trim($_POST['nid']);
    $reset = isset($_POST['reset_password']);

    if (empty($userName)) $errors[] = "Name required";
    if (empty($email)) $errors[] = "Email required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    else if ($email !== $user['email']) {
        $chk = $conn->prepare("SELECT UserID FROM user WHERE email = ? AND UserID != ?");
        $chk->bind_param('si', $email, $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows) $errors[] = "Email already taken";
        $chk->close();
    }

    // Image upload
    $imagePath = $user['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            $target = '../uploads/' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                if ($user['image'] && file_exists($user['image'])) unlink($user['image']);
                $imagePath = $target;
            } else $errors[] = "Upload failed";
        } else $errors[] = "Only JPG, PNG, GIF allowed";
    }

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE user SET UserName = ?, email = ?, role = ?, DateOfBirth = ?, NIDNumber = ?, image = ? WHERE UserID = ?");
        $upd->bind_param("ssssssi", $userName, $email, $role, $dob, $nid, $imagePath, $id);
        if ($upd->execute()) {
            $success = "User information updated successfully.";
            if ($reset) {
                $newPassword = bin2hex(random_bytes(5)); // 10 chars
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $passUpd = $conn->prepare("UPDATE user SET Password = ? WHERE UserID = ?");
                $passUpd->bind_param("si", $hashed, $id);
                if ($passUpd->execute()) {
                    $success .= " Password has been reset. <strong>New password: <span style='background:#f0f0f0; padding:2px 6px; border-radius:4px;'>$newPassword</span></strong> (copy this now).";
                } else $errors[] = "Password reset failed";
                $passUpd->close();
            }
            // Refresh user data
            $user['UserName'] = $userName;
            $user['email'] = $email;
            $user['role'] = $role;
            $user['DateOfBirth'] = $dob;
            $user['NIDNumber'] = $nid;
            $user['image'] = $imagePath;
        } else $errors[] = "Update failed: " . $upd->error;
        $upd->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fc; }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; cursor: pointer; }
        .card-header { background: linear-gradient(135deg, #4e73df, #224abe); color: white; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h4><i class="fas fa-user-edit"></i> Edit User</h4>
                </div>
                <div class="card-body">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data">
                        <div class="text-center mb-3">
                            <label for="imgInput">
                                <img src="<?= $user['image'] ? htmlspecialchars($user['image']) : 'https://via.placeholder.com/100' ?>" class="profile-img" id="preview">
                            </label>
                            <input type="file" id="imgInput" name="image" accept="image/*" class="d-none">
                            <div class="small text-muted mt-1">Click image to change</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="userName" class="form-control" value="<?= htmlspecialchars($user['UserName']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select">
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($user['DateOfBirth'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NID Number</label>
                                <input type="text" name="nid" class="form-control" value="<?= htmlspecialchars($user['NIDNumber'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="reset_password" id="resetCheck" class="form-check-input">
                                    <label for="resetCheck" class="form-check-label text-danger">
                                        <i class="fas fa-sync-alt"></i> Reset password (generate new random password)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('imgInput')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => document.getElementById('preview').src = ev.target.result;
            reader.readAsDataURL(file);
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>