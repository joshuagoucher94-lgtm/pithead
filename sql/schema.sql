-- PITHEAD ROASTWORKS — MySQL 8 / MariaDB 10.6+
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS stripe_events;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS wholesale_enquiries;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(128) NOT NULL,
  name VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  slug VARCHAR(128) NOT NULL,
  name VARCHAR(255) NOT NULL,
  tagline VARCHAR(255) DEFAULT NULL,
  short_description TEXT,
  long_description TEXT,
  price_cents INT UNSIGNED NOT NULL,
  currency VARCHAR(8) NOT NULL DEFAULT 'gbp',
  weight_label VARCHAR(32) DEFAULT NULL,
  sku VARCHAR(64) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  brew_suggestions TEXT,
  specs JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_slug (slug),
  KEY idx_products_category (category_id),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  path VARCHAR(512) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_product_images_product (product_id),
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_customers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_number VARCHAR(64) NOT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  status ENUM('draft','pending_payment','paid','failed','cancelled') NOT NULL DEFAULT 'pending_payment',
  subtotal_cents INT UNSIGNED NOT NULL,
  shipping_cents INT UNSIGNED NOT NULL DEFAULT 0,
  total_cents INT UNSIGNED NOT NULL,
  currency VARCHAR(8) NOT NULL DEFAULT 'gbp',
  stripe_checkout_session_id VARCHAR(255) DEFAULT NULL,
  stripe_payment_intent_id VARCHAR(255) DEFAULT NULL,
  stripe_customer_id VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_number (order_number),
  KEY idx_orders_status (status),
  KEY idx_orders_stripe_session (stripe_checkout_session_id),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  name_snapshot VARCHAR(255) NOT NULL,
  unit_price_cents INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wholesale_enquiries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_name VARCHAR(255) NOT NULL,
  contact_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(64) DEFAULT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE site_settings (
  `key` VARCHAR(128) NOT NULL,
  `value` TEXT,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stripe_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  stripe_event_id VARCHAR(255) NOT NULL,
  type VARCHAR(128) NOT NULL,
  payload JSON NOT NULL,
  processed_at TIMESTAMP NULL DEFAULT NULL,
  processing_error TEXT,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stripe_events_id (stripe_event_id),
  KEY idx_stripe_events_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed categories
INSERT INTO categories (slug, name, sort_order, is_active) VALUES
('coffee', 'Coffee', 10, 1),
('cacao', 'Cacao', 20, 1),
('drinks', 'Drinks', 30, 1);

-- Seed products (coffee category id = 1)
INSERT INTO products (category_id, slug, name, tagline, short_description, price_cents, currency, weight_label, sku, is_active, is_featured, brew_suggestions, specs) VALUES
(1, 'heavy-fuel', 'HEAVY FUEL', 'High torque espresso', 'Built for milk. Heavy body. Bitter chocolate.', 1299, 'gbp', '250g', 'PH-HF-250', 1, 1,
 '18g in / 36g out · 28–32s · 93°C. Designed for dense milk drinks.',
 JSON_OBJECT('origin', 'Blend', 'roast', 'Espresso')),
(1, 'amber', 'AMBER', 'Balanced, sweet espresso', 'Sweet balance. Clean finish. Milk-forward.', 1299, 'gbp', '250g', 'PH-AM-250', 1, 1,
 '18g in / 36g out · 27–31s · 92°C.',
 JSON_OBJECT('origin', 'Blend', 'roast', 'Espresso')),
(1, 'decaf-no-1', 'DECAF No.1', 'Engineered decaf', 'Same discipline. No caffeine.', 1399, 'gbp', '250g', 'PH-DC-250', 1, 1,
 'Treat like espresso. Slightly finer acceptable.',
 JSON_OBJECT('origin', 'Blend', 'roast', 'Espresso', 'decaf', 'Yes'));

INSERT INTO product_images (product_id, path, sort_order, is_primary) VALUES
(1, '/assets/products/heavy-fuel.svg', 0, 1),
(2, '/assets/products/amber.svg', 0, 1),
(3, '/assets/products/decaf.svg', 0, 1);

-- Default admin (dev only): admin@pithead.local / password — rotate immediately in production
INSERT INTO admins (email, password_hash) VALUES
('admin@pithead.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO site_settings (`key`, `value`) VALUES
('instagram_url', 'https://instagram.com/'),
('location_text', 'Location TBC'),
('order_notification_email', '');
