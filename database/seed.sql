-- ============================================================================
-- ADISHIV LUXURY HOTEL & SUITES — DEMO SEED DATA (IDEMPOTENT & NON-DESTRUCTIVE)
-- Engine: InnoDB | Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. SEED INITIAL CONFIGURATION DATA
-- ----------------------------------------------------------------------------
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('hotel_name', 'Adishiv Hotel & Suites', 'general', 'Official brand name'),
('hotel_tagline', 'Sanctuary in the Imperial Capital', 'general', 'Editorial tagline'),
('hotel_location', 'New Delhi, India', 'general', 'City and country'),
('hotel_address', 'G-59, Connaught Circus, Connaught Place, New Delhi, Delhi 110001', 'general', 'Physical address'),
('hotel_phone', '+91 11 4982 7700', 'contact', 'Primary concierge phone'),
('hotel_email', 'concierge@adishivhotel.com', 'contact', 'Concierge email'),
('currency_symbol', '₹', 'localization', 'Currency display symbol'),
('currency_code', 'INR', 'localization', 'Currency standard ISO code'),
('tax_rate_percent', '18.00', 'billing', 'Goods and Services Tax (GST) standard percentage'),
('tax_slab_threshold', '7500.00', 'billing', 'Per-day per-room value threshold for 5% vs 18% GST (Notification 22 Sep 2025)'),
('tax_low_rate', '5.00', 'billing', 'GST rate for rooms <= threshold'),
('tax_high_rate', '18.00', 'billing', 'GST rate for rooms > threshold'),
('tax_threshold_operator', 'lte', 'billing', 'Comparison operator for lower slab: lte (<= 7500) or lt (< 7500)'),
('check_in_time', '14:00', 'policy', 'Standard check-in time'),
('check_out_time', '11:00', 'policy', 'Standard check-out time'),
('max_advance_days', '365', 'policy', 'Maximum days in advance reservations are accepted online'),
('max_stay_nights', '30', 'policy', 'Maximum consecutive nights allowed for online booking')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- ----------------------------------------------------------------------------
-- 2. SEED AMENITIES CATALOG
-- ----------------------------------------------------------------------------
INSERT INTO `amenities` (`code`, `name`, `category`, `description`, `icon`, `is_highlight`) VALUES
('wifi', 'Complimentary High-Speed Wi-Fi 6', 'technology', 'Gigabit fibre internet in all suites and gardens', 'wifi', 1),
('butler', '24-Hour Imperial Butler Service', 'service', 'Dedicated private concierge and personal butler', 'bell', 1),
('spa', 'Ayurvedic & Modern Wellness Spa', 'wellness', 'Bespoke herbal therapies and thermal hydrotherapy', 'feather', 1),
('dining', 'Aura Fine Dining Restaurant', 'dining', 'Modern North Indian and pan-Asian culinary craftsmanship', 'coffee', 1),
('pool', 'Heated Marble Courtyard Pool', 'wellness', 'Temperature controlled outdoor swimming pool with daybeds', 'droplet', 1),
('gym', 'Technogym Fitness Studio', 'wellness', 'State-of-the-art cardiovascular and strength equipment', 'activity', 0),
('valet', 'Private Chauffeur & Valet Parking', 'service', 'Airport limousine transfers and secure subterranean parking', 'compass', 1),
('bar', 'The Peacock Library Bar', 'dining', 'Curated single malts, botanical gins, and artisanal cocktails', 'glass', 0)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ----------------------------------------------------------------------------
-- 3. SEED ROOM CATEGORIES
-- ----------------------------------------------------------------------------
INSERT INTO `room_types` (`slug`, `name`, `short_description`, `description`, `price_per_night`, `max_guests`, `bed_type`, `room_size_sqft`, `view_type`, `featured_image`, `sort_order`, `is_active`) VALUES
('deluxe-verandah-room', 
 'Deluxe Verandah Room', 
 'Serene sanctuary with handcrafted teak furnishings and private garden verandah.', 
 'The Deluxe Verandah Room offers an intimate retreat amidst the capital. Featuring rich hardwood floors, Indian brass accents, Italian marble ensuite bathroom with walk-in rain shower, and a private sunlit balcony looking out upon fragrant frangipani courtyards.', 
 18500.00, 2, 'King Bed', 480, 'Private Courtyard & Garden View', 'assets/images/rooms/deluxe-verandah.jpg', 1, 1),

