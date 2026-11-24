<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../php/db.php';
require_once __DIR__ . '/../config.php';

if ($pdo === null) exit(json_encode(['count'=>0]));
if (!isset($_SESSION[ADMIN_SESSION_KEY]) || $_SESSION[ADMIN_SESSION_KEY] !== true)
    exit(json_encode(['count'=>0]));

try {
    $count = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='user' AND is_read=0")->fetchColumn();
    exit(json_encode(['count'=>(int)$count]));
} catch (PDOException $e) {
    exit(json_encode(['count'=>0]));
}
