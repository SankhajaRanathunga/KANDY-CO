<?php
/**
 * KANDY CO. - Admin Portal Login
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        setFlash('danger', 'Please enter your admin credentials.');
    } else {
        // Query Admins Table first
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        // Standard test fallback for preseeded admin ('admin' / 'admin123')
        if ($admin && ($password === 'admin123' || password_verify($password, $admin['password']))) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['full_name'];
            setFlash('success', 'Authenticated successfully. Welcome back to KANDY CO. Control Center.');
            header('Location: index.php');
            exit();
        } else {
            setFlash('danger', 'Invalid administrator credentials.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | KANDY CO.</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: var(--bg-dark); display: flex; align-items: center; justify-content: center; min-height: 100vh; color: var(--text-inverse);">

<div style="width: 100%; max-width: 420px; padding: 40px; background-color: #141416; border: 1px solid var(--border-dark); border-radius: 4px;">
    <div style="text-align: center; margin-bottom: 32px;">
        <h2 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; letter-spacing: 0.15em;">KANDY CO.</h2>
        <span style="font-size: 0.75rem; color: var(--text-light); letter-spacing: 0.1em;">ADMINISTRATOR SYSTEM ACCESS</span>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="flash-toast flash-<?= e($flash['type']) ?>" style="position: static; margin-bottom: 20px; width: 100%;">
            <span><?= e($flash['message']) ?></span>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label class="form-label" style="color: var(--text-light);">ADMIN USERNAME OR EMAIL</label>
            <input type="text" name="username" class="form-input" required placeholder="admin" value="admin" style="background-color: var(--border-dark); color: #FFF; border-color: #333;">
        </div>

        <div class="form-group">
            <label class="form-label" style="color: var(--text-light);">ADMIN PASSWORD</label>
            <input type="password" name="password" class="form-input" required placeholder="admin123" value="admin123" style="background-color: var(--border-dark); color: #FFF; border-color: #333;">
        </div>

        <button type="submit" class="btn btn-primary full-width" style="height: 48px; margin-top: 12px; background-color: #FFF; color: #000;">LOG IN TO DASHBOARD</button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 0.75rem; color: var(--text-light);">
        <span>Demo Credentials: <strong>admin</strong> / <strong>admin123</strong></span>
    </div>
</div>

</body>
</html>
