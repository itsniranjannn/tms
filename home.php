<?php
session_start();
include "db_connect.php";

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];

// Fetch user data based on role (admin or user)
$user = null;
$role = '';

// Check if the logged-in user is an admin
$sql_admin = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($sql_admin);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin_result = $stmt->get_result();

if ($admin_result->num_rows > 0) {
    $user = $admin_result->fetch_assoc();
    $role = 'admin';
} else {
    // If not an admin, fetch from users table
    $sql_user = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql_user);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows > 0) {
        $user = $user_result->fetch_assoc();
        $role = 'user';
    }
}

$stmt->close();

// Handle search query
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    $sql = "SELECT * FROM tasks WHERE (title LIKE ? OR description LIKE ?) ORDER BY status = 'completed', id ASC";
    $stmt = $conn->prepare($sql);
    $param = "%" . $search_query . "%";
    $stmt->bind_param("ss", $param, $param);
} else {
    // Fetch tasks, keeping completed ones at the end
    $sql = "SELECT * FROM tasks ORDER BY status = 'completed', id ASC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Handle task completion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_completion'])) {
    if ($role !== 'admin') { // Ensure only users can request
        $task_id = $_POST['task_id'];
        $username = $user['username'];

        // Check if the task is pending and hasn't been requested yet
        $check_sql = "SELECT * FROM tasks WHERE id = ? AND status = 'pending' AND is_request_pending = 0";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Request the task completion
            $update_sql = "UPDATE tasks SET requested_by = ?, is_request_pending = 1 WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $username, $task_id);
            $stmt->execute();
        }

        $stmt->close();
    }

    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="logo">Task Tracker</div>
        <div class="search-container">
            <form method="GET">
                <input type="text" name="search" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        <div class="user-section">
            <span><?php echo htmlspecialchars($user['username']); ?></span>
            <div class="profile-icon">👤</div>
            <?php if ($role === 'admin'): ?>
                <a href="admin.php" class="dashboard-btn">Admin Panel</a>
            <?php else: ?>
                <a href="user.php" class="dashboard-btn">User Dashboard</a>
            <?php endif; ?>
            <button onclick="window.location.href='logout.php';" class="logout-button">Logout</button>
        </div>
    </nav>

<!-- Task List -->
<div class="task-section">
    <h2>Available Tasks</h2>
    <div class="task-slider available-tasks">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($task = $result->fetch_assoc()): ?>
                <?php if ($task['status'] === 'pending'): // Show only available tasks ?>
                    <div class="task-card">
                        <?php if ($task['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($task['image']); ?>" alt="Task Image">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p><?php echo htmlspecialchars($task['description']); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($task['status']); ?></p>
                        
                        <?php if ($role !== 'admin'): ?>
                            <?php if ($task['is_request_pending'] == 1): ?>
                                <button disabled>
                                    <?php echo ($task['requested_by'] == $user['username']) ? "Requested" : "Hold"; ?>
                                </button>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" name="request_completion">Request to Complete</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-results">No available tasks.</div>
        <?php endif; ?>
</div>
<br><hr>
<div class="task-section">
    <h2>Assigned Tasks</h2>
    <div class="task-slider assigned-tasks">
        <?php
        $result->data_seek(0); // Reset pointer for second loop
        if ($result->num_rows > 0): ?>
            <?php while ($task = $result->fetch_assoc()): ?>
                <?php if ($task['status'] !== 'pending'): // Show assigned tasks ?>
                    <div class="task-card">
                        <?php if ($task['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($task['image']); ?>" alt="Task Image">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p><?php echo htmlspecialchars($task['description']); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($task['status']); ?></p>
                        <button disabled>Already Assigned</button>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-results">No assigned tasks.</div>
        <?php endif; ?>
            </div>.
        </div>
        <hr>
<br>
    <?php
include "footer.php";
?>

</body>
</html>
