<?php
/**
 * KANDY CO. - Admin Add Product & Image Upload System
 *
 * ALL PHP processing (POST handling, redirects) must run BEFORE
 * require_once header.php, because header.php outputs full HTML.
 * Calling header() after HTML output causes "headers already sent".
 */

// ── 1. Bootstrap (no HTML output yet) ────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// ── 2. Handle Product Creation (POST) ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
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
    $primaryIndex     = (int)($_POST['primary_image_index'] ?? 0);

    // Auto slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    if (!$sku) {
        $sku = 'KND-' . strtoupper(substr($slug, 0, 3)) . '-' . rand(1000, 9999);
    }

    if (!$title || $categoryId <= 0 || $price <= 0 || !$description) {
        setFlash('danger', 'Please complete all required product fields.');
    } else {
        try {
            $pdo->beginTransaction();

            // Insert Base Product
            $stmt = $pdo->prepare("
                INSERT INTO products
                    (category_id, title, slug, description, short_description, price, sale_price,
                     brand, sku, gender, status, is_featured, is_new_arrival, is_on_sale)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $categoryId, $title, $slug, $description, $shortDescription,
                $price, $salePrice, $brand, $sku, $gender, $status,
                $isFeatured, $isNewArrival, $isOnSale
            ]);
            $productId = $pdo->lastInsertId();

            // Create product-specific upload folder: uploads/products/{product_id}/
            $productUploadDir = __DIR__ . "/../uploads/products/{$productId}/";
            if (!is_dir($productUploadDir)) {
                mkdir($productUploadDir, 0755, true);
            }

            // Handle Image Upload
            $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (isset($_FILES['product_images']) && is_array($_FILES['product_images']['name'])) {
                $fileCount        = count($_FILES['product_images']['name']);
                $savedImagesCount = 0;

                for ($i = 0; $i < $fileCount; $i++) {
                    $tmpName      = $_FILES['product_images']['tmp_name'][$i];
                    $originalName = $_FILES['product_images']['name'][$i];
                    $fileError    = $_FILES['product_images']['error'][$i];
                    $fileSize     = $_FILES['product_images']['size'][$i];

                    if ($fileError === UPLOAD_ERR_OK && $fileSize <= 5 * 1024 * 1024) {
                        $ext  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        $mime = mime_content_type($tmpName);

                        if (in_array($ext, $allowedExts) && in_array($mime, $allowedMimes)) {
                            $newFilename = 'image-' . bin2hex(random_bytes(8)) . '.' . $ext;
                            $targetPath  = $productUploadDir . $newFilename;

                            if (move_uploaded_file($tmpName, $targetPath)) {
                                // Store relative path — never an absolute filesystem path
                                $dbPath    = "uploads/products/{$productId}/" . $newFilename;
                                $isPrimary = ($i === $primaryIndex || ($savedImagesCount === 0 && $primaryIndex >= $fileCount)) ? 1 : 0;
                                $sortOrder = $savedImagesCount + 1;

                                $imgStmt = $pdo->prepare(
                                    "INSERT INTO product_images (product_id, image_path, is_primary, sort_order)
                                     VALUES (?, ?, ?, ?)"
                                );
                                $imgStmt->execute([$productId, $dbPath, $isPrimary, $sortOrder]);
                                $savedImagesCount++;
                            }
                        }
                    }
                }
            }

            // Insert Product Variants Matrix
            if (isset($_POST['variant_stock']) && is_array($_POST['variant_stock'])) {
                $varStmt = $pdo->prepare("
                    INSERT INTO product_variants
                        (product_id, size, color, color_hex, sku, stock_quantity, additional_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $hexMap = [
                    'Black' => '#0A0A0A', 'White' => '#FFFFFF',
                    'Cream' => '#F5F5DC', 'Brown' => '#4A3525', 'Grey' => '#808080'
                ];

                foreach ($_POST['variant_stock'] as $color => $sizesArray) {
                    $hex = $hexMap[$color] ?? '#18181B';
                    foreach ($sizesArray as $size => $stockData) {
                        $stock      = max(0, (int)($stockData['qty'] ?? 0));
                        $extraPrice = (float)($stockData['extra_price'] ?? 0.00);
                        $varSku     = $sku . '-' . strtoupper(substr($color, 0, 3)) . '-' . $size;
                        $varStmt->execute([$productId, $size, $color, $hex, $varSku, $stock, $extraPrice]);
                    }
                }
            }

            $pdo->commit();
            setFlash('success', "Product '{$title}' created successfully.");

            // ── Redirect BEFORE any HTML output ──────────────────────────────
            header('Location: products.php');
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Transaction failed: ' . $e->getMessage());
        }
    }
}

