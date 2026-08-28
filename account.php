<?php
/**
 * KANDY CO. - Customer Account Dashboard
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$user = getLoggedInUser($pdo);
$userId = $_SESSION['user_id'];

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($fullName) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $userId]);
        $_SESSION['user_name'] = $fullName;
        setFlash('success', 'Profile details updated successfully.');
        header('Location: account.php');
        exit();
    }
}

// Fetch Past Orders
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? OR email = ? ORDER BY id DESC");
    $stmt->execute([$userId, $user['email']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {}

$pageTitle = "My Account | KANDY CO.";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
            <div>
                <span class="section-subtitle">CUSTOMER PORTAL</span>
                <h1 class="section-title" style="margin-top: 4px; text-align: left;">WELCOME, <?= strtoupper(e($user['full_name'])) ?></h1>
            </div>
            <a href="login.php?logout=1" onclick="event.preventDefault(); location.href='login.php?action=logout';" class="btn btn-outline">SIGN OUT</a>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 48px;">
            <!-- Left: Order History Table -->
            <div>
                <h3 style="font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; letter-spacing: 0.1em; margin-bottom: 20px;">MY ORDER HISTORY</h3>

                <?php if (!empty($orders)): ?>
                    <table class="table-style">
                        <thead>
                            <tr>
                                <th>ORDER #</th>
                                <th>DATE</th>
                                <th>STATUS</th>
                                <th>TOTAL</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td><strong><?= e($ord['order_number']) ?></strong></td>
                                    <td><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                                    <td>
                                        <span class="badge-tag" style="background-color: var(--text-primary);"><?= e($ord['order_status']) ?></span>
                                    </td>
                                    <td><strong><?= formatPrice($ord['total_amount']) ?></strong></td>
                                    <td>
                                        <a href="order-confirmation.php?order_number=<?= e($ord['order_number']) ?>" style="font-size: 0.8rem; font-weight: 700; text-decoration: underline;">VIEW RECEIPT</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="background-color: var(--bg-alt); padding: 32px; text-align: center; border: 1px solid var(--border-color);">
                        <p style="color: var(--text-muted); font-size: 0.9rem;">You have not placed any orders yet.</p>
                        <a href="shop.php" class="btn btn-primary" style="margin-top: 16px;">START SHOPPING</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Edit Profile Settings -->
            <div>
                <div style="background-color: var(--bg-alt); padding: 32px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                    <h3 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; letter-spacing: 0.1em; margin-bottom: 20px;">ACCOUNT SETTINGS</h3>

                    <form action="account.php" method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group">
                            <label class="form-label">FULL NAME</label>
                            <input type="text" name="full_name" class="form-input" required value="<?= e($user['full_name']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">EMAIL ADDRESS (READ-ONLY)</label>
                            <input type="email" class="form-input" readonly disabled value="<?= e($user['email']) ?>" style="opacity: 0.7;">
                        </div>

                        <div class="form-group">
                            <label class="form-label">PHONE NUMBER</label>
                            <input type="tel" name="phone" class="form-input" value="<?= e($user['phone']) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary full-width" style="margin-top: 8px;">SAVE CHANGES</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
