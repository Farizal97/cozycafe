USE cozycafe;

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- Kredensial awal: username admin, password password. Segera ganti setelah login pertama.
INSERT IGNORE INTO admins (id, username, password_hash)
VALUES (1, 'admin', '$2y$10$ltIsi2/s0Q2GoY6Fztlkgud3pKpwW6ndhV/K10rrmgZrTn9rXO2EO');

INSERT IGNORE INTO categories (id, name, description, is_active) VALUES
    (1, 'Kopi', 'Pilihan minuman berbasis kopi.', 1),
    (2, 'Non-Kopi', 'Minuman tanpa kopi.', 1),
    (3, 'Makanan', 'Camilan dan makanan pendamping.', 1);

INSERT IGNORE INTO products (
    id, category_id, name, description, price, stock, min_stock,
    min_order_quantity, max_order_quantity, availability_status, is_active, has_order_history
) VALUES
    (1, 1, 'Americano', 'Espresso dengan air panas.', 20000, 30, 5, 1, 10, 'available', 1, 0),
    (2, 1, 'Kopi Susu Cozy', 'Espresso, susu, dan gula aren.', 23000, 30, 5, 1, 10, 'available', 1, 0),
    (3, 2, 'Matcha Latte', 'Minuman matcha dengan susu.', 25000, 20, 5, 1, 10, 'available', 1, 0),
    (4, 2, 'Teh Lemon', 'Teh segar dengan lemon.', 18000, 20, 5, 1, 10, 'available', 1, 0),
    (5, 3, 'Roti Bakar Cokelat', 'Roti panggang dengan cokelat.', 22000, 15, 3, 1, 10, 'available', 1, 0);

INSERT IGNORE INTO booking_areas (id, name, description, is_active) VALUES
    (1, 'Indoor', 'Area dalam ruangan.', 1),
    (2, 'Outdoor', 'Area luar ruangan.', 1),
    (3, 'Smoking', 'Area khusus merokok.', 1),
    (4, 'VIP', 'Area privat CozyCafe.', 1);

INSERT IGNORE INTO tables (id, table_number, area_id, capacity, position_x, position_y, is_active) VALUES
    (1, 'Table 01', 1, 2, 10, 15, 1),
    (2, 'Table 02', 1, 2, 30, 15, 1),
    (3, 'Table 03', 1, 4, 50, 15, 1),
    (4, 'Table 04', 1, 4, 70, 15, 1),
    (5, 'Table 05', 1, 2, 90, 15, 1),
    (6, 'Table 06', 1, 4, 10, 45, 1),
    (7, 'Table 07', 2, 2, 30, 45, 1),
    (8, 'Table 08', 2, 4, 50, 45, 1),
    (9, 'Table 09', 2, 4, 70, 45, 1),
    (10, 'Table 10', 2, 2, 90, 45, 1),
    (11, 'Table 11', 3, 2, 10, 75, 1),
    (12, 'Table 12', 3, 4, 30, 75, 1),
    (13, 'Table 13', 4, 4, 50, 75, 1),
    (14, 'Table 14', 4, 6, 70, 75, 1),
    (15, 'Table 15', 4, 6, 90, 75, 1);

INSERT IGNORE INTO payment_methods (
    id, name, method_type, is_enabled, deadline_minutes, instructions
) VALUES
    (1, 'Cash at Counter', 'cash', 1, 60, 'Pembayaran tunai dilakukan di kasir CozyCafe.'),
    (2, 'Bank Transfer', 'bank_transfer', 1, 30, 'Detail rekening dapat dikonfigurasi admin.'),
    (3, 'QRIS Statis', 'qris_static', 1, 15, 'Pindai QRIS kafe. Pembayaran diverifikasi admin.');

INSERT IGNORE INTO cafe_settings (id, setting_key, setting_value) VALUES
    (1, 'tax_percentage', '10'),
    (2, 'service_charge_percentage', '5'),
    (3, 'booking_confirmation_mode', 'manual'),
    (4, 'table_session_duration_minutes', '180');

INSERT IGNORE INTO operating_hours (id, day_of_week, opening_time, closing_time, is_open) VALUES
    (1, 1, '09:00:00', '22:00:00', 1),
    (2, 2, '09:00:00', '22:00:00', 1),
    (3, 3, '09:00:00', '22:00:00', 1),
    (4, 4, '09:00:00', '22:00:00', 1),
    (5, 5, '09:00:00', '22:00:00', 1),
    (6, 6, '09:00:00', '22:00:00', 1),
    (7, 7, '09:00:00', '22:00:00', 1);
