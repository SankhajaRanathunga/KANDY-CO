<?php
/**
 * KANDY CO. - Footer Component
 */
?>
<footer class="site-footer">
    <!-- Newsletter Section -->
    <div class="footer-newsletter">
        <div class="container newsletter-content">
            <div class="newsletter-text">
                <h3>JOIN THE KANDY CLUB</h3>
                <p>Subscribe for exclusive early access to drops, private sales, and seasonal lookbooks.</p>
            </div>
            <form action="#" method="POST" class="newsletter-form" onsubmit="event.preventDefault(); alert('Thank you for subscribing to KANDY CO.');">
                <input type="email" placeholder="ENTER YOUR EMAIL ADDRESS" required>
                <button type="submit" class="btn btn-primary">SUBSCRIBE</button>
            </form>
        </div>
    </div>

    <!-- Main Footer Links -->
    <div class="footer-main">
        <div class="container footer-grid">
            <!-- Brand Info Column -->
            <div class="footer-col brand-col">
                <h4 class="footer-logo">KANDY CO.</h4>
                <p class="brand-desc">Define your everyday. Minimal, heavy-cotton fashion designed with architectural silhouette precision for modern wardrobes worldwide.</p>
                <div class="social-links">
                    <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram">INSTAGRAM</a>
                    <a href="https://tiktok.com" target="_blank" rel="noopener" aria-label="TikTok">TIKTOK</a>
                    <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook">FACEBOOK</a>
                    <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube">YOUTUBE</a>
                </div>
            </div>

            <!-- Shop Column -->
            <div class="footer-col">
                <h5 class="footer-heading">SHOP</h5>
                <ul class="footer-links">
                    <li><a href="shop.php?new=1">New Arrivals</a></li>
                    <li><a href="shop.php?gender=men">Men's Collection</a></li>
                    <li><a href="shop.php?gender=women">Women's Collection</a></li>
                    <li><a href="shop.php?cat=tshirts">T-Shirts</a></li>
                    <li><a href="shop.php?cat=hoodies">Hoodies</a></li>
                    <li><a href="shop.php?cat=pants">Pants</a></li>
                    <li><a href="shop.php?cat=accessories">Accessories</a></li>
                    <li><a href="shop.php?sale=1">Sale</a></li>
                </ul>
            </div>

            <!-- Customer Service Column -->
            <div class="footer-col">
                <h5 class="footer-heading">CUSTOMER SERVICE</h5>
                <ul class="footer-links">
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="contact.php#shipping">Shipping & Delivery</a></li>
                    <li><a href="contact.php#returns">Returns & Exchanges</a></li>
                    <li><a href="shop.php">Size Guide</a></li>
                    <li><a href="contact.php#faq">Frequently Asked Questions</a></li>
                </ul>
            </div>

            <!-- Account Column -->
            <div class="footer-col">
                <h5 class="footer-heading">ACCOUNT</h5>
                <ul class="footer-links">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="account.php">My Account</a></li>
                        <li><a href="account.php#orders">Order History</a></li>
                        <li><a href="wishlist.php">Saved Wishlist</a></li>
                        <li><a href="admin/index.php">Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register Account</a></li>
                        <li><a href="wishlist.php">Wishlist</a></li>
                        <li><a href="admin/login.php">Admin Portal</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bottom Copyright -->
    <div class="footer-bottom">
        <div class="container footer-bottom-content">
            <p>&copy; <?= date('Y') ?> KANDY CO. ALL RIGHTS RESERVED.</p>
            <div class="payment-badges">
                <span>VISA</span>
                <span>MASTERCARD</span>
                <span>AMEX</span>
                <span>APPLE PAY</span>
                <span>PAYPAL</span>
            </div>
        </div>
    </div>
</footer>

<!-- Main JavaScript File -->
<script src="assets/js/main.js"></script>
</body>
</html>
