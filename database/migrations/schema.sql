CREATE DATABASE IF NOT EXISTS cozycafe
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cozycafe;

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_admin_login_username_time (username, attempted_at),
    KEY idx_admin_login_ip_time (ip_address, attempted_at),
    KEY idx_admin_login_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    KEY idx_categories_active_name (is_active, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    image VARCHAR(500) NULL,
    price DECIMAL(12,2) UNSIGNED NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    min_stock INT UNSIGNED NOT NULL DEFAULT 0,
    min_order_quantity INT UNSIGNED NOT NULL DEFAULT 1,
    max_order_quantity INT UNSIGNED NOT NULL DEFAULT 99,
    availability_status ENUM('available', 'sold_out', 'unavailable') NOT NULL DEFAULT 'available',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    has_order_history TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_products_category_active (category_id, is_active),
    KEY idx_products_active_name (is_active, name),
    KEY idx_products_availability (availability_status),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_products_order_quantity
        CHECK (min_order_quantity >= 1 AND max_order_quantity >= min_order_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NULL,
    phone VARCHAR(32) NULL,
    is_anonymized TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customers_phone (phone),
    KEY idx_customers_name (name),
    KEY idx_customers_anonymized (is_anonymized)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_areas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_areas_name (name),
    KEY idx_booking_areas_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tables (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    table_number VARCHAR(40) NOT NULL,
    area_id BIGINT UNSIGNED NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    position_x DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0,
    position_y DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tables_table_number (table_number),
    KEY idx_tables_area_active_capacity (area_id, is_active, capacity),
    CONSTRAINT fk_tables_area
        FOREIGN KEY (area_id) REFERENCES booking_areas (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_tables_capacity CHECK (capacity > 0),
    CONSTRAINT chk_tables_position_x CHECK (position_x <= 100),
    CONSTRAINT chk_tables_position_y CHECK (position_y <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_methods (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    method_type ENUM('cash', 'bank_transfer', 'qris_static') NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    deadline_minutes SMALLINT UNSIGNED NOT NULL,
    bank_name VARCHAR(120) NULL,
    account_number VARCHAR(80) NULL,
    account_holder VARCHAR(160) NULL,
    qris_image VARCHAR(500) NULL,
    instructions TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_methods_type (method_type),
    KEY idx_payment_methods_enabled (is_enabled),
    CONSTRAINT chk_payment_methods_deadline CHECK (deadline_minutes > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_code VARCHAR(32) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    slot_start TIME NOT NULL,
    slot_end TIME NOT NULL,
    guest_count TINYINT UNSIGNED NOT NULL,
    status ENUM(
        'pending', 'confirmed', 'rejected', 'completed', 'cancelled',
        'cancellation_requested', 'checked_in', 'no_show'
    ) NOT NULL DEFAULT 'pending',
    qr_token CHAR(64) NOT NULL,
    confirmation_mode ENUM('auto', 'manual') NOT NULL DEFAULT 'manual',
    checked_in_at DATETIME NULL,
    checkin_method ENUM('qr', 'admin') NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bookings_code (booking_code),
    UNIQUE KEY uq_bookings_qr_token (qr_token),
    KEY idx_bookings_customer_created (customer_id, created_at),
    KEY idx_bookings_date_slot_status (booking_date, slot_start, slot_end, status),
    KEY idx_bookings_status (status),
    CONSTRAINT fk_bookings_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_bookings_guest_count CHECK (guest_count > 0),
    CONSTRAINT chk_bookings_slot CHECK (slot_end > slot_start),
    CONSTRAINT chk_bookings_checkin_pair
        CHECK ((checked_in_at IS NULL AND checkin_method IS NULL)
            OR (checked_in_at IS NOT NULL AND checkin_method IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_code VARCHAR(32) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    order_type ENUM('dine_in', 'takeaway') NOT NULL,
    booking_id BIGINT UNSIGNED NULL,
    table_id BIGINT UNSIGNED NULL,
    subtotal DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    discount_amount DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    service_charge DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    payment_status ENUM(
        'unpaid', 'waiting_verification', 'paid', 'rejected', 'refunded'
    ) NOT NULL DEFAULT 'unpaid',
    order_status ENUM(
        'pending', 'processing', 'ready', 'completed', 'cancelled',
        'expired', 'cancellation_requested', 'cancellation_rejected'
    ) NOT NULL DEFAULT 'pending',
    payment_deadline DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME NULL,
    completed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_order_code (order_code),
    KEY idx_orders_customer_created (customer_id, created_at),
    KEY idx_orders_status_created (order_status, created_at),
    KEY idx_orders_payment_status_deadline (payment_status, payment_deadline),
    KEY idx_orders_booking (booking_id),
    KEY idx_orders_table (table_id),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_orders_booking
        FOREIGN KEY (booking_id) REFERENCES bookings (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_orders_table
        FOREIGN KEY (table_id) REFERENCES tables (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_orders_payment_method
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_orders_total
        CHECK (
            discount_amount <= subtotal
            AND total_amount = subtotal - discount_amount + tax_amount + service_charge
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_tables (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id BIGINT UNSIGNED NOT NULL,
    table_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_tables_pair (booking_id, table_id),
    KEY idx_booking_tables_table (table_id),
    CONSTRAINT fk_booking_tables_booking
        FOREIGN KEY (booking_id) REFERENCES bookings (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_booking_tables_table
        FOREIGN KEY (table_id) REFERENCES tables (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name_snapshot VARCHAR(160) NOT NULL,
    price_snapshot DECIMAL(12,2) UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_order_items_quantity CHECK (quantity > 0),
    CONSTRAINT chk_order_items_subtotal CHECK (subtotal = price_snapshot * quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_status_histories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    old_status ENUM(
        'pending', 'processing', 'ready', 'completed', 'cancelled',
        'expired', 'cancellation_requested', 'cancellation_rejected'
    ) NULL,
    new_status ENUM(
        'pending', 'processing', 'ready', 'completed', 'cancelled',
        'expired', 'cancellation_requested', 'cancellation_rejected'
    ) NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    changed_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_order_status_history_order_time (order_id, changed_at),
    KEY idx_order_status_history_admin (changed_by),
    CONSTRAINT fk_order_status_histories_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_status_histories_admin
        FOREIGN KEY (changed_by) REFERENCES admins (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_cancellation_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_response TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_order_cancellation_order_status (order_id, status),
    CONSTRAINT fk_order_cancellation_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_substitutions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NULL,
    original_product_id BIGINT UNSIGNED NULL,
    replacement_product_id BIGINT UNSIGNED NULL,
    original_product_name VARCHAR(160) NOT NULL,
    replacement_product_name VARCHAR(160) NOT NULL,
    replacement_price DECIMAL(12,2) UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    responded_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_substitutions_order_status (order_id, status),
    KEY idx_order_substitutions_expiry (status, expires_at),
    CONSTRAINT fk_order_substitutions_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_substitutions_item
        FOREIGN KEY (order_item_id) REFERENCES order_items (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_order_substitutions_original_product
        FOREIGN KEY (original_product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_order_substitutions_replacement_product
        FOREIGN KEY (replacement_product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_order_substitutions_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) UNSIGNED NOT NULL,
    status ENUM('unpaid', 'waiting_verification', 'paid', 'rejected', 'refunded') NOT NULL DEFAULT 'unpaid',
    verified_by BIGINT UNSIGNED NULL,
    verified_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_order_created (order_id, created_at),
    KEY idx_payments_method_status (payment_method_id, status),
    KEY idx_payments_verified_by (verified_by),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_payments_method
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_verified_by
        FOREIGN KEY (verified_by) REFERENCES admins (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_proofs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL COMMENT 'Path relatif terhadap storage/, di luar public/',
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    verification_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_payment_proofs_payment_upload (payment_id, uploaded_at),
    KEY idx_payment_proofs_status (verification_status, uploaded_at),
    CONSTRAINT fk_payment_proofs_payment
        FOREIGN KEY (payment_id) REFERENCES payments (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_payment_proofs_file_size CHECK (file_size > 0 AND file_size <= 5242880)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_extensions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    requested_deadline DATETIME NOT NULL,
    request_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_response TEXT NULL,
    approved_deadline DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_payment_extensions_order_status (order_id, request_status),
    CONSTRAINT fk_payment_extensions_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refunds (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) UNSIGNED NOT NULL,
    refund_date DATETIME NOT NULL,
    proof_path VARCHAR(500) NULL COMMENT 'Path relatif terhadap storage/, di luar public/',
    admin_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_refunds_order_date (order_id, refund_date),
    CONSTRAINT fk_refunds_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_refunds_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS table_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    table_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    qr_token CHAR(64) NOT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_end_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    checkin_method ENUM('qr', 'admin') NOT NULL,
    status ENUM('active', 'ended', 'expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_table_sessions_table_status (table_id, status, expected_end_at),
    KEY idx_table_sessions_customer (customer_id, started_at),
    KEY idx_table_sessions_order (order_id),
    KEY idx_table_sessions_qr_token (qr_token),
    CONSTRAINT fk_table_sessions_table
        FOREIGN KEY (table_id) REFERENCES tables (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_table_sessions_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_table_sessions_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_table_sessions_time CHECK (expected_end_at > started_at),
    CONSTRAINT chk_table_sessions_ended
        CHECK ((status = 'active' AND ended_at IS NULL)
            OR (status <> 'active' AND ended_at IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_cancellation_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id BIGINT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_response TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_booking_cancellation_booking_status (booking_id, status),
    CONSTRAINT fk_booking_cancellation_booking
        FOREIGN KEY (booking_id) REFERENCES bookings (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promotions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(12,2) UNSIGNED NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    minimum_purchase DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_promotions_active_period (is_active, start_date, end_date),
    CONSTRAINT chk_promotions_period CHECK (end_date > start_date),
    CONSTRAINT chk_promotions_discount CHECK (
        (discount_type = 'percentage' AND discount_value <= 100)
        OR (discount_type = 'fixed' AND discount_value > 0)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vouchers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(64) NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(12,2) UNSIGNED NOT NULL,
    minimum_purchase DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    total_usage_limit INT UNSIGNED NULL,
    per_phone_limit INT UNSIGNED NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vouchers_code (code),
    KEY idx_vouchers_active_period (is_active, start_date, end_date),
    CONSTRAINT chk_vouchers_period CHECK (end_date > start_date),
    CONSTRAINT chk_vouchers_limits CHECK (
        (total_usage_limit IS NULL OR total_usage_limit > 0)
        AND (per_phone_limit IS NULL OR per_phone_limit > 0)
    ),
    CONSTRAINT chk_vouchers_discount CHECK (
        (discount_type = 'percentage' AND discount_value <= 100)
        OR (discount_type = 'fixed' AND discount_value > 0)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS voucher_usages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    voucher_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    phone_snapshot VARCHAR(32) NOT NULL,
    used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_voucher_usage_order (voucher_id, order_id),
    KEY idx_voucher_usages_phone (voucher_id, phone_snapshot),
    KEY idx_voucher_usages_customer (customer_id),
    CONSTRAINT fk_voucher_usages_voucher
        FOREIGN KEY (voucher_id) REFERENCES vouchers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_voucher_usages_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_voucher_usages_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    review_type ENUM('product', 'cafe') NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    moderation_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reviews_product_moderation (product_id, moderation_status),
    KEY idx_reviews_customer_created (customer_id, created_at),
    KEY idx_reviews_order_type (order_id, review_type),
    CONSTRAINT fk_reviews_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reviews_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    review_id BIGINT UNSIGNED NOT NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_testimonials_review (review_id),
    KEY idx_testimonials_visible (is_approved, is_visible),
    CONSTRAINT fk_testimonials_review
        FOREIGN KEY (review_id) REFERENCES reviews (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    before_data JSON NULL,
    after_data JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_logs_created (created_at),
    KEY idx_activity_logs_entity (entity_type, entity_id, created_at),
    KEY idx_activity_logs_admin_created (admin_id, created_at),
    CONSTRAINT fk_activity_logs_admin
        FOREIGN KEY (admin_id) REFERENCES admins (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cafe_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cafe_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS operating_hours (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT '1=Senin hingga 7=Minggu',
    opening_time TIME NULL,
    closing_time TIME NULL,
    is_open TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_operating_hours_day (day_of_week),
    CONSTRAINT chk_operating_hours_day CHECK (day_of_week BETWEEN 1 AND 7),
    CONSTRAINT chk_operating_hours_times CHECK (
        (is_open = 0 AND opening_time IS NULL AND closing_time IS NULL)
        OR (is_open = 1 AND opening_time IS NOT NULL AND closing_time > opening_time)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS special_closures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    closure_date DATE NOT NULL,
    reason VARCHAR(255) NOT NULL,
    is_closed TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_special_closures_date (closure_date),
    KEY idx_special_closures_closed_date (is_closed, closure_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backup_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    backup_type ENUM('manual', 'scheduled') NOT NULL,
    file_size BIGINT UNSIGNED NULL,
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_backup_logs_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
