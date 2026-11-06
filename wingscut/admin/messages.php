<?php
require_once __DIR__ . '/includes/auth.php';

if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([(int)$_GET['read']]);
    adminRedirect('messages.php', 'Marked as read.', 'success');
}
if (isset($_GET['readall'])) {
    $pdo->query("UPDATE contact_messages SET is_read=1");
    adminRedirect('messages.php', 'All marked as read.', 'success');
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['delete']]);
    adminRedirect('messages.php', 'Message deleted.', 'success');
}

$filter = cl($_GET['filter'] ?? '');
$search = cl($_GET['search'] ?? '');
$where  = []; $params = [];
if ($filter === 'unread') $where[] = "is_read = 0";
if ($filter === 'read')   $where[] = "is_read = 1";
if ($search) {
    $where[] = "(name LIKE :s1 OR email LIKE :s2 OR subject LIKE :s3 OR message LIKE :s4)";
    $s = "%$search%";
    $params = [':s1'=>$s, ':s2'=>$s, ':s3'=>$s, ':s4'=>$s];
}
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$perPage = 10;
$page    = max(1,(int)($_GET['page'] ?? 1));
$offset  = ($page-1)*$perPage;

$c = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereSQL");
$c->execute($params);
$totalRows  = $c->fetchColumn();
$totalPages = ceil($totalRows/$perPage);

