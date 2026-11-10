-- Périmètre Dev 2 : catalogue et villes. À exécuter avant 102_orders_payments.sql.
CREATE TABLE IF NOT EXISTS cities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  delivery_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  delivery_delay_hours INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  photo_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_variants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  composition ENUM('avec_sucre','sans_sucre') NOT NULL,
  flavor ENUM('banane','vanille') NOT NULL,
  format ENUM('petit','grand') NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  stock_qty INT UNSIGNED NOT NULL DEFAULT 0,
  low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 5,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uq_variant (product_id, composition, flavor, format),
  INDEX idx_variants_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
