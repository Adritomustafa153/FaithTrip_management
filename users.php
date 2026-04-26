<?php
session_start();
require_once __DIR__ . '/access_control.php';
requirePermission('user.manage');

$conn = getDbConnection();

if (isset($_GET['unblock']) && is_numeric($_GET['unblock'])) {
    $id = (int)$_GET['unblock'];
    $upd = $conn->prepare("UPDATE user SET login_attempts = 0, is_locked = 0, lock_time = NULL WHERE UserID = ?");
    $upd->bind_param('i', $id);
    $upd->execute();
    header('Location: users.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fc; }
        .table th { background: #4e73df; color: white; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .search-container { position: relative; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-users me-2"></i>User Management</h2>
        <div>
            <?php if (hasPermission('roles.manage')): ?>
                <a href="role_permissions_matrix.php" class="btn btn-info me-2"><i class="fas fa-lock"></i> Role Permissions</a>
            <?php endif; ?>
            <a href="create_user.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New User</a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="mb-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Live search by name, email, role..." autocomplete="off">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="userTable">
                    <thead>
                        <tr><th>ID</th><th>Image</th><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Attempts</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr><td colspan="9" class="text-center">Loading users...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let searchTimeout = null;
const searchInput = document.getElementById('searchInput');
const usersTableBody = document.getElementById('usersTableBody');

function loadUsers(searchTerm = '') {
    fetch(`get_users.php?search=${encodeURIComponent(searchTerm)}`)
        .then(res => res.json())
        .then(data => {
            if (data.users && data.users.length > 0) {
                let html = '';
                data.users.forEach(user => {
                    const imageHtml = user.image ? `<img src="${user.image}" class="avatar">` : '<i class="fas fa-user-circle fa-2x text-secondary"></i>';
                    const statusBadge = user.is_locked ? '<span class="badge bg-danger">Locked</span>' : '<span class="badge bg-success">Active</span>';
                    const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';
                    html += `<tr>
                        <td>${user.UserID}</td>
                        <td>${imageHtml}</td>
                        <td>${escapeHtml(user.UserName)}</div>
                        <td>${escapeHtml(user.email)}</div>
                        <td>${escapeHtml(user.role)}</div>
                        <td>${lastLogin}</div>
                        <td>${user.login_attempts}</div>
                        <td>${statusBadge}</div>
                        <td>
                            <a href="edit_user.php?id=${user.UserID}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                            ${user.is_locked ? `<a href="?unblock=${user.UserID}" class="btn btn-sm btn-danger" onclick="return confirm('Unblock this user?')"><i class="fas fa-unlock-alt"></i> Unblock</a>` : ''}
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.UserID})"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    </tr>`;
                });
                usersTableBody.innerHTML = html;
            } else {
                usersTableBody.innerHTML = '<tr><td colspan="9" class="text-center">No users found</div></tr>';
            }
        })
        .catch(err => {
            console.error(err);
            usersTableBody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error loading users</div></tr>';
        });
}

searchInput.addEventListener('input', function() {
    const term = this.value.trim();
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadUsers(term), 300);
});

function deleteUser(userId) {
    if (confirm('Delete this user permanently? This cannot be undone.')) {
        fetch(`delete_user.php?id=${userId}`, { method: 'DELETE' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('User deleted');
                    loadUsers(searchInput.value.trim());
                } else alert('Error: ' + data.error);
            })
            .catch(err => alert('Request failed'));
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

loadUsers('');
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>