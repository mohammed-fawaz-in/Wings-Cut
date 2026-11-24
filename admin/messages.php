<?php
require_once __DIR__ . '/includes/auth.php';

/* Actions */
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([(int)$_GET['read']]);
    adminRedirect('messages.php', 'Marked as read.', 'success');
}
if (isset($_GET['readall'])) {
    $pdo->query("UPDATE contact_messages SET is_read=1");
    adminRedirect('messages.php', 'All messages marked as read.', 'success');
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['delete']]);
    adminRedirect('messages.php', 'Message deleted.', 'success');
}

/* AJAX: send reply via internal chat */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_action'])) {
    header('Content-Type: application/json');
    $email   = trim($_POST['email']   ?? '');
    $replyTx = trim($_POST['message'] ?? '');
    if (!$email || !$replyTx) { echo json_encode(['success'=>false,'error'=>'Missing fields.']); exit; }
    try {
        // Find user by email
        $u = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $u->execute([$email]);
        $user = $u->fetch();
        if ($user) {
            $ins = $pdo->prepare('INSERT INTO chat_messages (user_id,sender,message,created_at) VALUES (?,"admin",?,NOW())');
            $ins->execute([$user['id'], $replyTx]);
            // Mark contact message read too
            if (!empty($_POST['msg_id'])) {
                $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([(int)$_POST['msg_id']]);
            }
            echo json_encode(['success'=>true,'sent_via'=>'chat']);
        } else {
            // User hasn't registered — just mark as noted
            echo json_encode(['success'=>false,'error'=>'This person has no registered account to receive a chat message. Ask them to sign up on the site.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'error'=>'Database error.']);
    }
    exit;
}

/* Query */
$filter = cl($_GET['filter'] ?? '');
$search = cl($_GET['search'] ?? '');
$where  = []; $params = [];
if ($filter === 'unread') $where[] = "is_read = 0";
if ($filter === 'read')   $where[] = "is_read = 1";
if ($search) {
    $where[] = "(name LIKE :s1 OR email LIKE :s2 OR subject LIKE :s3 OR message LIKE :s4)";
    $s = "%$search%";
    $params = [':s1'=>$s,':s2'=>$s,':s3'=>$s,':s4'=>$s];
}
$whereSQL   = $where ? 'WHERE '.implode(' AND ',$where) : '';
$perPage    = 10;
$page       = max(1,(int)($_GET['page'] ?? 1));
$offset     = ($page-1)*$perPage;
$c = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereSQL");
$c->execute($params);
$totalRows  = $c->fetchColumn();
$totalPages = ceil($totalRows/$perPage);
$stmt = $pdo->prepare("SELECT * FROM contact_messages $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$messages    = $stmt->fetchAll();
$unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
$flash       = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Messages — Wings Cut Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/admin.css"/>
  <style>
    .mact { display:inline-flex;align-items:center;justify-content:center;gap:6px;height:34px;border-radius:99px;font-size:13px;font-weight:600;white-space:nowrap;cursor:pointer;transition:all .25s;text-decoration:none;border:none;line-height:1;font-family:var(--font); }
    .mact-reply  { padding:0 16px;background:rgba(139,0,0,.09);color:#8B0000;border:1.5px solid rgba(139,0,0,.2); }
    .mact-reply:hover  { background:#8B0000;color:#fff; }
    .mact-read   { padding:0 14px;background:#dbeafe;color:#2563eb;border:1.5px solid #bfdbfe; }
    .mact-read:hover   { background:#2563eb;color:#fff; }
    .mact-delete { width:34px;height:34px;padding:0;border-radius:8px;background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;flex-shrink:0; }
    .mact-delete:hover { background:#dc2626;color:#fff;border-color:#dc2626; }

    /* Reply panel — sends to user chat, no external mail */
    .reply-panel { padding:20px 24px 24px; }
    .reply-to-info { display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--surface);border-radius:10px;margin-bottom:16px;border:1px solid var(--border); }
    .reply-to-av { width:38px;height:38px;border-radius:50%;background:var(--red);color:white;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0; }
    .reply-to-name { font-size:14px;font-weight:700; }
    .reply-to-email { font-size:12px;color:var(--text-muted); }
    .reply-note { display:flex;align-items:flex-start;gap:10px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:13px;color:#166534;margin-bottom:14px;line-height:1.5; }
    .reply-note.warn { background:#fff7ed;border-color:#fed7aa;color:#9a3412; }
    .reply-note i { margin-top:1px;flex-shrink:0; }
    .reply-textarea { width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:var(--font);resize:vertical;outline:none;min-height:100px; }
    .reply-textarea:focus { border-color:var(--red); }
    .reply-send-btn { display:inline-flex;align-items:center;gap:8px;padding:11px 24px;background:var(--red);color:white;border:none;border-radius:99px;font-size:14px;font-weight:600;cursor:pointer;transition:all .25s;font-family:var(--font);margin-top:12px; }
    .reply-send-btn:hover { background:var(--red-light);transform:translateY(-1px); }
    .reply-send-btn:disabled { opacity:.6;cursor:not-allowed;transform:none; }
    .reply-cancel-btn { display:inline-flex;align-items:center;gap:6px;padding:11px 18px;border:1.5px solid var(--border);color:var(--text-muted);border-radius:99px;font-size:14px;font-weight:600;cursor:pointer;transition:all .25s;font-family:var(--font);margin-top:12px;background:none; }
    .reply-cancel-btn:hover { border-color:var(--red);color:var(--red); }
    .reply-actions { display:flex;gap:10px;flex-wrap:wrap; }
    .reply-result { margin-top:10px;font-size:13px;font-weight:600;padding:8px 14px;border-radius:8px; }
    .reply-result.ok  { background:#dcfce7;color:#166534; }
    .reply-result.err { background:#fee2e2;color:#991b1b; }

    #newMsgToast { display:none;position:fixed;bottom:80px;right:24px;background:#8B0000;color:#fff;padding:10px 18px;border-radius:99px;font-size:13px;font-weight:600;box-shadow:0 4px 20px rgba(139,0,0,.35);z-index:9999;align-items:center;gap:10px; }
  </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
  <?php include 'includes/topbar.php'; ?>
  <div class="admin-content">

    <?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> flash-alert">
      <i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash['text']) ?>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1><i class="fas fa-envelope" style="color:var(--red);margin-right:8px;"></i>Contact Messages</h1>
        <p><?= $totalRows ?> message<?= $totalRows!=1?'s':''?>
          <?php if($unreadCount>0): ?> — <strong style="color:#8B0000"><?= $unreadCount ?> unread</strong><?php endif; ?>
        </p>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <div class="filter-tabs">
          <a href="messages.php"      class="ftab <?= !$filter?'active':''?>">All</a>
          <a href="?filter=unread"    class="ftab <?= $filter==='unread'?'active':''?>">Unread<?php if($unreadCount>0):?> <span class="badge-count"><?=$unreadCount?></span><?php endif;?></a>
          <a href="?filter=read"      class="ftab <?= $filter==='read'?'active':''?>">Read</a>
        </div>
        <?php if($unreadCount>0): ?>
        <a href="?readall=1" class="btn-outline-sm"><i class="fas fa-check-double"></i> Mark All Read</a>
        <?php endif; ?>
      </div>
    </div>

    <form method="GET" class="filter-bar">
      <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search name, email, message…" value="<?= htmlspecialchars($search) ?>"/>
      </div>
      <?php if($filter): ?><input type="hidden" name="filter" value="<?= $filter ?>"/><?php endif; ?>
      <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Search</button>
    </form>

    <div class="messages-list">
      <?php foreach($messages as $m): ?>
      <div class="msg-card <?= $m['is_read']?'read':'unread' ?>" id="msg-<?= $m['id'] ?>">
        <div class="msg-card-avatar"><?= strtoupper(substr($m['name'],0,1)) ?></div>
        <div class="msg-card-body">
          <div class="msg-card-top">
            <div>
              <strong><?= htmlspecialchars($m['name']) ?></strong>
              <?php if(!$m['is_read']): ?><span class="new-badge">NEW</span><?php endif; ?>
              <span class="msg-email"><?= htmlspecialchars($m['email']) ?></span>
            </div>
            <small><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></small>
          </div>
          <?php if($m['subject']): ?>
          <div class="msg-subject"><i class="fas fa-tag"></i> <?= htmlspecialchars($m['subject']) ?></div>
          <?php endif; ?>
          <p class="msg-preview"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
          <div class="msg-card-actions">
            <button class="mact mact-reply"
              onclick="openReply(<?= $m['id'] ?>,<?= json_encode($m['name']) ?>,<?= json_encode($m['email']) ?>)">
              <i class="fas fa-comment-dots"></i> Reply via Chat
            </button>
            <?php if(!$m['is_read']): ?>
            <a href="?read=<?=$m['id']?>&filter=<?=$filter?>&search=<?=urlencode($search)?>" class="mact mact-read">
              <i class="fas fa-check"></i> Mark Read
            </a>
            <?php endif; ?>
            <a href="?delete=<?=$m['id']?>&filter=<?=$filter?>&search=<?=urlencode($search)?>"
               class="mact mact-delete msg-delete-btn" title="Delete">
              <i class="fas fa-trash"></i>
            </a>
          </div>
          <!-- Inline reply panel (hidden by default) -->
          <div class="reply-panel" id="rp-<?= $m['id'] ?>" style="display:none;">
            <div class="reply-to-info">
              <div class="reply-to-av"><?= strtoupper(substr($m['name'],0,1)) ?></div>
              <div>
                <div class="reply-to-name"><?= htmlspecialchars($m['name']) ?></div>
                <div class="reply-to-email"><?= htmlspecialchars($m['email']) ?></div>
              </div>
            </div>
            <div class="reply-note">
              <i class="fas fa-comments"></i>
              Your reply will appear directly in this user's <strong>Wings Cut chat inbox</strong>. They'll see it instantly when they're logged in — no email needed.
            </div>
            <textarea class="reply-textarea" id="rt-<?= $m['id'] ?>" placeholder="Type your reply to <?= htmlspecialchars($m['name']) ?>…"></textarea>
            <div id="rr-<?= $m['id'] ?>"></div>
            <div class="reply-actions">
              <button class="reply-send-btn" id="rsb-<?= $m['id'] ?>"
                onclick="sendReply(<?= $m['id'] ?>,<?= json_encode($m['email']) ?>)">
                <i class="fas fa-paper-plane"></i> Send to Chat
              </button>
              <button class="reply-cancel-btn" onclick="closeReply(<?= $m['id'] ?>)">
                <i class="fas fa-times"></i> Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($messages)): ?>
      <div class="empty-state"><i class="fas fa-inbox"></i><p>No messages found</p></div>
      <?php endif; ?>
    </div>

    <?php if($totalPages>1): ?>
    <div class="pagination">
      <?php for($i=1;$i<=$totalPages;$i++): ?>
      <a href="?page=<?=$i?>&filter=<?=$filter?>&search=<?=urlencode($search)?>"
         class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<div id="newMsgToast">
  <i class="fas fa-envelope"></i> New message received!
  <button onclick="location.reload()" style="background:#fff;color:#8B0000;border:none;border-radius:99px;padding:3px 12px;font-size:12px;font-weight:700;cursor:pointer;">View</button>
</div>

<script src="js/admin.js"></script>
<script>
let activeReplyId = null;

function openReply(id, name, email) {
  // Close any previously open reply panel
  if (activeReplyId && activeReplyId !== id) closeReply(activeReplyId);
  activeReplyId = id;
  const panel = document.getElementById('rp-' + id);
  if (panel) { panel.style.display = ''; panel.scrollIntoView({behavior:'smooth',block:'nearest'}); }
  document.getElementById('rt-' + id)?.focus();
}
function closeReply(id) {
  const panel = document.getElementById('rp-' + id);
  if (panel) panel.style.display = 'none';
  const ta = document.getElementById('rt-' + id);
  if (ta) ta.value = '';
  const rr = document.getElementById('rr-' + id);
  if (rr) rr.innerHTML = '';
  if (activeReplyId === id) activeReplyId = null;
}

async function sendReply(msgId, email) {
  const ta  = document.getElementById('rt-' + msgId);
  const btn = document.getElementById('rsb-' + msgId);
  const rr  = document.getElementById('rr-' + msgId);
  if (!ta || !btn) return;
  const text = ta.value.trim();
  if (!text) { ta.focus(); ta.style.borderColor='#dc2626'; setTimeout(()=>ta.style.borderColor='',1500); return; }
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
  rr.innerHTML  = '';
  try {
    const fd = new FormData();
    fd.append('reply_action', '1'); // handled by ajax/reply_chat.php
    fd.append('email',   email);
    fd.append('message', text);
    fd.append('msg_id',  msgId);
    const res  = await fetch('ajax/reply_chat.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      rr.innerHTML = '<div class="reply-result ok"><i class="fas fa-check-circle"></i> Reply sent to user\'s chat inbox!</div>';
      ta.value     = '';
      // Auto-close panel after 2s
      setTimeout(() => closeReply(msgId), 2000);
      // Update read state visually
      document.getElementById('msg-' + msgId)?.classList.replace('unread','read');
      document.querySelector('#msg-' + msgId + ' .new-badge')?.remove();
    } else {
      rr.innerHTML = '<div class="reply-result err"><i class="fas fa-exclamation-circle"></i> ' + (data.error||'Send failed.') + '</div>';
    }
  } catch(e) {
    rr.innerHTML = '<div class="reply-result err"><i class="fas fa-exclamation-circle"></i> Network error. Please try again.</div>';
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send to Chat';
}

// Keyboard shortcut: Ctrl+Enter to send
document.addEventListener('keydown', e => {
  if (!activeReplyId) return;
  if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
    const email = document.querySelector('[onclick*="openReply(' + activeReplyId + '"]')?.getAttribute('onclick')?.match(/,([^,]+)\)$/)?.[1]?.replace(/'/g,'').trim();
    sendReply(activeReplyId, email);
  }
});

// Auto-refresh check for new contact messages
let knownCount = <?= $unreadCount ?>;
setInterval(() => {
  fetch('ajax/msg_count.php').then(r=>r.json()).then(d=>{
    if (d.count > knownCount) {
      knownCount = d.count;
      const t = document.getElementById('newMsgToast');
      if (t) t.style.display = 'flex';
    }
  }).catch(()=>{});
}, 15000);
</script>
</body>
</html>
