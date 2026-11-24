<?php
/* Wings cut - php/chat.php
   Handles: send message, load history, poll for new messages
   Always returns JSON. */

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db.php';

// Must be logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

/* Send message */
if ($method === 'POST' && $action === 'send') {
    $message = trim($_POST['message'] ?? '');
    if (!$message || strlen($message) > 2000) {
        echo json_encode(['success' => false, 'error' => 'Invalid message.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO chat_messages (user_id, sender, message, created_at) VALUES (?, "user", ?, NOW())'
        );
        $stmt->execute([$userId, $message]);
        $id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (PDOException $e) {
        error_log('Chat send error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error.']);
    }
    exit;
}

/* History */
if ($action === 'history') {
    try {
        $stmt = $pdo->prepare(
            'SELECT id, sender, message, created_at FROM chat_messages
             WHERE user_id = ? ORDER BY created_at ASC LIMIT 100'
        );
        $stmt->execute([$userId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (PDOException $e) {
        error_log('Chat history error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'messages' => []]);
    }
    exit;
}

/* POLL (new messages since last id) */
if ($action === 'poll') {
    $since = (int) ($_GET['since'] ?? 0);
    try {
        $stmt = $pdo->prepare(
            'SELECT id, sender, message, created_at FROM chat_messages
             WHERE user_id = ? AND id > ? ORDER BY created_at ASC LIMIT 50'
        );
        $stmt->execute([$userId, $since]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (PDOException $e) {
        error_log('Chat poll error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'messages' => []]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);