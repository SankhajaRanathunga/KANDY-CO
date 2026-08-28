<?php
/**
 * KANDY CO. - Customer Service & Contact Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        setFlash('success', 'Thank you for contacting KANDY CO. Our customer support concierge will respond within 24 hours.');
    } else {
        setFlash('danger', 'Please fill out all required fields.');
    }
    header('Location: contact.php');
    exit();
}

$pageTitle = "Contact Concierge | KANDY CO.";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding" style="background-color: var(--bg-alt); padding: 40px 0; border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <h1 class="section-title" style="margin-bottom: 4px;">CUSTOMER CONCIERGE & SUPPORT</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em;">
            WE'RE HERE TO HELP WITH SIZING, SHIPPING & INQUIRIES
        </p>
    </div>
</div>

<div class="section-padding">
    <div class="container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 64px;">
        <!-- Left: Form -->
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; margin-bottom: 24px;">SEND US A MESSAGE</h3>

            <form action="contact.php" method="POST">
                <div class="form-group">
                    <label class="form-label">YOUR FULL NAME *</label>
                    <input type="text" name="name" class="form-input" required placeholder="Alex Turner">
                </div>

                <div class="form-group">
                    <label class="form-label">EMAIL ADDRESS *</label>
                    <input type="email" name="email" class="form-input" required placeholder="alex@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">PHONE NUMBER</label>
                    <input type="tel" name="phone" class="form-input" placeholder="+1 (555) 000-0000">
                </div>

                <div class="form-group">
                    <label class="form-label">MESSAGE / INQUIRY *</label>
                    <textarea name="message" class="form-textarea" rows="6" required placeholder="How can we assist you today?"></textarea>
                </div>

                <button type="submit" class="btn btn-primary full-width" style="height: 52px;">SUBMIT MESSAGE</button>
            </form>
        </div>

        <!-- Right: Information & FAQ -->
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; margin-bottom: 24px;">FLAGSHIP HEADQUARTERS</h3>

            <div style="font-size: 0.9rem; line-height: 1.8; margin-bottom: 32px; color: var(--text-muted);">
                <p><strong>KANDY CO. GLOBAL STUDIO</strong></p>
                <p>740 Fashion Avenue, Suite 1200</p>
                <p>New York, NY 10018, USA</p>
                <br>
                <p><strong>Email Concierge:</strong> concierge@kandyco.com</p>
                <p><strong>Support Telephone:</strong> +1 (800) 555-KANDY</p>
                <p><strong>Hours:</strong> Monday – Friday, 9:00 AM – 6:00 PM EST</p>
            </div>

            <h4 id="faq" style="font-family: var(--font-heading); font-size: 1rem; font-weight: 800; letter-spacing: 0.05em; margin-bottom: 16px;">FREQUENTLY ASKED QUESTIONS</h4>

            <div class="product-accordion">
                <div class="accordion-item active">
                    <button class="accordion-title">HOW DOES KANDY CO. SIZING FIT? <span>+</span></button>
                    <div class="accordion-content">
                        <p>Our garments are intentionally cut with an architectural boxy, oversized silhouette. If you prefer a traditional standard fit, we recommend selecting one size down.</p>
                    </div>
                </div>

                <div class="accordion-item" id="shipping">
                    <button class="accordion-title">WHAT ARE THE SHIPPING TIMES? <span>+</span></button>
                    <div class="accordion-content">
                        <p>Domestic US express shipping takes 2-3 business days. International express shipping takes 4-6 business days via DHL/FedEx Express.</p>
                    </div>
                </div>

                <div class="accordion-item" id="returns">
                    <button class="accordion-title">WHAT IS YOUR RETURN POLICY? <span>+</span></button>
                    <div class="accordion-content">
                        <p>We accept unworn, unwashed items with original brand tags attached within 30 days of delivery. Prepaid return labels are provided upon request.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
