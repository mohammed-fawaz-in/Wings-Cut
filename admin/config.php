<?php
/**
 * WINGS CUT — Admin Configuration
 * Password is stored as bcrypt hash — never plain text
 */

// Admin Credentials
// To change password: replace the hash below with a new one
// Generate new hash by visiting: admin/generate_hash.php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', ''); // Auto-generated on first run — see below

// Session Config
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('ADMIN_SESSION_KEY', 'wingscut_admin_logged_in');

// Site Info
define('SITE_NAME', 'Wings Cut Studio');
define('ADMIN_TITLE', 'Wings Cut — Admin Panel');

// Gallery Upload Config
define('UPLOAD_DIR', __DIR__ . '/../images/gallery/');
define('UPLOAD_URL', '../images/gallery/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Auto-setup password hash on first run
// This runs once to create the hash file
$hashFile = __DIR__ . '/includes/pw.php';
if (!file_exists($hashFile)) {
    $hash = password_hash('wee6_wingscut', PASSWORD_BCRYPT);
    file_put_contents($hashFile, "<?php define('ADMIN_PW_HASH', '$hash'); ?>");
}
require_once $hashFile;
