<?php
require_once __DIR__ . '/includes/auth.php';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id     = (int)($_POST['booking_id'] ?? 0);
    $action = cl($_POST['action']);
    $allowed = ['confirmed','cancelled','completed','pending'];

    if ($id > 0 && in_array($action, $allowed)) {
        $stmt = $pdo->prepare("UPDATE bookings SET status=:s WHERE id=:id");
        $stmt->execute([':s'=>$action,':id'=>$id]);
        adminRedirect('bookings.php', 'Booking status updated to ' . ucfirst($action) . '!', 'success');
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([(int)$_GET['delete']]);
    adminRedirect('bookings.php', 'Booking deleted.', 'success');
}

// Filters
$status  = cl($_GET['status']  ?? '');
$search  = cl($_GET['search']  ?? '');
$package = cl($_GET['package'] ?? '');

$where = []; $params = [];

if ($status)  { $where[] = "status = :status";   $params[':status']  = $status; }
if ($package) { $where[] = "package = :package"; $params[':package'] = $package; }
if ($search)  {
    $where[] = "(name LIKE :s1 OR email LIKE :s2 OR phone LIKE :s3)";
    $s = "%$search%";
    $params[':s1'] = $s; $params[':s2'] = $s; $params[':s3'] = $s;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$total   = $pdo->prepare("SELECT COUNT(*) FROM bookings $whereSQL");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $pdo->prepare("SELECT * FROM bookings $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bookings — Wings Cut Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/admin.css"/>
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
        <h1>Bookings</h1>
        <p><?= $totalRows ?> total booking<?= $totalRows != 1 ? 's' : '' ?></p>
      </div>
      <!-- Status summary pills -->
      <div class="status-pills">
        <?php
        $counts = $pdo->query("SELECT status, COUNT(*) as c FROM bookings GROUP BY status")->fetchAll();
        $cmap = array_column($counts,'c','status');
        foreach (['pending','confirmed','completed','cancelled'] as $s):
          $cnt = $cmap[$s] ?? 0;
        ?>
        <a href="?status=<?= $s ?>" class="status-pill <?= $s ?> <?= ($status===$s)?'active':'' ?>">
          <?= ucfirst($s) ?> <span><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
        <?php if ($status): ?><a href="bookings.php" class="status-pill clear">Clear <i class="fas fa-times"></i></a><?php endif; ?>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" class="filter-bar">
      <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search name, email, phone..."
               value="<?= htmlspecialchars($search) ?>"/>
      </div>
      <select name="package">
        <option value="">All Packages</option>
        <option value="essentials"  <?= $package==='essentials'?'selected':''  ?>>Essentials</option>
        <option value="signature"   <?= $package==='signature'?'selected':''   ?>>Signature</option>
        <option value="elite"       <?= $package==='elite'?'selected':''       ?>>Elite</option>
        <option value="custom"      <?= $package==='custom'?'selected':''      ?>>Custom</option>
      </select>
      <?php if ($status): ?><input type="hidden" name="status" value="<?= $status ?>"/><?php endif; ?>
      <button type="submit" class="btn-primary"><i class="fas fa-filter"></i> Filter</button>
    </form>

    <!-- Bookings Table -->
    <div class="table-card">
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Client</th>
              <th>Package</th>
              <th>Event</th>
              <th>Session</th>
              <th>Booking Date</th>
              <th>Submitted</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td class="td-id">#<?= $b['id'] ?></td>
            <td>
              <div class="client-cell">
                <div class="client-avatar"><?= strtoupper(substr($b['name'],0,1)) ?></div>
                <div>
                  <strong><?= htmlspecialchars($b['name']) ?></strong>
                  <small><?= htmlspecialchars($b['email']) ?></small>
                  <small><?= htmlspecialchars($b['phone']) ?></small>
                </div>
              </div>
            </td>
            <td><span class="pkg-tag <?= $b['package'] ?>"><?= ucfirst($b['package']) ?></span></td>
            <td><?= ucfirst($b['event_type'] ?: '—') ?></td>
            <td>
              <?php if($b['time_slot']): ?>
              <span style="font-size:12px;background:#fef3c7;color:#b45309;padding:2px 8px;border-radius:99px;font-weight:600;">
                <?= $b['time_slot']==='morning' ? '🌅 Morning (10AM–5PM)' : '🌆 Evening (5PM–12AM)' ?>
              </span>
              <?php else: echo '—'; endif; ?>
            </td>
            <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
            <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
            <td><span class="status-badge <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
            <td>
              <div class="action-btns">
                <!-- Status buttons in their own isolated form -->
                <form method="POST" style="display:contents" onsubmit="return true;">
                  <input type="hidden" name="booking_id" value="<?= $b['id'] ?>"/>
                  <?php if ($b['status'] === 'pending'): ?>
                    <button type="submit" name="action" value="confirmed" class="btn-act confirm" title="Confirm Booking">
                      <i class="fas fa-check"></i>
                    </button>
                    <button type="submit" name="action" value="cancelled" class="btn-act cancel" title="Cancel">
                      <i class="fas fa-times"></i>
                    </button>
                  <?php elseif ($b['status'] === 'confirmed'): ?>
                    <button type="submit" name="action" value="completed" class="btn-act complete" title="Mark Complete">
                      <i class="fas fa-flag-checkered"></i>
                    </button>
                    <button type="submit" name="action" value="cancelled" class="btn-act cancel" title="Cancel">
                      <i class="fas fa-times"></i>
                    </button>
                  <?php elseif ($b['status'] === 'cancelled'): ?>
                    <button type="submit" name="action" value="pending" class="btn-act reopen" title="Reopen">
                      <i class="fas fa-redo"></i>
                    </button>
                  <?php endif; ?>
                </form>
                <!-- Eye button OUTSIDE form so it never submits -->
                <button type="button" class="btn-act view"
                  onclick="openDetails(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)"
                  title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <!-- Delete OUTSIDE form -->
                <a href="?delete=<?= $b['id'] ?>" class="btn-act delete" title="Delete"
                   onclick="return confirm('Delete this booking permanently?')">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($bookings)): ?>
          <tr><td colspan="8" class="no-data-row">No bookings found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>&package=<?= $package ?>"
           class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- MOBILE CARD VIEW (shown on mobile via CSS, hidden on desktop) -->
    <div class="booking-card-list">
  <?php foreach ($bookings as $b):
    $bookingId = 'WC-' . str_pad($b['id'], 4, '0', STR_PAD_LEFT);
    $slotLabel = $b['time_slot'] === 'morning' ? '🌅 Morning' : ($b['time_slot'] === 'evening' ? '🌆 Evening' : '—');
  ?>
  <div class="booking-card">
    <div class="bc-header">
      <div class="bc-avatar"><?= strtoupper(substr($b['name'],0,1)) ?></div>
      <div>
        <div class="bc-name"><?= htmlspecialchars($b['name']) ?></div>
        <div class="bc-email"><?= htmlspecialchars($b['email']) ?></div>
      </div>
      <div class="bc-id"><?= $bookingId ?></div>
    </div>
    <div class="bc-grid">
      <div class="bc-field"><span>Package</span>
        <strong><span class="pkg-tag <?= $b['package'] ?>"><?= ucfirst($b['package']) ?></span></strong>
      </div>
      <div class="bc-field"><span>Status</span>
        <strong><span class="status-badge <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></strong>
      </div>
      <div class="bc-field"><span>Date</span>
        <strong><?= date('d M Y', strtotime($b['booking_date'])) ?></strong>
      </div>
      <div class="bc-field"><span>Session</span>
        <strong><?= $slotLabel ?></strong>
      </div>
      <div class="bc-field"><span>Event</span>
        <strong><?= ucfirst($b['event_type'] ?: '—') ?></strong>
      </div>
      <div class="bc-field"><span>Phone</span>
        <strong><?= htmlspecialchars($b['phone']) ?></strong>
      </div>
    </div>
    <div class="bc-actions">
      <form method="POST" style="display:contents">
        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>"/>
        <?php if ($b['status'] === 'pending'): ?>
          <button type="submit" name="action" value="confirmed" class="btn-act confirm" title="Confirm"><i class="fas fa-check"></i></button>
          <button type="submit" name="action" value="cancelled" class="btn-act cancel" title="Cancel"><i class="fas fa-times"></i></button>
        <?php elseif ($b['status'] === 'confirmed'): ?>
          <button type="submit" name="action" value="completed" class="btn-act complete" title="Complete"><i class="fas fa-flag-checkered"></i></button>
          <button type="submit" name="action" value="cancelled" class="btn-act cancel" title="Cancel"><i class="fas fa-times"></i></button>
        <?php elseif ($b['status'] === 'cancelled'): ?>
          <button type="submit" name="action" value="pending" class="btn-act reopen" title="Reopen"><i class="fas fa-redo"></i></button>
        <?php endif; ?>
      </form>
      <button type="button" class="btn-act view"
        onclick="openDetails(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)"
        title="View Details"><i class="fas fa-eye"></i></button>
      <a href="?delete=<?= $b['id'] ?>" class="btn-act delete"
        onclick="return confirm('Delete this booking?')"><i class="fas fa-trash"></i></a>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($bookings)): ?>
  <div class="empty-state"><i class="fas fa-calendar"></i><p>No bookings found</p></div>
  <?php endif; ?>
    </div><!-- /booking-card-list -->

  </div><!-- /admin-content -->
