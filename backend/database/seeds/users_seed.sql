-- Mot de passe temporaire pour les deux comptes : ChangeMe!2026
INSERT INTO users (name, phone, email, password_hash, role) VALUES
('Administrateur AMARIIZ', '+243000000001', 'admin@amariiz.local', '$2y$10$yRa4vpZw/9wsAu3lgWyQ1ORf0ARtiBtlNQixAjtkXOagZxkD9k6Qq', 'admin'),
('Vendeur AMARIIZ', '+243000000002', 'vendeur@amariiz.local', '$2y$10$yRa4vpZw/9wsAu3lgWyQ1ORf0ARtiBtlNQixAjtkXOagZxkD9k6Qq', 'vendeur')
ON DUPLICATE KEY UPDATE name=VALUES(name);
