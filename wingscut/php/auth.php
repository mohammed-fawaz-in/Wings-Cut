<?php
/**
 * WINGS CUT — Admin Auth Helper
 * Include this at the top of every protected admin page
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../php/db.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) session_start();

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Check session timeout
if (isset($_SESSION[ADMIN_SESSION_KEY]) && $_SESSION[ADMIN_SESSION_KEY] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: ../login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
} else {
    header('Location: ../login.php');
    exit;
}

// Helper: redirect with message
function adminRedirect($url, $msg = '', $type = 'success') {
    if ($msg) $_SESSION['flash_msg'] = ['text' => $msg, 'type' => $type];
    header("Location: $url");
    exit;
}

// Helper: flash message
function getFlash() {
    if (isset($_SESSION['flash_msg'])) {
        $f = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $f;
    }
    return null;
}

// Helper: sanitize
function cl($v) { return htmlspecialchars(strip_tags(trim($v))); }
