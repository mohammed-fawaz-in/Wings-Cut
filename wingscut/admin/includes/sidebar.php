<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$unread      = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
$pending     = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
// Safe queries — tables may not exist yet
try { $userUnread = $pdo->query("SELECT COUNT(*) FROM user_messages WHERE sender='user' AND is_read=0")->fetchColumn(); } catch(Exception $e) { $userUnread = 0; }
try { $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); } catch(Exception $e) { $totalUsers = 0; }
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
      <?php if ($pending > 0): ?><span class="nav-badge"><?= $pending ?></span><?php endif; ?>
    </a>
    <a href="messages.php" class="sidebar-link <?= $currentPage==='messages.php'?'active':'' ?>">
      <i class="fas fa-envelope"></i> <span>Messages</span>
      <?php if ($unread > 0): ?><span class="nav-badge"><?= $unread ?></span><?php endif; ?>
    </a>
    <a href="users.php" class="sidebar-link <?= $currentPage==='users.php'?'active':'' ?>">
      <i class="fas fa-users"></i> <span>Users</span>
      <?php if ($userUnread > 0): ?><span class="nav-badge"><?= $userUnread ?></span><?php endif; ?>
    </a>

    <span class="nav-section-label">Content</span>
    <a href="gallery.php" class="sidebar-link <?= $currentPage==='gallery.php'?'active':'' ?>">
      <i class="fas fa-images"></i> <span>Gallery</span>
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