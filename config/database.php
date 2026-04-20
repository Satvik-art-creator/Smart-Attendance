<?php
/**
 * Database Configuration & Connection
 * Smart Attendance Tracker
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// ── Database credentials ──
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_attendance');
define('DB_USER', 'root');
define('DB_PASS', '');          // default XAMPP password is empty
define('DB_CHARSET', 'utf8mb4');

// ── Application constants ──
define('OTP_EXPIRY_SECONDS', 30);          // OTP valid for 30 seconds
define('MIN_ATTENDANCE_PERCENT', 75);      // 75 % minimum rule
define('APP_NAME', 'Smart Attendance Tracker');

/**
 * Get a PDO database connection (singleton)
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';dbname='    . DB_NAME
             . ';charset='   . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
