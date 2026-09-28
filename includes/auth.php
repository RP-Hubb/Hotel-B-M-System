<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Authentication & Session Management
 */

require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Retrieve currently logged-in user data
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    static $currentUser = null;
    if ($currentUser === null) {
        $db = get_db();
        $stmt = $db->prepare("SELECT id, name, email, phone, role, status FROM users WHERE id = :id AND status = 'active' LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $currentUser = $stmt->fetch() ?: null;
        if (!$currentUser) {
            // User was deleted or deactivated
            logout_user();
        }
    }

    return $currentUser;
}

/**
 * Check if the logged-in user is an administrator
 */
function is_admin(): bool {
    $user = current_user();
    return $user && in_array($user['role'], ['admin', 'manager']);
}

/**
 * Guard: Require logged-in guest or admin
 */
function require_login(string $redirect = '/login.php'): void {
    if (!is_logged_in()) {
        flash_message('error', 'Please log in to continue.');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Guard: Require administrator privileges
 */
function require_admin(string $redirect = '/admin/login.php'): void {
    if (!is_admin()) {
        flash_message('error', 'Administrative authorization required.');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Attempt user authentication
 */
function login_user(string $email, string $password): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch();

    if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in_time'] = time();

        return true;
    }

    return false;
}

/**
 * Terminate user session
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
