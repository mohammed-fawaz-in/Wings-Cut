<?php
require_once __DIR__ . '/includes/auth.php';

// ── Fetch Stats ──────────────────────────────────────────
$stats = [];

// Bookings
$stats['total_bookings']     = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$stats['pending_bookings']   = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$stats['confirmed_bookings'] = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
$stats['completed_bookings'] = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn();

// Messages
$stats['total_messages']    = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$stats['unread_messages']   = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();

// Gallery
$stats['total_gallery'] = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();

// Revenue estimate
$packagePrices = ['essentials'=>15000,'signature'=>35000,'elite'=>65000,'custom'=>30000];
$completedRows = $pdo->query("SELECT package FROM bookings WHERE status IN ('confirmed','completed')")->fetchAll();
$revenue = 0;
foreach ($completedRows as $row) {
    $revenue += $packagePrices[$row['package']] ?? 0;
}

// Recent 5 bookings
$recentBookings = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent 5 messages
$recentMessages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Bookings by package (for mini chart)
$byPackage = $pdo->query("SELECT package, COUNT(*) as cnt FROM bookings GROUP BY package")->fetchAll();

// Monthly bookings (last 6 months)
$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b') as month, COUNT(*) as cnt
    FROM bookings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at ASC
")->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — Wings Cut Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet"/>
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
      <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
      <?= htmlspecialchars($flash['text']) ?>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1>Dashboard</h1>
        <p>Welcome back! Here's what's happening at Wings Cut.</p>
      </div>
      <a href="bookings.php" class="btn-primary"><i class="fas fa-calendar-check"></i> View Bookings</a>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card red">
        <div class="stat-card-top">
          <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
          <span class="stat-label">Total Bookings</span>
        </div>
        <div class="stat-num"><?= $stats['total_bookings'] ?></div>
        <div class="stat-badge pending"><?= $stats['pending_bookings'] ?> pending</div>
      </div>
      <div class="stat-card green">
        <div class="stat-card-top">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <span class="stat-label">Confirmed</span>
        </div>
        <div class="stat-num"><?= $stats['confirmed_bookings'] ?></div>
        <div class="stat-badge confirmed"><?= $stats['completed_bookings'] ?> completed</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-card-top">
          <div class="stat-icon"><i class="fas fa-envelope"></i></div>
          <span class="stat-label">Messages</span>
        </div>
        <div class="stat-num"><?= $stats['total_messages'] ?></div>
        <?php if ($stats['unread_messages'] > 0): ?>
        <div class="stat-badge unread"><?= $stats['unread_messages'] ?> unread</div>
        <?php else: ?>
        <div class="stat-badge confirmed">all read</div>
        <?php endif; ?>
      </div>
      <div class="stat-card gold">
        <div class="stat-card-top">
          <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
          <span class="stat-label">Est. Revenue</span>
        </div>
        <div class="stat-num">₹<?= number_format($revenue) ?></div>
        <div class="stat-badge revenue">confirmed + done</div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="dash-grid-2">

      <!-- Monthly Bookings Bar Chart -->
      <div class="dash-card">
        <div class="dash-card-header">
          <h3><i class="fas fa-chart-bar"></i> Monthly Bookings</h3>
          <span class="dash-card-sub">Last 6 months</span>
        </div>
        <?php if(empty($monthly)): ?>
        <div style="text-align:center;padding:40px 20px;color:#aaa;">
          <i class="fas fa-chart-bar" style="font-size:36px;margin-bottom:12px;display:block;color:#ddd;"></i>
          <p style="font-size:14px;">No bookings yet — chart appears here</p>
        </div>
        <?php else:
          $maxVal = max(1, max(array_column($monthly,'cnt')));
          // Fill missing months to always show 6 bars
          $monthLabels = [];
          for($i=5;$i>=0;$i--) {
            $monthLabels[] = date('M', strtotime("-$i months"));
          }
        ?>
        <div style="display:flex;align-items:flex-end;gap:8px;padding:24px 4px 0;position:relative;">
          <!-- Y axis grid lines -->
          <div style="position:absolute;top:0;left:0;right:0;height:calc(100% - 28px);display:flex;flex-direction:column;justify-content:space-between;pointer-events:none;">
            <?php for($g=0;$g<3;$g++): ?>
            <div style="border-top:1px dashed #f0f0f0;width:100%;"></div>
            <?php endfor; ?>
          </div>
          <?php
          // Map month name to count
          $monthData = [];
          foreach($monthly as $m) $monthData[$m['month']] = $m['cnt'];
          foreach($monthLabels as $lbl):
            $cnt = $monthData[$lbl] ?? 0;
            $barH = $cnt > 0 ? max(14, round(($cnt/$maxVal)*120)) : 6;
            $barColor = $cnt > 0 ? 'linear-gradient(180deg,#c0392b,#8B0000)' : '#f0f0f0';
            $numColor = $cnt > 0 ? '#8B0000' : '#ccc';
          ?>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
            <span style="font-size:11px;font-weight:700;color:<?= $numColor ?>;min-height:16px;"><?= $cnt > 0 ? $cnt : '' ?></span>
            <div style="width:100%;height:120px;display:flex;align-items:flex-end;">
              <div style="width:100%;height:<?= $barH ?>px;background:<?= $barColor ?>;border-radius:5px 5px 0 0;transition:height 0.6s ease;"></div>
            </div>
            <span style="font-size:11px;color:#999;font-weight:500;"><?= $lbl ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Package Distribution -->
      <div class="dash-card">
        <div class="dash-card-header">
          <h3><i class="fas fa-chart-pie"></i> Bookings by Package</h3>
          <span class="dash-card-sub">All time</span>
        </div>
        <div class="pkg-list">
          <?php
          $pkgColors = ['essentials'=>'#22c55e','signature'=>'#8B0000','elite'=>'#f59e0b','custom'=>'#6366f1'];
          $pkgTotal  = array_sum(array_column($byPackage,'cnt')) ?: 1;
          foreach ($byPackage as $pkg):
            $pct = round(($pkg['cnt']/$pkgTotal)*100);
            $col = $pkgColors[$pkg['package']] ?? '#888';
          ?>
          <div class="pkg-row">
            <div class="pkg-dot" style="background:<?= $col ?>"></div>
            <span class="pkg-name"><?= ucfirst($pkg['package']) ?></span>
            <div class="pkg-bar-track">
              <div class="pkg-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
            </div>
            <span class="pkg-count"><?= $pkg['cnt'] ?></span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($byPackage)): ?>
          <p class="no-data">No bookings yet</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Bookings + Messages -->
    <div class="dash-grid-2">

      <!-- Recent Bookings -->
      <div class="dash-card">
        <div class="dash-card-header">
          <h3><i class="fas fa-calendar"></i> Recent Bookings</h3>
          <a href="bookings.php" class="dash-see-all">See all <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="mini-table-wrap">
          <table class="mini-table">
            <thead><tr><th>Name</th><th>Package</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentBookings as $b): ?>
            <tr>
              <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
              <td><?= ucfirst($b['package']) ?></td>
              <td><?= date('d M', strtotime($b['booking_date'])) ?></td>
              <td><span class="status-badge <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentBookings)): ?>
            <tr><td colspan="4" class="no-data">No bookings yet</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Messages -->
      <div class="dash-card">
        <div class="dash-card-header">
          <h3><i class="fas fa-envelope"></i> Recent Messages</h3>
          <a href="messages.php" class="dash-see-all">See all <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="msg-list">
          <?php foreach ($recentMessages as $m): ?>
          <div class="msg-item <?= $m['is_read'] ? '' : 'unread' ?>">
            <div class="msg-avatar"><?= strtoupper(substr($m['name'],0,1)) ?></div>
            <div class="msg-body">
              <strong><?= htmlspecialchars($m['name']) ?></strong>
              <?php if (!$m['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
              <p><?= htmlspecialchars(substr($m['message'],0,55)) ?>...</p>
              <small><?= date('d M Y', strtotime($m['created_at'])) ?></small>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($recentMessages)): ?>
          <p class="no-data">No messages yet</p>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script src="js/admin.js"></script>
</body>
</html>
