# KANDY CO. — Luxury & Minimalist Streetwear E-Commerce

> **A modern, high-performance fashion e-commerce web application engineered with PHP, MySQL, and a custom minimalist luxury design system.**

---

## 📌 Table of Contents
- [Overview](#-overview)
- [Key Features](#-key-features)
  - [Storefront Experience](#storefront-experience)
  - [Admin Control Center](#admin-control-center)
- [Technology Stack](#-technology-stack)
- [Default Login Credentials](#-default-login-credentials)
- [Installation & Local Setup Guide](#-installation--local-setup-guide)
- [Project Directory Structure](#-project-directory-structure)
- [Database Schema & Architecture](#-database-schema--architecture)
- [Security & Engineering Standards](#-security--engineering-standards)
- [License](#-license)

---

## 🌟 Overview

**KANDY CO.** is a full-featured apparel e-commerce web application inspired by architectural minimalism and luxury streetwear aesthetics. It offers a smooth shopping experience with dynamic catalog filtering, shopping cart, wishlist management, multi-step checkout, and a full administrator dashboard for managing products, inventory, variants, categories, and customer orders.

---

## ✨ Key Features

### Storefront Experience
* **Discover Collection**: Clean, text-based navigation for browsing apparel lines (Men, Women, T-Shirts, Hoodies, Pants, Jackets, Shirts, Accessories).
* **Curated New Arrivals**: Live dynamic showcase of seasonal drops, sale badges, and pricing.
* **Filterable Catalog (`shop.php`)**: Filter by category, gender, size, color, price range, and sort order.
* **Instant Search Modal**: Keyword-based full-catalog search with autocomplete suggestions.
* **Product Details (`product.php`)**: Multi-angle image galleries, variant selection (size, color with visual swatches), stock checking, and customer reviews.
* **Shopping Cart & Wishlist**: Session-persistent cart system and authenticated wishlist.
* **Checkout Flow (`checkout.php`)**: Shipping address capture, coupon code discounts, and order confirmation tracking.
* **Customer Portal (`account.php`)**: Profile management, order history, and security updates.

### Admin Control Center
* **Live Analytics Dashboard**: Revenue metrics, order volume, catalog counts, and recent transactions.
* **Product Management**: Create, edit, and delete products with primary/secondary image uploads and SKU tracking.
* **Variant & Inventory Control**: Manage multi-variant stock quantities (Sizes: S, M, L, XL, XXL; Colors with hex swatches).
* **Category Management**: Organize apparel lines and collections.
* **Order Processing**: Real-time status updates (*Pending*, *Processing*, *Shipped*, *Delivered*, *Cancelled*).
* **Customer Directory**: View registered user profiles and order totals.

---

## 🛠 Technology Stack

* **Backend**: PHP 
* **Database**: MySQL / MariaDB (`kandy_co`)
* **Frontend**: HTML5, Vanilla CSS , JavaScript
* **Server Environment**: Apache (XAMPP / WAMP / LEMP / LAMP)

---

## 🔑 Default Login Credentials

### 1. Administrator Portal
* **URL**: `http://localhost/kandy-co/admin/login.php`
* **Username / Email**: `admin` or `admin@kandyco.com`
* **Password**: `admin123`

### 2. Customer Account (Demo)
* **URL**: `http://localhost/kandy-co/login.php`
* **Email**: `alex@example.com`
* **Password**: `password` *(or register a new user at `register.php`)*

---

## 🚀 Installation & Local Setup Guide

Follow these steps to run **KANDY CO.** locally on your machine using **XAMPP**:

### Step 1: Clone or Copy the Repository
Clone the repository into your local server's web root directory (for XAMPP on Windows, typically `C:\xampp\htdocs\`):

```bash
cd C:\xampp\htdocs
git clone https://github.com/your-username/kandy-co.git
```

### Step 2: Start Apache & MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for both **Apache** and **MySQL**.

### Step 3: Import the Database
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click **New** on the left sidebar and create a database named:
   ```
   kandy_co
   ```
3. Select the `kandy_co` database, go to the **Import** tab.
4. Click **Choose File** and select:
   ```
   kandy-co/database/kandy_co.sql
   ```
5. Click **Import** (or **Go**) at the bottom of the page.

*(Alternatively, via command line:)*
```bash
mysql -u root -p kandy_co < database/kandy_co.sql
```

### Step 4: Verify Database Configuration
Check [config/database.php](file:///c:/xampp/htdocs/kandy-co/config/database.php) to ensure your local MySQL credentials match:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kandy_co');
define('DB_USER', 'root');
define('DB_PASS', ''); // default is empty in XAMPP
```

### Step 5: Launch the Application
Open your browser and visit:
* **Storefront**: [http://localhost/kandy-co/](http://localhost/kandy-co/)
* **Admin Portal**: [http://localhost/kandy-co/admin/login.php](http://localhost/kandy-co/admin/login.php)

---

## 📁 Project Directory Structure

```text
kandy-co/
├── admin/                    # Administrator control panel
│   ├── add-product.php       # Create product with variants & images
│   ├── categories.php        # Manage categories
│   ├── customers.php         # Customer directory
│   ├── edit-product.php      # Edit catalog items
│   ├── index.php             # Admin metrics dashboard
│   ├── login.php             # Admin authentication
│   ├── logout.php            # Admin session termination
│   ├── orders.php            # Order tracking & fulfillment
│   ├── products.php          # Product catalog table
│   └── settings.php          # Store configuration
├── assets/
│   ├── css/
│   │   └── style.css         # Global design tokens & styling
│   ├── images/               # Brand graphics & icons
│   └── js/                   # Interactive scripts
├── config/
│   └── database.php          # Centralized PDO MySQL connection
├── database/
│   └── kandy_co.sql          # Full database schema and seed data
├── includes/
│   ├── auth.php              # Authentication & session helpers
│   ├── footer.php            # Site-wide footer component
│   ├── functions.php         # Utility helpers, cart, and formatting
│   ├── header.php            # HTML head, fonts, & metadata
│   └── navbar.php            # Navigation header & search drawer
├── uploads/
│   └── products/             # Uploaded product imagery
├── account.php               # Customer profile & order history
├── cart.php                  # Shopping cart management
├── checkout.php              # Checkout & payment processing
├── contact.php               # Contact & customer support page
├── index.php                 # Storefront homepage (Discover & New Arrivals)
├── login.php                 # Customer authentication
├── order-confirmation.php    # Post-checkout receipt & summary
├── product.php               # Product detail & review page
├── register.php              # Customer registration
├── shop.php                  # Filterable product catalog
├── wishlist.php              # Wishlist management
└── README.md                 # Project documentation
```

---

## 🗄 Database Schema & Architecture

The database `kandy_co` contains the following relational tables:
* `users` & `admins` — Customer accounts and administrator credentials.
* `categories` — Product classifications with SEO slugs.
* `products` — Base product records (price, sale price, gender, brand, status).
* `product_images` — Primary & secondary product gallery image paths.
* `product_variants` — Sizing, color hex codes, SKUs, and stock quantities.
* `cart` & `cart_items` — Database-backed persistent customer carts.
* `orders` & `order_items` — Processed customer orders, items, and billing details.
* `payments` — Transaction records and payment methods.
* `addresses` — Customer shipping and billing address book.
* `wishlist` — Customer saved favorites.
* `coupons` — Promotional discount codes (fixed and percentage).
* `reviews` — Customer product ratings and verified reviews.

---

## 🔒 Security & Engineering Standards

* **Prepared Statements**: All database operations use PDO parameterized queries to prevent SQL Injection.
* **Password Hashing**: Passwords stored using `PASSWORD_BCRYPT` via PHP's native `password_hash()`.
* **XSS Sanitization**: User-rendered output passed through `htmlspecialchars()` via helper `e()`.
* **Role-Based Access Control**: Strict session isolation between customer (`$_SESSION['user_id']`) and administrator (`$_SESSION['admin_logged_in']`) scopes.
* **Responsive Architecture**: Fluid viewport scaling supporting Mobile (320px+), Tablet, and Desktop displays.

---

## 📄 License

This project is created for **KANDY CO.** All rights reserved.
