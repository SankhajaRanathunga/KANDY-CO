<?php
/**
 * KANDY CO. - Checkout Page & Order Processor
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$cart = getSessionCart();
if (empty($cart)) {
    setFlash('warning', 'Your shopping cart is empty.');
    header('Location: shop.php');
    exit();
}

$cartItems = [];
$subtotal = 0.00;
foreach ($cart as $key => $item) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.price, p.sale_price,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image_path
        FROM products p WHERE p.id = ?
    ");
    $stmt->execute([$item['product_id']]);
    $prod = $stmt->fetch();

    if ($prod) {
        $unitPrice = ($prod['sale_price'] && $prod['sale_price'] < $prod['price']) ? $prod['sale_price'] : $prod['price'];
        $itemTotal = $unitPrice * $item['quantity'];
        $subtotal += $itemTotal;

        $cartItems[] = array_merge($item, [
            'name'       => $prod['title'],
            'unit_price' => $unitPrice,
            'item_total' => $itemTotal,
            'image_path' => $prod['image_path']
        ]);
    }
}

$discountAmount = 0.00;
if (isset($_SESSION['coupon'])) {
    $cp = $_SESSION['coupon'];
    if ($subtotal >= $cp['min_order_amount']) {
        $discountAmount = ($cp['discount_type'] === 'percentage') ? ($subtotal * $cp['discount_value']) / 100 : $cp['discount_value'];
    }
}
$shippingFee = ($subtotal >= 15000) ? 0.00 : 500.00;
$grandTotal = max(0, $subtotal - $discountAmount + $shippingFee);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'Sri Lanka');
    $paymentMethod = trim($_POST['payment_method'] ?? 'credit_card');

    if (!$fullName || !$email || !$phone || !$address || !$city || !$postalCode) {
        setFlash('danger', 'Please complete all required shipping fields.');
    } else {
        try {
            $pdo->beginTransaction();

            $orderNumber = 'KND-' . date('Ymd') . '-' . rand(1000, 9999);
            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;

            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, customer_name, email, phone, shipping_address, city, postal_code, country, subtotal, shipping_fee, discount_amount, total_amount, payment_method, payment_status, order_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'Confirmed')
            ");
            $stmt->execute([
                $orderNumber, $userId, $fullName, $email, $phone, $address, $city, $postalCode, $country,
                $subtotal, $shippingFee, $discountAmount, $grandTotal, $paymentMethod
            ]);
            $orderId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, variant_id, product_name, color, size, price, quantity, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stockStmt = $pdo->prepare("UPDATE product_variants SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");

            foreach ($cartItems as $ci) {
                $itemStmt->execute([
                    $orderId, $ci['product_id'], $ci['variant_id'], $ci['name'], $ci['color'], $ci['size'], $ci['unit_price'], $ci['quantity'], $ci['item_total']
                ]);
                if ($ci['variant_id'] > 0) {
                    $stockStmt->execute([$ci['quantity'], $ci['variant_id']]);
                }
            }

            $txnId = 'TXN-' . strtoupper(bin2hex(random_bytes(6)));
            $payStmt = $pdo->prepare("
                INSERT INTO payments (order_id, transaction_id, payment_method, amount, status)
                VALUES (?, ?, ?, ?, 'completed')
            ");
            $payStmt->execute([$orderId, $txnId, $paymentMethod, $grandTotal]);

            $pdo->commit();

            unset($_SESSION['cart']);
            unset($_SESSION['coupon']);

            header("Location: order-confirmation.php?order_number={$orderNumber}");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'An error occurred while processing your order: ' . e($e->getMessage()));
        }
    }
}

$user = isLoggedIn() ? getLoggedInUser($pdo) : null;

$pageTitle = "Checkout | KANDY CO.";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
    <div class="container">
        <h1 class="section-title" style="text-align: left; margin-bottom: 32px;">CHECKOUT</h1>

        <form action="checkout.php" method="POST">
            <div class="cart-grid">
                <div>
                    <h3 style="font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; letter-spacing: 0.1em; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">1. SHIPPING ADDRESS</h3>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">FULL NAME *</label>
                            <input type="text" name="full_name" class="form-input" required value="<?= e($_POST['full_name'] ?? ($user['full_name'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">EMAIL ADDRESS *</label>
                            <input type="email" name="email" class="form-input" required value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">PHONE NUMBER *</label>
                            <input type="tel" name="phone" class="form-input" required value="<?= e($_POST['phone'] ?? ($user['phone'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">COUNTRY *</label>
                            <select name="country" class="form-select" required>
                                <option value="Sri Lanka">Sri Lanka</option>
                                <option value="United States">United States</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Australia">Australia</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">STREET ADDRESS *</label>
                        <input type="text" name="address" class="form-input" placeholder="House number and street name" required value="<?= e($_POST['address'] ?? '') ?>">
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">CITY *</label>
                            <input type="text" name="city" class="form-input" required placeholder="Colombo / Kandy / Galle" value="<?= e($_POST['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">POSTAL CODE *</label>
                            <input type="text" name="postal_code" class="form-input" required value="<?= e($_POST['postal_code'] ?? '') ?>">
                        </div>
                    </div>

                    <h3 style="font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; letter-spacing: 0.1em; margin-top: 40px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">2. MOCK PAYMENT GATEWAY</h3>

                    <div style="margin-bottom: 20px; font-size: 0.85rem; background-color: var(--bg-alt); padding: 16px; border-left: 3px solid var(--text-primary);">
                        <em>This is a safe sandbox environment. Entering mock card details will instantly generate a verified order receipt.</em>
                    </div>

                    <div class="form-group">
                        <label class="form-label">PAYMENT METHOD</label>
                        <select name="payment_method" class="form-select">
                            <option value="credit_card">Credit / Debit Card (Visa, Mastercard)</option>
                            <option value="cash_on_delivery">Cash on Delivery (COD)</option>
                            <option value="koko_mintpay">Koko / Mintpay Pay-Later</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">CARDHOLDER NAME</label>
                        <input type="text" class="form-input" placeholder="John Doe" value="John Doe">
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">CARD NUMBER</label>
                            <input type="text" class="form-input" placeholder="4000 1234 5678 9010" value="4000 1234 5678 9010">
                        </div>
                        <div class="grid grid-2" style="gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">EXPIRY</label>
                                <input type="text" class="form-input" placeholder="12/28" value="12/28">
                            </div>
                            <div class="form-group">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-input" placeholder="123" value="123">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="cart-summary-card">
                        <h3 class="summary-title">ORDER ITEMS</h3>

                        <div style="max-height: 300px; overflow-y: auto; margin-bottom: 24px; display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($cartItems as $item): ?>
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <img src="<?= getImageUrl($item['image_path'], $item['name']) ?>" alt="<?= e($item['name']) ?>" style="width: 50px; height: 60px; object-fit: cover;">
                                    <div style="flex: 1; font-size: 0.85rem;">
                                        <div style="font-weight: 700;"><?= e($item['name']) ?></div>
                                        <div style="color: var(--text-muted); font-size: 0.75rem;">Qty: <?= $item['quantity'] ?> | <?= e($item['color']) ?>, <?= e($item['size']) ?></div>
                                    </div>
                                    <div style="font-weight: 700; font-size: 0.9rem;"><?= formatPrice($item['item_total']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span style="font-weight: 700;"><?= formatPrice($subtotal) ?></span>
                        </div>

                        <?php if ($discountAmount > 0): ?>
                            <div class="summary-row" style="color: var(--success);">
                                <span>Discount</span>
                                <span>-<?= formatPrice($discountAmount) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-row">
                            <span>Shipping Fee</span>
                            <span><?= $shippingFee > 0 ? formatPrice($shippingFee) : 'FREE' ?></span>
                        </div>

                        <div class="summary-row total">
                            <span>PAYMENT TOTAL</span>
                            <span><?= formatPrice($grandTotal) ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary full-width" style="margin-top: 24px; height: 56px; font-size: 0.9rem;">PLACE ORDER NOW</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
