-- ============================================================================
-- ADISHIV LUXURY HOTEL & SUITES — DATABASE SCHEMA (DDL ONLY)
-- Minimum Database Versions: MySQL 8.0.16+ / MariaDB 10.6+
-- Engine: InnoDB | Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. USERS & STAFF AUTHENTICATION TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `role` ENUM('admin', 'manager', 'frontdesk', 'guest') NOT NULL DEFAULT 'guest',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. ROOM CATEGORIES / TYPES
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `room_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(80) NOT NULL UNIQUE,
    `name` VARCHAR(120) NOT NULL,
    `short_description` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `price_per_night` DECIMAL(10, 2) NOT NULL, -- In INR (₹)
    `max_guests` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `bed_type` VARCHAR(60) NOT NULL DEFAULT 'King Bed',
    `room_size_sqft` SMALLINT UNSIGNED NOT NULL DEFAULT 450,
    `view_type` VARCHAR(80) NOT NULL DEFAULT 'Imperial Courtyard View',
    `featured_image` VARCHAR(255) NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_room_types_active` (`is_active`),
    INDEX `idx_room_types_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. PHYSICAL ROOMS INVENTORY
-- Note: 'floor' is VARCHAR(40) to accommodate multi-word floor titles
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_number` VARCHAR(20) NOT NULL UNIQUE,
    `room_type_id` INT UNSIGNED NOT NULL,
    `floor` VARCHAR(40) NOT NULL DEFAULT 'Ground',
    `status` ENUM('available', 'occupied', 'maintenance', 'housekeeping') NOT NULL DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rooms_room_type` 
        FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_rooms_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. OUT-OF-SERVICE ROOM BLOCKS (MAINTENANCE / DOWNTIME RANGES)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `room_blocks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id` INT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_room_blocks_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_room_blocks_user`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_room_blocks_dates` (`room_id`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. ROOM GALLERY IMAGES
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `room_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_type_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(150) NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_room_images_type` 
        FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. AMENITIES CATALOG
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `amenities` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('room', 'wellness', 'dining', 'technology', 'service') NOT NULL DEFAULT 'room',
    `description` VARCHAR(255) NULL,
    `icon` VARCHAR(60) NOT NULL DEFAULT 'feather-star',
    `is_highlight` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. ROOM TYPE AMENITIES (MANY-TO-MANY PIVOT)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `room_type_amenities` (
    `room_type_id` INT UNSIGNED NOT NULL,
    `amenity_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`room_type_id`, `amenity_id`),
    CONSTRAINT `fk_rta_room_type` 
        FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `fk_rta_amenity` 
        FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. BOOKINGS MASTER TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_reference` VARCHAR(30) NOT NULL UNIQUE,
    `idempotency_key` VARCHAR(64) NULL UNIQUE,
    `access_token` VARCHAR(64) NULL UNIQUE,
    `user_id` INT UNSIGNED NULL,
    `room_id` INT UNSIGNED NULL,
    `room_type_id` INT UNSIGNED NOT NULL,
    `check_in` DATE NOT NULL,
    `check_out` DATE NOT NULL,
    `total_nights` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `guests_count` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `price_per_night` DECIMAL(10, 2) NOT NULL,
    `subtotal_amount` DECIMAL(10, 2) NOT NULL,
    `tax_rate` DECIMAL(5, 2) NOT NULL DEFAULT 18.00,
    `tax_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
    `special_requests` TEXT NULL,
    `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show', 'expired') NOT NULL DEFAULT 'confirmed',
    `hold_expires_at` DATETIME NULL,
    `cancellation_reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bookings_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_room` 
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_room_type` 
        FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_bookings_dates` (`check_in`, `check_out`),
    INDEX `idx_bookings_ref` (`booking_reference`),
    INDEX `idx_bookings_status` (`status`),
    INDEX `idx_bookings_token` (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. BOOKING NIGHTS LEDGER (DATABASE-LEVEL DOUBLE-BOOKING BACKSTOP)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_nights` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `stay_date` DATE NOT NULL,
    CONSTRAINT `fk_booking_nights_booking` 
        FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_booking_nights_room` 
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uniq_room_stay_date` (`room_id`, `stay_date`),
    INDEX `idx_booking_nights_stay` (`stay_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. BOOKING STATUS TRANSITION AUDIT TRAIL
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `from_status` VARCHAR(30) NULL,
    `to_status` VARCHAR(30) NOT NULL,
    `actor` VARCHAR(120) NOT NULL DEFAULT 'system',
    `note` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_booking_events_booking` 
        FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_booking_events_bid` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. BOOKING PRIMARY & ADDITIONAL GUESTS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_guests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `city` VARCHAR(80) NULL,
    `country` VARCHAR(80) NOT NULL DEFAULT 'India',
    `is_primary` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_booking_guests_booking` 
        FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_booking_guests_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. PAYMENTS LEDGER
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
    `payment_method` ENUM('pay_at_hotel', 'upi', 'credit_card', 'debit_card', 'netbanking', 'cash') NOT NULL DEFAULT 'pay_at_hotel',
    `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `transaction_reference` VARCHAR(100) NULL,
    `gateway_response` TEXT NULL,
    `paid_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_payments_booking` 
        FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_payments_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. CONTACT & ENQUIRY MESSAGES
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `subject` VARCHAR(180) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read', 'replied', 'archived') NOT NULL DEFAULT 'unread',
    `admin_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contact_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 14. HOTEL SETTINGS & DYNAMIC CONFIGURATION
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(60) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `setting_group` VARCHAR(40) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 15. RATE LIMITS LEDGER (IP & ACTION THROTTLING)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `action_key` VARCHAR(100) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempt_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `first_attempt_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_attempt_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_action_ip` (`action_key`, `ip_address`),
    INDEX `idx_rate_limits_action` (`action_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