('imperial-heritage-suite', 
 'Imperial Heritage Suite', 
 'Expansive suite blending Mughal architectural motifs with contemporary luxury.', 
 'Embodying Delhi''s regal history, the Imperial Heritage Suite features an expansive living salon, bespoke handcrafted headboard with brass inlay, a freestanding oval marble soaking tub, walk-in dressing wardrobe, and personalized 24-hour butler assistance.', 
 32000.00, 3, 'King Bed + Daybed', 780, 'Reflecting Pool & Mughal Garden View', 'assets/images/rooms/imperial-suite.jpg', 2, 1),

('the-adishiv-presidential-residence', 
 'The Adishiv Presidential Residence', 
 'The pinnacle of bespoke hospitality with private terrace pool and dining salon.', 
 'Designed for heads of state, diplomats, and discerning connoisseurs, our signature Presidential Residence encompasses a panoramic wraparound terrace with heated plunge pool, eight-guest dining salon, pantry kitchen, private study, and grand master bedroom overlooking the diplomatic greens.', 
 75000.00, 4, 'Emperor King Bed', 1650, 'Panoramic Lutyens Skyline & Forest View', 'assets/images/rooms/presidential-residence.jpg', 3, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `price_per_night` = VALUES(`price_per_night`);

-- ----------------------------------------------------------------------------
-- 4. SEED PHYSICAL ROOMS INVENTORY
-- Note: Room types resolved via subquery slug lookup.
-- Note: Status is NOT overwritten on duplicate key to preserve operational state!
-- ----------------------------------------------------------------------------
INSERT INTO `rooms` (`room_number`, `room_type_id`, `floor`, `status`) VALUES
('101', (SELECT `id` FROM `room_types` WHERE `slug` = 'deluxe-verandah-room' LIMIT 1), 'First Floor', 'available'),
('102', (SELECT `id` FROM `room_types` WHERE `slug` = 'deluxe-verandah-room' LIMIT 1), 'First Floor', 'available'),
('103', (SELECT `id` FROM `room_types` WHERE `slug` = 'deluxe-verandah-room' LIMIT 1), 'First Floor', 'available'),
('201', (SELECT `id` FROM `room_types` WHERE `slug` = 'imperial-heritage-suite' LIMIT 1), 'Second Floor', 'available'),
('202', (SELECT `id` FROM `room_types` WHERE `slug` = 'imperial-heritage-suite' LIMIT 1), 'Second Floor', 'available'),
('301', (SELECT `id` FROM `room_types` WHERE `slug` = 'the-adishiv-presidential-residence' LIMIT 1), 'Third Floor Penthouse', 'available')
ON DUPLICATE KEY UPDATE `floor` = VALUES(`floor`);

-- ----------------------------------------------------------------------------
-- 5. SEED ROOM TYPE AMENITIES (MANY-TO-MANY PIVOT)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `room_type_amenities` (`room_type_id`, `amenity_id`)
SELECT rt.id, a.id 
FROM `room_types` rt 
CROSS JOIN `amenities` a
WHERE (rt.slug = 'deluxe-verandah-room' AND a.code IN ('wifi', 'dining', 'gym', 'valet'))
   OR (rt.slug = 'imperial-heritage-suite' AND a.code IN ('wifi', 'butler', 'spa', 'dining', 'pool', 'gym', 'valet', 'bar'))
   OR (rt.slug = 'the-adishiv-presidential-residence' AND a.code IN ('wifi', 'butler', 'spa', 'dining', 'pool', 'gym', 'valet', 'bar'));

-- ----------------------------------------------------------------------------
-- 6. SEED ROOM IMAGES
-- ----------------------------------------------------------------------------
INSERT INTO `room_images` (`room_type_id`, `image_path`, `caption`, `is_primary`, `sort_order`)
SELECT id, 'assets/images/rooms/deluxe-verandah.jpg', 'Deluxe Verandah Room Master Bedroom', 1, 1 
FROM `room_types` WHERE slug = 'deluxe-verandah-room'
UNION ALL
SELECT id, 'assets/images/rooms/imperial-suite.jpg', 'Imperial Heritage Suite Living Salon', 1, 1 
FROM `room_types` WHERE slug = 'imperial-heritage-suite'
UNION ALL
SELECT id, 'assets/images/rooms/presidential-residence.jpg', 'The Adishiv Presidential Residence Master Chamber', 1, 1 
FROM `room_types` WHERE slug = 'the-adishiv-presidential-residence'
ON DUPLICATE KEY UPDATE `caption` = VALUES(`caption`);
