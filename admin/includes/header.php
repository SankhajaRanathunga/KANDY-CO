<?php
/**
 * KANDY CO. - Admin Panel Header & Layout Component
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
$currentAdminScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KANDY CO. | Admin Control Center</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background-color: var(--bg-alt);
            min-height: 100vh;
        }
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 260px;
            background-color: var(--bg-dark);
            color: var(--text-inverse);
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
        }
        .admin-brand {
            padding: 0 24px 24px;
            border-bottom: 1px solid var(--border-dark);
            font-family: var(--font-heading);
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.1em;
        }
        .admin-menu {
            padding: 24px 0;
            flex: 1;
        }
        .admin-menu li {
            margin-bottom: 4px;
        }
        .admin-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-light);
            transition: var(--transition);
        }
        .admin-link:hover, .admin-link.active {
            color: var(--text-inverse);
            background-color: rgba(255,255,255,0.08);
            border-left: 3px solid var(--text-inverse);
        }
        .admin-main {
            margin-left: 260px;
            flex: 1;
            padding: 32px 40px;
        }
        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }
        .stat-card {
            background-color: var(--bg-main);
            padding: 24px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
        }
        .stat-val {
            font-family: var(--font-heading);
            font-size: 2rem;
            font-weight: 800;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            KANDY CO. <span style="font-size: 0.65rem; background-color: var(--border-dark); padding: 2px 6px; border-radius: 2px; vertical-align: middle;">ADMIN</span>
        </div>
        <ul class="admin-menu">
            <li><a href="index.php" class="admin-link <?= $currentAdminScript === 'index.php' ? 'active' : '' ?>">&bull; Dashboard Overview</a></li>
            <li><a href="products.php" class="admin-link <?= in_array($currentAdminScript, ['products.php', 'add-product.php', 'edit-product.php']) ? 'active' : '' ?>">&bull; Product Catalog</a></li>
            <li><a href="categories.php" class="admin-link <?= $currentAdminScript === 'categories.php' ? 'active' : '' ?>">&bull; Categories</a></li>
            <li><a href="orders.php" class="admin-link <?= $currentAdminScript === 'orders.php' ? 'active' : '' ?>">&bull; Orders Management</a></li>
            <li><a href="customers.php" class="admin-link <?= $currentAdminScript === 'customers.php' ? 'active' : '' ?>">&bull; Customer Directory</a></li>
            <li><a href="settings.php" class="admin-link <?= $currentAdminScript === 'settings.php' ? 'active' : '' ?>">&bull; Store Settings</a></li>
            <li style="margin-top: 32px;"><a href="../index.php" target="_blank" class="admin-link" style="color: var(--text-light);">&rarr; View Live Website</a></li>
            <li><a href="logout.php" class="admin-link" style="color: var(--danger);">&times; Sign Out Admin</a></li>
        </ul>
    </aside>

    <!-- Main Content Wrapper -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h2 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; text-transform: uppercase;">ADMINISTRATION PORTAL</h2>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Logged in as: <strong><?= e($adminUsername) ?></strong></span>
            </div>
            <div>
                <a href="add-product.php" class="btn btn-primary btn-sm">+ ADD NEW PRODUCT</a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php
        $flash = getFlash();
        if ($flash):
        ?>
        <div class="flash-toast flash-<?= e($flash['type']) ?>" style="position: static; margin-bottom: 24px; width: 100%;">
            <span><?= e($flash['message']) ?></span>
        </div>
        <?php endif; ?>
