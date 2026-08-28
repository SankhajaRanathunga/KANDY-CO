<?php
/**
 * KANDY CO. - Order Confirmation & Receipt Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$orderNumber = trim($_GET['order_number'] ?? '');
if (!$orderNumber) {
    header('Location: index.php');
    exit();
}

// Fetch Order Details
$order = null;
$orderItems = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $pdo->prepare("
            SELECT oi.*, 
                   (SELECT image_path FROM product_images WHERE product_id = oi.product_id ORDER BY is_primary DESC LIMIT 1) as image_path
            FROM order_items oi
            WHERE oi.order_id = ?
        ");
        $itemStmt->execute([$order['id']]);
        $orderItems = $itemStmt->fetchAll();
    }
} catch (Exception $e) {}

if (!$order) {
    echo "<div class='container section-padding' style='text-align:center;'><h2>ORDER NOT FOUND</h2><p><a href='index.php' class='btn btn-primary'>RETURN HOME</a></p></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$pageTitle = "Order Confirmed - " . e($order['order_number']) . " | KANDY CO.";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
    <div class="container-narrow">
        <!-- Success Banner -->
        <div style="text-align: center; margin-bottom: 48px;">
            <div style="width: 70px; height: 70px; background-color: var(--success); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2.2rem;">
                &#10003;
            </div>
            <span class="section-subtitle">THANK YOU FOR YOUR ORDER</span>
            <h1 class="section-title" style="margin-top: 8px;">ORDER CONFIRMED</h1>
            <p style="font-size: 0.95rem; color: var(--text-muted); margin-top: 8px;">
                We have received your order <strong><?= e($order['order_number']) ?></strong>. A confirmation email has been sent to <strong><?= e($order['email']) ?></strong>.
            </p>
        </div>

        <!-- Receipt Box -->
        <div style="background-color: var(--bg-alt); border: 1px solid var(--border-color); padding: 40px; border-radius: var(--radius-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 24px;">
                <div>
                    <h3 style="font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem;">KANDY CO. RECEIPT</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">DATE: <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></span>
                </div>
                <div>
                    <span class="badge-tag" style="background-color: var(--success); font-size: 0.75rem; padding: 6px 12px;"><?= e($order['order_status']) ?></span>
                </div>
            </div>

            <!-- Shipping Summary -->
            <div class="grid grid-2" style="margin-bottom: 32px; font-size: 0.85rem;">
                <div>
                    <h4 style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">SHIPPING TO:</h4>
                    <p><strong><?= e($order['customer_name']) ?></strong></p>
                    <p><?= e($order['shipping_address']) ?></p>
                    <p><?= e($order['city']) ?>, <?= e($order['postal_code']) ?></p>
                    <p><?= e($order['country']) ?></p>
                    <p>Phone: <?= e($order['phone']) ?></p>
                </div>
                <div>
                    <h4 style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">PAYMENT SUMMARY:</h4>
                    <p>Method: <?= strtoupper(str_replace('_', ' ', e($order['payment_method']))) ?></p>
                    <p>Status: <strong style="color: var(--success);"><?= strtoupper(e($order['payment_status'])) ?></strong></p>
                </div>
            </div>

            <!-- Items Table -->
            <table class="table-style" style="margin-bottom: 24px;">
                <thead>
                    <tr>
                        <th>ITEM</th>
                        <th>VARIANT</th>
                        <th>QTY</th>
                        <th>UNIT PRICE</th>
                        <th>SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?= getImageUrl($item['image_path'], $item['product_name']) ?>" alt="<?= e($item['product_name']) ?>" style="width: 40px; height: 50px; object-fit: cover;">
                                    <strong><?= e($item['product_name']) ?></strong>
                                </div>
                            </td>
                            <td><?= e($item['color']) ?> / <?= e($item['size']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= formatPrice($item['price']) ?></td>
                            <td><strong><?= formatPrice($item['subtotal']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Financial Totals -->
            <div style="max-width: 300px; margin-left: auto; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span>Subtotal:</span>
                    <span><?= formatPrice($order['subtotal']) ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--success);">
                        <span>Discount:</span>
                        <span>-<?= formatPrice($order['discount_amount']) ?></span>
                    </div>
                <?php endif; ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span>Shipping:</span>
                    <span><?= $order['shipping_fee'] > 0 ? formatPrice($order['shipping_fee']) : 'FREE' ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding-top: 12px; border-top: 1px solid var(--border-color); font-weight: 800; font-size: 1.1rem;">
                    <span>TOTAL PAID:</span>
                    <span><?= formatPrice($order['total_amount']) ?></span>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: center; gap: 16px; margin-top: 36px;">
            <button onclick="window.print();" class="btn btn-outline">PRINT RECEIPT</button>
            <a href="shop.php" class="btn btn-primary">CONTINUE SHOPPING</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
