<?php
require_once 'auth_check.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'faithtrip_accounts');
$stmt = $conn->prepare("SELECT * FROM user WHERE UserId = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <!-- Bootstrap CSS only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .profile-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container profile-container mt-4">
        <div class="profile-header text-center">
            <?php if (!empty($user['image'])): ?>
                <img src="<?php echo htmlspecialchars($user['image']); ?>" alt="Profile Image" class="profile-img mb-3">
            <?php else: ?>
                <div class="profile-img mb-3 bg-secondary d-inline-flex align-items-center justify-content-center">
                    <span class="text-white display-4"><?php echo strtoupper(substr($user['UserName'], 0, 1)); ?></span>
                </div>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($user['UserName']); ?></h1>
            <p class="text-muted">Member since <?php echo date('F Y', strtotime($user['created_at'] ?? 'now')); ?></p>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Personal Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>User ID:</strong> <?php echo htmlspecialchars($user['UserID']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date of Birth:</strong> <?php echo $user['DateOfBirth'] ? htmlspecialchars($user['DateOfBirth']) : 'Not provided'; ?></p>
                        <p><strong>NID Number:</strong> <?php echo $user['NIDNumber'] ? htmlspecialchars($user['NIDNumber']) : 'Not provided'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mb-4">
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>