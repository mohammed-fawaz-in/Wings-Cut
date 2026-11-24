<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($pdo === null)
    exit(json_encode(['success'=>false,'error'=>'Database is offline. Please start MySQL in XAMPP.']));
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    exit(json_encode(['success'=>false,'error'=>'Invalid request method.']));

function bClean($v) { return htmlspecialchars(strip_tags(trim((string)$v))); }

$name      = bClean($_POST['name']       ?? '');
$email     = strtolower(trim($_POST['email'] ?? ''));
$phone     = preg_replace('/[^0-9+\-\s()]/', '', $_POST['phone'] ?? '');
$package   = bClean($_POST['package']    ?? '');
$date      = bClean($_POST['date']       ?? '');
$eventType = bClean($_POST['event_type'] ?? '');
$venue     = bClean($_POST['venue']      ?? '');
$timeSlot  = bClean($_POST['time_slot']  ?? '');
$message   = bClean($_POST['message']    ?? '');

// Validation
if (strlen($name) < 2)
    exit(json_encode(['success'=>false,'error'=>'Please enter your full name.']));
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    exit(json_encode(['success'=>false,'error'=>'Please enter a valid email address.']));
if (strlen(preg_replace('/\D/','',$phone)) < 7)
    exit(json_encode(['success'=>false,'error'=>'Please enter a valid phone number.']));
if (empty($package))
    exit(json_encode(['success'=>false,'error'=>'Please select a package.']));
if (empty($date))
    exit(json_encode(['success'=>false,'error'=>'Please select a date.']));
if (empty($timeSlot))
    exit(json_encode(['success'=>false,'error'=>'Please select a session time (morning or evening).']));

// Date must be future
try {
    $sel   = new DateTime($date);
    $today = new DateTime('today');
    if ($sel <= $today)
        exit(json_encode(['success'=>false,'error'=>'Booking date must be in the future.']));
} catch (Exception $e) {
    exit(json_encode(['success'=>false,'error'=>'Invalid date format.']));
}

$userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

try {
    // Check slot availability (max 2 per slot per day)
    $sc = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND time_slot = ? AND status NOT IN ('cancelled')");
    $sc->execute([$date, $timeSlot]);
    if ((int)$sc->fetchColumn() >= 2)
        exit(json_encode(['success'=>false,'error'=>'This session is fully booked. Please choose a different date or time.']));

    // Insert — use only columns that definitely exist
    // Build query dynamically to handle tables with or without optional columns
    $cols   = ['name','email','phone','package','booking_date','status','created_at'];
    $vals   = [$name, $email, $phone, $package, $date, 'pending', date('Y-m-d H:i:s')];
    $marks  = ['?','?','?','?','?','?','?'];

    if ($userId !== null) { $cols[] = 'user_id';    $vals[] = $userId;    $marks[] = '?'; }
    if ($eventType)       { $cols[] = 'event_type'; $vals[] = $eventType; $marks[] = '?'; }
    if ($venue)           { $cols[] = 'venue';       $vals[] = $venue;     $marks[] = '?'; }
    if ($timeSlot)        { $cols[] = 'time_slot';  $vals[] = $timeSlot;  $marks[] = '?'; }
    if ($message)         { $cols[] = 'message';    $vals[] = $message;   $marks[] = '?'; }

    $sql = 'INSERT INTO bookings (' . implode(',',$cols) . ') VALUES (' . implode(',',$marks) . ')';
    $pdo->prepare($sql)->execute($vals);

    exit(json_encode(['success'=>true, 'booking_id'=>(int)$pdo->lastInsertId()]));

} catch (PDOException $e) {
    error_log('Booking insert error: ' . $e->getMessage());
    // Return the actual DB error so you can debug it
    exit(json_encode(['success'=>false,'error'=>'Database error: ' . $e->getMessage()]));
}
