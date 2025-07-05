<?php
session_start();
include("includes/db.php"); // Ensure this connects and sets $conn

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1")) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['restaurant_id'] = $user['restaurant_id'];

                switch ($user['role']) {
                    case 'super_admin':
                        header("Location: super_admin/dashboard.php");
                        exit();
                    case 'manager':
                        header("Location: manager/dashboard.php");
                        exit();
                    case 'staff':
                        header("Location: staff/dashboard.php");
                        exit();
                    default:
                        $error = "Invalid user role.";
                }
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
    <meta charset="UTF-8">
    <title>Login - Restaurant System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-container">
    <h2>Restaurant Login</h2>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required autofocus />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Login</button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>

</body>
</html>
