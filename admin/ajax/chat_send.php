<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../php/db.php';
require_once __DIR__ . '/../config.php';

if ($pdo === null) exit(json_encode(['success'=>false,'error'=>'Database offline.']));
if (!isset($_SESSION[ADMIN_SESSION_KEY]) || $_SESSION[ADMIN_SESSION_KEY] !== true)
    exit(json_encode(['success'=>false,'error'=>'Unauthorized.']));

$userId  = (int)($_POST['user_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
if (!$userId || !$message || strlen($message) > 2000)
    exit(json_encode(['success'=>false,'error'=>'Invalid input.']));

try {
    $pdo->prepare('INSERT INTO chat_messages (user_id,sender,message,created_at) VALUES (?,"admin",?,NOW())')
        ->execute([$userId, $message]);
    exit(json_encode(['success'=>true,'id'=>(int)$pdo->lastInsertId()]));
} catch (PDOException $e) {
    exit(json_encode(['success'=>false,'error'=>'DB error.']));
}
