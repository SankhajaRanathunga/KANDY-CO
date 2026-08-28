<?php
/**
 * KANDY CO. - Admin Orders Manager
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['order_status']);
    if ($orderId > 0 && $newStatus) {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        setFlash('success', "Order #{$orderId} updated to {$newStatus}.");
        header('Location: orders.php');
        exit();
    }
}

$statusFilter = trim($_GET['status'] ?? '');
$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];
if ($statusFilter !== '') {
    $sql .= " AND order_status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY id DESC";

$orders = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800;">ORDERS MANAGEMENT (<?= count($orders) ?>)</h3>

    <!-- Status Filter -->
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 0.8rem; font-weight: 700;">FILTER BY STATUS:</span>
        <select class="form-select" style="padding: 6px 12px; font-size: 0.85rem;" onchange="location = 'orders.php?status=' + this.value;">
            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Statuses</option>
            <?php foreach (['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $ord): ?>
        <?php
        // Fetch order items for this order
        $items = $pdo->query("SELECT * FROM order_items WHERE order_id = {$ord['id']}")->fetchAll();
        ?>
        <div style="background-color: var(--bg-main); border: 1px solid var(--border-color); padding: 24px; margin-bottom: 20px; border-radius: var(--radius-sm);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 16px;">
                <div>
                    <h4 style="font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; margin-bottom: 4px;">
                        ORDER #<?= e($ord['order_number']) ?>
                    </h4>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Placed on: <?= date('F j, Y g:i A', strtotime($ord['created_at'])) ?> &bull; Customer: <strong><?= e($ord['customer_name']) ?></strong> (<?= e($ord['email']) ?>)
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <span style="font-size: 1.25rem; font-weight: 800;"><?= formatPrice($ord['total_amount']) ?></span>
                    
                    <!-- Quick Status Change Form -->
                    <form action="orders.php" method="POST">
                        <input type="hidden" name="update_order_status" value="1">
                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                        <select name="order_status" class="form-select" style="padding: 6px 12px; font-size: 0.85rem;" onchange="this.form.submit()">
                            <?php foreach (['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $st): ?>
                                <option value="<?= $st ?>" <?= $ord['order_status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Address & Shipping Details -->
            <div class="grid grid-2" style="font-size: 0.85rem; margin-bottom: 16px;">
                <div>
                    <strong>Shipping Address:</strong> <?= e($ord['shipping_address']) ?>, <?= e($ord['city']) ?>, <?= e($ord['postal_code']) ?>, <?= e($ord['country']) ?> (Phone: <?= e($ord['phone']) ?>)
                </div>
                <div>
                    <strong>Payment Method:</strong> <?= strtoupper(e($ord['payment_method'])) ?> | Status: <strong style="color: var(--success);"><?= strtoupper(e($ord['payment_status'])) ?></strong>
                </div>
            </div>

            <!-- Items Purchased Table -->
            <table class="table-style" style="font-size: 0.8rem;">
                <thead>
                    <tr>
                        <th>ITEM NAME</th>
                        <th>COLOR</th>
                        <th>SIZE</th>
                        <th>QTY</th>
                        <th>UNIT PRICE</th>
                        <th>SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><strong><?= e($it['product_name']) ?></strong></td>
                            <td><?= e($it['color']) ?></td>
                            <td><?= e($it['size']) ?></td>
                            <td><?= $it['quantity'] ?></td>
                            <td><?= formatPrice($it['price']) ?></td>
                            <td><strong><?= formatPrice($it['subtotal']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div style="background-color: var(--bg-main); padding: 40px; text-align: center; border: 1px solid var(--border-color);">
        <p style="color: var(--text-muted);">No orders found matching the filter criteria.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
