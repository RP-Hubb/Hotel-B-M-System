<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Global Helper Functions, Security Utilities & Rate Limiting
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Escape HTML output for XSS prevention
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format currency with Indian Rupee symbol (₹)
 * Uses APP_CURRENCY_SYMBOL to avoid collision with Linux PHP nl_langinfo CURRENCY_SYMBOL
 */
function format_inr(float|int|string $amount): string {
    $num = (float)$amount;
    $isNegative = $num < 0;
    $num = abs($num);
    
    // Split integer and decimal
    $parts = explode('.', number_format($num, 2, '.', ''));
    $intPart = $parts[0];
    $decPart = $parts[1];

    // Indian numbering format: 3 digits, then groups of 2
    if (strlen($intPart) > 3) {
        $lastThree = substr($intPart, -3);
        $remaining = substr($intPart, 0, -3);
        $remaining = preg_replace('/(\d)(?=(\d{2})+(?!\d))/', '$1,', $remaining);
        $formatted = $remaining . ',' . $lastThree;
    } else {
        $formatted = $intPart;
    }

    if ($decPart !== '00') {
        $formatted .= '.' . $decPart;
    }

    $symbol = defined('APP_CURRENCY_SYMBOL') ? APP_CURRENCY_SYMBOL : '₹';
    return ($isNegative ? '-' : '') . $symbol . ' ' . $formatted;
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    ensure_session_started();
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

/**
 * Rotate CSRF token (called upon privilege escalation or login)
 */
function rotate_csrf_token(): string {
    ensure_session_started();
    $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    return $_SESSION[CSRF_TOKEN_KEY];
}

/**
 * Render a hidden CSRF input field
 */
function csrf_field(): string {
    $token = e(csrf_token());
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verify submitted CSRF token
 */
function verify_csrf_token(?string $token = null): bool {
    ensure_session_started();
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    $sessionToken = $_SESSION[CSRF_TOKEN_KEY] ?? '';
    if (empty($sessionToken) || empty($token)) {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

/**
 * Retrieve dynamic site setting from database (with memory caching & graceful fallback)
 */
function get_setting(string $key, string $default = ''): string {
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            $db = get_db();
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            while ($row = $stmt->fetch()) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            // Database unreachable or table does not exist: return default gracefully
            return $default;
        }
    }

    return $cache[$key] ?? $default;
}

/**
 * Update dynamic site setting
 */
function update_setting(string $key, string $value): bool {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                              VALUES (:key, :value) 
                              ON DUPLICATE KEY UPDATE setting_value = :value_update, updated_at = NOW()");
        return $stmt->execute([
            ':key' => $key,
            ':value' => $value,
            ':value_update' => $value
        ]);
    } catch (Throwable $e) {
        error_log("Failed to update setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Send standard HTTP security headers (WP3.4)
 */
function send_security_headers(): void {
    if (headers_sent()) {
        return;
    }

    // Modern Content Security Policy allowing self, Google Fonts, and GSAP CDN
    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com data:; "
         . "img-src 'self' data: https:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'none'; "
         . "base-uri 'self'; "
         . "form-action 'self';";

    header("Content-Security-Policy: {$csp}");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if ($isHttps) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
}

/**
 * Get client IP address accurately with proxy support
 */
function get_client_ip(): string {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '127.0.0.1';
}

/**
 * Rate Limiting Utility (WP2.6 / WP3.3)
 * Enforces per-IP and per-action caps via database ledger
 *
 * @param string $action       Action identifier (e.g. 'booking_create', 'login_fail', 'contact_submit')
 * @param int    $maxAttempts  Maximum allowed attempts in window
 * @param int    $decaySeconds Time window in seconds
 * @param string|null $identifier Optional custom identifier (e.g. email)
 * @return bool True if under limit, False if throttled
 */
function check_rate_limit(string $action, int $maxAttempts, int $decaySeconds, ?string $identifier = null): bool {
    $ip = get_client_ip();
    $actionKey = $identifier ? "{$action}:" . md5(strtolower(trim($identifier))) : $action;

    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT attempt_count, first_attempt_at FROM rate_limits WHERE action_key = :action AND ip_address = :ip LIMIT 1");
        $stmt->execute([':action' => $actionKey, ':ip' => $ip]);
        $record = $stmt->fetch();

        $now = time();

        if ($record) {
            $firstTime = strtotime($record['first_attempt_at']);
            if (($now - $firstTime) > $decaySeconds) {
                // Window has reset
                $reset = $db->prepare("UPDATE rate_limits SET attempt_count = 1, first_attempt_at = NOW(), last_attempt_at = NOW() WHERE action_key = :action AND ip_address = :ip");
                $reset->execute([':action' => $actionKey, ':ip' => $ip]);
                return true;
            }

            if ((int)$record['attempt_count'] >= $maxAttempts) {
                return false;
            }

            // Increment count
            $inc = $db->prepare("UPDATE rate_limits SET attempt_count = attempt_count + 1, last_attempt_at = NOW() WHERE action_key = :action AND ip_address = :ip");
            $inc->execute([':action' => $actionKey, ':ip' => $ip]);
            return true;
        } else {
            // First attempt
            $ins = $db->prepare("INSERT INTO rate_limits (action_key, ip_address, attempt_count, first_attempt_at) VALUES (:action, :ip, 1, NOW())");
            $ins->execute([':action' => $actionKey, ':ip' => $ip]);
            return true;
        }
    } catch (Throwable $e) {
        // Fallback open if rate limits table is not available
        error_log("Rate limit error: " . $e->getMessage());
        return true;
    }
}

/**
 * Retrieve all room types
 */
function get_room_types(bool $activeOnly = true): array {
    $db = get_db();
    $sql = "SELECT * FROM room_types";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, price_per_night ASC";
    return $db->query($sql)->fetchAll();
}

/**
 * Retrieve single room type by slug
 */
function get_room_type_by_slug(string $slug): ?array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM room_types WHERE slug = :slug AND is_active = 1 LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Retrieve single room type by ID
 */
function get_room_type_by_id(int $id): ?array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM room_types WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Retrieve all amenities
 */
function get_amenities(bool $highlightOnly = false): array {
    $db = get_db();
    $sql = "SELECT * FROM amenities";
    if ($highlightOnly) {
        $sql .= " WHERE is_highlight = 1";
    }
    $sql .= " ORDER BY category ASC, is_highlight DESC";
    return $db->query($sql)->fetchAll();
}

/**
 * Retrieve amenities specific to a room type via pivot table (WP4.4)
 */
function get_room_type_amenities(int $roomTypeId): array {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT a.* 
        FROM amenities a
        JOIN room_type_amenities rta ON rta.amenity_id = a.id
        WHERE rta.room_type_id = :room_type_id
        ORDER BY a.category ASC, a.is_highlight DESC
    ");
    $stmt->execute([':room_type_id' => $roomTypeId]);
    return $stmt->fetchAll();
}

/**
 * Emit JSON response and exit cleanly
 */
function json_response(array $data, int $statusCode = 200, array $headers = []): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        send_security_headers();
        foreach ($headers as $hKey => $hVal) {
            header("{$hKey}: {$hVal}");
        }
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Set flash alert message
 */
function flash_message(string $type, string $message): void {
    ensure_session_started();
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Get and clear flash alert messages
 */
function get_flash_messages(): array {
    ensure_session_started();
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Asset URL helper (handles relative directory depth)
 */
function asset_url(string $path): string {
    $path = ltrim($path, '/');
    return APP_URL . '/' . $path;
}
