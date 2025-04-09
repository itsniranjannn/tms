<?php
session_start();
include "db_connect.php";

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check in the users table first
    $sql_user = "SELECT * FROM users WHERE username='$username'";
    $result_user = $conn->query($sql_user);

    // If the user is not found in users table, check in the admins table
    if ($result_user->num_rows > 0) {
        // User found in users table
        $user = $result_user->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'user';  // Set role as 'user'
            header("Location: home.php");  // Redirect to user home page
            exit();
        } else {
            $error_message = "Invalid Password!";
        }
    } else {
        // Check if user is found in admins table
        $sql_admin = "SELECT * FROM admins WHERE username='$username'";
        $result_admin = $conn->query($sql_admin);

        if ($result_admin->num_rows > 0) {
            // User found in admins table
            $admin = $result_admin->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = 'admin';  // Set role as 'admin'
                header("Location:home.php");  // Redirect to admin dashboard
                exit();
            } else {
                $error_message = "Invalid Password!";
            }
        } else {
            $error_message = "User not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Task Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to right, #141e30, #243b55);
        }

        .container {
            display: flex;
            width: 800px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        .left {
            flex: 1;
            background: url('tmm.png') no-repeat center;
            background-size: cover;
        }

        .right {
            flex: 1;
            padding: 40px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .tagline {
            font-size: 18px;
            color: #555;
            margin-bottom: 20px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #243b55;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #1d2b41;
        }

        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 10px;
        }

        p a {
            color: #243b55;
            text-decoration: none;
            font-weight: bold;
        }

        p a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left"></div>
        <div class="right">
            <h2>Login to Task Tracker</h2>
            <p class="tagline">Manage your tasks efficiently</p>
            <?php if ($error_message) echo "<p class='error'>$error_message</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form><hr><br>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>
