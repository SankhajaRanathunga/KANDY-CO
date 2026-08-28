<?php
/**
 * KANDY CO. - Admin Edit Product & Image Manager
 *
 * ALL PHP processing (GET actions, POST updates, redirects) must run BEFORE
 * require_once header.php because header.php outputs full HTML immediately.
 */

// ── 1. Bootstrap (no HTML output yet) ────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$productId) {
    header('Location: products.php');
    exit();
}

// ── 2. Handle Image Delete Action ────────────────────────────────────────────
if (isset($_GET['img_action']) && $_GET['img_action'] === 'delete' && isset($_GET['img_id'])) {
    $imgId = (int)$_GET['img_id'];
    $stmt  = $pdo->prepare("SELECT * FROM product_images WHERE id = ? AND product_id = ?");
    $stmt->execute([$imgId, $productId]);
    $img = $stmt->fetch();

    if ($img) {
        // Remove physical file from disk
        $fullPath = __DIR__ . '/../' . ltrim($img['image_path'], '/');
        if (file_exists($fullPath) && !is_dir($fullPath)) {
            @unlink($fullPath);
        }

        $wasPrimary = $img['is_primary'];
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);

        // Auto-promote next image to primary if we just deleted the primary
        if ($wasPrimary) {
            $nextImg = $pdo->query(
                "SELECT id FROM product_images WHERE product_id = {$productId}
                 ORDER BY sort_order ASC, id ASC LIMIT 1"
            )->fetch();
            if ($nextImg) {
                $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?")
                    ->execute([$nextImg['id']]);
            }
        }
        setFlash('success', 'Image deleted from server and database.');
    }
    header("Location: edit-product.php?id={$productId}");
    exit();
}

// ── 3. Handle Set Primary Image Action ───────────────────────────────────────
if (isset($_GET['img_action']) && $_GET['img_action'] === 'primary' && isset($_GET['img_id'])) {
    $imgId = (int)$_GET['img_id'];
    $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$productId]);
    $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?")
        ->execute([$imgId, $productId]);
    setFlash('success', 'Primary product image updated.');
    header("Location: edit-product.php?id={$productId}");
    exit();
}