$stmt = $pdo->prepare("SELECT * FROM contact_messages $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$messages = $stmt->fetchAll();

$unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
$flash = getFlash();
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
    .mact { display:inline-flex;align-items:center;justify-content:center;gap:6px;height:34px;border-radius:99px;font-size:13px;font-weight:600;white-space:nowrap;cursor:pointer;transition:all .25s;text-decoration:none;border:none;line-height:1; }
    .mact-reply  { padding:0 16px;background:rgba(139,0,0,.09);color:#8B0000;border:1.5px solid rgba(139,0,0,.2); }
    .mact-reply:hover  { background:#8B0000;color:#fff; }
    .mact-read   { padding:0 14px;background:#dbeafe;color:#2563eb;border:1.5px solid #bfdbfe; }
    .mact-read:hover   { background:#2563eb;color:#fff; }
    .mact-delete { width:34px;height:34px;padding:0;border-radius:8px;background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;flex-shrink:0; }
    .mact-delete:hover { background:#dc2626;color:#fff;border-color:#dc2626; }
    .reply-info  { display:flex;gap:10px;background:#fff8e1;border:1px solid #f59e0b;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;line-height:1.5; }
    .reply-info i{ color:#f59e0b;margin-top:2px;flex-shrink:0; }
    .reply-row   { display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #eee;font-size:14px; }
    .reply-row-label { font-weight:700;color:#555;width:56px;flex-shrink:0; }
    .reply-textarea  { width:100%;padding:12px 14px;border:1.5px solid #e5e5e5;border-radius:10px;font-size:14px;font-family:'Outfit',sans-serif;resize:vertical;outline:none;margin-top:12px; }
    .reply-textarea:focus { border-color:#8B0000; }
    .modal-footer { display:flex;gap:10px;flex-wrap:wrap;margin-top:16px; }
    #newMsgToast { display:none;position:fixed;bottom:80px;right:24px;background:#8B0000;color:#fff;padding:10px 18px;border-radius:99px;font-size:13px;font-weight:600;box-shadow:0 4px 20px rgba(139,0,0,.35);z-index:999;align-items:center;gap:10px; }
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
        <h1>Messages</h1>
        <p><?= $totalRows ?> message<?= $totalRows!=1?'s':''?>
          <?php if($unreadCount>0): ?> — <strong style="color:#8B0000"><?= $unreadCount ?> unread</strong><?php endif; ?>
        </p>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <div class="filter-tabs">
          <a href="messages.php" class="ftab <?= !$filter?'active':''?>">All</a>
          <a href="?filter=unread" class="ftab <?= $filter==='unread'?'active':''?>">Unread <?php if($unreadCount>0):?><span class="badge-count"><?=$unreadCount?></span><?php endif;?></a>
          <a href="?filter=read"   class="ftab <?= $filter==='read'?'active':''?>">Read</a>
        </div>
        <?php if($unreadCount>0): ?>
        <a href="?readall=1" class="btn-outline-sm" id="readAllBtn"><i class="fas fa-check-double"></i> Mark All Read</a>
        <?php endif; ?>
      </div>
    </div>

    <form method="GET" class="filter-bar">
      <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search name, email, message..." value="<?= htmlspecialchars($search) ?>"/>
      </div>
      <?php if($filter): ?><input type="hidden" name="filter" value="<?= $filter ?>"/><?php endif; ?>
      <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Search</button>
    </form>

    <div class="messages-list">
      <?php foreach($messages as $m): ?>
      <div class="msg-card <?= $m['is_read']?'read':'unread' ?>">
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
              onclick='openReply(<?= json_encode($m["email"]) ?>,<?= json_encode($m["name"]) ?>,<?= json_encode($m["subject"]?:"Your Enquiry") ?>)'>
              <i class="fas fa-reply"></i> Reply
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

<!-- ═══ REPLY MODAL ═══ -->
<div class="modal-overlay" id="replyModal">
  <div class="modal-box" style="max-width:540px;">
    <div class="modal-header">
      <h3><i class="fas fa-reply"></i> Reply to Message</h3>
      <button class="modal-close" onclick="closeReply()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="reply-info">
        <i class="fas fa-lightbulb"></i>
        <div>Click <strong>"Open in Gmail"</strong> — it opens Gmail in a new tab with everything pre-filled. Just press Send! Works with Outlook too via "Mail App".</div>
      </div>
      <div class="reply-row"><span class="reply-row-label">To:</span><span id="rTo" style="color:#8B0000;font-weight:600;"></span></div>
      <div class="reply-row"><span class="reply-row-label">Subject:</span><span id="rSubject"></span></div>
      <textarea class="reply-textarea" id="rBody" rows="6" placeholder="Type your reply..."></textarea>
      <div class="modal-footer">
        <button class="btn-primary" onclick="openGmail()"><i class="fab fa-google"></i> Open in Gmail</button>
        <button class="btn-primary" onclick="openMailto()" style="background:#2563eb;"><i class="fas fa-envelope"></i> Mail App</button>
        <button class="btn-outline-sm" onclick="copyReplyText()"><i class="fas fa-copy"></i> Copy Text</button>
        <button class="btn-outline-sm" onclick="closeReply()">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- new-message toast -->
<div id="newMsgToast">
  <i class="fas fa-envelope"></i> New message received!
  <button onclick="location.reload()" style="background:#fff;color:#8B0000;border:none;border-radius:99px;padding:3px 12px;font-size:12px;font-weight:700;cursor:pointer;">Refresh</button>
</div>

<script src="js/admin.js"></script>
<script>
let _email='', _subject='';

function openReply(email, name, subject) {
  _email   = email;
  _subject = 'Re: ' + subject;
  document.getElementById('rTo').textContent      = name + ' <' + email + '>';
  document.getElementById('rSubject').textContent = 'Re: ' + subject;
  document.getElementById('rBody').value          = '\n\n—\nBest regards,\nWings Cut Studio';
  document.getElementById('replyModal').classList.add('active');
}
function closeReply() { document.getElementById('replyModal').classList.remove('active'); }
document.getElementById('replyModal').addEventListener('click', e => { if(e.target===document.getElementById('replyModal')) closeReply(); });

function openGmail() {
  const url = 'https://mail.google.com/mail/?view=cm&fs=1'
    + '&to='  + encodeURIComponent(_email)
    + '&su='  + encodeURIComponent(_subject)
    + '&body='+ encodeURIComponent(document.getElementById('rBody').value);
  window.open(url, '_blank');
  closeReply();
}
function openMailto() {
  window.location.href = 'mailto:' + _email
    + '?subject=' + encodeURIComponent(_subject)
    + '&body='    + encodeURIComponent(document.getElementById('rBody').value);
  closeReply();
}
function copyReplyText() {
  navigator.clipboard.writeText(document.getElementById('rBody').value);
  event.target.closest('button').innerHTML = '<i class="fas fa-check"></i> Copied!';
  setTimeout(()=>{ event.target.closest('button').innerHTML='<i class="fas fa-copy"></i> Copy Text'; }, 2000);
}

// ── Auto-refresh every 15s — reload immediately on new message ──
let knownCount = <?= $unreadCount ?>;
setInterval(() => {
  fetch('ajax/msg_count.php')
    .then(r => r.json())
    .then(d => {
      if (d.count > knownCount) {
        knownCount = d.count;
        const t = document.getElementById('newMsgToast');
        t.style.display = 'flex';
        // Reload after 1.5s so new message shows immediately
        setTimeout(() => location.reload(), 1500);
      }
    }).catch(()=>{});
}, 15000);
</script>
</body>
</html>