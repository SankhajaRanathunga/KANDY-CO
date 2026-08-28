<?php
/**
 * KANDY CO. - Admin Customers Directory
 */
require_once __DIR__ . '/includes/header.php';

$customers = [];
try {
    $stmt = $pdo->query("
        SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders
        FROM users u
        WHERE u.role = 'customer'
        ORDER BY u.id DESC
    ");
    $customers = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800;">REGISTERED CUSTOMERS (<?= count($customers) ?>)</h3>
</div>

<?php if (!empty($customers)): ?>
    <table class="table-style" style="background-color: var(--bg-main);">
        <thead>
            <tr>
                <th>ID</th>
                <th>FULL NAME</th>
                <th>EMAIL ADDRESS</th>
                <th>PHONE NUMBER</th>
                <th>REGISTERED DATE</th>
                <th>TOTAL ORDERS</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $cust): ?>
                <tr>
                    <td>#<?= $cust['id'] ?></td>
                    <td><strong><?= e($cust['full_name']) ?></strong></td>
                    <td><?= e($cust['email']) ?></td>
                    <td><?= e($cust['phone'] ?: 'N/A') ?></td>
                    <td><?= date('M d, Y', strtotime($cust['created_at'])) ?></td>
                    <td><span class="badge-tag"><?= $cust['total_orders'] ?> ORDERS</span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div style="background-color: var(--bg-main); padding: 40px; text-align: center; border: 1px solid var(--border-color);">
        <p style="color: var(--text-muted);">No registered customers found in database.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