// ── 3. Fetch Categories (for the form) ───────────────────────────────────────
$categories = [];
try {
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}

// ── 4. Now it is safe to output HTML ─────────────────────────────────────────
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 960px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800;">ADD NEW PRODUCT</h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Create new luxury fashion item &amp; manage image gallery</span>
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="button" class="btn btn-outline btn-sm" id="openPreviewBtn">&boxbox; PREVIEW PRODUCT</button>
            <a href="products.php" class="btn btn-outline btn-sm">&larr; BACK TO CATALOG</a>
        </div>
    </div>

    <form action="add-product.php" method="POST" enctype="multipart/form-data" id="addProductForm" style="background-color: var(--bg-main); padding: 40px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
        <input type="hidden" name="save_product" value="1">

        <!-- Basic Information -->
        <h4 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">1. BASIC INFORMATION</h4>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">PRODUCT TITLE *</label>
                <input type="text" name="title" id="formTitle" class="form-input" required placeholder="e.g. KANDY Oversized Heavyweight Tee" value="<?= e($_POST['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">CATEGORY *</label>
                <select name="category_id" id="formCategory" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">SHORT DESCRIPTION</label>
            <input type="text" name="short_description" id="formShortDesc" class="form-input" placeholder="280GSM organic cotton boxy streetwear tee.">
        </div>

        <div class="form-group">
            <label class="form-label">FULL PRODUCT DESCRIPTION *</label>
            <textarea name="description" id="formDesc" class="form-textarea" rows="4" required placeholder="Detailed description of architectural silhouette, materials, and care instructions..."></textarea>
        </div>

        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">REGULAR PRICE (LKR) *</label>
                <input type="number" step="0.01" name="price" id="formPrice" class="form-input" required placeholder="4500.00" value="<?= e($_POST['price'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">SALE PRICE (LKR)</label>
                <input type="number" step="0.01" name="sale_price" id="formSalePrice" class="form-input" placeholder="3800.00">
            </div>
            <div class="form-group">
                <label class="form-label">SKU</label>
                <input type="text" name="sku" class="form-input" placeholder="Auto-generated if left blank">
            </div>
        </div>

        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">GENDER AUDIENCE</label>
                <select name="gender" id="formGender" class="form-select">
                    <option value="Unisex">Unisex</option>
                    <option value="Men">Men</option>
                    <option value="Women">Women</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">PRODUCT STATUS</label>
                <select name="status" class="form-select">
                    <option value="Active">Active (Visible on website)</option>
                    <option value="Draft">Draft (Hidden in admin)</option>
                    <option value="Out of Stock">Out of Stock</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">BRAND NAME</label>
                <input type="text" name="brand" class="form-input" value="KANDY CO.">
            </div>
        </div>

        <!-- Tags / Collection Flags -->
        <div class="form-group" style="margin-top: 12px;">
            <label class="form-label">COLLECTION DISPLAY TAGS</label>
            <div style="display: flex; gap: 24px; font-size: 0.85rem;">
                <label><input type="checkbox" name="is_featured" value="1"> Featured Collection</label>
                <label><input type="checkbox" name="is_new_arrival" value="1" checked> New Arrival</label>
                <label><input type="checkbox" name="is_on_sale" value="1"> On Sale Badge</label>
            </div>
        </div>

        <!-- Image Upload Section -->
        <h4 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; margin-top: 36px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">2. PRODUCT IMAGES UPLOAD</h4>

        <div style="background-color: var(--bg-alt); padding: 24px; border: 1px dashed var(--border-color); border-radius: var(--radius-sm); margin-bottom: 24px;">
            <label class="form-label">SELECT MULTIPLE PRODUCT IMAGES (JPG, PNG, WEBP — max 5MB each)</label>
            <input type="file" name="product_images[]" id="imageInput" multiple accept=".jpg,.jpeg,.png,.webp" class="form-input" style="padding: 12px; background-color: var(--bg-main);">
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">
                Upload front view, back view, and detail shots. The first image or selected primary image will be displayed on the shop grid.
            </p>
        </div>

        <!-- Live Upload Image Previews -->
        <div id="imagePreviewContainer" style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;"></div>

        <!-- Variant Stock Matrix -->
        <h4 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; margin-top: 36px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">3. VARIANT SIZES, COLORS &amp; STOCK MATRIX</h4>

        <div style="margin-bottom: 20px;">
            <label class="form-label">AVAILABLE COLORS</label>
            <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 0.85rem;" id="colorSelector">
                <label><input type="checkbox" class="color-checkbox" value="Black" checked> Black</label>
                <label><input type="checkbox" class="color-checkbox" value="White" checked> White</label>
                <label><input type="checkbox" class="color-checkbox" value="Cream" checked> Cream</label>
                <label><input type="checkbox" class="color-checkbox" value="Brown" checked> Brown</label>
                <label><input type="checkbox" class="color-checkbox" value="Grey" checked> Grey</label>
            </div>
        </div>

        <table class="table-style" style="font-size: 0.8rem; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th>COLOR</th>
                    <th>XS</th><th>S</th><th>M</th><th>L</th><th>XL</th><th>XXL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (['Black', 'White', 'Cream', 'Brown', 'Grey'] as $col): ?>
                    <tr>
                        <td><strong><?= $col ?></strong></td>
                        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $sz): ?>
                            <td>
                                <input type="number" name="variant_stock[<?= $col ?>][<?= $sz ?>][qty]" value="10" min="0" class="form-input" style="width: 55px; padding: 4px; text-align: center;">
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Submit Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--border-color);">
            <a href="products.php" class="btn btn-outline">DISCARD &amp; CANCEL</a>
            <div style="display: flex; gap: 12px;">
                <button type="submit" name="status" value="Draft" class="btn btn-outline">SAVE AS DRAFT</button>
                <button type="submit" class="btn btn-primary" style="height: 48px;">PUBLISH PRODUCT NOW</button>
            </div>
        </div>
    </form>
