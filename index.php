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

<!-- Discover Collection Hero & Catalog Section (Clean Minimal Luxury Light Aesthetic) -->
<style>
.discover-hero-section {
    background-color: var(--bg-main);
    color: var(--text-primary);
    padding: 88px 0 80px;
    border-bottom: 1px solid var(--border-color);
}

.discover-header {
    text-align: center;
    max-width: 860px;
    margin: 0 auto 48px;
}

.discover-eyebrow {
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 14px;
}

.discover-main-title {
    font-family: var(--font-heading);
    font-size: clamp(2.4rem, 5.5vw, 4.2rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.03em;
    text-transform: uppercase;
    color: var(--text-primary);
    margin-bottom: 18px;
}

.discover-statement {
    font-size: 1.05rem;
    line-height: 1.8;
    color: var(--text-muted);
    max-width: 680px;
    margin: 0 auto 32px;
    font-weight: 400;
}

.discover-quick-actions {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 52px;
}

.discover-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 13px 26px;
    font-family: var(--font-heading);
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    border-radius: var(--radius-sm);
    transition: var(--transition);
    text-align: center;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    background-color: var(--bg-main);
}

.discover-action-btn:hover {
    background-color: var(--text-primary);
    color: var(--text-inverse);
    border-color: var(--text-primary);
    transform: translateY(-2px);
}

.discover-action-btn.active-pill {
    background-color: var(--text-primary);
    color: var(--text-inverse);
    border-color: var(--text-primary);
}

.discover-action-btn.active-pill:hover {
    background-color: var(--accent-hover);
    border-color: var(--accent-hover);
}

.discover-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.collection-card-item {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background-color: var(--bg-card);
    border: 1px solid var(--border-color);
    padding: 34px 28px 30px;
    border-radius: var(--radius-sm);
    transition: var(--transition);
    color: var(--text-primary);
    text-decoration: none;
    min-height: 220px;
}

.collection-card-item:hover {
    background-color: var(--bg-main);
    border-color: var(--text-primary);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.06);
    transform: translateY(-4px);
}

.collection-card-top {
    margin-bottom: 24px;
}

.collection-card-heading {
    font-family: var(--font-heading);
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.01em;
    text-transform: uppercase;
    color: var(--text-primary);
    margin-bottom: 12px;
    line-height: 1.2;
}

.collection-card-desc {
    font-size: 0.88rem;
    line-height: 1.65;
    color: var(--text-muted);
}

.collection-card-bottom {
    margin-top: auto;
    padding-top: 18px;
    border-top: 1px solid var(--border-color);
}

.collection-card-link-text {
    font-family: var(--font-heading);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--text-primary);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
}

.collection-card-item:hover .collection-card-link-text {
    color: var(--text-primary);
    transform: translateX(4px);
}

@media (max-width: 1100px) {
    .discover-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
}

@media (max-width: 640px) {
    .discover-hero-section {
        padding: 56px 0 48px;
    }
    .discover-header {
        margin-bottom: 32px;
    }
    .discover-statement {
        font-size: 0.95rem;
        margin-bottom: 24px;
    }
    .discover-quick-actions {
        margin-bottom: 32px;
        gap: 8px;
    }
    .discover-action-btn {
        padding: 11px 18px;
        font-size: 0.72rem;
        flex: 1 1 calc(50% - 8px);
    }
    .discover-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .collection-card-item {
        min-height: auto;
        padding: 26px 20px;
    }
}
</style>

