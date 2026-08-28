<?php
/**
 * KANDY CO. - Admin Overview Dashboard
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Handle Order Status Quick Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['order_status']);
    if ($orderId > 0 && $newStatus) {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        setFlash('success', "Order #{$orderId} status updated to {$newStatus}.");
        header('Location: index.php');
        exit();
    }
}

require_once __DIR__ . '/includes/header.php';

// Fetch Key Dashboard Metrics
$totalSales = 0.00;
$totalOrders = 0;
$totalCustomers = 0;
$totalProducts = 0;

try {
    $totalSales = (float)$pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
    $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
} catch (Exception $e) {}

// Fetch Low Stock Warnings (Variant stock < 5)
$lowStockVariants = [];
try {
    $stmt = $pdo->query("
        SELECT pv.*, p.name as product_name, p.slug
        FROM product_variants pv
        JOIN products p ON pv.product_id = p.id
        WHERE pv.stock_quantity < 5
        ORDER BY pv.stock_quantity ASC
    ");
    $lowStockVariants = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch Recent 10 Orders
$recentOrders = [];
try {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 10");
    $recentOrders = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<!-- Metric Cards Grid -->
<div class="grid grid-4" style="margin-bottom: 36px;">
    <div class="stat-card">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">TOTAL REVENUE</span>
        <div class="stat-val"><?= formatPrice($totalSales) ?></div>
    </div>
    <div class="stat-card">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">TOTAL ORDERS</span>
        <div class="stat-val"><?= number_format($totalOrders) ?></div>
    </div>
    <div class="stat-card">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">REGISTERED CUSTOMERS</span>
        <div class="stat-val"><?= number_format($totalCustomers) ?></div>
    </div>
    <div class="stat-card">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">ACTIVE PRODUCTS</span>
        <div class="stat-val"><?= number_format($totalProducts) ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 36px;">
    <!-- Left: Recent Orders Table -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800;">RECENT ORDERS</h3>
            <a href="orders.php" style="font-size: 0.8rem; font-weight: 700; text-decoration: underline;">VIEW ALL ORDERS &rarr;</a>
        </div>

        <?php if (!empty($recentOrders)): ?>
            <table class="table-style" style="background-color: var(--bg-main);">
                <thead>
                    <tr>
                        <th>ORDER #</th>
                        <th>CUSTOMER</th>
                        <th>TOTAL</th>
                        <th>STATUS</th>
                        <th>UPDATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $ord): ?>
                        <tr>
                            <td><strong><?= e($ord['order_number']) ?></strong></td>
                            <td>
                                <div><strong><?= e($ord['customer_name']) ?></strong></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($ord['email']) ?></div>
                            </td>
                            <td><strong><?= formatPrice($ord['total_amount']) ?></strong></td>
                            <td>
                                <span class="badge-tag" style="background-color: var(--text-primary);"><?= e($ord['order_status']) ?></span>
                            </td>
                            <td>
                                <form action="index.php" method="POST" style="display: flex; gap: 4px;">
                                    <input type="hidden" name="update_order_status" value="1">
                                    <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                    <select name="order_status" class="form-select" style="padding: 4px 8px; font-size: 0.75rem;" onchange="this.form.submit()">
                                        <?php foreach (['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $ord['order_status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No orders placed yet.</p>
        <?php endif; ?>
    </div>

    <!-- Right: Low Stock Alert Warning Box -->
    <div>
        <div style="background-color: var(--bg-main); padding: 24px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: var(--sale-color); margin-bottom: 16px;">
                &boxbox; LOW STOCK WARNING (<?= count($lowStockVariants) ?>)
            </h3>

            <?php if (!empty($lowStockVariants)): ?>
                <ul style="display: flex; flex-direction: column; gap: 12px; font-size: 0.85rem;">
                    <?php foreach ($lowStockVariants as $var): ?>
                        <li style="padding-bottom: 8px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                            <div>
                                <strong style="display: block;"><?= e($var['product_name']) ?></strong>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?= e($var['color']) ?> / <?= e($var['size']) ?></span>
                            </div>
                            <span class="badge-tag badge-sale" style="height: fit-content;"><?= $var['stock_quantity'] ?> LEFT</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 0.85rem;">All product inventory levels are optimal.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
