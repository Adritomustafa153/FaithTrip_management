<?php
include 'auth_check.php';
include 'db.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM iata_systems WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: view_iata_system.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View IATA Systems</title>
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
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--secondary-color);
            color: white;
            border-bottom: none;
        }
        
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            border-radius: 8px;
            padding: 8px 15px;
        }
        
        .password-field {
            font-family: 'Courier New', monospace;
        }
        
        .action-col {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="fas fa-network-wired me-2"></i>IATA Systems</h3>
                        <a href="iata_systems.php" class="btn btn-light">
                            <i class="fas fa-plus me-2"></i>Add New System
                        </a>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>System</th>
                                        <th>PCC</th>
                                        <th>URL</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th class="action-col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM iata_systems ORDER BY system ASC";
                                    $result = $conn->query($query);
                                    
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<tr>
                                                <td>' . htmlspecialchars($row['system']) . '</td>
                                                <td>' . htmlspecialchars($row['PCC']) . '</td>
                                                <td><a href="' . htmlspecialchars($row['url']) . '" target="_blank">' . htmlspecialchars($row['url']) . '</a></td>
                                                <td>' . htmlspecialchars($row['user']) . '</td>
                                                <td class="password-field">••••••••</td>
                                                <td class="action-col">
                                                    <button class="btn btn-sm btn-outline-primary view-password" data-password="' . htmlspecialchars($row['password']) . '">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <a href="iata_systems.php?edit=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="view_iata_system.php?delete=' . $row['id'] . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure you want to delete this system?\')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center">No IATA systems found</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password View Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">System Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="modalPassword" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyPassword">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password viewing functionality
        document.querySelectorAll('.view-password').forEach(button => {
            button.addEventListener('click', function() {
                const password = this.getAttribute('data-password');
                document.getElementById('modalPassword').value = password;
                const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
                modal.show();
            });
        });

        // Copy password to clipboard
        document.getElementById('copyPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('modalPassword');
            passwordInput.select();
            document.execCommand('copy');
            
            // Change button text temporarily
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    </script>
</body>
</html>

<?php
// Close connection only at the end
$conn->close();
?>