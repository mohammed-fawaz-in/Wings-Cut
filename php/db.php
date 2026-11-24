<?php
/**
 * WINGS CUT — Database Connection
 * Silent — never echoes or sends headers.
 * Sets $pdo (PDO object) or null on failure.
 * Sets $db_error (string) with the reason on failure.
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'wingscut_db');
define('DB_USER',    'root');
define('DB_PASS',    '');          // XAMPP default = empty password
define('DB_CHARSET', 'utf8mb4');

$pdo      = null;
$db_error = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    $db_error = $e->getMessage();
    error_log('Wings Cut DB error: ' . $db_error);
    $pdo = null;
}
