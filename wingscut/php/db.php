<?php
/**
 * WINGS CUT — Database Connection
 * Uses PDO for secure, prepared statements
 *
 * Setup: Create a MySQL database named 'wingscut_db'
 * Then run the SQL from database/schema.sql
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'wingscut_db');
define('DB_USER',    'root');        // Change to your MySQL username
define('DB_PASS',    '');            // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST, DB_NAME, DB_CHARSET
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());

    // Return JSON if called from an AJAX context (auth, booking, chat, etc.)
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database unavailable. Please try again later.']);
        exit;
    }
    die("DB Error: " . $e->getMessage() . "\n");
}