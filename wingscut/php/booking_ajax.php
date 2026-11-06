<?php
/**
 * WINGS CUT — AJAX Booking Handler
 * Returns JSON so JS can show success without page reload
 */
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Invalid request']); exit;
}

function cl($v) { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }
function clean($v) { return trim(strip_tags($v)); }

$name       = clean($_POST['name']       ?? '');
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$country    = clean($_POST['country_code'] ?? '+91');
$phone      = clean($_POST['phone']      ?? '');
$package    = clean($_POST['package']    ?? '');
$date       = clean($_POST['date']       ?? '');
$event_type = clean($_POST['event_type'] ?? '');
$venue      = clean($_POST['venue']      ?? '');
$time_slot  = clean($_POST['time_slot']  ?? '');
$message    = clean($_POST['message']    ?? '');
$fullPhone  = $country . $phone;

$errors = [];
if (strlen($name) < 2)                              $errors[] = 'Full name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Valid email required.';
if (!preg_match('/^[0-9]{10}$/', $phone))           $errors[] = 'Enter exactly 10 digits.';
if (!in_array($package, ['essentials','signature','elite','custom'])) $errors[] = 'Select a package.';
if (empty($date) || strtotime($date) < strtotime('today')) $errors[] = 'Select a valid future date.';
if (!in_array($time_slot, ['morning','evening']))   $errors[] = 'Select a session time.';

if (!empty($errors)) {
    echo json_encode(['ok'=>false,'msg'=>implode(' ', $errors)]); exit;
}

// Check slot limit (max 2 per slot per day)
$slotCheck = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date=? AND time_slot=? AND status NOT IN ('cancelled')");
$slotCheck->execute([$date, $time_slot]);
if ((int)$slotCheck->fetchColumn() >= 2) {
    $label = $time_slot === 'morning' ? 'Morning (10AM–5PM)' : 'Evening (5PM–12AM)';
    echo json_encode(['ok'=>false,'msg'=>"Sorry, the $label session on " . date('d M Y', strtotime($date)) . " is fully booked. Please choose another date or time."]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO bookings
            (name, email, phone, package, booking_date, event_type, venue, time_slot, message, status, created_at)
        VALUES
            (:name, :email, :phone, :package, :booking_date, :event_type, :venue, :time_slot, :message, 'pending', NOW())
    ");
    $stmt->execute([
        ':name'         => $name,
        ':email'        => $email,
        ':phone'        => $fullPhone,
        ':package'      => $package,
        ':booking_date' => $date,
        ':event_type'   => $event_type,
        ':venue'        => $venue,
        ':time_slot'    => $time_slot,
        ':message'      => $message,
    ]);
    $bookingId = 'WC-' . str_pad($pdo->lastInsertId(), 4, '0', STR_PAD_LEFT);
    echo json_encode([
        'ok'        => true,
        'msg'       => 'Booking received!',
        'booking_id'=> $bookingId,
        'name'      => $name,
    ]);
} catch (PDOException $e) {
    echo json_encode(['ok'=>false,'msg'=>'Database error. Please try again.']);
}