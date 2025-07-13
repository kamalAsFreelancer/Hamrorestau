<?php
session_start();
include("includes/db.php");

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $loginType = $_POST['login_type']; // 'admin' or 'staff'

    if ($loginType === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    } else {
        $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ? LIMIT 1");
    }

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['restaurant_id'] = $user['restaurant_id'];

                if ($loginType === 'admin') {
                    if ($user['role'] === 'super_admin') {
                        header("Location: super_admin/dashboard.php");
                    } elseif ($user['role'] === 'manager') {
                        header("Location: manager/dashboard.php");
                    } else {
                        $error = "Invalid admin role.";
                    }
                } else {
                    // Staff login always goes to staff dashboard
                    header("Location: staff/dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }

        $stmt->close();
    } else {
        $error = "Database error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login - Restaurant System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h2 {
            margin-bottom: 25px;
            color: #333;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 18px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #0066ff;
            box-shadow: 0 0 8px rgba(0,102,255,0.3);
        }
        button {
            background: #0066ff;
            color: white;
            border: none;
            padding: 14px 0;
            width: 100%;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #004fcc;
        }
        .switch-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 25px;
        }
        .switch-container label {
            cursor: pointer;
            font-weight: 600;
            color: #444;
            user-select: none;
        }
        .switch-container input[type="radio"] {
            margin-right: 8px;
            accent-color: #0066ff;
            cursor: pointer;
        }
        .error {
            margin-top: 15px;
            color: #cc0000;
            font-weight: 600;
        }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-container">
    <h2>Restaurant Login</h2>

    <form method="POST" action="">
        <div class="switch-container">
            <label>
                <input type="radio" name="login_type" value="admin" checked> Manager
            </label>
            <label>
                <input type="radio" name="login_type" value="staff"> Staff
            </label>
        </div>

        <input type="text" name="username" placeholder="Username" required autofocus />
        <input type="password" name="password" placeholder="Password" required />

        <button type="submit">Login</button>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
