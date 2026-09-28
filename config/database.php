<?php
/**
 * Adishiv Luxury Hotel & Suites
 * PDO Database Connection Singleton
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $dbname = env('DB_NAME', 'adishiv_hotel');
            $username = env('DB_USER', 'root');
            $password = env('DB_PASSWORD', '');
            $charset = env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
                } else {
                    error_log("Database connection error: " . $e->getMessage());
                    die("A temporary database error occurred. Please contact the concierge.");
                }
            }
        }

        return self::$instance;
    }
}

// Global convenience accessor
function get_db(): PDO {
    return Database::getConnection();
}
