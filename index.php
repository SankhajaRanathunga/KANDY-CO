<?php
/**
 * KANDY CO. - Homepage
 */
$pageTitle = "KANDY CO. | Modern Luxury & Minimal Streetwear Apparel";
$pageDescription = "Define your everyday with KANDY CO. Discover premium heavyweight oversized tees, essential hoodies, tailored shirts, and architectural streetwear.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch Featured / New Arrivals Active Products from DB
$featuredProducts = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, 
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary ASC, sort_order ASC LIMIT 1 OFFSET 1) as secondary_image
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'Active' AND (p.is_new_arrival = 1 OR p.is_featured = 1)
        ORDER BY p.id DESC
        LIMIT 8
    ");
    $stmt->execute();
    $featuredProducts = $stmt->fetchAll();
} catch (Exception $ex) {}
?>

<!-- Hero Banner Section -->
<section class="hero-section">
    <img src="<?= getImageUrl('assets/images/hero/kandy-hero-main.jpg', 'HERO MAIN') ?>" alt="KANDY CO. Modern Fashion" class="hero-bg-img">
    <div class="hero-content">
        <span class="hero-subtitle">NEW SEASON ARRIVALS</span>
        <h1 class="hero-title">KANDY CO.</h1>
        <p class="hero-tagline">DEFINE YOUR EVERYDAY.</p>
        <div class="hero-actions">
            <a href="shop.php?gender=Men" class="btn btn-primary">SHOP MEN</a>
            <a href="shop.php?gender=Women" class="btn btn-outline" style="border-color: #FFF; color: #FFF;">SHOP WOMEN</a>
        </div>
    </div>
</section>

<!-- Brand Philosophy Statement -->
<section class="section-padding" style="background-color: var(--bg-alt); text-align: center;">
    <div class="container-narrow">
        <span class="section-subtitle">OUR PHILOSOPHY</span>
        <h2 class="section-title" style="margin-top: 12px; font-size: 1.8rem; font-weight: 700;">ARCHITECTURAL SILHOUETTES & UNCOMPROMISING QUALITY</h2>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.8; margin-top: 16px;">
            KANDY CO. was created for those who value refined simplicity and heavyweight construction. 
            Every garment is custom-milled from organic high-density cotton, built with structural dropped shoulders, 
            and tailored to elevate your daily wardrobe seamlessly.
        </p>
    </div>
</section>

<!-- Category Cards Showcase Grid -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">DISCOVER</span>
            <h2 class="section-title">THE COLLECTIONS</h2>
        </div>
        <div class="grid grid-4">
            <div class="category-card">
                <img src="<?= getImageUrl('assets/images/categories/men/men-collection.jpg', 'MEN') ?>" alt="Men Collection" class="category-card-img">
                <div class="category-card-content">
                    <h3 class="category-card-title">MEN</h3>
                    <a href="shop.php?gender=Men" class="category-card-link">EXPLORE COLLECTION &rarr;</a>
                </div>
            </div>
            <div class="category-card">
                <img src="<?= getImageUrl('assets/images/categories/women/women-collection.jpg', 'WOMEN') ?>" alt="Women Collection" class="category-card-img">
                <div class="category-card-content">
                    <h3 class="category-card-title">WOMEN</h3>
                    <a href="shop.php?gender=Women" class="category-card-link">EXPLORE COLLECTION &rarr;</a>
                </div>
            </div>
            <div class="category-card">
                <img src="<?= getImageUrl('assets/images/categories/new-arrivals/new-arrivals-collection.jpg', 'NEW ARRIVALS') ?>" alt="New Arrivals" class="category-card-img">
                <div class="category-card-content">
                    <h3 class="category-card-title">NEW ARRIVALS</h3>
                    <a href="shop.php?new=1" class="category-card-link">DISCOVER DROPS &rarr;</a>
                </div>
            </div>
            <div class="category-card">
                <img src="<?= getImageUrl('assets/images/categories/sale/sale-collection.jpg', 'SALE') ?>" alt="Sale Items" class="category-card-img">
                <div class="category-card-content">
                    <h3 class="category-card-title" style="color: var(--sale-color);">SEASON SALE</h3>
                    <a href="shop.php?sale=1" class="category-card-link">SHOP UP TO 40% OFF &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Dynamic New Arrivals / Featured Products Grid -->
