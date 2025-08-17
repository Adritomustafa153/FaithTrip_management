<?php
// Database connection
$servername = "127.0.0.1";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "faithtrip_accounts";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users from the user table
$sql = "SELECT * FROM user";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;

            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-locked {
            color: red;
            font-weight: bold;
        }
        .user-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .search-container input {
            padding: 8px;
            width: 300px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container">
        <h1>User Activity Dashboard</h1>
        
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search for users..." onkeyup="searchTable()">
        </div>
        
        <table id="userTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Profile</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Last Login</th>
                    <th>Login Attempts</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $statusClass = $row["is_locked"] ? "status-locked" : "status-active";
                        $statusText = $row["is_locked"] ? "Locked" : "Active";
                        
                        // Format last login date
                        $lastLogin = $row["last_login"] ? date("M d, Y h:i A", strtotime($row["last_login"])) : "Never logged in";
                        
                        // Display image if available
                        $imageSrc = $row["image"] ? 'data:image/jpeg;base64,' . base64_encode($row["image"]) : 'https://via.placeholder.com/50';
                        
                        echo "<tr>";
                        echo "<td>" . $row["UserID"] . "</td>";
                        echo "<td><img src='" . $imageSrc . "' class='user-image' alt='Profile Image'></td>";
                        echo "<td>" . htmlspecialchars($row["UserName"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["role"]) . "</td>";
                        echo "<td>" . $lastLogin . "</td>";
                        echo "<td>" . $row["login_attempts"] . "</td>";
                        echo "<td class='" . $statusClass . "'>" . $statusText . "</td>";
                        echo "<td>
                                <button onclick='viewDetails(" . $row["UserID"] . ")'>View</button>
                                <button onclick='editUser(" . $row["UserID"] . ")'>Edit</button>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No users found</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
        
        <div class="pagination">
            <a href="#">&laquo;</a>
            <a href="#" class="active">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#">&raquo;</a>
        </div>
        
        <div class="action-buttons">
            <a href="create_user.php" class="btn">Add New User</a>
        </div>
    </div>

    <script>
        function searchTable() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("userTable");
            tr = table.getElementsByTagName("tr");
            
            for (i = 0; i < tr.length; i++) {
                // Skip the header row
                if (i === 0) continue;
                
                let found = false;
                // Search in username, email, and role columns (columns 2, 3, 4)
                for (let j = 2; j <= 4; j++) {
                    td = tr[i].getElementsByTagName("td")[j];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                if (found) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
        
        function viewDetails(userId) {
            alert("View details for user ID: " + userId);
            // In a real application, you would redirect to a details page or show a modal
            // window.location.href = 'user_details.php?id=' + userId;
        }
        
        function editUser(userId) {
            alert("Edit user ID: " + userId);
            // In a real application, you would redirect to an edit page
            // window.location.href = 'edit_user.php?id=' + userId;
        }
    </script>
</body>
</html>