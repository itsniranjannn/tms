<?php
include "db_connect.php";

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username exists in users or admins
    $check_user = $conn->query("SELECT COUNT(*) AS total FROM users WHERE username='$username'")->fetch_assoc();
    $check_admin = $conn->query("SELECT COUNT(*) AS total FROM admins WHERE username='$username'")->fetch_assoc();

    if ($check_user['total'] > 0 || $check_admin['total'] > 0) {
        $error_message = "Username already taken. Please choose a different one.";
    } else {
        // Check if it's the first user (make them an admin)
        $check_admin_count = $conn->query("SELECT COUNT(*) AS total FROM admins")->fetch_assoc();
        if ($check_admin_count['total'] == 0) {
            $sql = "INSERT INTO admins (username, password) VALUES ('$username', '$password')";
        } else {
            $sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
        }

        if ($conn->query($sql) === TRUE) {
            $success_message = "Registration successful! <a href='login.php'>Login</a>";
        } else {
            $error_message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Task Tracker</title>
    <style>
        /* General Page Styling */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            background: linear-gradient(to right, #4facfe, #00f2fe);
        }

        /* Left Section */
        .left-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .left-section h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .left-section p {
            font-size: 18px;
            opacity: 0.8;
        }

        /* Right Section */
        .right-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
            box-shadow: -5px 0px 10px rgba(0, 0, 0, 0.2);
        }

        /* Registration Form Container */
        .register-container {
            width: 350px;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        /* Form Inputs */
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        /* Submit Button */
        button {
            width: 100%;
            padding: 12px;
            background: #4facfe;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #00c6ff;
        }

        /* Success & Error Messages */
        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        /* Links */
        p a {
            color: #4facfe;
            text-decoration: none;
            font-weight: bold;
        }

        p a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>
    <div class="left-section">
        <div>
            <h1>Join Task Tracker</h1>
            <p>Get & Manage your tasks efficiently and stay organized.</p>
        </div>
    </div>

    <div class="right-section">
        <div class="register-container">
            <h2>Register</h2>
            <?php if ($error_message) echo "<p class='error'>$error_message</p>"; ?>
            <?php if ($success_message) echo "<p class='success'>$success_message</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
