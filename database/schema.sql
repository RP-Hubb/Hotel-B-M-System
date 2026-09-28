-- ============================================================================
-- ADISHIV LUXURY HOTEL & SUITES — DATABASE SCHEMA
-- Location: New Delhi, India | Currency: INR (₹)
-- Standard: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.4+
-- Engine: InnoDB | Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `adishiv_hotel` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `adishiv_hotel`;

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
    `amenities_json` JSON NULL, -- Fast caching of amenity IDs/slugs
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
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_number` VARCHAR(20) NOT NULL UNIQUE,
    `room_type_id` INT UNSIGNED NOT NULL,
    `floor` VARCHAR(20) NOT NULL DEFAULT 'Ground',
    `status` ENUM('available', 'occupied', 'maintenance', 'housekeeping') NOT NULL DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rooms_room_type` 
        FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_rooms_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. ROOM GALLERY IMAGES
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
-- 5. AMENITIES CATALOG
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `amenities` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('room', 'wellness', 'dining', 'technology', 'service') NOT NULL DEFAULT 'room',
    `description` VARCHAR(255) NULL,
    `icon` VARCHAR(60) NOT NULL DEFAULT 'feather-star', -- Icon class name
    `is_highlight` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. ROOM TYPE AMENITIES (MANY-TO-MANY PIVOT)
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
-- 7. BOOKINGS MASTER TABLE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_reference` VARCHAR(30) NOT NULL UNIQUE, -- e.g. ADI-2026-8942
    `user_id` INT UNSIGNED NULL, -- NULL if booked as guest checkout
    `room_id` INT UNSIGNED NULL, -- Physical room allocated (assigned on booking or check-in)
    `room_type_id` INT UNSIGNED NOT NULL,
    `check_in` DATE NOT NULL,
    `check_out` DATE NOT NULL,
    `total_nights` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `guests_count` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `price_per_night` DECIMAL(10, 2) NOT NULL,
    `subtotal_amount` DECIMAL(10, 2) NOT NULL,
    `tax_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00, -- 18% GST standard in India
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `special_requests` TEXT NULL,
    `status` ENUM('confirmed', 'checked_in', 'checked_out', 'cancelled') NOT NULL DEFAULT 'confirmed',
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
    INDEX `idx_bookings_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. BOOKING PRIMARY & ADDITIONAL GUESTS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_guests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `id_type` VARCHAR(50) NULL, -- 'Aadhaar', 'Passport', 'Driving License'
    `id_number` VARCHAR(50) NULL,
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
-- 9. PAYMENTS LEDGER (Structured for hotel counter & future gateway)
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
-- 10. CONTACT & ENQUIRY MESSAGES
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
-- 11. HOTEL SETTINGS & DYNAMIC CONFIGURATION
-- Allows modifying phone, email, address, tax rate without editing code
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
-- SEED INITIAL CONFIGURATION DATA
-- ----------------------------------------------------------------------------
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('hotel_name', 'Adishiv Hotel & Suites', 'general', 'Official brand name'),
('hotel_tagline', 'Sanctuary in the Imperial Capital', 'general', 'Editorial tagline'),
('hotel_location', 'New Delhi, India', 'general', 'City and country'),
('hotel_address', '14 Imperial Boulevard, Diplomatic Enclave, Chanakyapuri, New Delhi 110021, India', 'general', 'Physical address'),
('hotel_phone', '+91 11 4982 7700', 'contact', 'Primary concierge phone'),
('hotel_email', 'concierge@adishivhotel.com', 'contact', 'Concierge email'),
('currency_symbol', '₹', 'localization', 'Currency display symbol'),
('currency_code', 'INR', 'localization', 'Currency standard ISO code'),
('tax_rate_percent', '18.00', 'billing', 'Goods and Services Tax (GST) percentage'),
('check_in_time', '14:00', 'policy', 'Standard check-in time'),
('check_out_time', '11:00', 'policy', 'Standard check-out time')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- ----------------------------------------------------------------------------
-- SEED AMENITIES
-- ----------------------------------------------------------------------------
INSERT INTO `amenities` (`code`, `name`, `category`, `description`, `icon`, `is_highlight`) VALUES
('wifi', 'Complimentary High-Speed Wi-Fi 6', 'technology', 'Gigabit fibre internet in all suites and gardens', 'wifi', 1),
('butler', '24-Hour Imperial Butler Service', 'service', 'Dedicated private concierge and personal butler', 'bell', 1),
('spa', 'Ayurvedic & Modern Wellness Spa', 'wellness', 'Bespoke herbal therapies and thermal hydrotherapy', 'feather', 1),
('dining', 'Aura Fine Dining Restaurant', 'dining', 'Award-winning modern North Indian and pan-Asian cuisine', 'coffee', 1),
('pool', 'Heated Marble Courtyard Pool', 'wellness', 'Temperature controlled outdoor swimming pool with daybeds', 'droplet', 1),
('gym', 'Technogym Fitness Studio', 'wellness', 'State-of-the-art cardiovascular and strength equipment', 'activity', 0),
('valet', 'Private Chauffeur & Valet Parking', 'service', 'Airport limousine transfers and secure subterranean parking', 'compass', 1),
('bar', 'The Peacock Library Bar', 'dining', 'Curated single malts, botanical gins, and artisanal cocktails', 'glass', 0)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ----------------------------------------------------------------------------
-- SEED INITIAL LUXURY ROOM TYPES FOR ADISHIV
-- ----------------------------------------------------------------------------
INSERT INTO `room_types` (`slug`, `name`, `short_description`, `description`, `price_per_night`, `max_guests`, `bed_type`, `room_size_sqft`, `view_type`, `featured_image`, `sort_order`) VALUES
('deluxe-verandah-room', 'Deluxe Verandah Room', 'Serene sanctuary with handcrafted teak furnishings and private garden verandah.', 'The Deluxe Verandah Room offers an intimate retreat amidst the bustling capital. Featuring rich hardwood floors, Indian brass accents, Italian marble ensuite bathroom with walk-in rain shower, and a private sunlit balcony looking out upon fragrant frangipani courtyards.', 18500.00, 2, 'King Bed', 480, 'Private Courtyard & Garden View', 'assets/images/rooms/deluxe-verandah.jpg', 1),

('imperial-heritage-suite', 'Imperial Heritage Suite', 'Expansive suite blending Mughal architectural motifs with contemporary luxury.', 'Embodying Delhi''s regal history, the Imperial Heritage Suite features an expansive living salon, bespoke handcrafted headboard with brass inlay, a freestanding oval marble soaking tub, walk-in dressing wardrobe, and personalized 24-hour butler assistance.', 32000.00, 3, 'King Bed + Daybed', 780, 'Reflecting Pool & Mughal Garden View', 'assets/images/rooms/imperial-suite.jpg', 2),

('the-adishiv-presidential-residence', 'The Adishiv Presidential Residence', 'The pinnacle of bespoke hospitality with private terrace pool and dining salon.', 'Designed for heads of state, diplomats, and discerning connoisseurs, our signature Presidential Residence encompasses a panoramic wraparound terrace with heated plunge pool, eight-guest dining salon, pantry kitchen, private study, and grand master bedroom overlooking the diplomatic greens.', 75000.00, 4, 'Emperor King Bed', 1650, 'Panoramic Lutyens Skyline & Forest View', 'assets/images/rooms/presidential-residence.jpg', 3)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ----------------------------------------------------------------------------
-- SEED PHYSICAL ROOM INVENTORY
-- ----------------------------------------------------------------------------
INSERT INTO `rooms` (`room_number`, `room_type_id`, `floor`, `status`) VALUES
('101', 1, 'First Floor', 'available'),
('102', 1, 'First Floor', 'available'),
('103', 1, 'First Floor', 'available'),
('201', 2, 'Second Floor', 'available'),
('202', 2, 'Second Floor', 'available'),
('301', 3, 'Third Floor Penthouse', 'available')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- ----------------------------------------------------------------------------
-- SEED DEFAULT ADMIN USER
-- Password hash for 'Admin@Adishiv2026' generated via password_hash('Admin@Adishiv2026', PASSWORD_BCRYPT)
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('Adishiv Head Concierge', 'admin@adishivhotel.com', '$2y$10$w8T9bOa8Y7mZ7KjV4fI7xe6aE.vE83Xn8bYm3e69VbTzLz94yKqfa', '+91 11 4982 7701', 'admin', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
