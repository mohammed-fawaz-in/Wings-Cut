/**
 * WINGS CUT — Admin JavaScript
 */

// ── Sidebar toggle (mobile) ──────────────────────────────
function toggleSidebar() {
  document.getElementById('adminSidebar').classList.toggle('open');
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('adminSidebar');
  const toggle  = document.getElementById('sidebarToggle');
  if (sidebar && !sidebar.contains(e.target) && toggle && !toggle.contains(e.target)) {
    sidebar.classList.remove('open');
  }
});

// ── ADMIN CURSOR ──────────────────────────────────────
(function() {
  const dot  = document.getElementById('adminCursorDot');
  const ring = document.getElementById('adminCursorRing');
  if (!dot || !ring || !window.matchMedia('(pointer:fine)').matches) return;

  let mx=0, my=0, rx=0, ry=0, visible=false;

  document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    dot.style.left  = mx + 'px';
    dot.style.top   = my + 'px';
    if (!visible) {
      visible = true;
      dot.style.opacity  = '1';
      ring.style.opacity = '1';
    }

    // Check if cursor is over dark/red area — make ring white for contrast
    const el = document.elementFromPoint(mx, my);
    if (el) {
      const bg = window.getComputedStyle(el).backgroundColor;
      const isRed = bg.includes('139') || el.closest('.admin-sidebar');
      ring.style.borderColor = isRed ? 'rgba(255,255,255,0.6)' : 'rgba(139,0,0,0.4)';
      dot.style.background   = 'white';
    }
  });

  (function anim() {
    rx += (mx - rx) * 0.1;
    ry += (my - ry) * 0.1;
    ring.style.left = rx + 'px';
    ring.style.top  = ry + 'px';
    requestAnimationFrame(anim);
  })();

  document.querySelectorAll('a, button, .stat-card, .booking-card, .msg-card, .gallery-admin-card, input, select').forEach(el => {
    el.addEventListener('mouseenter', () => { dot.classList.add('hov'); ring.classList.add('hov'); });
    el.addEventListener('mouseleave', () => { dot.classList.remove('hov'); ring.classList.remove('hov'); });
  });

  document.addEventListener('mouseleave', () => { dot.style.opacity='0'; ring.style.opacity='0'; visible=false; });
  document.addEventListener('mouseenter', () => { dot.style.opacity='1'; ring.style.opacity='1'; });
})();

// ── Auto-dismiss flash alerts ───────────────────────────
document.querySelectorAll('.flash-alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity 0.5s ease, max-height 0.5s ease';
    el.style.opacity    = '0';
    el.style.maxHeight  = '0';
    el.style.overflow   = 'hidden';
    el.style.padding    = '0';
    el.style.margin     = '0';
  }, 4000);
});

// ── Animate bar chart fills ─────────────────────────────
window.addEventListener('load', () => {
  document.querySelectorAll('.bar-fill').forEach(bar => {
    const target = bar.style.height;
    bar.style.height = '0';
    setTimeout(() => { bar.style.height = target; }, 300);
  });
  document.querySelectorAll('.pkg-bar-fill').forEach(bar => {
    const target = bar.style.width;
    bar.style.width = '0';
    setTimeout(() => { bar.style.width = target; }, 500);
  });
});

