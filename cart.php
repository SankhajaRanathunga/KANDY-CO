<?php
/**
 * KANDY CO. - Shopping Cart Controller & Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$cart = getSessionCart();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $variantId = (int)($_POST['variant_id'] ?? 0);
    $color = trim($_POST['color'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if ($productId > 0) {
        $cartKey = $productId . '-' . md5($color . '-' . $size);
        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'color'      => $color,
                'size'       => $size,
                'quantity'   => $quantity
            ];
        }
        setFlash('success', 'Item successfully added to your shopping cart.');
    }
    header('Location: cart.php');
    exit();
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $key => $qty) {
            $qty = (int)$qty;
            if (isset($_SESSION['cart'][$key])) {
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$key]);
                } else {
                    $_SESSION['cart'][$key]['quantity'] = $qty;
                }
            }
        }
        setFlash('success', 'Cart updated successfully.');
    }
    header('Location: cart.php');
    exit();
}

if ($action === 'remove' && isset($_GET['key'])) {
    $key = $_GET['key'];
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        setFlash('success', 'Item removed from your cart.');
    }
    header('Location: cart.php');
    exit();
}

if ($action === 'apply_coupon' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
    if ($couponCode) {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active'");
        $stmt->execute([$couponCode]);
        $cp = $stmt->fetch();
        if ($cp) {
            $_SESSION['coupon'] = $cp;
            setFlash('success', 'Coupon code "' . e($couponCode) . '" applied!');
        } else {
            setFlash('danger', 'Invalid or expired coupon code.');
        }
    }
    header('Location: cart.php');
    exit();
}

$cartItems = [];
$subtotal = 0.00;
foreach (getSessionCart() as $key => $item) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, p.price, p.sale_price,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image_path
        FROM products p
        WHERE p.id = ?
    ");
    $stmt->execute([$item['product_id']]);
    $prod = $stmt->fetch();

    if ($prod) {
        $unitPrice = ($prod['sale_price'] && $prod['sale_price'] < $prod['price']) ? $prod['sale_price'] : $prod['price'];
        $itemTotal = $unitPrice * $item['quantity'];
        $subtotal += $itemTotal;

        $cartItems[] = [
            'key'        => $key,
            'product_id' => $prod['id'],
            'name'       => $prod['title'],
            'slug'       => $prod['slug'],
            'color'      => $item['color'],
            'size'       => $item['size'],
            'quantity'   => $item['quantity'],
            'unit_price' => $unitPrice,
            'item_total' => $itemTotal,
            'image_path' => $prod['image_path']
        ];
    }
}

$discountAmount = 0.00;
if (isset($_SESSION['coupon'])) {
    $cp = $_SESSION['coupon'];
    if ($subtotal >= $cp['min_order_amount']) {
        if ($cp['discount_type'] === 'percentage') {
            $discountAmount = ($subtotal * $cp['discount_value']) / 100;
        } else {
            $discountAmount = $cp['discount_value'];
        }
    }
}
$shippingFee = ($subtotal >= 15000 || $subtotal == 0) ? 0.00 : 500.00;
$grandTotal = max(0, $subtotal - $discountAmount + $shippingFee);

$pageTitle = "Shopping Cart | KANDY CO.";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
    <div class="container">
        <h1 class="section-title" style="text-align: left; margin-bottom: 32px;">YOUR SHOPPING CART</h1>

        <?php if (!empty($cartItems)): ?>
            <form action="cart.php?action=update" method="POST">
                <div class="cart-grid">
                    <div>
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>PRODUCT</th>
                                    <th>QUANTITY</th>
                                    <th>SUBTOTAL</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $ci): ?>
                                    <tr class="cart-item-row">
                                        <td>
                                            <div class="cart-item-info">
                                                <img src="<?= getImageUrl($ci['image_path'], $ci['name']) ?>" alt="<?= e($ci['name']) ?>" class="cart-item-img">
                                                <div class="cart-item-details">
                                                    <a href="product.php?slug=<?= e($ci['slug']) ?>" style="font-family: var(--font-heading); font-weight: 700; font-size: 0.95rem;"><?= e($ci['name']) ?></a>
                                                    <span style="font-size: 0.8rem; color: var(--text-muted);">COLOR: <?= e($ci['color']) ?> | SIZE: <?= e($ci['size']) ?></span>
                                                    <span style="font-size: 0.85rem; font-weight: 600; margin-top: 4px;"><?= formatPrice($ci['unit_price']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="quantities[<?= $ci['key'] ?>]" value="<?= $ci['quantity'] ?>" min="1" max="99" class="form-input" style="width: 70px; text-align: center;">
                                        </td>
                                        <td style="font-weight: 700; font-size: 1rem;">
                                            <?= formatPrice($ci['item_total']) ?>
                                        </td>
                                        <td>
                                            <a href="cart.php?action=remove&key=<?= urlencode($ci['key']) ?>" style="color: var(--danger); font-size: 1.2rem; font-weight: 700;" title="Remove Item">&times;</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="display: flex; justify-content: space-between; margin-top: 24px;">
                            <a href="shop.php" class="btn btn-outline">&larr; CONTINUE SHOPPING</a>
                            <button type="submit" class="btn btn-outline">UPDATE CART</button>
                        </div>
                    </div>

                    <div>
                        <div class="cart-summary-card">
                            <h3 class="summary-title">ORDER SUMMARY</h3>

                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span style="font-weight: 700;"><?= formatPrice($subtotal) ?></span>
                            </div>

                            <?php if ($discountAmount > 0): ?>
                                <div class="summary-row" style="color: var(--success);">
                                    <span>Discount (<?= e($_SESSION['coupon']['code']) ?>)</span>
                                    <span>-<?= formatPrice($discountAmount) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="summary-row">
                                <span>Shipping</span>
                                <span><?= $shippingFee > 0 ? formatPrice($shippingFee) : 'FREE' ?></span>
                            </div>

                            <div class="summary-row total">
                                <span>TOTAL</span>
                                <span><?= formatPrice($grandTotal) ?></span>
                            </div>

                            <a href="checkout.php" class="btn btn-primary full-width" style="margin-top: 24px; height: 52px;">PROCEED TO CHECKOUT</a>

                            <form action="cart.php?action=apply_coupon" method="POST" style="margin-top: 32px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                                <label style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">PROMO / GIFT CODE</label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="text" name="coupon_code" placeholder="Try KANDY10 or WELCOME20" class="form-input" required style="text-transform: uppercase;">
                                    <button type="submit" class="btn btn-outline">APPLY</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; background-color: var(--bg-alt); border: 1px solid var(--border-color);">
                <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 12px;">YOUR CART IS EMPTY</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px;">Explore our minimalist collection and add items to your cart.</p>
                <a href="shop.php" class="btn btn-primary">EXPLORE COLLECTION</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
