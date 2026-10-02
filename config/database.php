<?php
/**
 * Adishiv Luxury Hotel & Suites
 * PDO Database Connection Singleton
 */

require_once __DIR__ . '/config.php';

class DatabaseException extends RuntimeException {}

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
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci, sql_mode='STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION', time_zone='+05:30'",
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Log the real driver error outside webroot
                error_log("Database Connection Error: " . $e->getMessage());

                // Throw an exception instead of calling die() so JSON APIs and pages handle it cleanly
                $msg = (defined('APP_DEBUG') && APP_DEBUG && defined('APP_ENV') && APP_ENV !== 'production')
                    ? "Database Connection Error: " . $e->getMessage()
                    : "A temporary database service interruption occurred. Please try again shortly.";

                throw new DatabaseException($msg, (int)$e->getCode(), $e);
            }
        }

        return self::$instance;
    }

    /**
     * Reset instance for tests or reconnection
     */
    public static function reset(): void {
        self::$instance = null;
    }
}

// Global convenience accessor
function get_db(): PDO {
    return Database::getConnection();
}
