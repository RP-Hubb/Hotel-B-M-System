<?php
/**
 * Adishiv Luxury Hotel & Suites
 * API Endpoint: Calculate Pricing & Taxes
 */

require_once __DIR__ . '/../includes/booking-helper.php';

header('Content-Type: application/json; charset=utf-8');

$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$checkIn = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');

if (!$roomTypeId || empty($checkIn) || empty($checkOut)) {
    json_response([
        'success' => false,
        'error' => 'Missing required parameters (room_type_id, check_in, check_out).'
    ], 400);
}

$pricing = calculate_pricing($roomTypeId, $checkIn, $checkOut);

if (!$pricing['success']) {
    json_response($pricing, 422);
}

json_response($pricing);
