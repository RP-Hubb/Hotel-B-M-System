<?php
/**
 * Adishiv Luxury Hotel & Suites
 * API Endpoint: Contact Inquiry Submission (Hardened with Honeypot, CSRF & Rate Limits)
 */

require_once __DIR__ . '/../includes/functions.php';

send_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}

// Origin check (WP3.3)
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

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// Honeypot anti-spam verification (WP2.6)
if (!empty($input['hotel_ref_hp']) || !empty($input['website'])) {
    // Spambot filled the hidden honeypot: silently respond with generic success
    json_response([
        'success' => true,
        'message' => 'Your inquiry has been submitted.'
    ]);
}

$csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verify_csrf_token($csrfToken)) {
    json_response(['success' => false, 'error' => 'Security token invalid or expired. Please refresh the page.'], 403);
}

// Rate Limiting: 3 contact submissions per hour per IP (WP2.6)
if (!check_rate_limit('contact_submit', 3, 3600)) {
    json_response([
        'success' => false,
        'error' => 'Inquiry frequency limit reached. For urgent assistance, please telephone our concierge at +91 11 4982 7700.'
    ], 429);
}

$name = trim((string)($input['name'] ?? ''));
$email = strtolower(trim((string)($input['email'] ?? '')));
$phone = trim((string)($input['phone'] ?? ''));
$subject = trim((string)($input['subject'] ?? 'General Concierge Inquiry'));
$message = trim((string)($input['message'] ?? ''));

// Strict validation (WP2.3)
if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
    json_response(['success' => false, 'error' => 'Please provide a valid name (between 2 and 120 characters).'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    json_response(['success' => false, 'error' => 'Please provide a valid email address.'], 422);
}

if (mb_strlen($subject) > 180) {
    json_response(['success' => false, 'error' => 'Subject cannot exceed 180 characters.'], 422);
}

if (mb_strlen($message) < 5 || mb_strlen($message) > 5000) {
    json_response(['success' => false, 'error' => 'Message must be between 5 and 5000 characters.'], 422);
}

try {
    $db = get_db();
    $stmt = $db->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, status) 
        VALUES (:name, :email, :phone, :subject, :message, 'unread')
    ");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':subject' => $subject,
        ':message' => $message
    ]);

    json_response([
        'success' => true,
        'message' => 'Thank you for contacting Adishiv Concierge. Our head butler and concierge team will attend to your request promptly.'
    ]);
} catch (Throwable $e) {
    error_log("Failed to store contact message: " . $e->getMessage());
    json_response(['success' => false, 'error' => 'Could not submit your inquiry at this moment. Please call our concierge directly.'], 500);
}
