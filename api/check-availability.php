<?php
/**
 * Adishiv Luxury Hotel & Suites
 * API Endpoint: Check Suite Availability
 */

require_once __DIR__ . '/../includes/booking-helper.php';

header('Content-Type: application/json; charset=utf-8');

$checkIn = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');
$guests = max(1, (int)($_GET['guests'] ?? 1));

if (empty($checkIn) || empty($checkOut)) {
    json_response([
        'success' => false,
        'error' => 'Please provide both check-in and check-out dates.'
    ], 400);
}

[$valid, $error, $nights] = validate_date_range($checkIn, $checkOut);
if (!$valid) {
    json_response([
        'success' => false,
        'error' => $error
    ], 422);
}

$availableSuites = get_available_room_types($checkIn, $checkOut, $guests);

json_response([
    'success' => true,
    'check_in' => $checkIn,
    'check_out' => $checkOut,
    'guests' => $guests,
    'nights' => $nights,
    'count' => count($availableSuites),
    'suites' => $availableSuites
]);
