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

// Fetch user data
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
} else {
    header("Location: login.php"); // Redirect if user not found
    exit();
}

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    $task_id = $_POST['task_id'];

    // Update the status to completed in the database
    $update_sql = "UPDATE tasks SET status = 'completed' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->close();

    header("Location: user.php"); // Refresh page to show updated task list
    exit();
}

// Fetch assigned tasks
$sql_assigned = "SELECT * FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql_assigned);
$stmt->bind_param("s", $user['username']);
$stmt->execute();
$assigned_result = $stmt->get_result();

// Fetch requested tasks
$sql_requested = "SELECT * FROM tasks WHERE requested_by = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql_requested);
$stmt->bind_param("s", $user['username']);
$stmt->execute();
$requested_result = $stmt->get_result();

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        /* General Styling */
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        /* Navigation Bar */
        nav {
            background: #243b55;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        nav .logo {
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        nav ul {
            list-style: none;
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        nav ul li {
            margin: 0 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        /* Task Container */
        .task-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }

        .task-section {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .task-section h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        /* Task Cards */
        .task-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }

        .task-card {
            background: #f9f9f9;
            padding: 20px;
            margin: 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 280px;
            height: 350px;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            overflow: hidden;
        }

        .task-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .task-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
            transition: transform 0.3s ease-in-out;
        }

        .task-card:hover img {
            transform: scale(1.1);
        }

        .task-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .task-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .task-card .task-btn {
            background-color: #243b55;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .task-card .task-btn:hover {
            background-color: #1d2b41;
        }

        /* Task Section Heading */
        .task-section-header {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
        }

        .no-results {
            text-align: center;
            font-size: 18px;
            color: #f44336;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="logo">Task Tracker</div>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="admin.php" class="active">User Dashboard</a></li>
        </ul>
        
        <!-- User Section -->
        <div class="user-section">
            <span><?php echo htmlspecialchars($user['username']); ?></span>
            <div class="profile-icon">👤</div>
            <button onclick="window.location.href='logout.php';" class="logout-button">Logout</button>
        </div>
    </nav>

    <!-- Task List -->
    <div class="task-container">
        <!-- Assigned Tasks -->
        <div class="task-section">
            <h2 class="task-section-header">Assigned Tasks</h2>
            <?php if ($assigned_result->num_rows > 0): ?>
                <div class="task-cards">
                    <?php while ($task = $assigned_result->fetch_assoc()): ?>
                        <div class="task-card">
                            <?php if ($task['image']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($task['image']); ?>" alt="Task Image">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                            <p><strong>Status:</strong> <?php echo ucfirst($task['status']); ?></p>
                            <p><strong>Assigned to:</strong> <?php echo htmlspecialchars($task['assigned_to']); ?></p>
                            <p><strong>Created at:</strong> <?php echo htmlspecialchars($task['created_at']); ?></p>

                            <?php if ($task['status'] === 'assigned'): ?>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" name="complete_task" class="task-btn">Mark as Completed</button>
                                </form>
                            <?php elseif ($task['status'] === 'completed'): ?>
                                <button class="task-btn" disabled>Completed</button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>You have no assigned tasks.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Requested Tasks -->
        <div class="task-section">
            <h2 class="task-section-header">Requested Tasks</h2>
            <?php if ($requested_result->num_rows > 0): ?>
                <div class="task-cards">
                    <?php while ($task = $requested_result->fetch_assoc()): ?>
                        <div class="task-card">
                            <?php if ($task['image']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($task['image']); ?>" alt="Task Image">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                            <p><strong>Status:</strong> <?php echo ucfirst($task['status']); ?></p>
                            <p><strong>Requested by:</strong> <?php echo htmlspecialchars($task['requested_by']); ?></p>
                            <p><strong>Created at:</strong> <?php echo htmlspecialchars($task['created_at']); ?></p>

                            <?php if ($task['status'] === 'assigned'): ?>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" name="complete_task" class="task-btn">Mark as Completed</button>
                                </form>
                            <?php elseif ($task['status'] === 'completed'): ?>
                                <button class="task-btn" disabled>Completed</button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>You have no requested tasks.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <footer style="text-align: center; padding: 15px; background: #343a40; color: white; position: fixed; bottom: 0; width: 100%; border-top: 3px solid #007bff;">
    <p style="margin: 0;">
        <a href="home.php" style="color:rgb(255, 255, 255); text-decoration: none; font-weight: bold;">Home</a> | 
        &copy; <?php echo date('Y'); ?> Task Management System. All Rights Reserved.
    </p>
</footer>

</body>
</html>
