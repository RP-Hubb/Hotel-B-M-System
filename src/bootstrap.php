<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Central Application Bootstrap (WP4.1)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking-helper.php';

// Send modern defense-in-depth security headers
send_security_headers();
