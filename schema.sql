-- ============================================================
-- Tannparts Database Schema — 3NF
-- Run: mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS tannparts
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tannparts;

-- -------------------------------------------------------
-- USERS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(80)  NOT NULL,
    last_name     VARCHAR(80)  NOT NULL,
    user_email    VARCHAR(191) NOT NULL UNIQUE,
    user_password VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO users (user_id, first_name, last_name, user_email, user_password) VALUES
    (1, 'John',  'Doe',       'john@example.com',  '$2y$10$placeholder_john'),
    (2, 'Jane',  'Doe',       'jane@example.com',  '$2y$10$placeholder_jane'),
    (3, 'Ada',   'Wong',      'ada@example.com',   '$2y$10$placeholder_ada'),
    (4, 'Leon',  'Kennedy',   'leon@example.com',  '$2y$10$placeholder_leon'),
    (5, 'Mike',  'Hawk',      'mike@example.com',  '$2y$10$placeholder_mike'),
    (6, 'Nate',  'Higgerson', 'nate@example.com',  '$2y$10$placeholder_nate');

-- -------------------------------------------------------
-- CATEGORIES
-- category_id (was: cat_id), category_name (was: cat_name)
-- category_slug (was: slug)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(80)  NOT NULL,
    category_slug VARCHAR(80)  NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (category_id, category_name, category_slug) VALUES
    (1, 'CPUs',         'CPU'),
    (2, 'GPUs',         'GPU'),
    (3, 'Memory',       'Memory'),
    (4, 'Storage',      'Storage'),
    (5, 'Cooling',      'Cooling'),
    (6, 'Motherboards', 'Motherboard');

-- -------------------------------------------------------
-- BRANDS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS brands (
    brand_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(80)  NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO brands (brand_id, brand_name) VALUES
    (1, 'Intel'),
    (2, 'AMD'),
    (3, 'NVIDIA'),
    (4, 'Corsair'),
    (5, 'G.Skill'),
    (6, 'Samsung'),
    (7, 'NZXT'),
    (8, 'ASUS'),
    (9, 'MSI');

-- -------------------------------------------------------
-- PRODUCTS
-- category_id FK (was: cat_id), product_stock (was: stock)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    product_id        INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    product_name      VARCHAR(191)  NOT NULL,
    brand_id          INT UNSIGNED  NOT NULL,
    category_id       INT UNSIGNED  NOT NULL,
    product_price     DECIMAL(10,2) NOT NULL,
    product_old_price DECIMAL(10,2) DEFAULT NULL,
    rating            DECIMAL(3,1)  DEFAULT 0.0,
    review_count      INT UNSIGNED  DEFAULT 0,
    badge             VARCHAR(20)   DEFAULT NULL,
    product_stock     INT UNSIGNED  DEFAULT 100,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id)    REFERENCES brands(brand_id)        ON UPDATE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO products
    (product_id, product_name, brand_id, category_id, product_price, product_old_price, rating, review_count, badge, product_stock)
VALUES
    (1,  'Intel Core i9-14900K',             1, 1,  499.99,  569.99, 4.8, 2341, 'HOT',  50),
    (2,  'AMD Ryzen 9 7950X',                2, 1,  549.99,  699.99, 4.9, 1892, 'SALE', 40),
    (3,  'NVIDIA RTX 4090',                  3, 2, 1599.99, 1799.99, 4.9,  987, 'HOT',  20),
    (4,  'AMD Radeon RX 7900 XTX',           2, 2,  899.99,  999.99, 4.7,  876, 'SALE', 30),
    (5,  'Corsair Vengeance 32GB DDR5-6000', 4, 3,  119.99,  149.99, 4.8, 3421, 'SALE', 80),
    (6,  'G.Skill Trident Z5 RGB 64GB DDR5', 5, 3,  229.99,    NULL, 4.9,  765, 'NEW',  60),
    (7,  'Samsung 990 Pro 2TB NVMe',         6, 4,  149.99,  189.99, 4.9, 4123, 'SALE', 90),
    (8,  'NZXT Kraken 360 AIO',              7, 5,  179.99,  219.99, 4.7, 1432, 'SALE', 35),
    (9,  'ASUS ROG Maximus Z790',            8, 6,  699.99,  799.99, 4.8,  543, 'SALE', 25),
    (10, 'MSI MAG B650 Tomahawk',            9, 6,  219.99,    NULL, 4.7, 1234, 'NEW',  45);

