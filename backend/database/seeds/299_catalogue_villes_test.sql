-- Données Dev 2 de développement : à exécuter une seule fois sur une base de test.
INSERT INTO cities (name, delivery_fee, delivery_delay_hours, is_active) VALUES
('Likasi', 3000, 48, 1), ('Lubumbashi', 2000, 24, 1), ('Kolwezi', 5000, 72, 1);

INSERT INTO products (name, description, photo_url, is_active) VALUES
('Yaourt AMARIIZ', 'Yaourt frais AMARIIZ, disponible avec ou sans sucre.', NULL, 1);

INSERT INTO product_variants (product_id, composition, flavor, format, price, stock_qty, low_stock_threshold, is_available) VALUES
(LAST_INSERT_ID(), 'avec_sucre', 'banane', 'petit', 2500, 50, 5, 1),
(LAST_INSERT_ID(), 'avec_sucre', 'vanille', 'petit', 2500, 50, 5, 1),
(LAST_INSERT_ID(), 'sans_sucre', 'banane', 'petit', 2500, 50, 5, 1),
(LAST_INSERT_ID(), 'sans_sucre', 'vanille', 'petit', 2500, 50, 5, 1),
(LAST_INSERT_ID(), 'avec_sucre', 'banane', 'grand', 5000, 30, 5, 1),
(LAST_INSERT_ID(), 'avec_sucre', 'vanille', 'grand', 5000, 30, 5, 1),
(LAST_INSERT_ID(), 'sans_sucre', 'banane', 'grand', 5000, 30, 5, 1),
(LAST_INSERT_ID(), 'sans_sucre', 'vanille', 'grand', 5000, 30, 5, 1);
