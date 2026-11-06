<?php
require_once __DIR__ . '/includes/auth.php';

// Handle admin reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_msg'], $_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $msg    = trim(strip_tags($_POST['reply_msg']));
    if ($msg && $userId) {
        $pdo->prepare("INSERT INTO user_messages (user_id, sender, message) VALUES (?,?,?)")
            ->execute([$userId, 'admin', $msg]);
        // Mark user messages as read
        $pdo->prepare("UPDATE user_messages SET is_read=1 WHERE user_id=? AND sender='user'")->execute([$userId]);
        adminRedirect('users.php?chat='.$userId, 'Reply sent!', 'success');
    }
}

// Active chat user
$chatUserId = isset($_GET['chat']) ? (int)$_GET['chat'] : 0;
$chatUser   = null;
$chatMsgs   = [];
if ($chatUserId) {
    $chatUser = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $chatUser->execute([$chatUserId]);
    $chatUser = $chatUser->fetch();
    if ($chatUser) {
        $msgStmt = $pdo->prepare("SELECT * FROM user_messages WHERE user_id=? ORDER BY created_at ASC LIMIT 100");
        $msgStmt->execute([$chatUserId]);
        $chatMsgs = $msgStmt->fetchAll();
        // Mark as read
        $pdo->prepare("UPDATE user_messages SET is_read=1 WHERE user_id=? AND sender='user'")->execute([$chatUserId]);
    }
}