// ── 4. Handle Form POST (product update + new image upload) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $title            = trim($_POST['title'] ?? '');
    $categoryId       = (int)($_POST['category_id'] ?? 0);
    $description      = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $price            = (float)($_POST['price'] ?? 0);
    $salePrice        = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $brand            = trim($_POST['brand'] ?? 'KANDY CO.');
    $sku              = trim($_POST['sku'] ?? '');
    $gender           = trim($_POST['gender'] ?? 'Unisex');
    $status           = trim($_POST['status'] ?? 'Active');
    $isFeatured       = isset($_POST['is_featured']) ? 1 : 0;
    $isNewArrival     = isset($_POST['is_new_arrival']) ? 1 : 0;
    $isOnSale         = isset($_POST['is_on_sale']) ? 1 : 0;

    if ($title && $categoryId > 0 && $price > 0) {
        try {
            $pdo->beginTransaction();

            // Update base product row
            $stmt = $pdo->prepare("
                UPDATE products
                SET category_id = ?, title = ?, description = ?, short_description = ?,
                    price = ?, sale_price = ?, brand = ?, sku = ?, gender = ?,
                    status = ?, is_featured = ?, is_new_arrival = ?, is_on_sale = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $categoryId, $title, $description, $shortDescription,
                $price, $salePrice, $brand, $sku, $gender, $status,
                $isFeatured, $isNewArrival, $isOnSale, $productId
            ]);

            // Handle new image uploads ────────────────────────────────────────
            $productUploadDir = __DIR__ . "/../uploads/products/{$productId}/";
            if (!is_dir($productUploadDir)) {
                mkdir($productUploadDir, 0755, true);
            }

            $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (isset($_FILES['new_images']) && is_array($_FILES['new_images']['name'])) {
                $fileCount = count($_FILES['new_images']['name']);

                // Find out if this product has any existing images
                $existingCount = (int)$pdo->query(
                    "SELECT COUNT(*) FROM product_images WHERE product_id = {$productId}"
                )->fetchColumn();

                for ($i = 0; $i < $fileCount; $i++) {
                    $tmpName      = $_FILES['new_images']['tmp_name'][$i];
                    $originalName = $_FILES['new_images']['name'][$i];
                    $fileError    = $_FILES['new_images']['error'][$i];
                    $fileSize     = $_FILES['new_images']['size'][$i];

                    if ($fileError !== UPLOAD_ERR_OK) {
                        continue; // Skip files with upload errors
                    }
                    if ($fileSize > 5 * 1024 * 1024) {
                        continue; // Skip files over 5 MB
                    }

                    $ext  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $mime = mime_content_type($tmpName);

                    if (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes)) {
                        continue; // Skip invalid file types
                    }

                    $newFilename = 'image-' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $targetPath  = $productUploadDir . $newFilename;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        // Store relative path — never an absolute filesystem path
                        $dbPath = "uploads/products/{$productId}/" . $newFilename;

                        // Make this the primary image if product currently has none
                        $isPrimary = ($existingCount === 0 && $i === 0) ? 1 : 0;

                        $pdo->prepare(
                            "INSERT INTO product_images (product_id, image_path, is_primary, sort_order)
                             VALUES (?, ?, ?, 99)"
                        )->execute([$productId, $dbPath, $isPrimary]);

                        $existingCount++; // Track so only first gets primary flag
                    }
                }
            }

            // Update image sort order ranks
            if (isset($_POST['sort_order']) && is_array($_POST['sort_order'])) {
                foreach ($_POST['sort_order'] as $imgId => $orderVal) {
                    $pdo->prepare(
                        "UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?"
                    )->execute([(int)$orderVal, (int)$imgId, $productId]);
                }
            }

            // Update variant stock quantities & additional prices
            if (isset($_POST['variant_stock']) && is_array($_POST['variant_stock'])) {
                foreach ($_POST['variant_stock'] as $varId => $varData) {
                    $stock      = max(0, (int)($varData['stock'] ?? 0));
                    $extraPrice = (float)($varData['extra_price'] ?? 0.00);
                    $pdo->prepare(
                        "UPDATE product_variants SET stock_quantity = ?, additional_price = ?
                         WHERE id = ? AND product_id = ?"
                    )->execute([$stock, $extraPrice, (int)$varId, $productId]);
                }
            }

            $pdo->commit();
            setFlash('success', 'Product details and images updated successfully.');

            // ── Redirect BEFORE any HTML output ──────────────────────────────
            header("Location: edit-product.php?id={$productId}");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error updating product: ' . $e->getMessage());
        }
    } else {
        setFlash('danger', 'Please fill in all required fields (Title, Category, Price).');
    }
}

// ── 5. Fetch Product Data (after all redirects) ───────────────────────────────
$product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$product->execute([$productId]);
$product = $product->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$images     = $pdo->query(
    "SELECT * FROM product_images WHERE product_id = {$productId}
     ORDER BY is_primary DESC, sort_order ASC"
)->fetchAll();
$variants   = $pdo->query(
    "SELECT * FROM product_variants WHERE product_id = {$productId}
     ORDER BY color ASC, size ASC"
)->fetchAll();

