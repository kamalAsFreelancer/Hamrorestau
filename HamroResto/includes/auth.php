<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }
}

function checkRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    echo "Access Denied";
}
?>
