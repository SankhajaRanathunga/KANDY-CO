-- KANDY CO. E-Commerce Database Schema (LKR Currency)
-- Database: `kandy_co`

CREATE DATABASE IF NOT EXISTS `kandy_co` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kandy_co`;

-- Drop existing tables in reverse foreign key order
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS TABLE
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `role` ENUM('customer', 'admin') DEFAULT 'customer',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. ADMINS TABLE
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. CATEGORIES TABLE
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. PRODUCTS TABLE
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `description` TEXT NOT NULL,
  `short_description` TEXT DEFAULT NULL,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `gender` ENUM('Men', 'Women', 'Unisex') DEFAULT 'Unisex',
  `price` DECIMAL(10,2) NOT NULL,
  `sale_price` DECIMAL(10,2) DEFAULT NULL,
  `brand` VARCHAR(50) DEFAULT 'KANDY CO.',
  `status` ENUM('Active', 'Draft', 'Out of Stock') DEFAULT 'Active',
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_new_arrival` TINYINT(1) DEFAULT 0,
  `is_on_sale` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. PRODUCT IMAGES TABLE
CREATE TABLE `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. PRODUCT VARIANTS TABLE
CREATE TABLE `product_variants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `size` VARCHAR(20) NOT NULL,
  `color` VARCHAR(50) NOT NULL,
  `color_hex` VARCHAR(10) DEFAULT '#000000',
  `sku` VARCHAR(60) DEFAULT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `additional_price` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. CART TABLE
CREATE TABLE `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `session_id` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. CART ITEMS TABLE
CREATE TABLE `cart_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cart_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cart_id`) REFERENCES `cart`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. ORDERS TABLE
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `shipping_fee` DECIMAL(10,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'credit_card',
  `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
  `order_status` ENUM('Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. ORDER ITEMS TABLE
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` INT DEFAULT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `color` VARCHAR(50) DEFAULT NULL,
  `size` VARCHAR(20) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. PAYMENTS TABLE
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(30) DEFAULT 'completed',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. ADDRESSES TABLE
CREATE TABLE `addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `address_line` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. WISHLIST TABLE
CREATE TABLE `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_product_unique` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. COUPONS TABLE
CREATE TABLE `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('fixed', 'percentage') DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_order_amount` DECIMAL(10,2) DEFAULT 0.00,
  `expiry_date` DATE DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. REVIEWS TABLE
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `review_text` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `status`) VALUES
(1, 'T-Shirts', 'tshirts', 'Oversized heavy cotton tees designed for minimal streetwear statement.', 'Active'),
(2, 'Hoodies', 'hoodies', 'Premium plush heavyweight hoodies with relaxed modern silhouettes.', 'Active'),
(3, 'Shirts', 'shirts', 'Clean tailored & relaxed button-down shirts.', 'Active'),
(4, 'Pants', 'pants', 'Structured cargo & relaxed fit trousers.', 'Active'),
(5, 'Jackets', 'jackets', 'Architectural outerwear, jackets, and puffers.', 'Active'),
(6, 'Accessories', 'accessories', 'Signature caps, bags, and luxury everyday essentials.', 'Active');

-- Seed Admins & Users
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `role`) VALUES
(1, 'KANDY Administrator', 'admin@kandyco.com', '$2y$10$qS37H28Z7p1mXgP1.yZJ9.7xM.Rz0R8sK/gD1xJ6wA8vB2c3d4e5f', '+18005550199', 'admin'),
(2, 'Alex Turner', 'alex@example.com', '$2y$10$qS37H28Z7p1mXgP1.yZJ9.7xM.Rz0R8sK/gD1xJ6wA8vB2c3d4e5f', '+18005550122', 'customer');

INSERT INTO `admins` (`id`, `user_id`, `username`, `password`, `full_name`, `email`) VALUES
(1, 1, 'admin', '$2y$10$qS37H28Z7p1mXgP1.yZJ9.7xM.Rz0R8sK/gD1xJ6wA8vB2c3d4e5f', 'KANDY Administrator', 'admin@kandyco.com');

