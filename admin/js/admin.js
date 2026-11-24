/* Wings cut - admin.js v3
   Cursor matches main site exactly. Real-time unread poll. */
'use strict';

/* Sidebar toggle */
function toggleSidebar() {
  document.getElementById('adminSidebar').classList.toggle('open');
}
document.addEventListener('click', e => {
  const sb  = document.getElementById('adminSidebar');
  const tog = document.getElementById('sidebarToggle');
  if (sb && tog && !sb.contains(e.target) && !tog.contains(e.target)) {
    sb.classList.remove('open');
  }
});

/* Admin cursor - exactly matches main site cursor style
   (dot + ring, red palette, smooth inertia ring, hover expand) */
(function initAdminCursor() {
  if (!window.matchMedia('(pointer: fine)').matches) return;

  const dot  = document.getElementById('adminCursorDot');
  const ring = document.getElementById('adminCursorRing');
  if (!dot || !ring) return;

  let mx = 0, my = 0, rx = 0, ry = 0;
  let visible = false;

  /* Position dot immediately on move, ring lags (inertia) */
  document.addEventListener('mousemove', e => {
    mx = e.clientX;
    my = e.clientY;
    dot.style.left = mx + 'px';
    dot.style.top  = my + 'px';
    if (!visible) {
      visible = true;
      dot.style.opacity  = '1';
      ring.style.opacity = '1';
    }
  });

  /* Smooth ring follow */
  (function animRing() {
    rx += (mx - rx) * 0.12;
    ry += (my - ry) * 0.12;
    ring.style.left = rx + 'px';
    ring.style.top  = ry + 'px';
    requestAnimationFrame(animRing);
  })();

  /* Hover targets — same set as main site + admin-specific */
  const SEL = 'a, button, input, select, textarea, .stat-card, .sidebar-link, .booking-card, .msg-card, .gallery-admin-card, [role="button"], .filter-btn, .user-row, .ftab, .page-btn';

  function attachHover(el) {
    if (el.dataset.cursorInit) return;
    el.dataset.cursorInit = '1';
    el.addEventListener('mouseenter', () => { dot.classList.add('hov'); ring.classList.add('hov'); });
    el.addEventListener('mouseleave', () => { dot.classList.remove('hov'); ring.classList.remove('hov'); });
  }
  document.querySelectorAll(SEL).forEach(attachHover);

  /* MutationObserver for dynamic content */
  new MutationObserver(() => {
    document.querySelectorAll(SEL + ':not([data-cursor-init])').forEach(attachHover);
  }).observe(document.body, { childList: true, subtree: true });

  /* Click feedback */
  document.addEventListener('mousedown', () => dot.classList.add('click'));
  document.addEventListener('mouseup',   () => dot.classList.remove('click'));

  /* Hide/show on leave/enter */
  document.addEventListener('mouseleave', () => { dot.style.opacity = '0'; ring.style.opacity = '0'; visible = false; });
  document.addEventListener('mouseenter', () => { dot.style.opacity = '1'; ring.style.opacity = '1'; });
})();

/* Auto-dismiss flash alerts */
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

/* Animate bar chart fills */
window.addEventListener('load', () => {
  document.querySelectorAll('.bar-fill').forEach(bar => {
    const target = bar.style.height;
    bar.style.height = '0';
    requestAnimationFrame(() => {
      bar.style.transition = 'height 1s cubic-bezier(0.4,0,0.2,1)';
      bar.style.height = target;
    });
  });
});

/* In-app confirm dialog (replaces browser confirm()) */
let _confirmResolve = null;

function adminConfirm(title, msg, isWarn) {
  return new Promise(resolve => {
    _confirmResolve = resolve;
    const overlay   = document.getElementById('confirmOverlay');
    const iconEl    = document.getElementById('confirmIcon');
    const titleEl   = document.getElementById('confirmTitle');
    const msgEl     = document.getElementById('confirmMsg');
    const okBtn     = document.getElementById('confirmOk');
    if (!overlay) { resolve(window.confirm(msg)); return; }
    titleEl.textContent = title || 'Are you sure?';
    msgEl.textContent   = msg   || '';
    iconEl.className    = isWarn ? 'warn' : '';
    okBtn.className     = isWarn ? 'warn-ok' : '';
    overlay.classList.add('show');
  });
}
function confirmYes() {
  document.getElementById('confirmOverlay')?.classList.remove('show');
  if (_confirmResolve) { _confirmResolve(true); _confirmResolve = null; }
}
function confirmNo() {
  document.getElementById('confirmOverlay')?.classList.remove('show');
  if (_confirmResolve) { _confirmResolve(false); _confirmResolve = null; }
}
document.getElementById('confirmOk')?.addEventListener('click',     confirmYes);
document.getElementById('confirmCancel')?.addEventListener('click', confirmNo);
document.getElementById('confirmOverlay')?.addEventListener('click', e => {
  if (e.target === document.getElementById('confirmOverlay')) confirmNo();
});

/* Wire delete buttons to in-app confirm */
document.addEventListener('click', async e => {
  const delBtn = e.target.closest('.msg-delete-btn, .mact-delete, .mmc-btn-del');
  if (!delBtn) return;
  e.preventDefault();
  const href = delBtn.getAttribute('href');
  if (!href) return;
  const yes = await adminConfirm('Delete Message', 'This message will be permanently deleted. This cannot be undone.', false);
  if (yes) window.location.href = href;
});

/* Stat counter animation */
document.querySelectorAll('[data-count]').forEach(el => {
  const target = parseInt(el.dataset.count);
  const dur    = 1800;
  const step   = 16;
  const inc    = target / (dur / step);
  let cur = 0;
  const prefix = el.dataset.prefix || '';
  const suffix = el.dataset.suffix || '';
  const t = setInterval(() => {
    cur += inc; if (cur >= target) { cur = target; clearInterval(t); }
    el.textContent = prefix + Math.floor(cur) + suffix;
  }, step);
});

/* Real-time unread chat badge on topbar */
(function pollUnreadChat() {
  const chatBtn = document.querySelector('.topbar-icon-btn[href="users.php"]');
  if (!chatBtn) return;
  setInterval(async () => {
    try {
      const res  = await fetch('ajax/unread_chat.php');
      const data = await res.json();
      let badge = chatBtn.querySelector('.topbar-badge');
      if (data.count > 0) {
        if (!badge) { badge = document.createElement('span'); badge.className='topbar-badge'; badge.style.background='#22c55e'; chatBtn.appendChild(badge); }
        badge.textContent = data.count;
      } else {
        if (badge) badge.remove();
      }
    } catch {}
  }, 6000);
})();

/* Gallery admin: confirm before delete */
document.querySelectorAll('.gallery-delete-btn').forEach(btn => {
  btn.addEventListener('click', async e => {
    e.preventDefault();
    const href = btn.getAttribute('href');
    const yes  = await adminConfirm('Delete Image', 'This gallery image will be permanently removed.', false);
    if (yes) window.location.href = href;
  });
});

/* Booking status color helper */
function statusColor(status) {
  const map = { pending:'#f59e0b', confirmed:'#22c55e', cancelled:'#ef4444', completed:'#8b5cf6' };
  return map[status] || '#888';
}