</div><!-- /admin-main -->

<!-- Booking Detail Modal -->
<div class="modal-overlay" id="detailModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-calendar-check"></i> Booking Details</h3>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function openDetails(b) {
  const pkg = b.package.charAt(0).toUpperCase()+b.package.slice(1);
  const status = b.status.charAt(0).toUpperCase()+b.status.slice(1);
  const bookingId = 'WC-' + String(b.id).padStart(4,'0');

  // Build Gmail confirmation link
  const subject = encodeURIComponent('Your Booking is Confirmed — Wings Cut Studio (' + bookingId + ')');
  const body = encodeURIComponent(
    'Dear ' + b.name + ',\n\n' +
    'We are delighted to confirm your booking with Wings Cut Studio!\n\n' +
    '━━━━━━━━━━━━━━━━━━━━━━━━\n' +
    'BOOKING DETAILS\n' +
    '━━━━━━━━━━━━━━━━━━━━━━━━\n' +
    'Booking ID   : ' + bookingId + '\n' +
    'Package      : ' + pkg + '\n' +
    'Session Time : ' + (b.time_slot ? (b.time_slot==='morning' ? 'Morning (10AM–5PM)' : 'Evening (5PM–12AM)') : 'Not specified') + '\n' +
    'Event Type   : ' + (b.event_type || 'Not specified') + '\n' +
    'Date         : ' + b.booking_date + '\n' +
    '━━━━━━━━━━━━━━━━━━━━━━━━\n\n' +
    'Our team will reach out to you shortly to discuss the finer details of your session.\n\n' +
    'For any queries, feel free to reply to this email or WhatsApp us.\n\n' +
    'Warm regards,\n' +
    'Wings Cut Studio\n' +
    'hello@wingscut.studio | +91 98765 43210'
  );
  const gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to=' + encodeURIComponent(b.email) + '&su=' + subject + '&body=' + body;

  document.getElementById('modalBody').innerHTML = `
    <div class="detail-grid">
      <div class="detail-item"><span>Booking ID</span><strong style="color:var(--red);font-size:16px;">${bookingId}</strong></div>
      <div class="detail-item"><span>Status</span><span class="status-badge ${b.status}">${status}</span></div>
      <div class="detail-item"><span>Name</span><strong>${b.name}</strong></div>
      <div class="detail-item"><span>Email</span><strong>${b.email}</strong></div>
      <div class="detail-item"><span>Phone</span><strong>${b.phone}</strong></div>
      <div class="detail-item"><span>Package</span><strong>${pkg}</strong></div>
      <div class="detail-item"><span>Event Date</span><strong>${b.booking_date}</strong></div>
      <div class="detail-item"><span>Event Type</span><strong>${b.event_type || '—'}</strong></div>
      <div class="detail-item"><span>Submitted On</span><strong>${b.created_at}</strong></div>
      <div class="detail-item"><span>Session Time</span><strong>${b.time_slot ? (b.time_slot.charAt(0).toUpperCase()+b.time_slot.slice(1) + (b.time_slot==='morning' ? ' 🌅 (10AM–5PM)' : ' 🌆 (5PM–12AM)')) : '—'}</strong></div>
      <div class="detail-item"><span>Venue / Address</span><strong>${b.venue && b.venue.trim() ? b.venue : '—'}</strong></div>
      ${b.message ? `<div class="detail-item full"><span>Message</span><p>${b.message}</p></div>` : ''}
    </div>
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid #eee;display:flex;gap:10px;flex-wrap:wrap;">
      <a href="${gmailUrl}" target="_blank"
         style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#8B0000;color:white;border-radius:99px;font-size:14px;font-weight:600;text-decoration:none;">
        <i class="fab fa-google"></i> Send Confirmation via Gmail
      </a>
      <a href="mailto:${b.email}?subject=${subject}&body=${body}"
         style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#2563eb;color:white;border-radius:99px;font-size:14px;font-weight:600;text-decoration:none;">
        <i class="fas fa-envelope"></i> Mail App
      </a>
    </div>`;
  document.getElementById('detailModal').classList.add('active');
}
function closeModal() {
  document.getElementById('detailModal').classList.remove('active');
}
document.getElementById('detailModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>
