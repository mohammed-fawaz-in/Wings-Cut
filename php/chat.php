<?php
/**
 * Wings Cut — User Chat API
 * Handles: send, history, poll
 */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db.php';

// Must be logged in
if (empty($_SESSION['user_id'])) {
    exit(json_encode(['success'=>false,'error'=>'Not logged in.','messages'=>[]]));
}
if ($pdo === null) {
    exit(json_encode(['success'=>false,'error'=>'Database offline.','messages'=>[]]));
}

$userId = (int)$_SESSION['user_id'];
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

/* Send */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    $msg = trim($_POST['message'] ?? '');
    if (!$msg || strlen($msg) > 3000) {
        exit(json_encode(['success'=>false,'error'=>'Invalid message.']));
    }
    try {
        $pdo->prepare('INSERT INTO chat_messages (user_id, sender, message, created_at) VALUES (?, "user", ?, NOW())')
            ->execute([$userId, $msg]);
        exit(json_encode(['success'=>true, 'id'=>(int)$pdo->lastInsertId()]));
    } catch (PDOException $e) {
        exit(json_encode(['success'=>false,'error'=>'DB error.']));
    }
}

/* History */
if ($action === 'history') {
    try {
        $stmt = $pdo->prepare('SELECT id, sender, message, created_at FROM chat_messages WHERE user_id=? ORDER BY created_at ASC LIMIT 100');
        $stmt->execute([$userId]);
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Mark admin messages as read
        $pdo->prepare('UPDATE chat_messages SET is_read=1 WHERE user_id=? AND sender="admin" AND is_read=0')
            ->execute([$userId]);
        exit(json_encode(['success'=>true, 'messages'=>$msgs]));
    } catch (PDOException $e) {
        exit(json_encode(['success'=>true, 'messages'=>[]]));
    }
}

/* POLL (new messages since last ID) */
if ($action === 'poll') {
    $since = max(0, (int)($_GET['since'] ?? 0));
    try {
        $stmt = $pdo->prepare('SELECT id, sender, message, created_at FROM chat_messages WHERE user_id=? AND id>? ORDER BY created_at ASC LIMIT 50');
        $stmt->execute([$userId, $since]);
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Mark admin messages as read
        $pdo->prepare('UPDATE chat_messages SET is_read=1 WHERE user_id=? AND sender="admin" AND is_read=0')
            ->execute([$userId]);
        exit(json_encode(['success'=>true, 'messages'=>$msgs]));
    } catch (PDOException $e) {
        exit(json_encode(['success'=>true, 'messages'=>[]]));
    }
}

exit(json_encode(['success'=>false,'error'=>'Unknown action.','messages'=>[]]));
