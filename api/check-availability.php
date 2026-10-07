<?php
/**
 * Adishiv Luxury Hotel & Suites
 * API Endpoint: Check Suite Availability (Sessionless, Whitelisted & Cached)
 */

require_once __DIR__ . '/../includes/booking-helper.php';

send_security_headers();
header('Cache-Control: private, max-age=15');

$checkIn = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');
$rawGuests = $_GET['guests'] ?? 1;

if (empty($checkIn) || empty($checkOut)) {
    json_response([
        'success' => false,
        'error' => 'Please provide both check-in and check-out dates.'
    ], 400);
}

if (!is_numeric($rawGuests) || (int)$rawGuests < 1) {
    json_response([
        'success' => false,
        'error' => 'Guest count must be at least 1 person.'
    ], 422);
}
$guests = (int)$rawGuests;

[$valid, $error, $nights] = validate_date_range($checkIn, $checkOut);
if (!$valid) {
    json_response([
        'success' => false,
        'error' => $error
    ], 422);
}

try {
    $availableSuites = get_available_room_types($checkIn, $checkOut, $guests);

    // Whitelist sanitized response fields (WP2.9)
    $cleanSuites = [];
    foreach ($availableSuites as $s) {
        $cleanSuites[] = [
            'id' => (int)$s['id'],
            'name' => $s['name'],
            'slug' => $s['slug'],
            'short_description' => $s['short_description'],
            'price_per_night' => (float)$s['price_per_night'],
            'price_per_night_formatted' => format_inr($s['price_per_night']),
            'max_guests' => (int)$s['max_guests'],
            'bed_type' => $s['bed_type'],
            'room_size_sqft' => (int)$s['room_size_sqft'],
            'view_type' => $s['view_type'],
            'featured_image' => $s['featured_image'],
            'available_rooms_count' => (int)$s['available_rooms_count'],
            'calculated_total' => $s['calculated_total'] ?? 0,
            'calculated_total_formatted' => $s['calculated_total_formatted'] ?? '',
            'pricing' => $s['pricing'] ?? null
        ];
    }

    json_response([
        'success' => true,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guests' => $guests,
        'nights' => $nights,
        'count' => count($cleanSuites),
        'suites' => $cleanSuites
    ]);

} catch (Throwable $e) {
    error_log("API check-availability error: " . $e->getMessage());
    $isDbErr = str_contains($e->getMessage(), 'Database Connection') 
            || str_contains($e->getMessage(), 'SQLSTATE')
            || str_contains($e->getMessage(), 'Connection refused');
    if ((defined('APP_DEBUG') && APP_DEBUG) || $isDbErr) {
        $errorMessage = 'Database connection error. Please ensure the MySQL service is started in XAMPP. (' . $e->getMessage() . ')';
    } else {
        $errorMessage = 'Inventory availability service is temporarily unavailable. Please try again shortly.';
    }
    json_response([
        'success' => false,
        'error' => $errorMessage
    ], 503);
}
