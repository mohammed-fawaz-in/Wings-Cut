<?php
require_once __DIR__ . '/includes/auth.php';

try {
    $users = $pdo->query("
        SELECT u.*,
               COUNT(m.id) as total_msgs,
               SUM(CASE WHEN m.sender='user' AND m.is_read=0 THEN 1 ELSE 0 END) as unread
        FROM users u
        LEFT JOIN chat_messages m ON m.user_id = u.id
        GROUP BY u.id ORDER BY u.created_at DESC
    ")->fetchAll();
    $totalUsers = count($users);
} catch(Exception $e){ $users=[]; $totalUsers=0; }

$activeChatId   = isset($_GET['chat']) ? (int)$_GET['chat'] : 0;
$activeChatUser = null;
if ($activeChatId) {
    $s = $pdo->prepare('SELECT * FROM users WHERE id=?');
    $s->execute([$activeChatId]);
    $activeChatUser = $s->fetch();
}
$flash = getFlash();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Users &amp; Chat — Wings Cut Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
/* Users & Chat layout */
.uc-layout{display:grid;grid-template-columns:300px 1fr;gap:20px;height:calc(100vh - var(--topbar-h,64px) - 100px)}
.users-panel{background:#fff;border-radius:var(--radius,12px);border:1px solid var(--border,#eee);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.users-panel-head{padding:16px 16px 12px;border-bottom:1px solid var(--border,#eee);background:#fafafa}
.users-panel-head h3{font-size:14px;font-weight:700;display:flex;align-items:center;gap:7px;margin-bottom:10px}
.users-panel-head h3 i{color:#8B0000}
.users-search{position:relative}
.users-search input{width:100%;padding:8px 12px 8px 32px;border:1.5px solid var(--border,#eee);border-radius:99px;font-size:13px;outline:none;font-family:var(--font,'Outfit');transition:border-color .18s}
.users-search input:focus{border-color:#8B0000}
.users-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#bbb;font-size:12px;pointer-events:none}
.users-list{flex:1;overflow-y:auto}
.users-list::-webkit-scrollbar{width:3px}
.users-list::-webkit-scrollbar-thumb{background:#ddd;border-radius:2px}
.user-row{display:flex;align-items:center;gap:10px;padding:11px 14px;border-bottom:1px solid #f8f8f8;cursor:pointer;transition:background .15s;text-decoration:none;color:inherit}
.user-row:hover{background:#fafafa}
.user-row.active{background:rgba(139,0,0,.05);border-left:3px solid #8B0000}
.ua-av{width:38px;height:38px;border-radius:50%;background:#8B0000;color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0}
.ua-info{flex:1;min-width:0}
.ua-name{font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ua-email{font-size:12px;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ua-date{font-size:10px;color:#bbb;margin-top:1px}
.ua-badge{background:#8B0000;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;flex-shrink:0}

/* Chat panel */
.chat-panel{background:#fff;border-radius:var(--radius,12px);border:1px solid var(--border,#eee);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.chat-panel-head{padding:14px 18px;border-bottom:1px solid var(--border,#eee);background:#fafafa;display:flex;align-items:center;gap:12px;flex-shrink:0}
.cp-av{width:38px;height:38px;border-radius:50%;background:#8B0000;color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0}
.cp-info{flex:1}
.cp-name{font-size:15px;font-weight:700}
.cp-email{font-size:12px;color:#888}
.cp-status{display:flex;align-items:center;gap:5px;font-size:12px;color:#22c55e}
.cp-dot{width:7px;height:7px;border-radius:50%;background:#22c55e;flex-shrink:0}

.chat-msgs{flex:1;min-height:0;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:12px;background:#fafafa}
.chat-msgs::-webkit-scrollbar{width:3px}
.chat-msgs::-webkit-scrollbar-thumb{background:#ddd;border-radius:2px}
.cm-row{display:flex;gap:9px;align-items:flex-end}
.cm-row.admin-msg{flex-direction:row-reverse}
.cm-av{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}
.cm-av.user-av{background:#8B0000}
.cm-av.admin-av{background:#334155}
.cm-bubble{padding:10px 14px;border-radius:16px;font-size:13.5px;line-height:1.55;max-width:65%;word-break:break-word;-webkit-font-smoothing:antialiased}
.cm-bubble.from-user{background:#fff;border:1px solid #eee;border-radius:0 16px 16px 16px}
.cm-bubble.from-admin{background:#8B0000;color:#fff;border-radius:16px 16px 0 16px}
.cm-time{display:block;font-size:10px;opacity:.45;margin-top:3px}
.cm-row.admin-msg .cm-time{text-align:right}

.chat-input-bar{padding:12px 14px;border-top:1px solid var(--border,#eee);display:flex;gap:9px;align-items:flex-end;background:#fff;flex-shrink:0}
.chat-input-bar textarea{flex:1;border:1.5px solid var(--border,#eee);border-radius:12px;padding:10px 13px;font-size:14px;font-family:var(--font,'Outfit');resize:none;outline:none;transition:border-color .18s;max-height:110px;-webkit-font-smoothing:antialiased}
.chat-input-bar textarea:focus{border-color:#8B0000}
.chat-send-btn{width:42px;height:42px;border-radius:50%;background:#8B0000;color:#fff;border:none;display:flex;align-items:center;justify-content:center;font-size:15px;cursor:pointer;flex-shrink:0;transition:all .2s;box-shadow:0 3px 12px rgba(139,0,0,.28)}
.chat-send-btn:hover{background:#a30000;transform:scale(1.08)}

.chat-empty-state{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;color:#bbb}
.chat-empty-state i{font-size:48px;color:#e5e5e5}
.chat-empty-state h3{font-size:17px;font-weight:700;color:#555}
.chat-empty-state p{font-size:14px;text-align:center;max-width:260px;line-height:1.5}
.no-users-state{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:#bbb;padding:20px;text-align:center}
.no-users-state i{font-size:36px;color:#e5e5e5}

@media(max-width:768px){
  .uc-layout{grid-template-columns:1fr;height:auto}
  .users-panel{height:250px}
  .chat-panel{height:480px}
}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
  <?php include 'includes/topbar.php'; ?>
  <div class="admin-content">

    <?php if($flash): ?>
    <div class="alert alert-<?=$flash['type']?> flash-alert"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($flash['text'])?></div>
    <?php endif; ?>

    <div class="page-header" style="margin-bottom:18px">
      <div>
        <h1><i class="fas fa-users" style="color:#8B0000;margin-right:8px"></i>Users &amp; Messaging</h1>
        <p><?=$totalUsers?> registered user<?=$totalUsers!=1?'s':''?> — real-time chat</p>
      </div>
    </div>

    <div class="uc-layout">

      <!-- Users list -->
      <div class="users-panel">
        <div class="users-panel-head">
          <h3><i class="fas fa-users"></i>All Users <span style="font-size:11px;font-weight:400;color:#aaa;margin-left:4px">(<?=$totalUsers?>)</span></h3>
          <div class="users-search">
            <i class="fas fa-search"></i>
            <input type="text" id="userSearch" placeholder="Search users…" oninput="filterUsers(this.value)"/>
          </div>
        </div>
        <div class="users-list" id="usersList">
          <?php if(empty($users)): ?>
          <div class="no-users-state"><i class="fas fa-user-slash"></i><p>No registered users yet.</p></div>
          <?php else: foreach($users as $u): $unread=(int)($u['unread']??0); ?>
          <a href="?chat=<?=$u['id']?>" class="user-row <?=$activeChatId===$u['id']?'active':''?>"
             data-name="<?=htmlspecialchars(strtolower($u['name']))?>"
             data-email="<?=htmlspecialchars(strtolower($u['email']))?>">
            <div class="ua-av"><?=strtoupper(substr($u['name'],0,1))?></div>
            <div class="ua-info">
              <div class="ua-name"><?=htmlspecialchars($u['name'])?></div>
              <div class="ua-email"><?=htmlspecialchars($u['email'])?></div>
              <div class="ua-date"><i class="fas fa-calendar" style="font-size:9px"></i> <?=date('d M Y',strtotime($u['created_at']))?></div>
            </div>
            <?php if($unread>0): ?><span class="ua-badge"><?=$unread?></span><?php endif; ?>
          </a>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Chat panel -->
      <div class="chat-panel">
        <?php if($activeChatUser): ?>
        <div class="chat-panel-head">
          <div class="cp-av"><?=strtoupper(substr($activeChatUser['name'],0,1))?></div>
          <div class="cp-info">
            <div class="cp-name"><?=htmlspecialchars($activeChatUser['name'])?></div>
            <div class="cp-email"><?=htmlspecialchars($activeChatUser['email'])?></div>
          </div>
          <div class="cp-status"><span class="cp-dot"></span> Active</div>
        </div>
        <div class="chat-msgs" id="adminChatMsgs">
          <div style="text-align:center;padding:20px;color:#bbb;font-size:13px"><i class="fas fa-spinner fa-spin"></i> Loading messages…</div>
        </div>
        <div class="chat-input-bar">
          <textarea id="adminChatInput" placeholder="Type a reply to <?=htmlspecialchars($activeChatUser['name'])?>…" rows="1"
            oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,110)+'px'"></textarea>
          <button class="chat-send-btn" onclick="adminSend()" title="Send message">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
        <?php else: ?>
        <div class="chat-empty-state">
          <i class="fas fa-comments"></i>
          <h3>Select a User</h3>
          <p>Click any user from the left to view and reply to their messages in real time.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script src="js/admin.js"></script>
<script>
/* User search filter */
function filterUsers(q) {
  q = q.toLowerCase().trim();
  document.querySelectorAll('.user-row').forEach(row => {
    const match = !q || row.dataset.name.includes(q) || row.dataset.email.includes(q);
    row.style.display = match ? '' : 'none';
  });
}

<?php if($activeChatUser): ?>
/* Real-time admin chat */
const CHAT_UID = <?=(int)$activeChatId?>;
const UNAME    = <?=json_encode(strtoupper(substr($activeChatUser['name'],0,1)))?>;
let lastId = 0, pollTimer = null;

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function appendMsg(m, isTemp) {
  const c = document.getElementById('adminChatMsgs'); if(!c) return;
  if (!isTemp && m.id && c.querySelector('[data-aid="'+m.id+'"]')) return;
  const isAdmin = m.sender === 'admin';
  const row = document.createElement('div');
  row.className = 'cm-row ' + (isAdmin ? 'admin-msg' : '');
  if (m.id) row.dataset.aid = m.id;
  const t = m.created_at ? new Date(m.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) : '';
  const txt = esc(m.message||'').replace(/\n/g,'<br>');
  if (isAdmin) {
    row.innerHTML = `<div class="cm-bubble from-admin">${txt}<span class="cm-time">${t}</span></div><div class="cm-av admin-av"><i class="fas fa-user-shield"></i></div>`;
  } else {
    row.innerHTML = `<div class="cm-av user-av">${UNAME}</div><div class="cm-bubble from-user">${txt}<span class="cm-time">${t}</span></div>`;
  }
  c.appendChild(row);
  c.scrollTop = c.scrollHeight;
}

async function loadHistory() {
  try {
    const res  = await fetch('ajax/chat_history.php?user_id='+CHAT_UID);
    const data = await res.json();
    const c    = document.getElementById('adminChatMsgs');
    if(c) c.innerHTML = '';
    (data.messages||[]).forEach(m => { appendMsg(m,false); if(m.id) lastId=Math.max(lastId,m.id); });
  } catch(e){}
}

async function adminSend() {
  const inp = document.getElementById('adminChatInput'); if(!inp) return;
  const txt = inp.value.trim(); if(!txt) return;
  inp.value = ''; inp.style.height = 'auto';
  appendMsg({message:txt, sender:'admin', created_at:new Date().toISOString()}, true);
  try {
    const fd = new FormData();
    fd.append('user_id', CHAT_UID);
    fd.append('message', txt);
    const res  = await fetch('ajax/chat_send.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.id) lastId = Math.max(lastId, data.id);
  } catch(e){}
}

document.getElementById('adminChatInput')?.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); adminSend(); }
});

function startPoll() {
  clearInterval(pollTimer);
  pollTimer = setInterval(async () => {
    try {
      const res  = await fetch('ajax/chat_poll.php?user_id='+CHAT_UID+'&since='+lastId);
      const data = await res.json();
      (data.messages||[]).forEach(m => { appendMsg(m,false); if(m.id) lastId=Math.max(lastId,m.id); });
    } catch(e){}
  }, 3000);
}

/* Poll sidebar badge for unread */
function pollSidebarBadge() {
  setInterval(async () => {
    try {
      const res  = await fetch('ajax/unread_chat.php');
      const data = await res.json();
      const badge = document.querySelector('.nav-badge[data-chat]');
      if (badge) badge.textContent = data.count > 0 ? data.count : '';
    } catch(e){}
  }, 6000);
}

loadHistory().then(startPoll);
pollSidebarBadge();
<?php endif; ?>
</script>
</body>
</html>
