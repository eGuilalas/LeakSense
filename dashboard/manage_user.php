<?php
// Example data for demonstration (replace with database queries in production)
$users = [
    ["username" => "johndoe", "userrole" => "admin", "type" => "corporate", "name" => "John Doe", "email" => "johndoe@example.com", "phone" => "123-456-7890", "address" => "123 Main St, City"],
    ["username" => "janedoe", "userrole" => "user", "type" => "homeowner", "name" => "Jane Doe", "email" => "janedoe@example.com", "phone" => "987-654-3210", "address" => "456 Oak St, City"],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Leaksense Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #1E1E2D; color: #fff; display: flex; }
        .dashboard-container { display: flex; height: 100vh; width: 100%; }
        .sidebar { background-color: #2B2D42; width: 220px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #8D99AE; font-size: 1.5em; margin-bottom: 20px; }
        .sidebar ul { list-style: none; padding-left: 0; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a {
            text-decoration: none;
            color: #D6D8E7;
            font-size: 1em;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #F72585;
            color: #fff;
        }
        
        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .form-container, .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        h3 { color: #8D99AE; margin-bottom: 15px; }
        label { color: #D6D8E7; display: block; margin-top: 10px; }
        input, select {
            width: 100%;
            padding: 8px;
            background-color: #2B2D42;
            color: #D6D8E7;
            border: 1px solid #444;
            border-radius: 5px;
            margin-top: 5px;
        }
        .button-group { margin-top: 15px; }
        button {
            padding: 10px 15px;
            background-color: #F72585;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover { background-color: #FF4571; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .action-buttons a { color: #F72585; text-decoration: none; margin-right: 10px; font-weight: bold; }
        .search-container { margin-bottom: 10px; }
        
        /* Bottom section styling */
        .bottom-section {
            border-top: 1px solid #444;
            padding-top: 20px;
            color: #D6D8E7;
            text-align: left;
        }
        .bottom-section h3, .bottom-section h5 { margin-bottom: 10px; color: #D6D8E7; }
        .bottom-section a { color: #F72585; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }
        .bottom-section a:hover { text-decoration: underline; }
    </style>
    <script>
        function filterUsers() {
            const filter = document.getElementById("searchName").value.toLowerCase();
            const rows = document.querySelectorAll("#userTable tbody tr");
            rows.forEach(row => {
                const name = row.querySelector("td:nth-child(4)").innerText.toLowerCase();
                row.style.display = name.includes(filter) ? "" : "none";
            });
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h2>Leaksense Dashboard</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="gs1.php">ESP32-GasSensor1</a></li>
                        <li><a href="gs2.php">ESP32-GasSensor2</a></li>
                        <li><a href="Reports.php">Reports</a></li>
                        <li><a href="manage_user.php" class="active">Manage User</a></li>
                        <li><a href="Threshold.php">Threshold Setup</a></li>
                    </ul>
                </nav>
            </div>
            <div class="bottom-section">
                <h3>USERNAME</h3>
                <h3>Role</h3>
            </div>
            <div class="bottom-section">
                <h3>Language</h3>
                <h5>ENG - FR</h5>
            </div>
            <div class="bottom-section">
                <a href="login.php">Logout</a>
            </div>
        </aside>

        <main class="main-dashboard">
            <!-- Add New User Form -->
            <div class="form-container">
                <h3>Add / Edit User</h3>
                <form action="" method="post">
                    <label>Username</label>
                    <input type="text" name="username" required>

                    <label>Password</label>
                    <input type="password" name="password" required>

                    <label>User Role</label>
                    <select name="userrole" required>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                        <option value="super_user">Super User</option>
                        <option value="super_admin">Super Admin</option>
                    </select>

                    <label>Type</label>
                    <select name="type" required>
                        <option value="corporate">Corporate</option>
                        <option value="homeowner">Homeowner</option>
                    </select>

                    <label>Name</label>
                    <input type="text" name="name" required>

                    <label>Email</label>
                    <input type="email" name="email" required>

                    <label>Phone</label>
                    <input type="text" name="phone" required>

                    <label>Address</label>
                    <input type="text" name="address" required>

                    <div class="button-group">
                        <button type="submit">Save User</button>
                        <button type="reset">Clear Form</button>
                    </div>
                </form>
            </div>

            <!-- User Table with Search Filter -->
            <div class="table-container">
                <h3>All Users</h3>
                <div class="search-container">
                    <label for="searchName">Search by Name:</label>
                    <input type="text" id="searchName" onkeyup="filterUsers()" placeholder="Enter name to search">
                </div>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>User Role</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user["username"]; ?></td>
                                <td><?php echo $user["userrole"]; ?></td>
                                <td><?php echo $user["type"]; ?></td>
                                <td><?php echo $user["name"]; ?></td>
                                <td><?php echo $user["email"]; ?></td>
                                <td><?php echo $user["phone"]; ?></td>
                                <td><?php echo $user["address"]; ?></td>
                                <td class="action-buttons">
                                    <a href="edit_user.php?username=<?php echo $user["username"]; ?>">Edit</a>
                                    <a href="delete_user.php?username=<?php echo $user["username"]; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                                    <a href="unlockaccount.php?username=<?php echo $user["username"]; ?>">Unlock</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
