<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginType = $_POST['login_type'] ?? 'admin';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } elseif ($loginType === 'admin') {
        $stmt = $conn->prepare("SELECT id, username, password, role, restaurant_id, status FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['restaurant_id'] = $user['restaurant_id'] !== null ? (int)$user['restaurant_id'] : null;

            if (in_array($user['role'], ['super_admin', 'manager'], true)) {
                header('Location: ' . ($user['role'] === 'super_admin' ? 'super_admin/dashboard.php' : 'manager/dashboard.php'));
                exit;
            }
        }
        $error = 'Invalid username or password.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role, restaurant_id, status FROM staff WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $staff = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($staff && $staff['status'] === 'active' && password_verify($password, $staff['password'])) {
            $_SESSION['user_id'] = (int)$staff['id'];
            $_SESSION['username'] = $staff['username'];
            $_SESSION['role'] = $staff['role'];
            $_SESSION['restaurant_id'] = (int)$staff['restaurant_id'];
            header('Location: staff/dashboard.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hamrorestau</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { min-height:100vh; display:grid; place-items:center; margin:0; background:#f5f7fb; font-family:Arial,sans-serif; }
        .login-container { width:min(420px,92vw); background:#fff; padding:32px; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 12px 35px rgba(0,0,0,.08); }
        .login-container h2 { margin:0 0 24px; text-align:center; }
        .switch-container { display:flex; justify-content:center; gap:24px; margin-bottom:20px; }
        .login-container input[type=text], .login-container input[type=password] { box-sizing:border-box; width:100%; padding:12px 14px; margin-bottom:14px; border:1px solid #d1d5db; border-radius:8px; }
        .login-container button { width:100%; padding:12px; border:0; border-radius:8px; background:#2563eb; color:#fff; font-weight:600; cursor:pointer; }
        .error { color:#b91c1c; text-align:center; margin:14px 0 0; }
    </style>
</head>
<body>
<div class="login-container">
    <h2>Hamrorestau Login</h2>
    <form method="POST">
        <div class="switch-container">
            <label><input type="radio" name="login_type" value="admin" checked> Manager/Admin</label>
            <label><input type="radio" name="login_type" value="staff"> Staff</label>
        </div>
        <input type="text" name="username" placeholder="Username" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    </form>
</div>
</body>
</html>
