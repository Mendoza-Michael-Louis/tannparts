-- ============================================================
-- CoreVault Database Schema
-- Run this file once to set up the database:
--   mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS tannparts
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tannparts;

-- -------------------------------------------------------
-- USERS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80)  NOT NULL,
    last_name  VARCHAR(80)  NOT NULL,
    email      VARCHAR(191) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,          -- bcrypt hash
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- CATEGORIES
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    icon VARCHAR(10) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (name, icon, slug) VALUES
    ('CPUs',         '🔵', 'CPU'),
    ('GPUs',         '💚', 'GPU'),
    ('Memory',       '🟩', 'Memory'),
    ('Storage',      '💾', 'Storage'),
    ('Cooling',      '🌊', 'Cooling'),
    ('Motherboards', '🔌', 'Motherboard');

-- -------------------------------------------------------
-- PRODUCTS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(191) NOT NULL,
    brand        VARCHAR(80)  NOT NULL,
    category_slug VARCHAR(80) NOT NULL,
    price        DECIMAL(10,2) NOT NULL,
    old_price    DECIMAL(10,2) DEFAULT NULL,
    rating       DECIMAL(3,1) DEFAULT 0.0,
    review_count INT UNSIGNED DEFAULT 0,
    icon         VARCHAR(10)  NOT NULL DEFAULT '📦',
    badge        VARCHAR(20)  DEFAULT NULL,     -- HOT | SALE | NEW | NULL
    stock        INT UNSIGNED DEFAULT 100,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_slug) REFERENCES categories(slug) ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO products (id, name, brand, category_slug, price, old_price, rating, review_count, icon, badge, stock) VALUES
    (1,  'Intel Core i9-14900K',             'Intel',   'CPU',         499.99, 569.99, 4.8, 2341, '🔵', 'HOT',  50),
    (2,  'AMD Ryzen 9 7950X',                'AMD',     'CPU',         549.99, 699.99, 4.9, 1892, '🔴', 'SALE', 40),
    (3,  'NVIDIA RTX 4090',                  'NVIDIA',  'GPU',        1599.99,1799.99, 4.9,  987, '💚', 'HOT',  20),
    (4,  'AMD Radeon RX 7900 XTX',           'AMD',     'GPU',         899.99, 999.99, 4.7,  876, '❤️', 'SALE', 30),
    (5,  'Corsair Vengeance 32GB DDR5-6000', 'Corsair', 'Memory',      119.99, 149.99, 4.8, 3421, '🟩', 'SALE', 80),
    (6,  'G.Skill Trident Z5 RGB 64GB DDR5', 'G.Skill', 'Memory',      229.99,   NULL, 4.9,  765, '🌈', 'NEW',  60),
    (7,  'Samsung 990 Pro 2TB NVMe',          'Samsung', 'Storage',     149.99, 189.99, 4.9, 4123, '💾', 'SALE', 90),
    (8,  'NZXT Kraken 360 AIO',              'NZXT',    'Cooling',     179.99, 219.99, 4.7, 1432, '🌊', 'SALE', 35),
    (9,  'ASUS ROG Maximus Z790',             'ASUS',    'Motherboard', 699.99, 799.99, 4.8,  543, '🔌', 'SALE', 25),
    (10, 'MSI MAG B650 Tomahawk',             'MSI',     'Motherboard', 219.99,   NULL, 4.7, 1234, '🔌', 'NEW',  45);

-- -------------------------------------------------------
-- CART (persisted per user — one active cart row per item)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- ORDERS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    subtotal     DECIMAL(10,2) NOT NULL,
    shipping     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total        DECIMAL(10,2) NOT NULL,
    status       ENUM('pending','processing','shipped','delivered','cancelled')
                 NOT NULL DEFAULT 'pending',
    placed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- ORDER ITEMS (snapshot of product at time of purchase)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NOT NULL,
    product_name VARCHAR(191) NOT NULL,
    product_icon VARCHAR(10)  NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    quantity     INT UNSIGNED NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
