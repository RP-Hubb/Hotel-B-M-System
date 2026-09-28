<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Global Configuration & Environment Loader
 */

// Start session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Function to load .env variables
function load_env($filePath) {
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
            // Remove surrounding quotes
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

// Load .env from root directory
load_env(__DIR__ . '/../.env');

// Environment Helper
function env($key, $default = null) {
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
define('APP_DEBUG', env('APP_DEBUG', true));
define('APP_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/'));
define('CURRENCY_CODE', env('CURRENCY_CODE', 'INR'));
define('CURRENCY_SYMBOL', env('CURRENCY_SYMBOL', '₹'));
define('CSRF_TOKEN_KEY', env('CSRF_TOKEN_KEY', 'adishiv_csrf_token'));

// Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
