<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($pdo === null) exit(json_encode(['available'=>true,'remaining'=>2]));

$date = trim($_GET['date'] ?? '');
$slot = trim($_GET['slot'] ?? '');
if (!$date || !$slot) exit(json_encode(['available'=>false,'remaining'=>0]));

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date=? AND time_slot=? AND status NOT IN ('cancelled')");
    $stmt->execute([$date,$slot]);
    $count = (int)$stmt->fetchColumn();
    exit(json_encode(['available'=>$count < 2,'remaining'=>max(0, 2-$count)]));
} catch (PDOException $e) {
    exit(json_encode(['available'=>true,'remaining'=>2]));
}