<section class="section-padding" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">CURATED SELECTION</span>
            <h2 class="section-title">NEW ARRIVALS</h2>
        </div>

        <div class="grid grid-4">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $prod): ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <a href="product.php?slug=<?= e($prod['slug']) ?>">
                                <img src="<?= getImageUrl($prod['primary_image'], $prod['title']) ?>" alt="<?= e($prod['title']) ?>" class="product-img">
                                <?php if (!empty($prod['secondary_image'])): ?>
                                    <img src="<?= getImageUrl($prod['secondary_image'], $prod['title']) ?>" alt="<?= e($prod['title']) ?>" class="product-img-secondary">
                                <?php endif; ?>
                            </a>

                            <div class="product-badges">
                                <?php if (($prod['is_on_sale'] || $prod['sale_price']) && $prod['sale_price'] < $prod['price']): ?>
                                    <span class="badge-tag badge-sale">SALE</span>
                                <?php endif; ?>
                                <?php if ($prod['is_new_arrival']): ?>
                                    <span class="badge-tag">NEW</span>
                                <?php endif; ?>
                            </div>

                            <a href="wishlist.php?action=add&id=<?= $prod['id'] ?>" class="wishlist-btn-overlay" title="Add to Wishlist" aria-label="Add to Wishlist">
                                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.72-8.72 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </a>
                        </div>

                        <div class="product-info">
                            <span class="product-brand"><?= e($prod['brand']) ?> &bull; <?= e($prod['category_name']) ?></span>
                            <h3 class="product-name">
                                <a href="product.php?slug=<?= e($prod['slug']) ?>"><?= e($prod['title']) ?></a>
                            </h3>
                            <div class="product-price-row">
                                <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['price']): ?>
                                    <span class="price-original"><?= formatPrice($prod['price']) ?></span>
                                    <span class="price-sale"><?= formatPrice($prod['sale_price']) ?></span>
                                <?php else: ?>
                                    <span><?= formatPrice($prod['price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; grid-column: 1 / -1; color: var(--text-muted);">No products found in the catalog.</p>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 48px;">
            <a href="shop.php" class="btn btn-outline">VIEW ALL PRODUCTS</a>
        </div>
    </div>
</section>

<!-- Brand Values Banner -->
<section class="section-padding">
    <div class="container">
        <div class="grid grid-4" style="text-align: center;">
            <div style="padding: 24px; border: 1px solid var(--border-color);">
                <h4 style="font-family: var(--font-heading); font-weight: 800; font-size: 0.9rem; letter-spacing: 0.1em; margin-bottom: 8px;">280GSM ORGANIC COTTON</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Ultra-dense, preshrunk organic cotton fabrics engineered to hold structure after every wash.</p>
            </div>
            <div style="padding: 24px; border: 1px solid var(--border-color);">
                <h4 style="font-family: var(--font-heading); font-weight: 800; font-size: 0.9rem; letter-spacing: 0.1em; margin-bottom: 8px;">COMPLIMENTARY SHIPPING</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Worldwide express tracked shipping on all orders exceeding LKR 15,000.</p>
            </div>
            <div style="padding: 24px; border: 1px solid var(--border-color);">
                <h4 style="font-family: var(--font-heading); font-weight: 800; font-size: 0.9rem; letter-spacing: 0.1em; margin-bottom: 8px;">30-DAY EASY RETURNS</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Hassle-free global exchange policy and instant prepaid return labels.</p>
            </div>
            <div style="padding: 24px; border: 1px solid var(--border-color);">
                <h4 style="font-family: var(--font-heading); font-weight: 800; font-size: 0.9rem; letter-spacing: 0.1em; margin-bottom: 8px;">SUSTAINABLE PRODUCTION</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Small-batch limited releases designed to minimize textile waste and environmental footprint.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
