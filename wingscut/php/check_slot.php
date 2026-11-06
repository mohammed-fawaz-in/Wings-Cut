<?php
/**
 * WINGS CUT — Slot Availability Checker
 * Max 2 morning + 2 evening sessions per day
 */
require_once 'db.php';
header('Content-Type: application/json');

$date = trim($_GET['date'] ?? '');
$slot = trim($_GET['slot'] ?? '');
$maxPerSlot = 2;

if (!$date || !$slot || !in_array($slot, ['morning','evening'])) {
    echo json_encode(['available' => false, 'remaining' => 0, 'msg' => 'Invalid input']);
    exit;
}

// Count confirmed/pending bookings for this date + slot
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM bookings
    WHERE booking_date = ?
    AND time_slot = ?
    AND status NOT IN ('cancelled')
");
$stmt->execute([$date, $slot]);
$booked = (int)$stmt->fetchColumn();

$remaining = $maxPerSlot - $booked;
$available = $remaining > 0;

echo json_encode([
    'available' => $available,
    'booked'    => $booked,
    'remaining' => max(0, $remaining),
    'max'       => $maxPerSlot,
    'msg'       => $available
        ? "$remaining slot(s) available"
        : "Session fully booked"
]);
