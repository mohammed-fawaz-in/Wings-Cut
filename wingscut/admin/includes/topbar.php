<?php
$pageTitles = [
  'dashboard.php' => 'Dashboard',
  'bookings.php'  => 'Bookings',
  'messages.php'  => 'Messages',
  'gallery.php'   => 'Gallery Manager',
];
$currentPage  = basename($_SERVER['PHP_SELF']);
$pageTitle    = $pageTitles[$currentPage] ?? 'Admin';
$unread       = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
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
    <a href="messages.php" class="topbar-icon-btn" title="Messages">
      <i class="fas fa-envelope"></i>
      <?php if ($unread > 0): ?><span class="topbar-badge"><?= $unread ?></span><?php endif; ?>
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
<!-- Admin Cursor -->
<div id="adminCursorDot"></div>
<div id="adminCursorRing"></div>