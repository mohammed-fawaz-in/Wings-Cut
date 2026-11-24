<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { http_response_code(403); exit('Please log in.'); }
if ($pdo === null)                { http_response_code(503); exit('Database offline.'); }

$userId    = (int)$_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';
$bid       = (int)($_GET['booking_id'] ?? 0);
if (!$bid) { http_response_code(400); exit('Invalid request.'); }

try {
    $s = $pdo->prepare('SELECT name,delivery_file,delivery_status FROM bookings WHERE id=? AND (user_id=? OR email=?) LIMIT 1');
    $s->execute([$bid,$userId,$userEmail]);
    $b = $s->fetch();
} catch(PDOException $e) { http_response_code(500); exit('DB error.'); }

if (!$b)                    { http_response_code(403); exit('Access denied.'); }
if (!$b['delivery_file'])   { http_response_code(404); exit('No file available yet.'); }
if (!in_array($b['delivery_status'],['ready','delivered'])) { http_response_code(403); exit('File not ready.'); }

$path = __DIR__.'/../'.$b['delivery_file'];
if (!file_exists($path))    { http_response_code(404); exit('File not found on server.'); }

try { $pdo->prepare('UPDATE bookings SET delivery_status="delivered" WHERE id=?')->execute([$bid]); } catch(Exception $e){}

$dlName = 'WingsCut_'.preg_replace('/[^a-zA-Z0-9]/','_',$b['name']).'_'.$bid.'.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.$dlName.'"');
header('Content-Length: '.filesize($path));
header('Cache-Control: no-cache');
readfile($path);
exit;
