<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($pdo === null) {
    // Give a helpful message — most common cause on XAMPP is MySQL not running
    exit(json_encode([
        'success' => false,
        'error'   => 'Cannot connect to database. Please make sure MySQL is running in XAMPP Control Panel.'
    ]));
}

$action = trim($_POST['action'] ?? '');

/* Signup */
if ($action === 'signup') {
    $name     = htmlspecialchars(strip_tags(trim($_POST['name']     ?? '')));
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';

    if (strlen($name) < 2)
        exit(json_encode(['success'=>false,'error'=>'Please enter your full name.']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        exit(json_encode(['success'=>false,'error'=>'Please enter a valid email address.']));
    if (strlen($password) < 6)
        exit(json_encode(['success'=>false,'error'=>'Password must be at least 6 characters.']));

    try {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetch())
            exit(json_encode(['success'=>false,'error'=>'An account with this email already exists. Please sign in.']));

        $pdo->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())')
            ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

        session_regenerate_id(true);
        $_SESSION['user_id']    = (int)$pdo->lastInsertId();
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;

        exit(json_encode(['success'=>true, 'name'=>$name]));

    } catch (PDOException $e) {
        error_log('Signup: ' . $e->getMessage());
        exit(json_encode(['success'=>false,'error'=>'Database error: ' . $e->getMessage()]));
    }
}

/* Login */
if ($action === 'login') {
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email))    exit(json_encode(['success'=>false,'error'=>'Please enter your email.']));
    if (empty($password)) exit(json_encode(['success'=>false,'error'=>'Please enter your password.']));

    try {
        $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password']))
            exit(json_encode(['success'=>false,'error'=>'Incorrect email or password. Please try again.']));

        session_regenerate_id(true);
        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        exit(json_encode(['success'=>true, 'name'=>$user['name']]));

    } catch (PDOException $e) {
        error_log('Login: ' . $e->getMessage());
        exit(json_encode(['success'=>false,'error'=>'Database error: ' . $e->getMessage()]));
    }
}

/* Logout */
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    exit(json_encode(['success'=>true]));
}

exit(json_encode(['success'=>false,'error'=>'Unknown action.']));