-- -------------------------------------------------------
-- CART ITEMS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    cart_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    product_id    INT UNSIGNED NOT NULL,
    cart_item_qty INT UNSIGNED NOT NULL DEFAULT 1,
    added_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- ORDERS
-- order_subtotal, order_shipping, order_total, order_status
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    order_id       INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED  NOT NULL,
    order_subtotal DECIMAL(10,2) NOT NULL,
    order_shipping DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_total    DECIMAL(10,2) NOT NULL,
    order_status   ENUM('pending','processing','shipped','delivered','cancelled')
                   NOT NULL DEFAULT 'pending',
    placed_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO orders
    (order_id, user_id, order_subtotal, order_shipping, order_total, order_status, placed_at)
VALUES
    (1, 1, 1599.94,  198.06, 1798.00, 'delivered',  '2025-06-01 00:00:00'),
    (2, 2, 1599.99,   67.00, 1666.99, 'shipped',    '2025-06-03 00:00:00'),
    (3, 3,  745.94,  420.00, 1165.94, 'pending',    '2025-06-10 00:00:00'),
    (4, 4, 2419.95,   69.00, 2488.95, 'processing', '2025-06-12 00:00:00'),
    (5, 5,  919.96,    0.00,  919.96, 'cancelled',  '2025-06-15 00:00:00'),
    (6, 6,  219.99,    3.00,  222.99, 'pending',    '2025-06-16 00:00:00'),
    (7, 1, 2439.89,    6.00, 2445.89, 'shipped',    '2025-06-17 00:00:00'),
    (8, 4, 3199.98,    9.00, 3208.98, 'shipped',    '2025-06-18 00:00:00');

-- -------------------------------------------------------
-- ORDER ITEMS
-- Composite PK (order_id, product_id)
-- order_items_price, order_items_qty, order_items_total
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    order_id          INT UNSIGNED  NOT NULL,
    product_id        INT UNSIGNED  NOT NULL,
    order_items_price DECIMAL(10,2) NOT NULL,
    order_items_qty   INT UNSIGNED  NOT NULL,
    order_items_total DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (order_id, product_id),
    FOREIGN KEY (order_id)   REFERENCES orders(order_id)     ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT IGNORE INTO order_items
    (order_id, product_id, order_items_price, order_items_qty, order_items_total)
VALUES
    (1, 1,  499.99, 2,  999.98),
    (1, 7,  149.99, 4,  599.96),
    (2, 3, 1599.99, 1, 1599.99),
    (3, 5,  119.99, 6,  719.94),
    (3, 8,  179.99, 2,  359.98),
    (4, 2,  549.99, 4, 2199.96),
    (4, 10, 219.99, 1,  219.99),
    (5, 6,  229.99, 4,  919.96),
    (6, 10, 219.99, 1,  219.99),
    (7, 6,  229.99, 3,  689.97),
    (7, 7,  149.99, 7, 1049.93),
    (7, 9,  699.99, 1,  699.99),
    (8, 3, 1599.99, 2, 3199.98);

-- -------------------------------------------------------
-- VIEW: products_full
-- Used by all PHP API files. Aliases columns back to the
-- names app.js expects: id, name, brand, category_slug,
-- price, old_price, rating, review_count, badge, stock
-- -------------------------------------------------------
CREATE OR REPLACE VIEW products_full AS
    SELECT
        p.product_id          AS id,
        p.product_name        AS name,
        b.brand_name          AS brand,
        b.brand_id            AS brand_id,
        c.category_slug       AS category_slug,
        c.category_id         AS cat_id,
        p.product_price       AS price,
        p.product_old_price   AS old_price,
        p.rating,
        p.review_count,
        p.badge,
        p.product_stock       AS stock,
        p.created_at
    FROM      products   p
    JOIN      brands     b ON b.brand_id    = p.brand_id
    JOIN      categories c ON c.category_id = p.category_id;
