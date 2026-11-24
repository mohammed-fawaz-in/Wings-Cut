<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../php/db.php';

if (isset($_SESSION[ADMIN_SESSION_KEY]) && $_SESSION[ADMIN_SESSION_KEY] === true) {
    header('Location: dashboard.php'); exit;
}

// Clear any lockout from previous attempts
unset($_SESSION['login_attempts'], $_SESSION['login_time']);

$error   = '';
$timeout = isset($_GET['timeout']);
$dbOk    = ($pdo !== null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$dbOk) {
        $error = 'MySQL is not running. Please start it in XAMPP Control Panel.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PW_HASH)) {
            session_regenerate_id(true);
            $_SESSION[ADMIN_SESSION_KEY] = true;
            $_SESSION['last_activity']   = time();
            $_SESSION['created']         = time();
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Login — Wings Cut</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/admin.css"/>
</head>
<body class="login-body">
<div class="login-bg">
  <div class="login-shape s1"></div>
  <div class="login-shape s2"></div>
  <div class="login-shape s3"></div>
</div>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-icon"><i class="fas fa-camera-retro"></i></div>
      <div>
        <span class="login-brand">WINGS CUT</span>
        <span class="login-sub">Admin Panel</span>
      </div>
    </div>
    <h1>Welcome Back</h1>
    <p class="login-desc">Sign in to manage your studio</p>

    <?php if (!$dbOk): ?>
    <div class="alert alert-error" style="margin-bottom:18px;">
      <i class="fas fa-database"></i>
      <strong>MySQL is offline.</strong> Start it in XAMPP, then refresh.
    </div>
    <?php endif; ?>
    <?php if ($timeout): ?>
    <div class="alert alert-warning" style="margin-bottom:18px;">
      <i class="fas fa-clock"></i> Session expired. Please sign in again.
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:18px;">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="login-form" novalidate>
      <div class="form-group">
        <label><i class="fas fa-user"></i> Username</label>
        <input type="text" name="username" placeholder="admin"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               autocomplete="username" required <?= !$dbOk?'disabled':'' ?>/>
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pwInput"
                 placeholder="Enter your password"
                 autocomplete="current-password" required <?= !$dbOk?'disabled':'' ?>/>
          <button type="button" class="pw-toggle" onclick="togglePw()">
            <i class="fas fa-eye" id="pwIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-login" <?= !$dbOk?'disabled':'' ?>>
        <span>Sign In</span> <i class="fas fa-arrow-right"></i>
      </button>
    </form>
    <p style="margin-top:16px;font-size:13px;color:#888;text-align:center;">
      Default password: <code style="background:#f3f3f3;padding:2px 8px;border-radius:4px;">wee6_wingscut</code>
    </p>
    <p class="login-back">
      <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
    </p>
  </div>
</div>
<div id="adminCursorDot"></div>
<div id="adminCursorRing"></div>
<script src="js/admin.js"></script>
<script>
  function togglePw() {
    const inp  = document.getElementById('pwInput');
    const icon = document.getElementById('pwIcon');
    if (inp.type === 'password') { inp.type='text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
    else { inp.type='password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
  }
</script>
</body>
</html>
