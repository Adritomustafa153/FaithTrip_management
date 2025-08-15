<?php
session_start();
include 'db.php';



if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}
include 'nav.php';

$user_id = $_SESSION['UserID'];

// Fetch user info
$stmt = $conn->prepare("SELECT UserName, email, DateOfBirth, NIDNumber, image FROM user WHERE UserID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['UserName'];
    $email = $_POST['email'];
    $dob = $_POST['DateOfBirth'];
    $nid = $_POST['NIDNumber'];

    if (!empty($_FILES['image']['tmp_name'])) {
        $imgData = file_get_contents($_FILES['image']['tmp_name']);
        $stmt = $conn->prepare("UPDATE user SET UserName=?, email=?, DateOfBirth=?, NIDNumber=?, image=? WHERE UserID=?");
        $stmt->bind_param("ssssbi", $username, $email, $dob, $nid, $null, $user_id);
        $null = NULL;
        $stmt->send_long_data(4, $imgData);
    } else {
        $stmt = $conn->prepare("UPDATE user SET UserName=?, email=?, DateOfBirth=?, NIDNumber=? WHERE UserID=?");
        $stmt->bind_param("ssssi", $username, $email, $dob, $nid, $user_id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully!'); window.location='my_profile.php';</script>";
    } else {
        echo "<script>alert('Error updating profile!');</script>";
    }
    $stmt->close();
}
?>

<div class="container mt-4">
    <h2>My Profile</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Profile Picture</label><br>
            <?php if (!empty($user['image'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($user['image']); ?>" width="100" height="100" class="rounded-circle mb-2">
            <?php else: ?>
                <img src="default.png" width="100" height="100" class="rounded-circle mb-2">
            <?php endif; ?>
            <input type="file" name="image" class="form-control">
        </div>

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="UserName" value="<?= htmlspecialchars($user['UserName']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Date of Birth</label>
            <input type="date" name="DateOfBirth" value="<?= $user['DateOfBirth'] ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label>NID Number</label>
            <input type="text" name="NIDNumber" value="<?= htmlspecialchars($user['NIDNumber']) ?>" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>