// ── IN-APP CONFIRM DIALOG ──────────────────────────────
(function() {
  const html = `
  <div id="confirmOverlay">
    <div id="confirmBox">
      <div id="confirmIcon"></div>
      <div id="confirmTitle"></div>
      <div id="confirmMsg"></div>
      <div class="confirm-btns">
        <button id="confirmCancel">Cancel</button>
        <button id="confirmOk">Delete</button>
      </div>
    </div>
  </div>`;
  document.body.insertAdjacentHTML('beforeend', html);

  const overlay  = document.getElementById('confirmOverlay');
  const titleEl  = document.getElementById('confirmTitle');
  const msgEl    = document.getElementById('confirmMsg');
  const iconEl   = document.getElementById('confirmIcon');
  const okBtn    = document.getElementById('confirmOk');
  const cancelBtn= document.getElementById('confirmCancel');
  let _resolve   = null;

  function showConfirm(t, m, type) {
    titleEl.textContent = t;
    msgEl.textContent   = m;
    if (type === 'warn') {
      iconEl.className  = 'warn';
      iconEl.innerHTML  = '<i class="fas fa-exclamation-triangle"></i>';
      okBtn.className   = 'warn-ok';
      okBtn.textContent = 'Yes, proceed';
    } else {
      iconEl.className  = '';
      iconEl.innerHTML  = '<i class="fas fa-trash"></i>';
      okBtn.className   = '';
      okBtn.textContent = 'Delete';
    }
    overlay.classList.add('show');
    return new Promise(res => { _resolve = res; });
  }

  function closeConfirm(val) {
    overlay.classList.remove('show');
    if (_resolve) { _resolve(val); _resolve = null; }
  }

  okBtn.addEventListener('click',     () => closeConfirm(true));
  cancelBtn.addEventListener('click', () => closeConfirm(false));
  overlay.addEventListener('click', e => { if (e.target === overlay) closeConfirm(false); });

  // Global function for onclick usage
  window.appConfirm = async function(t, m, action, type) {
    const ok = await showConfirm(t, m, type || 'delete');
    if (!ok) return false;
    if (typeof action === 'function') action();
    else if (typeof action === 'string') window.location.href = action;
    return false;
  };

  // ── Intercept ALL delete links across all admin pages ──
  document.addEventListener('click', async function(e) {
    const link = e.target.closest('a[href*="delete="]');
    if (!link) return;
    e.preventDefault();
    e.stopPropagation();

    const page = window.location.pathname;
    let itemName = 'this item';
    if (page.includes('booking'))  itemName = 'this booking';
    if (page.includes('message'))  itemName = 'this message';
    if (page.includes('gallery'))  itemName = 'this image';

    const ok = await showConfirm(
      'Delete ' + itemName + '?',
      'It will be permanently removed and cannot be undone.',
      'delete'
    );
    if (ok) window.location.href = link.getAttribute('href');
  }, true); // capture=true fires BEFORE onclick

  // ── Intercept Mark All Read ──
  document.addEventListener('click', async function(e) {
    const link = e.target.closest('#readAllBtn, a[href*="readall"]');
    if (!link) return;
    e.preventDefault();
    e.stopPropagation();
    const ok = await showConfirm(
      'Mark all as read?',
      'All unread messages will be marked as read.',
      'warn'
    );
    if (ok) window.location.href = link.getAttribute('href');
  }, true);
})();


// ── File drop highlight ─────────────────────────────────
const fileDrop = document.getElementById('fileDrop');
if (fileDrop) {
  ['dragenter','dragover'].forEach(evt => {
    fileDrop.addEventListener(evt, e => {
      e.preventDefault();
      fileDrop.style.borderColor = '#8B0000';
      fileDrop.style.background  = 'rgba(139,0,0,0.05)';
    });
  });
  ['dragleave','drop'].forEach(evt => {
    fileDrop.addEventListener(evt, e => {
      e.preventDefault();
      fileDrop.style.borderColor = '';
      fileDrop.style.background  = '';
    });
  });
  fileDrop.addEventListener('drop', e => {
    const file = e.dataTransfer.files[0];
    if (file) {
      document.getElementById('fileInput').files = e.dataTransfer.files;
      document.getElementById('fileName').textContent = file.name;
    }
  });
  // Click anywhere on drop zone opens file picker
  fileDrop.addEventListener('click', () => document.getElementById('fileInput').click());
}

// ── Active nav link highlight ───────────────────────────
const currentPath = window.location.pathname.split('/').pop();
document.querySelectorAll('.sidebar-link').forEach(link => {
  const href = link.getAttribute('href');
  if (href && href === currentPath) link.classList.add('active');
});