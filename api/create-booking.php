<?php
/**
 * Adishiv Luxury Hotel & Suites
 * API Endpoint: Create Reservation (Hardened with CSRF, Rate Limiting & User Scoping)
 */

require_once __DIR__ . '/../includes/booking-helper.php';
require_once __DIR__ . '/../includes/auth.php';

send_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}

// Origin / Sec-Fetch-Site check on POST requests (WP3.3)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin)) {
    $originHost = strtolower(parse_url($origin, PHP_URL_HOST) ?? '');
    $reqHost = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    $appHost = strtolower(parse_url(defined('APP_URL') ? APP_URL : '', PHP_URL_HOST) ?? '');
    $loopbackHosts = ['127.0.0.1', 'localhost', '::1'];

    $isDirectMatch = ($originHost && ($originHost === $reqHost || $originHost === $appHost));
    $isLoopbackMatch = (in_array($originHost, $loopbackHosts, true) && (in_array($reqHost, $loopbackHosts, true) || in_array($appHost, $loopbackHosts, true)));

    if (!$isDirectMatch && !$isLoopbackMatch) {
        json_response(['success' => false, 'error' => 'Cross-origin request forbidden.'], 403);
    }
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

// Scope authenticated user_id securely on the server side (WP1.4)
$authUserId = null;
if (is_logged_in()) {
    $user = current_user();
    if ($user) {
        $authUserId = (int)$user['id'];
        if (empty($input['email'])) {
            $input['email'] = $user['email'];
        }
        if (empty($input['name'])) {
            $input['name'] = $user['name'];
        }
    }
}

// Execute atomic reservation
$result = create_booking_reservation($input, $authUserId);

if (!$result['success']) {
    json_response($result, 422);
}

json_response($result, 201);