<section class="discover-hero-section">
    <div class="container">
        <!-- Minimal Luxury Header -->
        <div class="discover-header">
            <span class="discover-eyebrow">CORE ARCHIVE &bull; PERMANENT COLLECTION</span>
            <h1 class="discover-main-title">DISCOVER COLLECTION</h1>
            <p class="discover-statement">
                Refined simplicity, structural silhouettes, and uncompromising heavyweight construction.
                Explore our curated range of minimalist apparel crafted for everyday elevation.
            </p>
            <!-- Clean Text-Based Action Buttons -->
            <div class="discover-quick-actions">
                <a href="shop.php?gender=Men" class="discover-action-btn active-pill">SHOP MEN</a>
                <a href="shop.php?gender=Women" class="discover-action-btn">SHOP WOMEN</a>
                <a href="shop.php?new=1" class="discover-action-btn">NEW ARRIVALS</a>
                <a href="shop.php?sale=1" class="discover-action-btn" style="border-color: var(--sale-color); color: var(--sale-color);">SALE ARCHIVE</a>
                <a href="shop.php" class="discover-action-btn">ALL PRODUCTS</a>
            </div>
        </div>

        <!-- Clean Text-Based Collection Cards Grid -->
        <div class="discover-grid">
            <a href="shop.php?gender=Men" class="collection-card-item">
                <div class="collection-card-top">
                    <h2 class="collection-card-heading">MEN</h2>
                    <p class="collection-card-desc">Architectural dropped shoulders, heavyweight boxy tees, relaxed tailoring, and structured street essentials.</p>
                </div>
                <div class="collection-card-bottom">
                    <span class="collection-card-link-text">EXPLORE MEN &rarr;</span>
                </div>
            </a>

            <a href="shop.php?gender=Women" class="collection-card-item">
                <div class="collection-card-top">
                    <h2 class="collection-card-heading">WOMEN</h2>
                    <p class="collection-card-desc">Contemporary minimalist silhouettes, relaxed proportions, heavyweight layers, and elevated daily staples.</p>
                </div>
                <div class="collection-card-bottom">
                    <span class="collection-card-link-text">EXPLORE WOMEN &rarr;</span>
                </div>
            </a>

            <a href="shop.php?cat=tshirts" class="collection-card-item">
                <div class="collection-card-top">
                    <h2 class="collection-card-heading">T-SHIRTS</h2>
                    <p class="collection-card-desc">280GSM organic high-density cotton tees with reinforced ribbed collars engineered to maintain structure.</p>
                </div>
                <div class="collection-card-bottom">
                    <span class="collection-card-link-text">EXPLORE T-SHIRTS &rarr;</span>
                </div>
            </a>

            <a href="shop.php?cat=hoodies" class="collection-card-item">
                <div class="collection-card-top">
                    <h2 class="collection-card-heading">HOODIES &amp; SWEATS</h2>
                    <p class="collection-card-desc">450GSM plush heavyweight fleece, double-lined seamless hoods, and relaxed dropped-shoulder drape.</p>
                </div>
                <div class="collection-card-bottom">
                    <span class="collection-card-link-text">EXPLORE HOODIES &rarr;</span>
                </div>
            </a>

            <a href="shop.php?cat=pants" class="collection-card-item">
                <div class="collection-card-top">
                    <h2 class="collection-card-heading">PANTS &amp; CARGOS</h2>
                    <p class="collection-card-desc">Tailored utility cargo pants, wide-leg pleated trousers, and heavyweight French terry sweatpants.</p>
                </div>
                <div class="collection-card-bottom">
                    <span class="collection-card-link-text">EXPLORE PANTS &rarr;</span>
                </div>
            </a>

            <a href="shop.php?cat=jackets" class="collection-card-item">
                <div class="collection-card-top">
                    <h2 class="collection-card-heading">JACKETS &amp; OUTERWEAR</h2>
                    <p class="collection-card-desc">Structural outerwear, minimal bomber jackets, oversized puffers, and architectural transitional coats.</p>
                </div>
                <div class="collection-card-bottom">
                    <span class="collection-card-link-text">EXPLORE JACKETS &rarr;</span>
                </div>
            </a>

            <a href="shop.php?cat=shirts" class="collection-card-item">
                <div class="collection-card-top">
                    <h2 class="collection-card-heading">SHIRTS</h2>
                    <p class="collection-card-desc">Clean tailored button-down shirts and textured linen overshirts built with modern relaxed fits.</p>
                </div>
                <div class="collection-card-bottom">
                    <span class="collection-card-link-text">EXPLORE SHIRTS &rarr;</span>
                </div>
            </a>

            <a href="shop.php?cat=accessories" class="collection-card-item">
                <div class="collection-card-top">
                    <h2 class="collection-card-heading">ACCESSORIES</h2>
                    <p class="collection-card-desc">Signature heavyweight caps, structured canvas totes, socks, and minimalist everyday essentials.</p>
                </div>
                <div class="collection-card-bottom">
                    <span class="collection-card-link-text">EXPLORE ACCESSORIES &rarr;</span>
                </div>
            </a>
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
