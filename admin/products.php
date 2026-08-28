<?php
/**
 * KANDY CO. - Admin Product Catalog & Directory Purging Controller
 *
 * Delete action (which calls header()) must run BEFORE require_once header.php
 * to avoid "headers already sent" warnings.
 */

// ── 1. Bootstrap (no HTML output yet) ────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// ── 2. Handle Product Delete Action + File Directory Purge ───────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    if ($delId > 0) {
        try {
            // Fetch image records to physically unlink files
            $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
            $stmt->execute([$delId]);
            $imgs = $stmt->fetchAll();

            foreach ($imgs as $img) {
                $fullFile = __DIR__ . '/../' . ltrim($img['image_path'], '/');
                if (file_exists($fullFile) && !is_dir($fullFile)) {
                    @unlink($fullFile);
                }
            }

            // Remove product uploads folder: uploads/products/{delId}/
            $prodDir = __DIR__ . "/../uploads/products/{$delId}/";
            if (is_dir($prodDir)) {
                $files = array_diff(scandir($prodDir), ['.', '..']);
                foreach ($files as $f) {
                    @unlink($prodDir . $f);
                }
                @rmdir($prodDir);
            }

            // Delete DB record (CASCADE removes product_images, product_variants, wishlist, etc.)
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$delId]);

            setFlash('success', "Product #{$delId} and all associated image files were permanently deleted.");
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting product: ' . $e->getMessage());
        }
    }
    // ── Redirect BEFORE any HTML output ──────────────────────────────────────
    header('Location: products.php');
    exit();
}

// ── 3. Fetch Filter Options & Products ───────────────────────────────────────
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$search       = trim($_GET['q'] ?? '');
$genderFilter = trim($_GET['gender'] ?? '');
$catFilter    = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$statusFilter = trim($_GET['status'] ?? '');
$sort         = trim($_GET['sort'] ?? 'newest');

$sql = "
    SELECT p.*, c.name as category_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as main_image,
           (SELECT SUM(stock_quantity) FROM product_variants WHERE product_id = p.id) as total_stock
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.title LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[]   = $searchTerm;
    $params[]   = $searchTerm;
    $params[]   = $searchTerm;
}
if ($genderFilter !== '') {
    $sql    .= " AND p.gender = ?";
    $params[] = $genderFilter;
}
if ($catFilter > 0) {
    $sql    .= " AND p.category_id = ?";
    $params[] = $catFilter;
}
if ($statusFilter !== '') {
    $sql    .= " AND p.status = ?";
    $params[] = $statusFilter;
}

switch ($sort) {
    case 'price_low':  $sql .= " ORDER BY p.price ASC";    break;
    case 'price_high': $sql .= " ORDER BY p.price DESC";   break;
    case 'stock_low':  $sql .= " ORDER BY total_stock ASC"; break;
    case 'newest':
    default:           $sql .= " ORDER BY p.id DESC";      break;
}

$products = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Exception $e) {}

// ── 4. Now safe to output HTML ────────────────────────────────────────────────
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title & Add Button -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h3 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800;">PRODUCTS DIRECTORY (<?= count($products) ?>)</h3>
        <span style="font-size: 0.8rem; color: var(--text-muted);">Manage product listings, inventory levels, and image galleries</span>
    </div>
    <a href="add-product.php" class="btn btn-primary btn-sm">+ ADD NEW PRODUCT</a>
</div>

