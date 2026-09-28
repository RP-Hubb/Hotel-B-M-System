<?php
/**
 * Adishiv Luxury Hotel & Suites
 * API Endpoint: Contact Inquiry Submission
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verify_csrf_token($csrfToken)) {
    json_response(['success' => false, 'error' => 'Security token invalid. Please reload.'], 403);
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$subject = trim($input['subject'] ?? 'General Concierge Inquiry');
$message = trim($input['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    json_response(['success' => false, 'error' => 'Please provide your name, email, and message.'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'error' => 'Please provide a valid email address.'], 422);
}

$db = get_db();
try {
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
        'message' => 'Thank you for contacting Adishiv Concierge. Our team will attend to your request promptly.'
    ]);
} catch (Exception $e) {
    error_log("Failed to store contact message: " . $e->getMessage());
    json_response(['success' => false, 'error' => 'Could not send message. Please call the concierge directly.'], 500);
}