</div>

<!-- Product Preview Modal Overlay -->
<div class="modal-overlay" id="productPreviewModal">
    <div class="modal-box" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <button class="modal-close" id="closePreviewBtn">&times;</button>
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--sale-color); letter-spacing: 0.1em; display: block; margin-bottom: 12px;">&boxbox; LIVE PRODUCT STOREFRONT PREVIEW</span>
        
        <div class="product-detail-grid" style="grid-template-columns: 1fr 1fr; gap: 32px;">
            <div>
                <div style="aspect-ratio: 4/5; background-color: var(--bg-alt); display: flex; align-items: center; justify-content: center;" id="previewMainImgBox">
                    <span style="color: var(--text-muted); font-size: 0.85rem;">[ Upload Product Images to Preview ]</span>
                </div>
            </div>
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);" id="previewCategory">KANDY CO. &bull; CATEGORY</span>
                <h2 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 800;" id="previewTitle">KANDY Product Title</h2>
                <div style="font-size: 1.5rem; font-weight: 700; margin: 12px 0;" id="previewPrice">$65.00</div>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;" id="previewShortDesc">Short description preview...</p>
                <div style="margin-bottom: 16px;">
                    <span class="form-label" style="font-size: 0.75rem;">SIZES AVAILABLE:</span>
                    <div style="display: flex; gap: 8px;">
                        <span class="size-btn">S</span><span class="size-btn active">M</span><span class="size-btn">L</span>
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <span class="form-label" style="font-size: 0.75rem;">PRODUCT DETAILS:</span>
                    <p style="font-size: 0.85rem; color: var(--text-muted);" id="previewDesc">Full product description preview...</p>
                </div>
                <button class="btn btn-primary full-width" disabled>ADD TO CART (PREVIEW ONLY)</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Multi-Image Preview & Primary Radio Handler
    const imageInput = document.getElementById('imageInput');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');

    if (imageInput) {
        imageInput.addEventListener('change', function() {
            imagePreviewContainer.innerHTML = '';
            const files = Array.from(this.files);

            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const card = document.createElement('div');
                    card.style.cssText = 'position: relative; width: 110px; border: 1px solid var(--border-color); padding: 8px; background: var(--bg-alt); text-align: center; font-size: 0.75rem;';
                    card.innerHTML = `
                        <img src="${e.target.result}" style="width: 100%; height: 110px; object-fit: cover; margin-bottom: 6px;">
                        <label style="display: block; cursor: pointer;">
                            <input type="radio" name="primary_image_index" value="${index}" ${index === 0 ? 'checked' : ''}> Primary
                        </label>
                    `;
                    imagePreviewContainer.appendChild(card);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Live Product Preview Modal Logic
    const openPreviewBtn  = document.getElementById('openPreviewBtn');
    const closePreviewBtn = document.getElementById('closePreviewBtn');
    const productPreviewModal = document.getElementById('productPreviewModal');

    if (openPreviewBtn && productPreviewModal) {
        openPreviewBtn.addEventListener('click', () => {
            const title        = document.getElementById('formTitle').value || 'KANDY Product Title';
            const price        = document.getElementById('formPrice').value || '65.00';
            const salePrice    = document.getElementById('formSalePrice').value;
            const shortDesc    = document.getElementById('formShortDesc').value || 'Short description preview...';
            const desc         = document.getElementById('formDesc').value || 'Full product description preview...';
            const categorySelect = document.getElementById('formCategory');
            const categoryText = categorySelect.options[categorySelect.selectedIndex]?.text || 'COLLECTION';

            document.getElementById('previewTitle').innerText    = title;
            document.getElementById('previewCategory').innerText = 'KANDY CO. • ' + categoryText.toUpperCase();
            document.getElementById('previewShortDesc').innerText = shortDesc;
            document.getElementById('previewDesc').innerText     = desc;

            if (salePrice && parseFloat(salePrice) < parseFloat(price)) {
                const formattedPrice = parseFloat(price).toLocaleString('en-US');
                const formattedSalePrice = parseFloat(salePrice).toLocaleString('en-US');
                document.getElementById('previewPrice').innerHTML = `<span style="text-decoration:line-through; font-size:1.1rem; color:var(--text-muted);">LKR ${formattedPrice}</span> <span style="color:var(--sale-color); font-weight:800;">LKR ${formattedSalePrice}</span>`;
            } else {
                const formattedPrice = parseFloat(price || 0).toLocaleString('en-US');
                document.getElementById('previewPrice').innerText = 'LKR ' + formattedPrice;
            }

            const firstImg = imagePreviewContainer.querySelector('img');
            if (firstImg) {
                document.getElementById('previewMainImgBox').innerHTML = `<img src="${firstImg.src}" style="width:100%; height:100%; object-fit:cover;">`;
            }

            productPreviewModal.classList.add('active');
        });
    }

    if (closePreviewBtn && productPreviewModal) {
        closePreviewBtn.addEventListener('click', () => {
            productPreviewModal.classList.remove('active');
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
