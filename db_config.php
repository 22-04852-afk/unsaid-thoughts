<?php
/**
 * Central database configuration for production-safe deployment.
 *
 * Override these values with environment variables on your server:
 * DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
 */

define('DB_HOST', getenv('DB_HOST') ?: 'sql301.infinityfree.com');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'if0_41418874_unsaidthoughts');
define('DB_USER', getenv('DB_USER') ?: 'if0_41418874');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'Sayth3nam317');

if (!function_exists('dbConnect')) {
    function dbConnect($withDatabase = true)
    {
        $database = $withDatabase ? DB_NAME : '';
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, $database, DB_PORT);

        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');
        return $conn;
    }
}
