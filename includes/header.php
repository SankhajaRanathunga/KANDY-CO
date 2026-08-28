<?php
/**
 * KANDY CO. - Global HTML Header Component
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'KANDY CO. | Minimalist Fashion & Premium Apparel';
$pageDescription = $pageDescription ?? 'KANDY CO. - Define your everyday with modern luxury clothing, oversized heavyweight tees, essential hoodies, and tailored streetwear.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?></title>

    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Announcement Bar -->
<div class="announcement-bar">
    <div class="container announcement-content">
        <span>COMPLIMENTARY EXPRESS SHIPPING ON ORDERS OVER LKR 15,000</span>
        <a href="shop.php?sale=1" class="announcement-link">EXPLORE SALE</a>
    </div>
</div>