// Users list with unread count
try {
    $users = $pdo->query("
        SELECT u.*,
               COUNT(m.id) as total_msgs,
               SUM(CASE WHEN m.sender='user' AND m.is_read=0 THEN 1 ELSE 0 END) as unread
        FROM users u
        LEFT JOIN user_messages m ON m.user_id = u.id
        GROUP BY u.id
        ORDER BY u.last_seen DESC
    ")->fetchAll();
    $totalUsers  = count($users);
    $activeToday = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(last_seen)=CURDATE()")->fetchColumn();
} catch(Exception $e) {
    $users = []; $totalUsers = 0; $activeToday = 0;
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Users — Wings Cut Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/admin.css"/>
  <style>
    /* Users layout */
    .users-layout { display: grid; grid-template-columns: 320px 1fr; gap: 20px; height: calc(100vh - var(--topbar-h) - 80px); }
    .users-sidebar-panel { background:white; border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; display:flex; flex-direction:column; }
    .users-sidebar-header { padding:16px; border-bottom:1px solid var(--border); }
    .users-sidebar-header h3 { font-size:15px; font-weight:700; display:flex;align-items:center;gap:8px; }
    .users-search { position:relative; margin-top:10px; }
    .users-search input { width:100%;padding:8px 12px 8px 32px;border:1.5px solid var(--border);border-radius:99px;font-size:13px;outline:none;font-family:var(--font); }
    .users-search input:focus { border-color:var(--red); }
    .users-search i { position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#aaa;font-size:13px; }
    .users-list { flex:1; overflow-y:auto; }
    .user-row {
      display:flex; align-items:center; gap:10px; padding:12px 16px;
      border-bottom:1px solid #f5f5f5; cursor:pointer; transition:background .15s;
      text-decoration:none; color:inherit;
    }
    .user-row:hover { background:#fafafa; }
    .user-row.active { background:rgba(139,0,0,0.05); border-left:3px solid var(--red); }
    .user-av {
      width:38px;height:38px;border-radius:50%;background:var(--red);color:white;
      display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0;
    }
    .user-row-info { flex:1;min-width:0; }
    .user-row-name { font-size:13.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .user-row-email{ font-size:11.5px;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .user-row-time { font-size:11px;color:#aaa;white-space:nowrap;flex-shrink:0; }
    .user-unread { background:var(--red);color:white;font-size:10px;font-weight:700;padding:2px 6px;border-radius:99px;flex-shrink:0; }
    .online-dot { width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0; }
    .offline-dot { width:8px;height:8px;border-radius:50%;background:#d1d5db;flex-shrink:0; }

    /* Chat panel */
    .chat-panel { background:white; border-radius:var(--radius); border:1px solid var(--border); display:flex;flex-direction:column;overflow:hidden; }
    .chat-header { display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border); }
    .chat-header-av { width:40px;height:40px;border-radius:50%;background:var(--red);color:white;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700; }
    .chat-header-name { font-size:15px;font-weight:700; }
    .chat-header-email{ font-size:12px;color:#888; }
    .chat-header-time { margin-left:auto;font-size:11.5px;color:#aaa; }
    .chat-messages { flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px;background:#f9f9f9; }
    .chat-bubble { max-width:72%; padding:10px 14px; border-radius:14px; font-size:13.5px;line-height:1.5; word-break:break-word; }
    .chat-bubble.user  { background:white;border:1px solid #eee;align-self:flex-start;border-bottom-left-radius:4px; }
    .chat-bubble.admin { background:var(--red);color:white;align-self:flex-end;border-bottom-right-radius:4px; }
    .bubble-time { font-size:10px;opacity:0.6;margin-top:4px;display:block; }
    .chat-input-wrap { padding:14px 16px;border-top:1px solid var(--border);display:flex;gap:10px; }
    .chat-input-wrap textarea { flex:1;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:var(--font);resize:none;outline:none;height:40px;transition:border-color .2s; }
    .chat-input-wrap textarea:focus { border-color:var(--red); height:72px; }
    .chat-send-btn { background:var(--red);color:white;border:none;border-radius:10px;padding:0 18px;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:background .2s;font-family:var(--font);font-weight:600; }
    .chat-send-btn:hover { background:#a80000; }
    .no-chat { display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#bbb;gap:12px; }
    .no-chat i { font-size:48px; }
    .no-chat p { font-size:14px; }

    /* Stats row */
    .user-stats { display:flex;gap:16px;margin-bottom:20px; }
    .user-stat-card { background:white;border-radius:var(--radius);border:1px solid var(--border);padding:16px 20px;flex:1;display:flex;align-items:center;gap:12px; }
    .user-stat-icon { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px; }

    @media (max-width:900px) {
      .users-layout { grid-template-columns:1fr; height:auto; gap:16px; }
      .users-sidebar-panel { max-height:320px; }
      .chat-panel { height:60vh; }
      .user-stats { flex-direction:column; gap:10px; }
      .chat-window { max-width:100%; }
    }
    @media (max-width:600px) {
      /* Mobile: full-width list, chat takes screen */
      .users-layout { gap:12px; }
      .users-sidebar-panel { max-height:260px; border-radius:10px; }
      .chat-panel { height:55vh; border-radius:10px; }
      .chat-win-header { padding:12px 14px; }
      .chat-header { padding:12px 14px; flex-wrap:wrap; gap:8px; }
      .chat-header-time { width:100%; font-size:10px; }
      .chat-messages { padding:12px; gap:8px; }
      .chat-bubble { max-width:86%; font-size:13px; padding:8px 12px; }
      .chat-input-wrap { padding:10px 12px; gap:8px; }
      .user-stats { gap:8px; }
      .user-stat-card { padding:12px 14px; }
      .user-stats .user-stat-card div:first-child + div > div:first-child { font-size:18px; }
      .page-header h1 { font-size:20px; }
      /* User rows compact */
      .user-row { padding:10px 12px; gap:8px; }
      .user-av { width:34px; height:34px; font-size:13px; }
      .user-row-name { font-size:13px; }
      .user-row-email { font-size:11px; }
    }
  </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
  <?php include 'includes/topbar.php'; ?>
  <div class="admin-content">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> flash-alert">
      <i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash['text']) ?>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1>Users & Messages</h1>
        <p><?= $totalUsers ?> registered user<?= $totalUsers!=1?'s':'' ?> — <?= $activeToday ?> active today</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="user-stats">
      <div class="user-stat-card">
        <div class="user-stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-users"></i></div>
        <div><div style="font-size:22px;font-weight:700;"><?= $totalUsers ?></div><div style="font-size:12px;color:#888;">Total Users</div></div>
      </div>
      <div class="user-stat-card">
        <div class="user-stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-circle"></i></div>
        <div><div style="font-size:22px;font-weight:700;"><?= $activeToday ?></div><div style="font-size:12px;color:#888;">Active Today</div></div>
      </div>
      <div class="user-stat-card">
        <?php $totalUnread = $pdo->query("SELECT COUNT(*) FROM user_messages WHERE sender='user' AND is_read=0")->fetchColumn(); ?>
        <div class="user-stat-icon" style="background:rgba(139,0,0,0.08);color:var(--red);"><i class="fas fa-envelope"></i></div>
        <div><div style="font-size:22px;font-weight:700;"><?= $totalUnread ?></div><div style="font-size:12px;color:#888;">Unread Messages</div></div>
      </div>
    </div>

    <div class="users-layout">
      <!-- Users sidebar -->
      <div class="users-sidebar-panel">
        <div class="users-sidebar-header">
          <h3><i class="fas fa-users" style="color:var(--red)"></i> Users</h3>
          <div class="users-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search users..." id="userSearch" oninput="filterUsers(this.value)"/>
          </div>
        </div>
        <div class="users-list" id="usersList">
          <?php if(empty($users)): ?>
          <div style="text-align:center;padding:40px 20px;color:#bbb;">
            <i class="fas fa-user-slash" style="font-size:32px;margin-bottom:10px;display:block;"></i>
            <p style="font-size:13px;">No users yet</p>
          </div>
          <?php endif; ?>
          <?php foreach($users as $u):
            $isOnline = $u['last_seen'] && (strtotime($u['last_seen']) > time() - 300);
            $timeAgo  = $u['last_seen'] ? human_time($u['last_seen']) : 'Never';
          ?>
          <a href="?chat=<?= $u['id'] ?>"
             class="user-row <?= $chatUserId===$u['id']?'active':'' ?>">
            <div class="user-av"><?= strtoupper(substr($u['name'],0,1)) ?></div>
            <div class="user-row-info">
              <div class="user-row-name"><?= htmlspecialchars($u['name']) ?></div>
              <div class="user-row-email"><?= htmlspecialchars($u['email']) ?></div>
            </div>
            <?php if($u['unread']>0): ?>
            <span class="user-unread"><?= $u['unread'] ?></span>
            <?php endif; ?>
            <div class="<?= $isOnline?'online-dot':'offline-dot' ?>" title="<?= $isOnline?'Online':'Last seen '.$timeAgo ?>"></div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Chat panel -->
      <div class="chat-panel">
        <?php if($chatUser): ?>
        <div class="chat-header">
          <div class="chat-header-av"><?= strtoupper(substr($chatUser['name'],0,1)) ?></div>
          <div>
            <div class="chat-header-name"><?= htmlspecialchars($chatUser['name']) ?></div>
            <div class="chat-header-email"><?= htmlspecialchars($chatUser['email']) ?></div>
          </div>
          <div class="chat-header-time">
            Joined <?= date('d M Y', strtotime($chatUser['created_at'])) ?>
          </div>
        </div>

        <div class="chat-messages" id="chatMessages">
          <?php if(empty($chatMsgs)): ?>
          <div style="text-align:center;color:#bbb;padding:20px;font-size:13px;">No messages yet — start the conversation!</div>
          <?php endif; ?>
          <?php foreach($chatMsgs as $msg): ?>
          <div>
            <div class="chat-bubble <?= $msg['sender'] ?>">
              <?= nl2br(htmlspecialchars($msg['message'])) ?>
              <span class="bubble-time"><?= date('d M, h:i A', strtotime($msg['created_at'])) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <form method="POST" class="chat-input-wrap">
          <input type="hidden" name="user_id" value="<?= $chatUser['id'] ?>"/>
          <textarea name="reply_msg" placeholder="Type your reply..." rows="1"></textarea>
          <button type="submit" class="chat-send-btn"><i class="fas fa-paper-plane"></i> Send</button>
        </form>

        <?php else: ?>
        <div class="no-chat">
          <i class="fas fa-comments"></i>
          <p>Select a user to view their messages</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script src="js/admin.js"></script>
<script>
// Filter users by search
function filterUsers(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.user-row').forEach(row => {
    const name  = row.querySelector('.user-row-name')?.textContent.toLowerCase() || '';
    const email = row.querySelector('.user-row-email')?.textContent.toLowerCase() || '';
    row.style.display = (name.includes(q) || email.includes(q)) ? '' : 'none';
  });
}
// Scroll chat to bottom
const chatEl = document.getElementById('chatMessages');
if (chatEl) chatEl.scrollTop = chatEl.scrollHeight;
</script>
</body>
</html>
<?php
function human_time($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60).'m ago';
    if ($diff < 86400)  return floor($diff/3600).'h ago';
    return floor($diff/86400).'d ago';
}