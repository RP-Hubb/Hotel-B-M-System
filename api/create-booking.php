<?php
/**
 * Adishiv Luxury Hotel & Suites
 * API Endpoint: Create Reservation
 */

require_once __DIR__ . '/../includes/booking-helper.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}

// Support JSON POST or form-data
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// CSRF check: allow verify_csrf_token() or check header
$csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verify_csrf_token($csrfToken)) {
    json_response([
        'success' => false, 
        'error' => 'Security token expired or invalid. Please refresh the page.'
    ], 403);
}

// Attach logged in user ID if guest is authenticated
if (is_logged_in()) {
    $user = current_user();
    $input['user_id'] = $user['id'];
    if (empty($input['email'])) {
        $input['email'] = $user['email'];
    }
    if (empty($input['name'])) {
        $input['name'] = $user['name'];
    }
}

$result = create_booking_reservation($input);

if (!$result['success']) {
    json_response($result, 422);
}

json_response($result, 201);
