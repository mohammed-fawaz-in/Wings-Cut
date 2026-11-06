<?php
/* ═══════════════════════════════════════════════════════════════════
   WINGS CUT — php/auth.php
   Handles: login, signup, logout
   Always returns JSON.
   ═══════════════════════════════════════════════════════════════════ */

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/db.php';   // provides $pdo

$action = trim($_POST['action'] ?? '');

/* ── LOGOUT ────────────────────────────────────────────────────── */
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

/* ── LOGIN ─────────────────────────────────────────────────────── */
if ($action === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
        exit;
    }
    if (!$password || strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'error' => 'Incorrect email or password.']);
            exit;
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        echo json_encode(['success' => true, 'name' => $user['name']]);
    } catch (PDOException $e) {
        error_log('Auth login error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error. Please try again.']);
    }
    exit;
}

/* ── SIGNUP ────────────────────────────────────────────────────── */
if ($action === 'signup') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate
    if (!$name || strlen($name) < 2) {
        echo json_encode(['success' => false, 'error' => 'Please enter your full name.']);
        exit;
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
        exit;
    }
    if (!$password || strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
        exit;
    }

    try {
        // Check for existing email
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'This email is already registered. Please sign in.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins  = $pdo->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())');
        $ins->execute([$name, $email, $hash]);
        $userId = $pdo->lastInsertId();

        // Auto-login after signup
        session_regenerate_id(true);
        $_SESSION['user_id']    = $userId;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;

        echo json_encode(['success' => true, 'name' => $name]);
    } catch (PDOException $e) {
        error_log('Auth signup error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error. Please try again.']);
    }
    exit;
}

// Unknown action
echo json_encode(['success' => false, 'error' => 'Unknown action.']);