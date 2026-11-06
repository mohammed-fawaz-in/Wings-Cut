<?php
/**
 * WINGS CUT — Booking Form Handler
 * Validates input, saves to MySQL, sends confirmation email
 */

session_start();
require_once 'db.php';

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php#booking');
    exit;
}

// ---- Sanitize & Validate ----
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val)));
}

$name       = clean($_POST['name']       ?? '');
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone      = clean($_POST['phone']      ?? '');
$package    = clean($_POST['package']    ?? '');
$date       = clean($_POST['date']       ?? '');
$event_type = clean($_POST['event_type'] ?? '');
$venue      = clean($_POST['venue']      ?? '');
$time_slot  = clean($_POST['time_slot']  ?? '');
$message    = clean($_POST['message']    ?? '');
$errors     = [];

if (strlen($name) < 2)                             $errors[] = "Full name is required.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = "Valid email is required.";
if (!preg_match('/^[\d\s\+\-]{7,15}$/', $phone))  $errors[] = "Valid phone number is required.";
if (empty($package))                               $errors[] = "Please select a package.";
if (empty($date))                                  $errors[] = "Please select a booking date.";
if (!empty($date)) {
    $selected = new DateTime($date);
    $today    = new DateTime('today');
    if ($selected <= $today) $errors[] = "Booking date must be in the future.";
}

if (!empty($errors)) {
    $_SESSION['booking_error'] = implode(' ', $errors);
    header('Location: ../index.php#booking');
    exit;
}

// ---- Check slot availability (max 2 per slot per day) ----
$slotCheck = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date=? AND time_slot=? AND status NOT IN ('cancelled')");
$slotCheck->execute([$date, $time_slot]);
if ((int)$slotCheck->fetchColumn() >= 2) {
    $_SESSION['booking_error'] = "Sorry, the " . ucfirst($time_slot) . " session on " . date('d M Y', strtotime($date)) . " is fully booked. Please choose another date or time.";
    header('Location: ../index.php#booking');
    exit;
}

// ---- Insert into Database ----
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
        ':phone'        => $phone,
        ':package'      => $package,
        ':booking_date' => $date,
        ':event_type'   => $event_type,
        ':venue'        => $venue,
        ':time_slot'    => $time_slot,
        ':message'      => $message,
    ]);
    $bookingId = $pdo->lastInsertId();

} catch (PDOException $e) {
    error_log('Booking DB error: ' . $e->getMessage());
    $_SESSION['booking_error'] = "A database error occurred. Please try again.";
    header('Location: ../index.php#booking');
    exit;
}

// ---- Send Confirmation Email (Optional - requires mail server) ----
$subject     = "Booking Confirmation — Wings Cut Studio";
$emailBody   = "Dear {$name},\n\nThank you for booking with Wings Cut Studio!\n\n"
             . "Booking Details:\n"
             . "  Package     : " . ucfirst($package) . "\n"
             . "  Date        : " . date('d M Y', strtotime($date)) . "\n"
             . "  Event Type  : " . ucfirst($event_type ?: 'Not specified') . "\n\n"
             . "We will confirm your booking within 24 hours.\n\n"
             . "Regards,\nWings Cut Studio\n+91 98765 43210";
$emailHeaders = "From: Wings Cut Studio <hello@wingscut.studio>\r\nReply-To: hello@wingscut.studio\r\n";

// Uncomment to enable email
// @mail($email, $subject, $emailBody, $emailHeaders);

// ---- Redirect with success ----
$_SESSION['booking_success'] = true;
header('Location: ../index.php#booking');
exit;
