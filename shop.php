<?php
/**
 * KANDY CO. - Shop Catalog Page
 */
$pageTitle = "Shop Catalog | KANDY CO. Minimalist Apparel";
$pageDescription = "Browse the full collection of KANDY CO. clothing. Filter by category, size, color, and price.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Get Filter Query Parameters
$search = trim($_GET['q'] ?? '');
$catSlug = trim($_GET['cat'] ?? '');
$gender = trim($_GET['gender'] ?? '');
$isSale = isset($_GET['sale']) && $_GET['sale'] == '1';
$isNew = isset($_GET['new']) && $_GET['new'] == '1';
$size = trim($_GET['size'] ?? '');
$color = trim($_GET['color'] ?? '');
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$sort = trim($_GET['sort'] ?? 'newest');

// Fetch Categories for Filter Sidebar
$categories = [];
try {
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}

// Build Dynamic SQL Query
$sql = "
    SELECT DISTINCT p.*, c.name as category_name, c.slug as category_slug,
           (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image,
           (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary ASC, sort_order ASC LIMIT 1 OFFSET 1) as secondary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    WHERE p.status = 'Active'
";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.brand LIKE ? OR c.name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($catSlug !== '') {
    $sql .= " AND c.slug = ?";
    $params[] = $catSlug;
}

if ($gender !== '') {
    $sql .= " AND (p.gender = ? OR p.gender = 'Unisex')";
    $params[] = $gender;
}

if ($isSale) {
    $sql .= " AND (p.is_on_sale = 1 OR p.sale_price IS NOT NULL)";
}

if ($isNew) {
    $sql .= " AND p.is_new_arrival = 1";
}

if ($size !== '') {
    $sql .= " AND pv.size = ?";
    $params[] = $size;
}

if ($color !== '') {
    $sql .= " AND pv.color = ?";
    $params[] = $color;
}

if ($minPrice > 0 || $maxPrice < 1000) {
    $sql .= " AND (COALESCE(p.sale_price, p.price) BETWEEN ? AND ?)";
    $params[] = $minPrice;
    $params[] = $maxPrice;
}

switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY COALESCE(p.sale_price, p.price) ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY COALESCE(p.sale_price, p.price) DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.title ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}

$products = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Exception $e) {}

$catalogTitle = "ALL PRODUCTS";
if ($catSlug) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $catSlug) {
            $catalogTitle = strtoupper($cat['name']);
            break;
        }
    }
} elseif (strtolower($gender) === 'men') {
    $catalogTitle = "MEN'S COLLECTION";
} elseif (strtolower($gender) === 'women') {
    $catalogTitle = "WOMEN'S COLLECTION";
} elseif ($isSale) {
    $catalogTitle = "SEASON SALE";
} elseif ($isNew) {
    $catalogTitle = "NEW ARRIVALS";
} elseif ($search !== '') {
    $catalogTitle = "SEARCH: \"" . strtoupper(e($search)) . "\"";
}
?>

<div class="section-padding" style="background-color: var(--bg-alt); padding: 40px 0; border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <h1 class="section-title" style="margin-bottom: 4px;"><?= $catalogTitle ?></h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em;">
            SHOWING <?= count($products) ?> ITEMS
        </p>
    </div>
</div>

<div class="section-padding">
    <div class="container" style="display: grid; grid-template-columns: 260px 1fr; gap: 48px;">
        
        <!-- Filter Sidebar -->
        <aside class="shop-sidebar">
            <form action="shop.php" method="GET" id="filterForm">
                <?php if ($search): ?>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-family: var(--font-heading); font-size: 0.9rem; font-weight: 800; letter-spacing: 0.1em;">FILTERS</h3>
                    <a href="shop.php" style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-decoration: underline;">CLEAR ALL</a>
                </div>

                <!-- Categories -->
                <div class="filter-group" style="margin-bottom: 28px;">
                    <h4 style="font-size: 0.8rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 12px;">CATEGORY</h4>
                    <ul style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 8px;">
                        <li>
                            <a href="shop.php" style="<?= ($catSlug === '' && !$isSale && !$isNew && !$gender) ? 'font-weight: 700; text-decoration: underline;' : '' ?>">All Categories</a>
                        </li>
                        <?php foreach ($categories as $c): ?>
                            <li>
                                <a href="shop.php?cat=<?= e($c['slug']) ?>" style="<?= ($catSlug === $c['slug']) ? 'font-weight: 700; text-decoration: underline;' : '' ?>"><?= e($c['name']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Gender -->
                <div class="filter-group" style="margin-bottom: 28px;">
                    <h4 style="font-size: 0.8rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 12px;">GENDER</h4>
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.85rem;">
                        <label><input type="radio" name="gender" value="" <?= $gender === '' ? 'checked' : '' ?> onchange="this.form.submit()"> All</label>
                        <label><input type="radio" name="gender" value="Men" <?= strtolower($gender) === 'men' ? 'checked' : '' ?> onchange="this.form.submit()"> Men</label>
                        <label><input type="radio" name="gender" value="Women" <?= strtolower($gender) === 'women' ? 'checked' : '' ?> onchange="this.form.submit()"> Women</label>
                    </div>
                </div>

                <!-- Size -->
                <div class="filter-group" style="margin-bottom: 28px;">
                    <h4 style="font-size: 0.8rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 12px;">SIZE</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $sz): ?>
                            <a href="shop.php?size=<?= $sz ?>" class="size-btn <?= $size === $sz ? 'active' : '' ?>" style="min-width: 38px; height: 38px; font-size: 0.75rem;"><?= $sz ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Color -->
                <div class="filter-group" style="margin-bottom: 28px;">
                    <h4 style="font-size: 0.8rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 12px;">COLOR</h4>
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.85rem;">
                        <?php foreach (['Black', 'White', 'Cream', 'Brown', 'Grey'] as $col): ?>
                            <a href="shop.php?color=<?= urlencode($col) ?>" style="<?= $color === $col ? 'font-weight: 700; text-decoration: underline;' : '' ?>">
                                &bull; <?= $col ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </aside>

        <!-- Main Product Grid -->
        <main class="shop-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);">
                <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">
                    Showing <strong><?= count($products) ?></strong> active products
                </span>

                <div style="display: flex; align-items: center; gap: 12px;">
                    <label for="sortSelect" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.1em;">SORT BY:</label>
                    <select id="sortSelect" class="form-select" style="padding: 8px 12px; font-size: 0.85rem; width: auto;" onchange="location = this.value;">
                        <option value="shop.php?<?= http_build_query(array_merge($_GET, ['sort' => 'newest'])) ?>" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                        <option value="shop.php?<?= http_build_query(array_merge($_GET, ['sort' => 'price_low'])) ?>" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="shop.php?<?= http_build_query(array_merge($_GET, ['sort' => 'price_high'])) ?>" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="shop.php?<?= http_build_query(array_merge($_GET, ['sort' => 'name_asc'])) ?>" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($products)): ?>
                <div class="grid grid-3">
                    <?php foreach ($products as $prod): ?>
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
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 80px 20px; background-color: var(--bg-alt); border: 1px solid var(--border-color);">
                    <h3 style="font-family: var(--font-heading); font-size: 1.25rem; margin-bottom: 8px;">NO PRODUCTS FOUND</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">We couldn't find any items matching your selected criteria.</p>
                    <a href="shop.php" class="btn btn-primary">CLEAR ALL FILTERS</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
