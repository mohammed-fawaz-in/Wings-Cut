<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) exit(json_encode(['success'=>false,'error'=>'Not logged in.']));
if ($pdo === null) exit(json_encode(['success'=>false,'error'=>'Database offline.']));

$userId    = (int)$_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';
$action    = trim($_POST['action'] ?? $_GET['action'] ?? '');

/* GET payment info */
if ($action === 'get') {
    $bid = (int)($_GET['booking_id'] ?? 0);
    try {
        $s = $pdo->prepare('SELECT b.*,u.name as uname FROM bookings b LEFT JOIN users u ON u.id=b.user_id WHERE b.id=? AND (b.user_id=? OR b.email=?) LIMIT 1');
        $s->execute([$bid,$userId,$userEmail]);
        $booking = $s->fetch();
        if (!$booking) exit(json_encode(['success'=>false,'error'=>'Not found.']));
        $h = $pdo->prepare('SELECT * FROM payment_history WHERE booking_id=? ORDER BY created_at DESC');
        $h->execute([$bid]);
        exit(json_encode(['success'=>true,'booking'=>$booking,'history'=>$h->fetchAll()]));
    } catch(PDOException $e){ exit(json_encode(['success'=>false,'error'=>$e->getMessage()])); }
}

/* Upload screenshot */
if ($action === 'upload_screenshot') {
    $bid = (int)($_POST['booking_id'] ?? 0);
    if (!$bid || empty($_FILES['screenshot'])) exit(json_encode(['success'=>false,'error'=>'Missing data.']));
    $f = $_FILES['screenshot'];
    if (!in_array($f['type'],['image/jpeg','image/jpg','image/png','image/webp']))
        exit(json_encode(['success'=>false,'error'=>'Only JPG/PNG/WEBP.']));
    if ($f['size'] > 5*1024*1024) exit(json_encode(['success'=>false,'error'=>'Max 5MB.']));
    $check = $pdo->prepare('SELECT id FROM bookings WHERE id=? AND (user_id=? OR email=?)');
    $check->execute([$bid,$userId,$userEmail]);
    if (!$check->fetch()) exit(json_encode(['success'=>false,'error'=>'Booking not found.']));
    $ext  = pathinfo($f['name'], PATHINFO_EXTENSION);
    $name = 'pay_'.$bid.'_'.$userId.'_'.time().'.'.$ext;
    $dest = __DIR__.'/../uploads/payment-screenshots/'.$name;
    $web  = 'uploads/payment-screenshots/'.$name;
    if (!move_uploaded_file($f['tmp_name'],$dest)) exit(json_encode(['success'=>false,'error'=>'Upload failed.']));
    try {
        $pdo->prepare('UPDATE bookings SET payment_screenshot=?,payment_verified=0,payment_status=IF(payment_status="unpaid","partial",payment_status) WHERE id=?')
            ->execute([$web,$bid]);
        $amt = (float)($_POST['amount']??0);
        $pdo->prepare('INSERT INTO payment_history (booking_id,user_id,amount,payment_type,screenshot,created_at) VALUES (?,?,?,?,?,NOW())')
            ->execute([$bid,$userId,$amt,'advance',$web]);
        exit(json_encode(['success'=>true,'message'=>'Screenshot uploaded! Admin will verify shortly.']));
    } catch(PDOException $e){ exit(json_encode(['success'=>false,'error'=>$e->getMessage()])); }
}

/* "I Have Paid" — customer flags payment, admin still verifies */
if ($action === 'mark_paid') {
    $bid = (int)($_POST['booking_id'] ?? 0);
    $chk = $pdo->prepare('SELECT id FROM bookings WHERE id=? AND (user_id=? OR email=?)');
    $chk->execute([$bid,$userId,$userEmail]);
    if (!$chk->fetch()) exit(json_encode(['success'=>false,'error'=>'Booking not found.']));
    $pdo->prepare('UPDATE bookings SET payment_note=CONCAT(IFNULL(payment_note,""),"[Customer marked as paid] ") WHERE id=?')->execute([$bid]);
    exit(json_encode(['success'=>true,'message'=>'Noted! Now upload your screenshot so admin can verify.']));
}

exit(json_encode(['success'=>false,'error'=>'Unknown action.']));
