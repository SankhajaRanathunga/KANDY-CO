<?php
/**
 * KANDY CO. - Navigation Header Component
 */
$cartCount = getCartCount();
$wishlistCount = isset($pdo) && isLoggedIn() ? getWishlistCount($pdo, $_SESSION['user_id']) : 0;
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<header class="site-header" id="mainHeader">
    <div class="header-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-toggle" id="mobileMenuBtn" aria-label="Toggle Navigation Menu">
            <svg class="icon" viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.8" fill="none">
                <line x1="4" y1="7" x2="20" y2="7"></line>
                <line x1="4" y1="12" x2="20" y2="12"></line>
                <line x1="4" y1="17" x2="20" y2="17"></line>
            </svg>
        </button>

        <!-- Brand Logo -->
        <a href="index.php" class="brand-logo">
            <span class="logo-text">KANDY CO.</span>
        </a>

        <!-- Desktop Navigation -->
        <nav class="main-nav" id="mainNav">
            <ul class="nav-list">
                <li class="nav-item"><a href="index.php" class="nav-link <?= $currentScript === 'index.php' ? 'active' : '' ?>">HOME</a></li>
                <li class="nav-item"><a href="shop.php" class="nav-link <?= $currentScript === 'shop.php' && !isset($_GET['cat']) && !isset($_GET['new']) && !isset($_GET['gender']) && !isset($_GET['sale']) ? 'active' : '' ?>">SHOP</a></li>
                <li class="nav-item"><a href="shop.php?new=1" class="nav-link <?= isset($_GET['new']) ? 'active' : '' ?>">NEW ARRIVALS</a></li>
                <li class="nav-item"><a href="shop.php?gender=men" class="nav-link <?= (isset($_GET['gender']) && $_GET['gender'] === 'men') ? 'active' : '' ?>">MEN</a></li>
                <li class="nav-item"><a href="shop.php?gender=women" class="nav-link <?= (isset($_GET['gender']) && $_GET['gender'] === 'women') ? 'active' : '' ?>">WOMEN</a></li>
                <li class="nav-item"><a href="shop.php?cat=hoodies" class="nav-link <?= (isset($_GET['cat']) && $_GET['cat'] === 'hoodies') ? 'active' : '' ?>">COLLECTIONS</a></li>
                <li class="nav-item"><a href="shop.php?sale=1" class="nav-link sale-tag <?= isset($_GET['sale']) ? 'active' : '' ?>">SALE</a></li>
            </ul>
        </nav>

        <!-- Right Side Icons -->
        <div class="header-actions">
            <!-- Search Icon -->
            <button class="action-btn" id="searchOpenBtn" aria-label="Open Search">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.8" fill="none">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>

            <!-- User Account -->
            <a href="<?= isLoggedIn() ? 'account.php' : 'login.php' ?>" class="action-btn" aria-label="User Account" title="<?= isLoggedIn() ? 'My Account (' . e($_SESSION['user_name'] ?? '') . ')' : 'Login / Register' ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.8" fill="none">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>

            <!-- Wishlist -->
            <a href="wishlist.php" class="action-btn wishlist-icon" aria-label="Wishlist">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.8" fill="none">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.72-8.72 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <?php if ($wishlistCount > 0): ?>
                    <span class="badge" id="wishlistBadge"><?= $wishlistCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Cart -->
            <a href="cart.php" class="action-btn cart-icon" aria-label="Shopping Cart">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.8" fill="none">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span class="badge" id="cartBadge"><?= $cartCount ?></span>
            </a>
        </div>
    </div>
</header>

<!-- Mobile Overlay Navigation Drawer -->
<div class="mobile-drawer-overlay" id="mobileOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <div class="drawer-header">
        <span class="drawer-logo">KANDY CO.</span>
        <button class="drawer-close" id="mobileDrawerClose">&times;</button>
    </div>
    <ul class="drawer-nav">
        <li><a href="index.php">HOME</a></li>
        <li><a href="shop.php">ALL SHOP</a></li>
        <li><a href="shop.php?new=1">NEW ARRIVALS</a></li>
        <li><a href="shop.php?gender=men">MEN</a></li>
        <li><a href="shop.php?gender=women">WOMEN</a></li>
        <li><a href="shop.php?cat=hoodies">HOODIES & SWEATS</a></li>
        <li><a href="shop.php?cat=tshirts">T-SHIRTS</a></li>
        <li><a href="shop.php?cat=pants">PANTS & CARGOS</a></li>
        <li><a href="shop.php?cat=jackets">JACKETS & OUTERWEAR</a></li>
        <li><a href="shop.php?sale=1" class="sale-tag">SALE</a></li>
    </ul>
    <div class="drawer-footer">
        <?php if (isLoggedIn()): ?>
            <a href="account.php" class="btn btn-outline full-width">MY ACCOUNT</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary full-width">LOGIN / REGISTER</a>
        <?php endif; ?>
    </div>
</div>

<!-- Full-Screen Search Modal -->
<div class="search-overlay" id="searchOverlay">
    <div class="search-modal">
        <button class="search-close" id="searchCloseBtn">&times;</button>
        <form action="shop.php" method="GET" class="search-form">
            <label for="searchInput" class="search-label">SEARCH KANDY CO.</label>
            <div class="search-input-wrapper">
                <input type="text" name="q" id="searchInput" placeholder="Search for T-Shirt, Hoodie, Black, Oversized..." autocomplete="off">
                <button type="submit" class="search-submit-btn">SEARCH</button>
            </div>
        </form>
        <div class="search-suggestions">
            <span>POPULAR SEARCHES:</span>
            <a href="shop.php?q=Oversized">Oversized</a>
            <a href="shop.php?q=Hoodie">Hoodie</a>
            <a href="shop.php?q=Black">Black</a>
            <a href="shop.php?q=Linen">Linen Shirt</a>
            <a href="shop.php?q=Cargo">Cargo Pants</a>
        </div>
    </div>
</div>

<!-- Flash Toast Notifications -->
<?php
$flash = getFlash();
if ($flash):
?>
<div class="flash-toast flash-<?= e($flash['type']) ?>" id="flashToast">
    <span><?= e($flash['message']) ?></span>
    <button onclick="document.getElementById('flashToast').remove();">&times;</button>
</div>
<?php endif; ?>
