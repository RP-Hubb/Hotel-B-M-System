<?php
/**
 * Adishiv Luxury Hotel & Suites
 * DB Schema Migration & Alignment Script
 */

require_once __DIR__ . '/../config/database.php';

$db = get_db();
echo "Checking and migrating database schema...\n";

// Add idempotency_key if missing
$cols = $db->query("SHOW COLUMNS FROM bookings LIKE 'idempotency_key'")->fetchAll();
if (empty($cols)) {
    echo "Adding idempotency_key to bookings...\n";
    $db->exec("ALTER TABLE bookings ADD COLUMN idempotency_key VARCHAR(64) NULL UNIQUE AFTER booking_reference");
}

// Add access_token if missing
$cols = $db->query("SHOW COLUMNS FROM bookings LIKE 'access_token'")->fetchAll();
if (empty($cols)) {
    echo "Adding access_token to bookings...\n";
    $db->exec("ALTER TABLE bookings ADD COLUMN access_token VARCHAR(64) NULL UNIQUE AFTER idempotency_key");
}

// Add hold_expires_at if missing
$cols = $db->query("SHOW COLUMNS FROM bookings LIKE 'hold_expires_at'")->fetchAll();
if (empty($cols)) {
    echo "Adding hold_expires_at to bookings...\n";
    $db->exec("ALTER TABLE bookings ADD COLUMN hold_expires_at DATETIME NULL AFTER status");
}

// Add tax_rate if missing
$cols = $db->query("SHOW COLUMNS FROM bookings LIKE 'tax_rate'")->fetchAll();
if (empty($cols)) {
    echo "Adding tax_rate to bookings...\n";
    $db->exec("ALTER TABLE bookings ADD COLUMN tax_rate DECIMAL(5, 2) NOT NULL DEFAULT 18.00 AFTER subtotal_amount");
}

// Add currency if missing
$cols = $db->query("SHOW COLUMNS FROM bookings LIKE 'currency'")->fetchAll();
if (empty($cols)) {
    echo "Adding currency to bookings...\n";
    $db->exec("ALTER TABLE bookings ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER total_amount");
}

// Add cancellation_reason if missing
$cols = $db->query("SHOW COLUMNS FROM bookings LIKE 'cancellation_reason'")->fetchAll();
if (empty($cols)) {
    echo "Adding cancellation_reason to bookings...\n";
    $db->exec("ALTER TABLE bookings ADD COLUMN cancellation_reason VARCHAR(255) NULL AFTER hold_expires_at");
}

// Ensure rooms.floor is VARCHAR(40)
$db->exec("ALTER TABLE rooms MODIFY COLUMN floor VARCHAR(40) NOT NULL");

echo "Database schema migration completed successfully!\n";
