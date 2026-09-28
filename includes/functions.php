<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Global Helper Functions & Security Utilities
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

    return ($isNegative ? '-' : '') . CURRENCY_SYMBOL . ' ' . $formatted;
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
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
 * Retrieve dynamic site setting from database (with request memory cache)
 */
function get_setting(string $key, string $default = ''): string {
    static $cache = null;
    $db = get_db();

    if ($cache === null) {
        $cache = [];
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            while ($row = $stmt->fetch()) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // Table might not exist yet or connection error
            return $default;
        }
    }

    return $cache[$key] ?? $default;
}

/**
 * Update dynamic site setting
 */
function update_setting(string $key, string $value): bool {
    $db = get_db();
    try {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                              VALUES (:key, :value) 
                              ON DUPLICATE KEY UPDATE setting_value = :value_update, updated_at = NOW()");
        return $stmt->execute([
            ':key' => $key,
            ':value' => $value,
            ':value_update' => $value
        ]);
    } catch (Exception $e) {
        error_log("Failed to update setting: " . $e->getMessage());
        return false;
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
 * Emit JSON response and exit
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Set flash alert message
 */
function flash_message(string $type, string $message): void {
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
