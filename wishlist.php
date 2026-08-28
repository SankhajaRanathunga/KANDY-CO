<?php
/**
 * KANDY CO. - Wishlist Controller & Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'add' && $productId > 0) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        setFlash('success', 'Item saved to your wishlist.');
    } catch (Exception $e) {}
    header('Location: wishlist.php');
    exit();
}

if ($action === 'remove' && $productId > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        setFlash('success', 'Item removed from your wishlist.');
    } catch (Exception $e) {}
    header('Location: wishlist.php');
    exit();
}

$wishlistItems = [];
try {
    $stmt = $pdo->prepare("
        SELECT w.id as wishlist_id, p.*, c.name as category_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE w.user_id = ? AND p.status = 'Active'
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    $wishlistItems = $stmt->fetchAll();
} catch (Exception $e) {}

$pageTitle = "My Wishlist | KANDY CO.";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
    <div class="container">
        <h1 class="section-title" style="text-align: left; margin-bottom: 32px;">MY SAVED WISHLIST</h1>

        <?php if (!empty($wishlistItems)): ?>
            <div class="grid grid-4">
                <?php foreach ($wishlistItems as $prod): ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <a href="product.php?slug=<?= e($prod['slug']) ?>">
                                <img src="<?= getImageUrl($prod['primary_image'], $prod['title']) ?>" alt="<?= e($prod['title']) ?>" class="product-img">
                            </a>

                            <a href="wishlist.php?action=remove&id=<?= $prod['id'] ?>" class="wishlist-btn-overlay active" title="Remove from Wishlist">
                                &times;
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
                            <a href="product.php?slug=<?= e($prod['slug']) ?>" class="btn btn-outline full-width" style="margin-top: 12px; padding: 10px 0; font-size: 0.75rem;">SELECT OPTIONS</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; background-color: var(--bg-alt); border: 1px solid var(--border-color);">
                <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 12px;">YOUR WISHLIST IS EMPTY</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px;">Click the heart icon on any product to save items for later.</p>
                <a href="shop.php" class="btn btn-primary">EXPLORE CATALOG</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