-- Seed Products (LKR Pricing)
INSERT INTO `products` (`id`, `category_id`, `title`, `slug`, `description`, `short_description`, `price`, `sale_price`, `brand`, `sku`, `status`, `is_featured`, `is_new_arrival`, `is_on_sale`, `gender`) VALUES
(1, 1, 'KANDY Heavyweight Oversized Tee', 'kandy-heavyweight-oversized-tee', 'Crafted from 280GSM combed organic cotton with dropped shoulders and a thick ribbed collar. Minimal KANDY CO. embroidery on left chest.', '280GSM combed organic cotton boxy tee.', 4500.00, 3800.00, 'KANDY CO.', 'KND-TSH-001', 'Active', 1, 1, 1, 'Unisex'),
(2, 2, 'KANDY Essential Minimal Hoodie', 'kandy-essential-minimal-hoodie', 'Ultra-soft 450GSM French terry hoodie featuring double-layer hood, hidden side seam pockets, and subtle high-density brand logo.', '450GSM heavy French terry plush hoodie.', 9800.00, NULL, 'KANDY CO.', 'KND-HUD-001', 'Active', 1, 1, 0, 'Unisex'),
(3, 3, 'KANDY Relaxed Linen Shirt', 'kandy-relaxed-linen-shirt', 'Lightweight premium linen blend shirt with mother-of-pearl buttons. Designed for clean summer layering.', 'Breathable organic linen blend shirt.', 6500.00, 5200.00, 'KANDY CO.', 'KND-SHR-001', 'Active', 0, 1, 1, 'Men'),
(4, 4, 'KANDY Structured Utility Cargo Pants', 'kandy-structured-utility-cargo-pants', 'Heavy cotton twill cargo trousers with adjustable drawstrings at cuff, multi-pocket detailing, and tailored relaxed fit.', 'Heavy twill multi-pocket utility cargo trousers.', 8200.00, NULL, 'KANDY CO.', 'KND-PNT-001', 'Active', 1, 0, 0, 'Unisex'),
(5, 5, 'KANDY Classic Wool Blend Jacket', 'kandy-classic-wool-blend-jacket', 'Structured minimalist jacket crafted from rich wool blend with heavy satin lining and custom metal zipper pulls.', 'Tailored wool blend jacket with satin lining.', 16500.00, 14000.00, 'KANDY CO.', 'KND-JCK-001', 'Active', 1, 1, 1, 'Men'),
(6, 6, 'KANDY Signature Embroidered Cap', 'kandy-signature-embroidered-cap', '6-panel dad hat built from washed cotton twill with low-profile crown and brass adjustable strap slider.', 'Washed cotton dad hat with brass hardware.', 3200.00, NULL, 'KANDY CO.', 'KND-ACC-001', 'Active', 0, 1, 0, 'Unisex');

-- Seed Product Images
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`) VALUES
(1, 1, 'uploads/products/1/black-oversized-tee-front.jpg', 1, 1),
(2, 1, 'uploads/products/1/black-oversized-tee-back.jpg', 0, 2),
(3, 2, 'uploads/products/2/essential-hoodie-front.jpg', 1, 1),
(4, 3, 'uploads/products/3/linen-shirt-front.jpg', 1, 1),
(5, 4, 'uploads/products/4/cargo-pants-front.jpg', 1, 1),
(6, 5, 'uploads/products/5/wool-jacket-front.jpg', 1, 1),
(7, 6, 'uploads/products/6/signature-cap-front.jpg', 1, 1);

-- Seed Product Variants
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `color_hex`, `sku`, `stock_quantity`, `additional_price`) VALUES
(1, 1, 'S', 'Black', '#0A0A0A', 'KND-TSH-001-BLK-S', 12, 0.00),
(2, 1, 'M', 'Black', '#0A0A0A', 'KND-TSH-001-BLK-M', 20, 0.00),
(3, 1, 'L', 'Black', '#0A0A0A', 'KND-TSH-001-BLK-L', 15, 0.00),
(4, 1, 'M', 'White', '#FFFFFF', 'KND-TSH-001-WHT-M', 18, 0.00),
(5, 2, 'M', 'Black', '#0A0A0A', 'KND-HUD-001-BLK-M', 14, 0.00),
(6, 2, 'L', 'Black', '#0A0A0A', 'KND-HUD-001-BLK-L', 10, 0.00),
(7, 3, 'M', 'White', '#FFFFFF', 'KND-SHR-001-WHT-M', 9, 0.00),
(8, 4, 'M', 'Black', '#0A0A0A', 'KND-PNT-001-BLK-M', 15, 0.00),
(9, 5, 'L', 'Black', '#0A0A0A', 'KND-JCK-001-BLK-L', 6, 0.00),
(10, 6, 'One Size', 'Black', '#0A0A0A', 'KND-ACC-001-BLK-OS', 25, 0.00);

-- Seed Coupons (LKR Values)
INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `expiry_date`, `status`) VALUES
(1, 'KANDY10', 'percentage', 10.00, 5000.00, '2027-12-31', 'active'),
(2, 'WELCOME20', 'fixed', 1500.00, 10000.00, '2027-12-31', 'active');
