<?php
session_start();
require_once __DIR__ . '/access_control.php';

// Use a permission that makes sense; you can add 'agents.view' to permissions table
// or temporarily use 'user.manage' for admin access.
// requirePermission('agents.view');  // Change to 'user.manage' if you haven't added agents.view
requirePermission('user.manage');

$conn = getDbConnection();

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $conn->real_escape_string($_GET['delete']);
    $delStmt = $conn->prepare("DELETE FROM agents WHERE AgentsID = ?");
    $delStmt->bind_param('s', $id);
    if ($delStmt->execute()) {
        $success = "Agent deleted successfully.";
    } else {
        $error = "Delete failed: " . $delStmt->error;
    }
    $delStmt->close();
    // Redirect to avoid re-submission on refresh
    header("Location: agents_list.php");
    exit;
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM agents";
if (!empty($search)) {
    $like = "%$search%";
    $sql .= " WHERE AgentName LIKE ? OR ShopName LIKE ? OR Email LIKE ? OR PhoneNumber LIKE ? OR AgentsID LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agents List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fc; }
        .table th { background: #4e73df; color: white; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-users me-2"></i>Agents List</h2>
        <a href="insert_agent.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Agent</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <!-- Search form -->
            <form method="get" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by ID, Name, Shop, Email, Phone..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="agents_list.php" class="btn btn-outline-danger"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Agent ID</th>
                            <th>Image</th>
                            <th>Agent Name</th>
                            <th>Shop Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>NID</th>
                            <th>Date of Birth</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($agent = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($agent['AgentsID']) ?></td>
                                    <td>
                                        <?php if (!empty($agent['Image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $agent['Image'])): ?>
                                            <img src="<?= htmlspecialchars($agent['Image']) ?>" class="avatar">
                                        <?php else: ?>
                                            <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($agent['AgentName']) ?></td>
                                    <td><?= htmlspecialchars($agent['ShopName']) ?></td>
                                    <td><?= htmlspecialchars($agent['Email']) ?></td>
                                    <td><?= htmlspecialchars($agent['PhoneNumber']) ?></td>
                                    <td><?= htmlspecialchars($agent['NID']) ?></td>
                                    <td><?= date('d M Y', strtotime($agent['DateOfBirth'])) ?></td>
                                    <td>
                                        <a href="edit_agents.php?id=<?= urlencode($agent['AgentsID']) ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?delete=<?= urlencode($agent['AgentsID']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this agent permanently?')"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center">No agents found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>