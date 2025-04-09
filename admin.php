<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql_admin = "SELECT * FROM admins WHERE id = '$user_id'";
$admin_result = $conn->query($sql_admin);
$admin = $admin_result->fetch_assoc();

// Add Task
if (isset($_POST['add_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : NULL;
    $image = $_FILES['image']['name'];

    if ($image) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }

    // Use a prepared statement to avoid SQL injection
    $status = $assigned_to ? 'assigned' : 'pending';
$stmt = $conn->prepare("INSERT INTO tasks (title, description, status, assigned_to, image) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $title, $description, $status, $assigned_to, $image);
$stmt->execute();
$stmt->close();

    header("Location: admin.php");
    exit();
}

// Delete Task
if (isset($_GET['delete_task'])) {
    $task_id = $_GET['delete_task'];
    $conn->query("DELETE FROM tasks WHERE id = '$task_id'");
}

// Approve Task Completion Request
if (isset($_GET['approve_request'])) {
    $task_id = $_GET['approve_request'];

    // Get the requested_by username
    $query = $conn->query("SELECT requested_by FROM tasks WHERE id = '$task_id'");
    $task = $query->fetch_assoc();
    $requested_by = $task['requested_by'];

    if ($requested_by) {
        $conn->query("UPDATE tasks SET assigned_to = '$requested_by', is_request_pending = 0, status = 'assigned', requested_by = NULL WHERE id = '$task_id'");
    }
}

// Reject Task Completion Request
if (isset($_GET['reject_request'])) {
    $task_id = $_GET['reject_request'];

    // Get the requested_by username
    $query = $conn->query("SELECT requested_by FROM tasks WHERE id = '$task_id'");
    $task = $query->fetch_assoc();
    $requested_by = $task['requested_by'];

    $conn->query("UPDATE tasks SET is_request_pending = 0, status = 'rejected', requested_by = NULL WHERE id = '$task_id'");
}



// Fetch Tasks
$sql_tasks = "SELECT * FROM tasks";
$tasks_result = $conn->query($sql_tasks);

// Fetch Users
$sql_users = "SELECT * FROM users";
$users_result = $conn->query($sql_users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <nav>
        <div class="logo">Task Tracker</div>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="admin.php" class="active">Admin Panel</a></li>
        </ul>
        <div class="user-section">
            <span><?php echo $admin['username']; ?></span>
            <div class="profile-icon">👤</div>
            <button class="logout-button" onclick="window.location.href='logout.php';">Logout</button>
        </div>
    </nav>

    <div class="sidebar">
        <a href="#" onclick="showSection('add-task')">Add Task</a>
        <a href="#" onclick="showSection('manage-tasks')">Manage Tasks</a>
        <a href="#" onclick="showSection('completion-requests')">Approve Requests</a>
        <a href="#" onclick="showSection('manage-users')">Manage Users</a>
    </div>

    <div class="content">
        <!-- Add Task -->
        <div id="add-task" class="section">
            <h3>Add Task</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Task Title" required>
                <textarea name="description" placeholder="Task Description" required></textarea>

                <!-- Select user or mark task as public -->
                <label for="assigned_to">Assign to:</label>
                <select name="assigned_to">
                    <option value="">Public Task (Anyone can request)</option>
                    <?php
                    $users = $conn->query("SELECT username FROM users");
                    while ($user = $users->fetch_assoc()) {
                        echo "<option value='{$user['username']}'>{$user['username']}</option>";
                    }
                    ?>
                </select>

                <input type="file" name="image">
                <button type="submit" name="add_task">Add Task</button>
            </form>
        </div>

        <!-- Manage Tasks -->
        <div id="manage-tasks" class="section hidden">
            <h3>Manage Tasks</h3>
            <div class="task-container">
                <?php while ($task = $tasks_result->fetch_assoc()): ?>
                    <div class="task-card">
                        <h3><?php echo $task['title']; ?></h3>
                        <p><?php echo $task['description']; ?></p>
                        <p>Status: <?php echo ucfirst($task['status']); ?></p>
                        <p>Assigned To: <?php echo $task['assigned_to'] ?: "Unassigned"; ?></p>
                        <a href="admin.php?delete_task=<?php echo $task['id']; ?>"><button>Delete</button></a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Approve Completion Requests -->
        <div id="completion-requests" class="section hidden">
            <h3>Approve Completion Requests</h3>
            <div class="task-container">
                <?php 
                $requests = $conn->query("SELECT * FROM tasks WHERE is_request_pending = 1");
                while ($task = $requests->fetch_assoc()): ?>
                    <div class="task-card">
                        <h3><?php echo $task['title']; ?></h3>
                        <p>Requested by: <?php echo $task['requested_by']; ?></p>
                        <a href="admin.php?approve_request=<?php echo $task['id']; ?>"><button>Approve</button></a>
                        <a href="admin.php?reject_request=<?php echo $task['id']; ?>"><button>Reject</button></a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Manage Users -->
        <div id="manage-users" class="section hidden">
            <h3>Manage Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><a href="admin.php?delete_user=<?php echo $user['id']; ?>"><button>Delete</button></a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(sec => sec.classList.add('hidden'));
            document.getElementById(sectionId).classList.remove('hidden');
        }
    </script>
<footer style="text-align: center; padding: 15px; background: #343a40; color: white; position: fixed; bottom: 0; width: 100%; border-top: 3px solid #007bff;">
    <p style="margin: 0;">
        <a href="home.php" style="color: #007bff; text-decoration: none; font-weight: bold;">Home</a> | 
        &copy; <?php echo date('Y'); ?> Task Management System. All Rights Reserved.
    </p>
</footer>
</body>
</html>
