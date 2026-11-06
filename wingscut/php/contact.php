<?php
/**
 * WINGS CUT — Contact Form Handler
 * Accepts JSON (AJAX) or standard POST
 */

require_once 'db.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
          (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false);

function clean($val) {
    return htmlspecialchars(strip_tags(trim($val)));
}

function jsonRes($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonRes(false, 'Invalid request method.');
}

$name    = clean($_POST['c_name']    ?? '');
$email   = filter_var(trim($_POST['c_email']   ?? ''), FILTER_SANITIZE_EMAIL);
$subject = clean($_POST['c_subject'] ?? 'General Inquiry');
$message = clean($_POST['c_message'] ?? '');

if (strlen($name) < 2)                           jsonRes(false, 'Please enter your name.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  jsonRes(false, 'Please enter a valid email.');
if (strlen($message) < 5)                        jsonRes(false, 'Please enter a message.');

try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, subject, message, created_at)
        VALUES (:name, :email, :subject, :message, NOW())
    ");
    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':subject' => $subject,
        ':message' => $message,
    ]);
} catch (PDOException $e) {
    error_log('Contact DB error: ' . $e->getMessage());
    jsonRes(false, 'Could not save your message. Please try again.');
}

// Notify studio by email (optional)
$mailBody    = "New Contact Message from Wings Cut Website\n\n"
             . "Name   : {$name}\nEmail  : {$email}\n"
             . "Subject: {$subject}\n\nMessage:\n{$message}";
$mailHeaders = "From: {$name} <{$email}>\r\nReply-To: {$email}\r\n";
// @mail('hello@wingscut.studio', "Contact: {$subject}", $mailBody, $mailHeaders);

jsonRes(true, 'Message sent successfully!');
