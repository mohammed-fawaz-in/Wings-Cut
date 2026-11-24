<?php
/**
 * Wings Cut Admin — Send reply to user via internal chat
 * Dedicated endpoint — only returns JSON, never HTML
 */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../php/db.php';

// Auth check
if (!isset($_SESSION[ADMIN_SESSION_KEY]) || $_SESSION[ADMIN_SESSION_KEY] !== true) {
    exit(json_encode(['success'=>false,'error'=>'Unauthorized.']));
}
if ($pdo === null) {
    exit(json_encode(['success'=>false,'error'=>'Database offline. Please start MySQL in XAMPP.']));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success'=>false,'error'=>'POST required.']));
}

$email   = strtolower(trim($_POST['email']   ?? ''));
$message = trim($_POST['message'] ?? '');
$msgId   = (int)($_POST['msg_id'] ?? 0);

if (!$email)   exit(json_encode(['success'=>false,'error'=>'Email is required.']));
if (!$message) exit(json_encode(['success'=>false,'error'=>'Message cannot be empty.']));

try {
    // Find user by email
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        exit(json_encode([
            'success' => false,
            'error'   => 'No account found for ' . htmlspecialchars($email) . '. The person needs to register on the site first before you can chat with them.'
        ]));
    }

    // Insert the admin message into chat
    $pdo->prepare('INSERT INTO chat_messages (user_id, sender, message, created_at) VALUES (?, "admin", ?, NOW())')
        ->execute([$user['id'], $message]);

    // Mark the contact message as read
    if ($msgId > 0) {
        $pdo->prepare('UPDATE contact_messages SET is_read=1 WHERE id=?')->execute([$msgId]);
    }

    exit(json_encode([
        'success' => true,
        'msg'     => 'Reply sent to ' . htmlspecialchars($user['name']) . "'s chat. They'll see it instantly when logged in."
    ]));

} catch (PDOException $e) {
    error_log('reply_chat error: ' . $e->getMessage());
    exit(json_encode(['success'=>false,'error'=>'Database error: ' . $e->getMessage()]));
}
