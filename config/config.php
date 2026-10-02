<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Global Configuration & Environment Loader
 */

// Function to load .env variables
function load_env(string $filePath): void {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (preg_match('/^"(.*)"$/', $val, $m) || preg_match("/^'(.*)'$/", $val, $m)) {
                $val = $m[1];
            }
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }
}

// Load .env from root directory (or parent if called from public/src)
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $envPath = dirname(__DIR__, 2) . '/.env';
}
load_env($envPath);

// Environment Helper
function env(string $key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $default;
    }
    if ($val === 'true' || $val === '(true)') return true;
    if ($val === 'false' || $val === '(false)') return false;
    if ($val === 'null' || $val === '(null)') return null;
    return $val;
}

// Application Constants
define('APP_NAME', env('APP_NAME', 'Adishiv Hotel & Suites'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', (bool)env('APP_DEBUG', false));
define('APP_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/'));

// Currency Constants (Prefixed with APP_ to avoid collision with Linux PHP nl_langinfo CURRENCY_SYMBOL)
define('APP_CURRENCY_SYMBOL', env('APP_CURRENCY_SYMBOL', env('CURRENCY_SYMBOL', '₹')));
define('APP_CURRENCY_CODE', env('APP_CURRENCY_CODE', env('CURRENCY_CODE', 'INR')));
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', APP_CURRENCY_SYMBOL);
}
if (!defined('CURRENCY_CODE')) {
    define('CURRENCY_CODE', APP_CURRENCY_CODE);
}

define('APP_CSRF_TOKEN_KEY', env('CSRF_TOKEN_KEY', 'adishiv_csrf_token'));
define('CSRF_TOKEN_KEY', APP_CSRF_TOKEN_KEY);

// Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));

// Production Safeguard: Refuse to boot if debug is active in production
if (APP_ENV === 'production' && APP_DEBUG) {
    http_response_code(500);
    echo "Configuration Error: APP_DEBUG must be false in production environments.\n";
    exit(1);
}

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

/**
 * Lazy Session Initialization with Hardened Security
 * Sessions are NOT started automatically on every request.
 * Public GET APIs must remain sessionless to avoid session file lock contention.
 */
function ensure_session_started(string $role = 'guest'): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    // Separate cookie names for staff and guests
    $cookieName = ($role === 'admin' || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin'))
        ? 'ADISHIV_STAFF_SESS'
        : 'ADISHIV_GUEST_SESS';

    session_name($cookieName);

    session_set_cookie_params([
        'lifetime' => 0, // Session cookie expires when browser is closed
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_start();

    // Enforce idle timeout and absolute session lifetime (WP3.2)
    check_session_lifetime($role);
}

/**
 * Enforce idle timeout and maximum lifetime
 */
function check_session_lifetime(string $role): void {
    $now = time();
    $isAdmin = ($role === 'admin' || !empty($_SESSION['is_admin']));

    // Idle timeout: 30 minutes for admin, 24 hours for guests
    $idleTimeout = $isAdmin ? 1800 : 86400;

    // Absolute lifetime: 8 hours for admin, 7 days for guests
    $absoluteLifetime = $isAdmin ? 28800 : 604800;

    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $idleTimeout) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        return;
    }
    $_SESSION['last_activity'] = $now;

    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    } elseif (($now - $_SESSION['created_at']) > $absoluteLifetime) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        return;
    }
}
