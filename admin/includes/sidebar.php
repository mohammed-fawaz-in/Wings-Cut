<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$unread      = 0; $pending = 0; $userUnread = 0;
try { $unread     = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(); } catch(Exception $e) {}
try { $pending    = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(); } catch(Exception $e) {}
try { $userUnread = (int)$pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='user' AND is_read=0")->fetchColumn(); } catch(Exception $e) {}
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon"><i class="fas fa-camera-retro"></i></div>
    <div>
      <span class="sidebar-brand">WINGS CUT</span>
      <span class="sidebar-sub">Admin Panel</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <span class="nav-section-label">Main</span>
    <a href="dashboard.php" class="sidebar-link <?= $currentPage==='dashboard.php'?'active':'' ?>">
      <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
    </a>
    <a href="bookings.php" class="sidebar-link <?= $currentPage==='bookings.php'?'active':'' ?>">
      <i class="fas fa-calendar-check"></i> <span>Bookings</span>
      <?php if($pending>0): ?><span class="nav-badge"><?= $pending ?></span><?php endif; ?>
    </a>
    <a href="messages.php" class="sidebar-link <?= $currentPage==='messages.php'?'active':'' ?>">
      <i class="fas fa-envelope"></i> <span>Messages</span>
      <?php if($unread>0): ?><span class="nav-badge"><?= $unread ?></span><?php endif; ?>
    </a>
    <a href="users.php" class="sidebar-link <?= $currentPage==='users.php'?'active':'' ?>">
      <i class="fas fa-comments"></i> <span>User Chat</span>
      <?php if($userUnread>0): ?><span class="nav-badge" style="background:#22c55e;"><?= $userUnread ?></span><?php endif; ?>
    </a>
    <span class="nav-section-label">Content</span>
    <a href="gallery.php" class="sidebar-link <?= $currentPage==='gallery.php'?'active':'' ?>">
      <i class="fas fa-images"></i> <span>Gallery</span>
    </a>
    <a href="payments.php" class="sidebar-link <?= $currentPage==='payments.php'?'active':'' ?>">
      <i class="fas fa-credit-card"></i> <span>Payments</span>
      <?php
        try {
          $pv=$pdo->query("SELECT COUNT(*) FROM bookings WHERE payment_screenshot IS NOT NULL AND payment_verified=0")->fetchColumn();
          if($pv>0) echo '<span class="nav-badge" style="background:#f59e0b;margin-left:auto">'.$pv.'</span>';
        } catch(Exception $e){}
      ?>
    </a>
    <span class="nav-section-label">Account</span>
    <a href="../index.php" target="_blank" class="sidebar-link">
      <i class="fas fa-external-link-alt"></i> <span>View Website</span>
    </a>
    <a href="logout.php" class="sidebar-link logout">
      <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </a>
  </nav>
  <div class="sidebar-footer">
    <small>Logged in as <strong>admin</strong></small>
  </div>
</aside>
