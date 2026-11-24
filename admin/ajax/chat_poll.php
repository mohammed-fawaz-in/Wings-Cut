<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../php/db.php';
require_once __DIR__ . '/../config.php';

if ($pdo === null) exit(json_encode(['messages'=>[]]));
if (!isset($_SESSION[ADMIN_SESSION_KEY]) || $_SESSION[ADMIN_SESSION_KEY] !== true)
    exit(json_encode(['messages'=>[]]));

$userId = (int)($_GET['user_id'] ?? 0);
$since  = (int)($_GET['since']   ?? 0);
if (!$userId) exit(json_encode(['messages'=>[]]));

try {
    $stmt = $pdo->prepare('SELECT id,sender,message,created_at FROM chat_messages WHERE user_id=? AND id>? ORDER BY created_at ASC LIMIT 50');
    $stmt->execute([$userId, $since]);
    $pdo->prepare('UPDATE chat_messages SET is_read=1 WHERE user_id=? AND sender="user" AND is_read=0')->execute([$userId]);
    exit(json_encode(['messages'=>$stmt->fetchAll()]));
} catch (PDOException $e) {
    exit(json_encode(['messages'=>[]]));
}
