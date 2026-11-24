<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($pdo === null)
    exit(json_encode(['success'=>false,'error'=>'Database is offline. Please try again later.']));
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    exit(json_encode(['success'=>false,'error'=>'Invalid request.']));

$name    = htmlspecialchars(strip_tags(trim($_POST['c_name']    ?? '')));
$email   = filter_var(trim($_POST['c_email']   ?? ''), FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars(strip_tags(trim($_POST['c_subject'] ?? 'General Inquiry')));
$message = htmlspecialchars(strip_tags(trim($_POST['c_message'] ?? '')));

if (strlen($name) < 2)                          exit(json_encode(['success'=>false,'error'=>'Name is required.']));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit(json_encode(['success'=>false,'error'=>'Valid email is required.']));
if (strlen($message) < 5)                       exit(json_encode(['success'=>false,'error'=>'Message is too short.']));

try {
    $pdo->prepare("INSERT INTO contact_messages (name,email,subject,message,is_read,created_at) VALUES (?,?,?,?,0,NOW())")
        ->execute([$name,$email,$subject,$message]);
    exit(json_encode(['success'=>true]));
} catch (PDOException $e) {
    exit(json_encode(['success'=>false,'error'=>'Server error. Please try again.']));
}
