<?php
/**
 * KANDY CO. - Admin Store Settings
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    setFlash('success', 'Store settings updated successfully.');
    header('Location: settings.php');
    exit();
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 700px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; margin-bottom: 24px;">STORE CONFIGURATION & SETTINGS</h3>

    <form action="settings.php" method="POST" style="background-color: var(--bg-main); padding: 36px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
        <div class="form-group">
            <label class="form-label">BRAND NAME</label>
            <input type="text" class="form-input" value="KANDY CO." required>
        </div>

        <div class="form-group">
            <label class="form-label">BRAND TAGLINE</label>
            <input type="text" class="form-input" value="DEFINE YOUR EVERYDAY." required>
        </div>

        <div class="form-group">
            <label class="form-label">STORE CURRENCY</label>
            <input type="text" class="form-input" value="LKR" readonly style="opacity: 0.8; cursor: not-allowed;">
            <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: block;">Store is configured to Sri Lankan Rupee (LKR) throughout the system.</span>
        </div>

        <div class="form-group">
            <label class="form-label">SUPPORT CONCIERGE EMAIL</label>
            <input type="email" class="form-input" value="concierge@kandyco.com" required>
        </div>

        <div class="form-group">
            <label class="form-label">FREE SHIPPING THRESHOLD (LKR)</label>
            <input type="number" class="form-input" value="15000" required>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 16px;">SAVE STORE CONFIGURATION</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