// ── 6. Now safe to output HTML ────────────────────────────────────────────────
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 960px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800;">EDIT PRODUCT: <?= e($product['title']) ?></h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Product ID: #<?= $product['id'] ?> &bull; SKU: <?= e($product['sku']) ?></span>
        </div>
        <a href="products.php" class="btn btn-outline btn-sm">&larr; BACK TO CATALOG</a>
    </div>

    <form action="edit-product.php?id=<?= $productId ?>" method="POST" enctype="multipart/form-data" style="background-color: var(--bg-main); padding: 40px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
        <input type="hidden" name="update_product" value="1">

        <!-- 1. Basic Information -->
        <h4 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">1. BASIC INFORMATION</h4>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">PRODUCT TITLE *</label>
                <input type="text" name="title" class="form-input" required value="<?= e($product['title']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">CATEGORY *</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">SHORT DESCRIPTION</label>
            <input type="text" name="short_description" class="form-input" value="<?= e($product['short_description']) ?>">
        </div>

        <div class="form-group">
            <label class="form-label">FULL PRODUCT DESCRIPTION *</label>
            <textarea name="description" class="form-textarea" rows="4" required><?= e($product['description']) ?></textarea>
        </div>

        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">REGULAR PRICE (LKR) *</label>
                <input type="number" step="0.01" name="price" class="form-input" required value="<?= e($product['price']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">SALE PRICE (LKR)</label>
                <input type="number" step="0.01" name="sale_price" class="form-input" value="<?= e($product['sale_price']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">SKU</label>
                <input type="text" name="sku" class="form-input" value="<?= e($product['sku']) ?>">
            </div>
        </div>

        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">GENDER</label>
                <select name="gender" class="form-select">
                    <option value="Unisex" <?= $product['gender'] === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                    <option value="Men"    <?= $product['gender'] === 'Men'    ? 'selected' : '' ?>>Men</option>
                    <option value="Women"  <?= $product['gender'] === 'Women'  ? 'selected' : '' ?>>Women</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">STATUS</label>
                <select name="status" class="form-select">
                    <option value="Active"       <?= $product['status'] === 'Active'       ? 'selected' : '' ?>>Active</option>
                    <option value="Draft"        <?= $product['status'] === 'Draft'        ? 'selected' : '' ?>>Draft</option>
                    <option value="Out of Stock" <?= $product['status'] === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">BRAND NAME</label>
                <input type="text" name="brand" class="form-input" value="<?= e($product['brand']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">DISPLAY TAGS</label>
            <div style="display: flex; gap: 24px; font-size: 0.85rem;">
                <label><input type="checkbox" name="is_featured"   value="1" <?= $product['is_featured']   ? 'checked' : '' ?>> Featured</label>
                <label><input type="checkbox" name="is_new_arrival" value="1" <?= $product['is_new_arrival'] ? 'checked' : '' ?>> New Arrival</label>
                <label><input type="checkbox" name="is_on_sale"    value="1" <?= $product['is_on_sale']    ? 'checked' : '' ?>> On Sale</label>
            </div>
        </div>

        <!-- 2. Product Image Gallery -->
        <h4 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; margin-top: 36px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">2. PRODUCT IMAGE GALLERY</h4>

        <!-- Current Images -->
        <?php if (!empty($images)): ?>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">Current images — click <strong>Set as Main</strong> to change the primary image, or <strong>DELETE</strong> to permanently remove it.</p>
            <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
                <?php foreach ($images as $img): ?>
                    <div style="width: 150px; border: 2px solid <?= $img['is_primary'] ? 'var(--text-primary)' : 'var(--border-color)' ?>; padding: 10px; background-color: var(--bg-alt); text-align: center;">
                        <img src="<?= getImageUrl($img['image_path'], $product['title']) ?>"
                             alt="Product image"
                             style="width: 100%; height: 140px; object-fit: cover; margin-bottom: 8px;">

                        <?php if ($img['is_primary']): ?>
                            <span class="badge-tag" style="display: block; margin-bottom: 6px; background-color: var(--text-primary);">&#9733; MAIN IMAGE</span>
                        <?php else: ?>
                            <a href="edit-product.php?id=<?= $productId ?>&img_action=primary&img_id=<?= $img['id'] ?>"
                               style="display: block; font-size: 0.75rem; text-decoration: underline; margin-bottom: 6px;">Set as Main</a>
                        <?php endif; ?>

                        <div style="display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 0.75rem; margin-bottom: 6px;">
                            <span>Rank:</span>
                            <input type="number" name="sort_order[<?= $img['id'] ?>]" value="<?= $img['sort_order'] ?>"
                                   style="width: 40px; text-align: center; border: 1px solid var(--border-color); padding: 2px;">
                        </div>

                        <a href="edit-product.php?id=<?= $productId ?>&img_action=delete&img_id=<?= $img['id'] ?>"
                           onclick="return confirm('Permanently delete this image file from the server?');"
                           style="color: var(--danger); font-size: 0.75rem; text-decoration: underline; font-weight: 700;">DELETE FILE</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background-color: var(--bg-alt); padding: 20px; border: 1px dashed var(--border-color); margin-bottom: 16px; text-align: center;">
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0;">
                    &#128247; No images currently attached to this product — upload one below.
                </p>
            </div>
        <?php endif; ?>

        <!-- Upload New Images -->
        <div class="form-group" style="background-color: var(--bg-alt); padding: 20px; border: 1px dashed var(--border-color); border-radius: var(--radius-sm);">
            <label class="form-label">
                <?= empty($images) ? 'ADD PRODUCT IMAGE (JPG, PNG, WEBP — max 5MB each)' : 'UPLOAD ADDITIONAL IMAGES (JPG, PNG, WEBP — max 5MB each)' ?>
            </label>
            <input type="file" name="new_images[]" id="newImagesInput" multiple accept=".jpg,.jpeg,.png,.webp"
                   class="form-input" style="padding: 10px; background: var(--bg-main);">
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">
                <?php if (empty($images)): ?>
                    The first uploaded image will automatically become the primary (main) product image.
                <?php else: ?>
                    New images will be added to the gallery. Use "Set as Main" to change which image is displayed first.
                <?php endif; ?>
            </p>
        </div>

        <!-- New image live preview -->
        <div id="newImagePreviewContainer" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; margin-bottom: 24px;"></div>

        <!-- 3. Variant Stock Matrix -->
        <h4 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; margin-top: 36px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">3. INVENTORY STOCK &amp; VARIANT PRICES</h4>

        <?php if (!empty($variants)): ?>
            <table class="table-style" style="font-size: 0.85rem; margin-bottom: 24px;">
                <thead>
                    <tr>
                        <th>COLOR</th>
                        <th>SIZE</th>
                        <th>SKU</th>
                        <th>STOCK QTY</th>
                        <th>EXTRA PRICE (LKR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variants as $var): ?>
                        <tr>
                            <td><strong><?= e($var['color']) ?></strong></td>
                            <td><?= e($var['size']) ?></td>
                            <td><code><?= e($var['sku']) ?></code></td>
                            <td>
                                <input type="number"
                                       name="variant_stock[<?= $var['id'] ?>][stock]"
                                       value="<?= $var['stock_quantity'] ?>"
                                       min="0"
                                       class="form-input"
                                       style="width: 80px; padding: 4px; text-align: center;">
                            </td>
                            <td>
                                <input type="number" step="0.01"
                                       name="variant_stock[<?= $var['id'] ?>][extra_price]"
                                       value="<?= $var['additional_price'] ?>"
                                       class="form-input"
                                       style="width: 80px; padding: 4px; text-align: center;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 24px;">No variants defined for this product.</p>
        <?php endif; ?>

        <!-- Actions -->
        <div style="display: flex; justify-content: space-between; margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--border-color);">
            <a href="products.php" class="btn btn-outline">CANCEL</a>
            <button type="submit" class="btn btn-primary" style="height: 48px;">SAVE CHANGES</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Live preview for newly selected upload images
    const newImagesInput      = document.getElementById('newImagesInput');
    const newPreviewContainer = document.getElementById('newImagePreviewContainer');

    if (newImagesInput && newPreviewContainer) {
        newImagesInput.addEventListener('change', function () {
            newPreviewContainer.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement('div');
                    div.style.cssText = 'width: 100px; text-align: center; font-size: 0.75rem; border: 1px solid var(--border-color); padding: 6px; background: var(--bg-alt);';
                    div.innerHTML = `<img src="${e.target.result}" style="width:100%; height:90px; object-fit:cover; margin-bottom:4px;"><span style="color:var(--text-muted);">New</span>`;
                    newPreviewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
