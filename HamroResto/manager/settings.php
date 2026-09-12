<?php
include("../includes/auth.php");
requireLogin();
include("../includes/db.php");

if (!checkRole('manager')) exit("Access Denied");

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch current user info
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);

        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_username, $user_id);
        if ($stmt->execute()) {
            $_SESSION['username'] = $new_username;
            $message = "Profile updated successfully.";
        } else {
            $message = "Error updating profile.";
        }
        $stmt->close();
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($current_hash);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current, $current_hash)) {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_hash, $user_id);
            if ($stmt->execute()) {
                $message = "Password changed successfully.";
            } else {
                $message = "Failed to change password.";
            }
            $stmt->close();
        } else {
            $message = "Current password is incorrect.";
        }
    }
}

include('header/header.php');
include('sidebar.php');
?>

<div class="main-content">
    <h1>Settings</h1>

    <?php if ($message): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Update Profile</h2>
    <form method="POST">
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br><br>
        <button type="submit" name="update_profile">Update Profile</button>
    </form>

    <h2>Change Password</h2>
    <form method="POST">
        <input type="password" name="current_password" placeholder="Current Password" required><br><br>
        <input type="password" name="new_password" placeholder="New Password" required><br><br>
        <button type="submit" name="change_password">Change Password</button>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
