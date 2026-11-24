<?php
$pageTitles = [
  'dashboard.php' => 'Dashboard',
  'bookings.php'  => 'Bookings',
  'messages.php'  => 'Messages',
  'gallery.php'   => 'Gallery',
  'users.php'     => 'Users & Chat',
];
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle   = $pageTitles[$currentPage] ?? 'Admin';
$unread      = 0;
$chatUnread  = 0;
try { $unread     = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(); } catch(Exception $e) {}
try { $chatUnread = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='user' AND is_read=0")->fetchColumn(); } catch(Exception $e) {}
$totalBadge = (int)$unread + (int)$chatUnread;
?>
<header class="admin-topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
      <i class="fas fa-bars"></i>
    </button>
    <div class="topbar-breadcrumb">
      <span>Wings Cut</span>
      <i class="fas fa-chevron-right"></i>
      <span class="current"><?= $pageTitle ?></span>
    </div>
  </div>
  <div class="topbar-right">
    <a href="messages.php" class="topbar-icon-btn" title="Contact Messages">
      <i class="fas fa-envelope"></i>
      <?php if($unread > 0): ?><span class="topbar-badge"><?= $unread ?></span><?php endif; ?>
    </a>
    <a href="users.php" class="topbar-icon-btn" title="User Messages">
      <i class="fas fa-comments"></i>
      <?php if($chatUnread > 0): ?><span class="topbar-badge" style="background:#22c55e;"><?= $chatUnread ?></span><?php endif; ?>
    </a>
    <a href="../index.php" target="wingscut_preview" class="topbar-icon-btn" title="View Website">
      <i class="fas fa-external-link-alt"></i>
    </a>
    <div class="topbar-user">
      <div class="topbar-avatar">A</div>
      <span>Admin</span>
    </div>
  </div>
</header>
<!-- Custom Cursor (matching main site) -->
<div id="adminCursorDot"></div>
<div id="adminCursorRing"></div>
