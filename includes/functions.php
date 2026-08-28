<?php
/**
 * KANDY CO. - Helper & Utility Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape HTML output to prevent XSS attacks
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency price in Sri Lankan Rupees (LKR)
 * e.g., 4500 -> LKR 4,500
 */
function formatPrice($amount) {
    if ($amount === null || $amount === '') {
        return 'LKR 0';
    }
    $val = (float)$amount;
    if (floor($val) == $val) {
        return 'LKR ' . number_format($val, 0, '.', ',');
    }
    return 'LKR ' . number_format($val, 2, '.', ',');
}

/**
 * Base URL generator
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
    $baseUrl = $protocol . "://" . $host . $scriptDir;
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

/**
 * Get Image URL with resilient local fallback placeholder
 */
function getImageUrl($path, $fallbackTitle = 'KANDY CO.') {
    if (!empty($path)) {
        $cleanPath = str_replace('\\', '/', $path);
        $baseDir = __DIR__ . '/../';
        $fullSystemPath = $baseDir . ltrim($cleanPath, '/');
        
        if (file_exists($fullSystemPath) && !is_dir($fullSystemPath)) {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            if (basename($scriptDir) === 'admin') {
                return '../' . ltrim($cleanPath, '/');
            }
            return ltrim($cleanPath, '/');
        }
    }

    $title = strtoupper(substr($fallbackTitle, 0, 24));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="750" viewBox="0 0 600 750">
        <rect width="100%" height="100%" fill="#141416"/>
        <rect x="20" y="20" width="560" height="710" fill="none" stroke="#27272A" stroke-width="1"/>
        <text x="50%" y="46%" dominant-baseline="middle" text-anchor="middle" fill="#52525B" font-family="Outfit, sans-serif" font-weight="700" font-size="22" letter-spacing="6">KANDY CO.</text>
        <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#A1A1AA" font-family="Inter, sans-serif" font-weight="400" font-size="14" letter-spacing="2">' . htmlspecialchars($title) . '</text>
    </svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Flash Message Handler
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Cart Session Management
 */
function getSessionCart() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function getCartCount() {
    $cart = getSessionCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += (int)($item['quantity'] ?? 0);
    }
    return $count;
}

/**
 * Wishlist Helpers
 */
function isInWishlist($pdo, $userId, $productId) {
    if (!$userId) return false;
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    return (bool)$stmt->fetch();
}

function getWishlistCount($pdo, $userId) {
    if (!$userId) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
