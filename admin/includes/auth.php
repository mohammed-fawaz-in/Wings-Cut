<?php
/**
 * Wings Cut — Admin Auth Guard
 * Include at the TOP of every admin page (after nothing else).
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../php/db.php';

// DB down → show friendly error
if ($pdo === null) {
    http_response_code(503);
    ?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Database Offline — Wings Cut</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet"/>
<style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Outfit',sans-serif;background:#fafafa;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}.box{background:#fff;border:1px solid #e8e8e8;border-radius:16px;padding:48px 40px;max-width:440px;width:100%;text-align:center;box-shadow:0 4px 32px rgba(0,0,0,.08)}.icon{font-size:52px;margin-bottom:16px}h2{font-size:22px;font-weight:700;color:#111;margin-bottom:12px}p{font-size:14px;color:#666;line-height:1.65;margin-bottom:8px}.step{background:#fff8f8;border:1px solid #fde8e8;border-radius:8px;padding:14px 16px;margin:16px 0;font-size:13px;color:#8B0000;text-align:left;line-height:1.7}a{display:inline-block;margin-top:20px;background:#8B0000;color:#fff;padding:10px 24px;border-radius:99px;font-size:14px;font-weight:600;text-decoration:none}code{background:#f3f3f3;padding:2px 7px;border-radius:4px;font-size:12px}</style>
</head><body><div class="box">
<div class="icon">🔌</div>
<h2>MySQL is Offline</h2>
<p>Wings Cut Admin cannot connect to <code>wingscut_db</code>.</p>
<div class="step">
  1. Open <strong>XAMPP Control Panel</strong><br>
  2. Click <strong>Start</strong> next to <strong>MySQL</strong><br>
  3. Wait for the green indicator, then click the button below
</div>
<p style="font-size:12px;color:#aaa">If MySQL is already green, check that <code>DB_USER</code> and <code>DB_PASS</code> in <code>php/db.php</code> are correct.</p>
<a href="login.php">↩ Try Again</a>
</div></body></html><?php
    exit;
}

// Not logged in → login page
if (!isset($_SESSION[ADMIN_SESSION_KEY]) || $_SESSION[ADMIN_SESSION_KEY] !== true) {
    header('Location: login.php'); exit;
}

// Session expiry
if (!isset($_SESSION['created'])) $_SESSION['created'] = time();
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset(); session_destroy();
    header('Location: login.php?timeout=1'); exit;
}
$_SESSION['last_activity'] = time();

// Helper functions
if (!function_exists('adminRedirect')) {
    function adminRedirect($url, $msg = '', $type = 'success') {
        if ($msg) $_SESSION['flash_msg'] = ['text'=>$msg,'type'=>$type];
        header("Location: $url"); exit;
    }
}
if (!function_exists('getFlash')) {
    function getFlash() {
        if (!isset($_SESSION['flash_msg'])) return null;
        $f = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $f;
    }
}
if (!function_exists('cl')) {
    function cl($v) { return htmlspecialchars(strip_tags(trim((string)$v))); }
}
