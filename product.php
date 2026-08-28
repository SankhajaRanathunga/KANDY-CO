<?php
/**
 * KANDY CO. - Product Detail Page
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$slug = trim($_GET['slug'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$slug && !$id) {
    header('Location: shop.php');
    exit();
}

$product = null;
try {
    if ($slug) {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.status = 'Active'");
        $stmt->execute([$slug]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 'Active'");
        $stmt->execute([$id]);
    }
    $product = $stmt->fetch();
} catch (Exception $e) {}

if (!$product) {
    echo "<div class='container section-padding' style='text-align:center;'><h2>PRODUCT NOT AVAILABLE</h2><p><a href='shop.php' class='btn btn-primary' style='margin-top:20px;'>RETURN TO SHOP</a></p></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$productId = $product['id'];

// Fetch Images ordered by sort_order
$images = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
    $stmt->execute([$productId]);
    $images = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch Variants
$variants = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY size ASC, color ASC");
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll();
} catch (Exception $e) {}

$colors = [];
$sizes = [];
foreach ($variants as $v) {
    if (!in_array($v['color'], $colors)) $colors[] = $v['color'];
    if (!in_array($v['size'], $sizes)) $sizes[] = $v['size'];
}
$defaultColor = $colors[0] ?? 'Black';
$defaultSize = $sizes[0] ?? 'M';

$defaultVariant = null;
foreach ($variants as $v) {
    if (strtolower($v['color']) === strtolower($defaultColor) && strtolower($v['size']) === strtolower($defaultSize)) {
        $defaultVariant = $v;
        break;
    }
}
if (!$defaultVariant && !empty($variants)) {
    $defaultVariant = $variants[0];
}

// Reviews
$reviews = [];
try {
    $stmt = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC");
    $stmt->execute([$productId]);
    $reviews = $stmt->fetchAll();
} catch (Exception $e) {}

$variantsJson = json_encode($variants);
?>

<script>
    window.productVariants = <?= $variantsJson ?>;
</script>

<div class="section-padding">
    <div class="container">
        <!-- Breadcrumbs -->
        <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 32px;">
            <a href="index.php">HOME</a> &nbsp;/&nbsp; <a href="shop.php">SHOP</a> &nbsp;/&nbsp; <a href="shop.php?cat=<?= e($product['category_name']) ?>"><?= e($product['category_name']) ?></a> &nbsp;/&nbsp; <span><?= e($product['title']) ?></span>
        </div>

        <div class="product-detail-grid">
            <!-- Left: Gallery -->
            <div class="gallery-container">
                <div class="gallery-thumbnails">
                    <?php if (!empty($images)): ?>
                        <?php foreach ($images as $idx => $img): ?>
                            <div class="thumb-item <?= $idx === 0 ? 'active' : '' ?>" data-img="<?= getImageUrl($img['image_path'], $product['title']) ?>">
                                <img src="<?= getImageUrl($img['image_path'], $product['title']) ?>" alt="<?= e($product['title']) ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="thumb-item active" data-img="<?= getImageUrl('', $product['title']) ?>">
                            <img src="<?= getImageUrl('', $product['title']) ?>" alt="Placeholder">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="gallery-main">
                    <?php $primaryPath = !empty($images) ? $images[0]['image_path'] : ''; ?>
                    <img src="<?= getImageUrl($primaryPath, $product['title']) ?>" alt="<?= e($product['title']) ?>" id="mainGalleryImg">
                </div>
            </div>

            <!-- Right: Info -->
            <div class="product-summary">
                <span class="product-brand"><?= e($product['brand']) ?> &bull; <?= e($product['category_name']) ?></span>
                <h1 class="product-title-lg"><?= e($product['title']) ?></h1>

                <?php if ($product['short_description']): ?>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px;"><?= e($product['short_description']) ?></p>
                <?php endif; ?>

                <div class="product-price-lg">
                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <span class="price-original" style="font-size: 1.25rem;"><?= formatPrice($product['price']) ?></span>
                        <span class="price-sale" style="font-size: 1.75rem;"><?= formatPrice($product['sale_price']) ?></span>
                        <span class="badge-tag badge-sale">SAVE <?= formatPrice($product['price'] - $product['sale_price']) ?></span>
                    <?php else: ?>
                        <span><?= formatPrice($product['price']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Stock Indicator -->
                <div class="stock-status-badge" id="stockStatusElem">
                    <?php if ($defaultVariant && $defaultVariant['stock_quantity'] > 0): ?>
                        <span class="stock-indicator"></span> IN STOCK (<?= $defaultVariant['stock_quantity'] ?> AVAILABLE)
                    <?php else: ?>
                        <span class="stock-indicator low"></span> OUT OF STOCK
                    <?php endif; ?>
                </div>

                <form action="cart.php?action=add" method="POST">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    <input type="hidden" name="color" id="selectedColor" value="<?= e($defaultColor) ?>">
                    <input type="hidden" name="size" id="selectedSize" value="<?= e($defaultSize) ?>">
                    <input type="hidden" name="variant_id" id="selectedVariantId" value="<?= $defaultVariant ? $defaultVariant['id'] : 0 ?>">

                    <?php if (!empty($colors)): ?>
                        <div class="variant-option-group">
                            <div class="variant-header">
                                <span class="form-label">COLOR:</span>
                            </div>
                            <div class="color-options">
                                <?php foreach ($colors as $c): ?>
                                    <button type="button" class="color-btn <?= strtolower($c) === strtolower($defaultColor) ? 'active' : '' ?>" data-color="<?= e($c) ?>">
                                        <?= e($c) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="variant-option-group">
                        <div class="variant-header">
                            <span class="form-label">SELECT SIZE:</span>
                            <span class="size-guide-link" id="sizeGuideTrigger">SIZE GUIDE</span>
                        </div>
                        <div class="size-options">
                            <?php 
                            $allSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                            foreach ($allSizes as $s): 
                                $isAvailable = in_array($s, $sizes);
                            ?>
                                <button type="button" class="size-btn <?= $s === $defaultSize ? 'active' : '' ?> <?= !$isAvailable ? 'disabled' : '' ?>" data-size="<?= $s ?>">
                                    <?= $s ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="quantity-add-group">
                        <div class="quantity-stepper">
                            <button type="button" class="qty-btn" id="qtyMinus">-</button>
                            <input type="text" name="quantity" id="qtyInput" class="qty-input" value="1" readonly>
                            <button type="button" class="qty-btn" id="qtyPlus">+</button>
                        </div>
                        <button type="submit" class="btn btn-primary full-width" style="height: 52px;">ADD TO CART</button>
                    </div>

                    <a href="wishlist.php?action=add&id=<?= $productId ?>" class="btn btn-outline full-width" style="margin-bottom: 24px;">SAVE TO WISHLIST</a>
                </form>

                <div class="product-accordion">
                    <div class="accordion-item active">
                        <button class="accordion-title">PRODUCT DETAILS & DESCRIPTION <span>+</span></button>
                        <div class="accordion-content">
                            <p><?= nl2br(e($product['description'])) ?></p>
                            <ul style="margin-top: 12px; list-style: disc; padding-left: 20px;">
                                <li>280GSM Premium Heavyweight Organic Cotton</li>
                                <li>Pre-shrunk architectural dropped shoulder cut</li>
                                <li>Custom embroidered minimal KANDY CO. insignia</li>
                                <li>Imported luxury finish</li>
                            </ul>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-title">SHIPPING & FREE RETURNS <span>+</span></button>
                        <div class="accordion-content">
                            <p>Complimentary tracked express shipping on orders over LKR 15,000. Orders placed before 2 PM local time ship same day. 30-day effortless prepaid returns and exchanges.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Reviews -->
        <div style="margin-top: 80px; padding-top: 48px; border-top: 1px solid var(--border-color);">
            <h3 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; margin-bottom: 24px;">CUSTOMER REVIEWS</h3>

            <?php if (!empty($reviews)): ?>
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <?php foreach ($reviews as $rev): ?>
                        <div style="background-color: var(--bg-alt); padding: 24px; border-radius: var(--radius-sm);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <strong style="font-size: 0.9rem;"><?= e($rev['full_name']) ?></strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div style="color: var(--sale-color); margin-bottom: 8px;">
                                <?= str_repeat('&#9733;', $rev['rating']) ?><?= str_repeat('&#9734;', 5 - $rev['rating']) ?>
                            </div>
                            <p style="font-size: 0.9rem; color: var(--text-primary);"><?= e($rev['review_text']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">No reviews yet for this product.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Size Guide Modal -->
<div class="modal-overlay" id="sizeGuideModal">
    <div class="modal-box">
        <button class="modal-close" id="sizeGuideClose">&times;</button>
        <h3 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; text-transform: uppercase;">SIZE GUIDE</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">All measurements are listed in inches (IN). Oversized boxy fit.</p>

        <table class="table-style">
            <thead>
                <tr><th>SIZE</th><th>CHEST (IN)</th><th>LENGTH (IN)</th><th>SHOULDER (IN)</th></tr>
            </thead>
            <tbody>
                <tr><td>XS</td><td>40 - 42</td><td>27.5</td><td>20.0</td></tr>
                <tr><td>S</td><td>42 - 44</td><td>28.5</td><td>21.0</td></tr>
                <tr><td>M</td><td>44 - 46</td><td>29.5</td><td>22.0</td></tr>
                <tr><td>L</td><td>46 - 48</td><td>30.5</td><td>23.0</td></tr>
                <tr><td>XL</td><td>48 - 50</td><td>31.5</td><td>24.0</td></tr>
                <tr><td>XXL</td><td>50 - 52</td><td>32.5</td><td>25.0</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
