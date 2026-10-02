<?php
/**
 * Adishiv Luxury Hotel & Suites
 * API Endpoint: Calculate Pricing & Taxes (Sessionless, Whitelisted & Cached)
 */

require_once __DIR__ . '/../includes/booking-helper.php';

send_security_headers();
header('Cache-Control: private, max-age=15');

$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$checkIn = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');

if (!$roomTypeId || empty($checkIn) || empty($checkOut)) {
    json_response([
        'success' => false,
        'error' => 'Missing required parameters (room_type_id, check_in, check_out).'
    ], 400);
}

try {
    $pricing = calculate_pricing($roomTypeId, $checkIn, $checkOut);

    if (!$pricing['success']) {
        json_response($pricing, 422);
    }

    json_response($pricing);

} catch (Throwable $e) {
    error_log("API calculate-pricing error: " . $e->getMessage());
    json_response([
        'success' => false,
        'error' => 'Pricing calculation service is temporarily unavailable. Please try again shortly.'
    ], 503);
}
