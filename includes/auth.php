<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
}

function checkRole(string $role): bool
{
    return isset($_SESSION['role']) && hash_equals((string)$role, (string)$_SESSION['role']);
}

function requireRole(string ...$roles): void
{
    requireLogin();

    $currentRole = $_SESSION['role'] ?? '';
    if (!in_array($currentRole, $roles, true)) {
        http_response_code(403);
        exit('Access Denied');
    }
}

function redirectByRole(): void
{
    switch ($_SESSION['role'] ?? '') {
        case 'super_admin':
            header('Location: super_admin/dashboard.php');
            break;
        case 'manager':
            header('Location: manager/dashboard.php');
            break;
        case 'waiter':
        case 'kitchen':
        case 'bartender':
            header('Location: staff/dashboard.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}
?>
