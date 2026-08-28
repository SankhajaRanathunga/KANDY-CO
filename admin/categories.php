<?php
/**
 * KANDY CO. - Admin Categories Manager
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    if ($name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $description]);
            setFlash('success', "Category '{$name}' created.");
            header('Location: categories.php');
            exit();
        } catch (Exception $e) {
            setFlash('danger', 'Error adding category: ' . e($e->getMessage()));
        }
    }
}

// Handle Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$delId]);
    setFlash('success', 'Category deleted.');
    header('Location: categories.php');
    exit();
}

$categories = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
    FROM categories c 
    ORDER BY c.name ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 36px;">
    <!-- Left: Categories Table -->
    <div>
        <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; margin-bottom: 24px;">PRODUCT CATEGORIES (<?= count($categories) ?>)</h3>

        <table class="table-style" style="background-color: var(--bg-main);">
            <thead>
                <tr>
                    <th>NAME</th>
                    <th>SLUG</th>
                    <th>DESCRIPTION</th>
                    <th>PRODUCTS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong><?= e($cat['name']) ?></strong></td>
                        <td><code><?= e($cat['slug']) ?></code></td>
                        <td style="font-size: 0.8rem; color: var(--text-muted);"><?= e($cat['description']) ?></td>
                        <td><span class="badge-tag"><?= $cat['product_count'] ?> ITEMS</span></td>
                        <td>
                            <a href="categories.php?action=delete&id=<?= $cat['id'] ?>" onclick="return confirm('Delete this category?');" style="color: var(--danger); font-size: 0.8rem; text-decoration: underline;">DELETE</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Right: Add Category Form -->
    <div>
        <div style="background-color: var(--bg-main); padding: 28px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <h4 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; margin-bottom: 20px;">ADD NEW CATEGORY</h4>

            <form action="categories.php" method="POST">
                <input type="hidden" name="add_category" value="1">
                
                <div class="form-group">
                    <label class="form-label">CATEGORY NAME *</label>
                    <input type="text" name="name" class="form-input" required placeholder="e.g. Denim Jackets">
                </div>

                <div class="form-group">
                    <label class="form-label">DESCRIPTION</label>
                    <textarea name="description" class="form-textarea" rows="4" placeholder="Short category description..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary full-width">CREATE CATEGORY</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