<!-- Filters Bar -->
<div style="background-color: var(--bg-main); padding: 20px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-bottom: 24px;">
    <form action="products.php" method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
        <input type="text" name="q" placeholder="Search by Title, SKU..." value="<?= e($search) ?>"
               class="form-input" style="flex: 1; min-width: 200px; padding: 8px 12px; font-size: 0.85rem;">

        <select name="gender" class="form-select" style="width: auto; padding: 8px 12px; font-size: 0.85rem;" onchange="this.form.submit()">
            <option value="">All Genders</option>
            <option value="Men"    <?= $genderFilter === 'Men'    ? 'selected' : '' ?>>Men</option>
            <option value="Women"  <?= $genderFilter === 'Women'  ? 'selected' : '' ?>>Women</option>
            <option value="Unisex" <?= $genderFilter === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
        </select>

        <select name="category_id" class="form-select" style="width: auto; padding: 8px 12px; font-size: 0.85rem;" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="form-select" style="width: auto; padding: 8px 12px; font-size: 0.85rem;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Active"       <?= $statusFilter === 'Active'       ? 'selected' : '' ?>>Active</option>
            <option value="Draft"        <?= $statusFilter === 'Draft'        ? 'selected' : '' ?>>Draft</option>
            <option value="Out of Stock" <?= $statusFilter === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
        </select>

        <button type="submit" class="btn btn-outline btn-sm">FILTER</button>
        <a href="products.php" style="font-size: 0.8rem; text-decoration: underline; color: var(--text-muted);">RESET</a>
    </form>
</div>

<!-- Product Directory Table -->
<?php if (!empty($products)): ?>
    <table class="table-style" style="background-color: var(--bg-main);">
        <thead>
            <tr>
                <th>MAIN IMAGE</th>
                <th>PRODUCT TITLE</th>
                <th>SKU</th>
                <th>GENDER</th>
                <th>CATEGORY</th>
                <th>PRICE</th>
                <th>TOTAL STOCK</th>
                <th>STATUS</th>
                <th>ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $prod): ?>
                <tr>
                    <td>
                        <img src="<?= getImageUrl($prod['main_image'], $prod['title']) ?>"
                             alt="<?= e($prod['title']) ?>"
                             style="width: 50px; height: 60px; object-fit: cover;">
                    </td>
                    <td>
                        <a href="../product.php?slug=<?= e($prod['slug']) ?>" target="_blank"
                           style="font-weight: 700; color: var(--text-primary);"><?= e($prod['title']) ?></a>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($prod['brand']) ?></div>
                    </td>
                    <td><code><?= e($prod['sku']) ?></code></td>
                    <td><span class="badge-tag" style="background-color: var(--border-dark);"><?= e($prod['gender']) ?></span></td>
                    <td><?= e($prod['category_name']) ?></td>
                    <td>
                        <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['price']): ?>
                            <span style="text-decoration: line-through; font-size: 0.8rem; color: var(--text-muted);"><?= formatPrice($prod['price']) ?></span>
                            <strong style="color: var(--sale-color); display: block;"><?= formatPrice($prod['sale_price']) ?></strong>
                        <?php else: ?>
                            <strong><?= formatPrice($prod['price']) ?></strong>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-tag" style="background-color: <?= ($prod['total_stock'] ?? 0) > 5 ? 'var(--text-primary)' : 'var(--sale-color)' ?>;">
                            <?= (int)($prod['total_stock'] ?? 0) ?> UNITS
                        </span>
                    </td>
                    <td>
                        <?php if ($prod['status'] === 'Active'): ?>
                            <span class="badge-tag" style="background-color: var(--success);">ACTIVE</span>
                        <?php elseif ($prod['status'] === 'Draft'): ?>
                            <span class="badge-tag" style="background-color: var(--border-dark);">DRAFT</span>
                        <?php else: ?>
                            <span class="badge-tag badge-sale">OUT OF STOCK</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit-product.php?id=<?= $prod['id'] ?>"
                           style="font-size: 0.8rem; font-weight: 700; text-decoration: underline; margin-right: 12px;">EDIT</a>
                        <a href="products.php?action=delete&id=<?= $prod['id'] ?>"
                           onclick="return confirm('Permanently delete product \'<?= e($prod['title']) ?>\' and purge all image files from server?');"
                           style="font-size: 0.8rem; font-weight: 700; color: var(--danger); text-decoration: underline;">DELETE</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div style="background-color: var(--bg-main); padding: 40px; text-align: center; border: 1px solid var(--border-color);">
        <p style="color: var(--text-muted);">No products match your filter criteria.</p>
        <a href="add-product.php" class="btn btn-primary btn-sm" style="margin-top: 16px;">+ ADD FIRST PRODUCT</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
