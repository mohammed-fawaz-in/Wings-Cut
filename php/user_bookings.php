<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) exit(json_encode(['success'=>false,'error'=>'Not logged in.','bookings'=>[]]));
if ($pdo === null)               exit(json_encode(['success'=>false,'error'=>'Database offline.','bookings'=>[]]));

$userId = (int)$_SESSION['user_id'];
$email  = $_SESSION['user_email'] ?? '';

try {
    // Include all payment and delivery fields
    $stmt = $pdo->prepare(
        'SELECT id, package, booking_date, time_slot, event_type, venue, status,
                total_amount, advance_paid, pending_amount, payment_status,
                payment_screenshot, payment_verified, payment_note,
                delivery_file, delivery_status, created_at
         FROM bookings
         WHERE user_id=? OR email=?
         ORDER BY created_at DESC
         LIMIT 20'
    );
    $stmt->execute([$userId, $email]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    exit(json_encode(['success'=>true, 'bookings'=>$bookings]));
} catch (PDOException $e) {
    exit(json_encode(['success'=>false,'error'=>'DB error.','bookings'=>[]]));
}
