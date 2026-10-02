<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Hardened Authentication, RBAC, Timing Protection & Session Security
 */

require_once __DIR__ . '/functions.php';

// Constant dummy hash for constant-time evaluation when user is not found (prevents timing attacks)
const DUMMY_BCRYPT_HASH = '$2y$12$e80MvQG13pB0O2K1gLqjjeKqL55B5YmB7aV9q8b8l9Z2g3h4j5k6m';

const ROLE_PERMISSIONS = [
    'admin' => ['view_dashboard', 'manage_bookings', 'manage_rooms', 'manage_settings', 'view_customers', 'view_messages', 'delete_records'],
    'manager' => ['view_dashboard', 'manage_bookings', 'manage_rooms', 'view_customers', 'view_messages'],
    'frontdesk' => ['view_dashboard', 'manage_bookings', 'view_customers'],
    'guest' => ['view_own_booking']
];

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    ensure_session_started();
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
        try {
            $db = get_db();
            $stmt = $db->prepare("SELECT id, name, email, phone, role, status FROM users WHERE id = :id AND status = 'active' LIMIT 1");
            $stmt->execute([':id' => (int)$_SESSION['user_id']]);
            $currentUser = $stmt->fetch() ?: null;
            if (!$currentUser) {
                logout_user();
            }
        } catch (Throwable $e) {
            error_log("current_user error: " . $e->getMessage());
            return null;
        }
    }

    return $currentUser;
}

/**
 * Strict role check: returns true ONLY for genuine admin role (WP3.3)
 */
function is_admin(): bool {
    $user = current_user();
    return $user && ($user['role'] === 'admin');
}

/**
 * Permission checker based on RBAC map
 */
function has_permission(string $permission): bool {
    $user = current_user();
    if (!$user) return false;
    $role = $user['role'] ?? 'guest';
    $perms = ROLE_PERMISSIONS[$role] ?? [];
    return in_array($permission, $perms, true);
}

/**
 * Guard: Require logged-in guest or staff
 */
function require_login(string $redirect = '/login.php'): void {
    if (!is_logged_in()) {
        flash_message('error', 'Please log in to continue.');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Guard: Require specific roles explicitly (WP3.3)
 */
function require_role(array $allowedRoles, string $redirect = '/admin/login.php'): void {
    $user = current_user();
    if (!$user || !in_array($user['role'], $allowedRoles, true)) {
        flash_message('error', 'Administrative authorization required.');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Guard: Require administrator privileges strictly
 */
function require_admin(string $redirect = '/admin/login.php'): void {
    require_role(['admin'], $redirect);
}

/**
 * Attempt user authentication with rate-limiting, timing equality, and rehash checks (WP3.3)
 *
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'error' => string|null, 'user' => array|null]
 */
function login_user(string $email, string $password): array {
    ensure_session_started();
    $cleanEmail = strtolower(trim($email));

    // 1. Rate limiting: 5 attempts per 15 minutes per email/IP
    if (!check_rate_limit('login_attempt', 5, 900, $cleanEmail)) {
        return [
            'success' => false,
            'error' => 'Too many failed login attempts. For security, access is temporarily locked for 15 minutes.'
        ];
    }

    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $cleanEmail]);
        $user = $stmt->fetch();

        // 2. Equalize execution timing for unknown emails to prevent account enumeration
        $hashToVerify = $user ? $user['password_hash'] : DUMMY_BCRYPT_HASH;
        $isValidPassword = password_verify($password, $hashToVerify);

        if (!$user || !$isValidPassword) {
            return [
                'success' => false,
                'error' => 'Invalid email address or password combination.'
            ];
        }

        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'error' => 'Your account is currently inactive or suspended. Please contact management.'
            ];
        }

        // 3. Password rehash if cost or algorithm needs updating
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd = $db->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
            $upd->execute([':h' => $newHash, ':id' => $user['id']]);
        }

        // 4. Session regeneration & CSRF rotation to prevent session fixation (WP3.2)
        session_regenerate_id(true);
        rotate_csrf_token();

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['is_admin'] = ($user['role'] === 'admin');
        $_SESSION['logged_in_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['created_at'] = time();

        return [
            'success' => true,
            'error' => null,
            'user' => $user
        ];
    } catch (Throwable $e) {
        error_log("login_user exception: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Authentication service temporarily unavailable. Please try again.'
        ];
    }
}

/**
 * Terminate user session with complete cookie destruction
 */
function logout_user(): void {
    ensure_session_started();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
