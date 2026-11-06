/* ═══════════════════════════════════════════════════════════════════
   WINGS CUT — script.js  (fully fixed)
   Fixes: newline regex in chat, login/signup AJAX, auth theme, all UI
   ═══════════════════════════════════════════════════════════════════ */

'use strict';

/* ──────────────────────────────────────────────────────────────────
   1. PAGE LOAD REVEAL
   ────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  document.body.style.opacity = '0';
  document.body.style.transition = 'opacity 0.55s ease';
  requestAnimationFrame(() => { document.body.style.opacity = '1'; });

  initNavbar();
  initHamburger();
  initRevealAnimations();
  initGallery();
  initLightbox();
  initBookingForm();
  initContactForm();
  initCounters();
  initTestimonialStrip();
  initBackToTop();
  initRippleButtons();
  initCustomCursor();
  initPolyCanvas();
  initChatWidget();
  setMinBookingDate();
});

/* ──────────────────────────────────────────────────────────────────
   2. NAVBAR — scroll effect + active link
   ────────────────────────────────────────────────────────────────── */
function initNavbar() {
  const navbar   = document.getElementById('navbar');
  const navLinks = document.querySelectorAll('.nav-link');
  const sections = document.querySelectorAll('section[id]');
  if (!navbar) return;

  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 60);

    let current = '';
    sections.forEach(sec => {
      if (window.scrollY >= sec.offsetTop - 120) current = sec.id;
    });
    navLinks.forEach(link => {
      link.classList.toggle('active', link.getAttribute('href') === '#' + current);
    });
  }, { passive: true });
}

/* ──────────────────────────────────────────────────────────────────
   3. HAMBURGER
   ────────────────────────────────────────────────────────────────── */
function initHamburger() {
  const btn   = document.getElementById('hamburger');
  const links = document.getElementById('navLinks');
  if (!btn || !links) return;

  btn.addEventListener('click', () => {
    btn.classList.toggle('open');
    links.classList.toggle('open');
  });
  links.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      btn.classList.remove('open');
      links.classList.remove('open');
    });
  });
}

/* ──────────────────────────────────────────────────────────────────
   4. SCROLL REVEAL
   ────────────────────────────────────────────────────────────────── */
function initRevealAnimations() {
  const els = document.querySelectorAll('.reveal-up, .reveal-left, .reveal-right');
  if (!els.length) return;

  const obs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const delay = parseInt(entry.target.dataset.delay || 0);
      setTimeout(() => entry.target.classList.add('revealed'), delay);
      obs.unobserve(entry.target);
    });
  }, { threshold: 0.1 });

  els.forEach(el => obs.observe(el));
}

/* ──────────────────────────────────────────────────────────────────
   5. GALLERY FILTER + LAZY LOAD
   ────────────────────────────────────────────────────────────────── */
function initGallery() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const grid       = document.getElementById('masonryGrid');
  const loadMore   = document.getElementById('loadMoreBtn');
  if (!grid) return;

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.dataset.filter;
      grid.querySelectorAll('.gallery-item').forEach(item => {
        const show = filter === 'all' || item.dataset.category === filter;
        item.style.display  = show ? '' : 'none';
        if (show) item.style.animation = 'fadeInUp 0.4s ease forwards';
      });
    });
  });

  // Lazy load
  const lazyImgs = grid.querySelectorAll('img[data-src]');
  if ('IntersectionObserver' in window) {
    const imgObs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (!e.isIntersecting) return;
        const img = e.target;
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
        imgObs.unobserve(img);
      });
    });
    lazyImgs.forEach(img => imgObs.observe(img));
  } else {
    lazyImgs.forEach(img => { img.src = img.dataset.src; });
  }

  if (loadMore) {
    loadMore.addEventListener('click', () => {
      loadMore.innerHTML = '<span>Loading&#8230;</span> <i class="fas fa-spinner fa-spin"></i>';
      setTimeout(() => {
        loadMore.innerHTML = '<span>All Loaded</span> <i class="fas fa-check"></i>';
        loadMore.disabled  = true;
        loadMore.style.opacity = '0.5';
      }, 900);
    });
  }
}

/* ──────────────────────────────────────────────────────────────────
   6. LIGHTBOX
   ────────────────────────────────────────────────────────────────── */
function initLightbox() {
  const lightbox  = document.getElementById('lightbox');
  if (!lightbox) return;

  const lbImg     = document.getElementById('lightboxImg');
  const lbLabel   = document.getElementById('lightboxLabel');
  const lbCat     = document.getElementById('lightboxCat');
  const lbPH      = document.getElementById('lightboxPlaceholder');
  const backdrop  = lightbox.querySelector('.lightbox-backdrop');
  const closeBtn  = lightbox.querySelector('.lightbox-close');
  const prevBtn   = lightbox.querySelector('.lightbox-prev');
  const nextBtn   = lightbox.querySelector('.lightbox-next');

  let items   = [];
  let current = 0;

  function openLightbox(idx) {
    items   = Array.from(document.querySelectorAll('.gallery-item:not([style*="display: none"])'));
    current = idx;
    showItem(current);
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
    if (lbImg) { lbImg.src = ''; lbImg.style.display = 'none'; }
    if (lbPH)  lbPH.style.display = 'flex';
  }

  function showItem(idx) {
    const item  = items[idx];
    if (!item) return;
    const img   = item.querySelector('img');
    const label = (item.querySelector('.gallery-zoom') || {}).dataset
      ? item.querySelector('.gallery-zoom').dataset.label
      : 'Image';
    const cat   = item.dataset.category || '';

    if (lbLabel) lbLabel.textContent = label || 'Image';
    if (lbCat)   lbCat.textContent   = cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : '';

    const hasSrc = img && img.src && !img.src.includes('data:image/gif');
    if (lbImg) { lbImg.src = hasSrc ? img.src : ''; lbImg.style.display = hasSrc ? 'block' : 'none'; lbImg.alt = label || ''; }
    if (lbPH)  lbPH.style.display = hasSrc ? 'none' : 'flex';
  }

  document.addEventListener('click', e => {
    const zoomBtn = e.target.closest('.gallery-zoom');
    if (!zoomBtn) return;
    const item = zoomBtn.closest('.gallery-item');
    if (!item) return;
    const allVisible = Array.from(document.querySelectorAll('.gallery-item:not([style*="display: none"])'));
    openLightbox(allVisible.indexOf(item));
  });

  if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
  if (backdrop) backdrop.addEventListener('click', closeLightbox);
  if (prevBtn)  prevBtn.addEventListener('click', () => { current = (current - 1 + items.length) % items.length; showItem(current); });
  if (nextBtn)  nextBtn.addEventListener('click', () => { current = (current + 1) % items.length; showItem(current); });

  document.addEventListener('keydown', e => {
    if (!lightbox.classList.contains('active')) return;
    if (e.key === 'Escape')     closeLightbox();
    if (e.key === 'ArrowLeft')  { current = (current - 1 + items.length) % items.length; showItem(current); }
    if (e.key === 'ArrowRight') { current = (current + 1) % items.length; showItem(current); }
  });
}

/* ──────────────────────────────────────────────────────────────────
   7. BOOKING FORM — AJAX submit
   ────────────────────────────────────────────────────────────────── */
function setMinBookingDate() {
  const dateEl = document.getElementById('date');
  if (!dateEl) return;
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const yyyy = tomorrow.getFullYear();
  const mm   = String(tomorrow.getMonth() + 1).padStart(2, '0');
  const dd   = String(tomorrow.getDate()).padStart(2, '0');
  dateEl.min = `${yyyy}-${mm}-${dd}`;
}

function initBookingForm() {
  const form = document.getElementById('bookingForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateBooking()) return;

    const btn  = form.querySelector('.btn-book');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span>Submitting&#8230;</span> <i class="fas fa-spinner fa-spin"></i>';
    btn.disabled  = true;

    try {
      const fd    = new FormData(form);
      const code  = (document.getElementById('countryCode') || {}).value || '+91';
      const phone = (document.getElementById('phone') || {}).value || '';
      fd.set('phone', code + phone);

      const res  = await fetch('php/booking.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        form.style.display = 'none';
        const successMsg  = document.getElementById('successMsg');
        const successName = document.getElementById('successName');
        const successId   = document.getElementById('successId');
        if (successMsg)  successMsg.style.display = 'block';
        if (successName) successName.textContent  = 'Thank you, ' + (fd.get('name') || '') + '!';
        if (successId)   successId.textContent    = data.booking_id ? 'Booking ID: ' + data.booking_id : '';
      } else {
        showToast(data.error || 'Booking failed. Please try again.', 'error');
        btn.innerHTML = orig;
        btn.disabled  = false;
      }
    } catch {
      showToast('Network error. Please try again.', 'error');
      btn.innerHTML = orig;
      btn.disabled  = false;
    }
  });
}

function validateBooking() {
  let ok = true;
  const rules = [
    { id: 'name',      errId: 'nameError',    check: v => v.trim().length >= 2,                      msg: 'Please enter your full name.' },
    { id: 'email',     errId: 'emailError',   check: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),      msg: 'Enter a valid email.' },
    { id: 'phone',     errId: 'phoneError',   check: v => /^\d{10}$/.test(v),                        msg: 'Must be exactly 10 digits.' },
    { id: 'package',   errId: 'packageError', check: v => v !== '',                                   msg: 'Please select a package.' },
    { id: 'date',      errId: 'dateError',    check: v => v !== '',                                   msg: 'Please choose a date.' },
    { id: 'time_slot', errId: 'slotError',    check: v => v !== '',                                   msg: 'Please select a session time.' },
  ];
  rules.forEach(r => {
    const el  = document.getElementById(r.id);
    const err = document.getElementById(r.errId);
    if (!el) return;
    if (!r.check(el.value)) {
      if (err) err.textContent = r.msg;
      el.style.borderColor = '#dc2626';
      ok = false;
    } else {
      if (err) err.textContent = '';
      el.style.borderColor = '';
    }
  });
  return ok;
}

function resetBookingForm() {
  const form       = document.getElementById('bookingForm');
  const successMsg = document.getElementById('successMsg');
  if (form)       { form.reset(); form.style.display = ''; }
  if (successMsg) successMsg.style.display = 'none';
}

/* ──────────────────────────────────────────────────────────────────
   8. CONTACT FORM — AJAX
   ────────────────────────────────────────────────────────────────── */
function initContactForm() {
  const form = document.getElementById('contactForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn  = form.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span>Sending&#8230;</span> <i class="fas fa-spinner fa-spin"></i>';
    btn.disabled  = true;

    try {
      const fd   = new FormData(form);
      const res  = await fetch('php/contact.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        form.reset();
        showToast("Message sent! We'll reply within 24 hours.", 'success');
      } else {
        showToast(data.error || 'Failed to send. Please try again.', 'error');
      }
    } catch {
      showToast('Network error. Please try again.', 'error');
    }
    btn.innerHTML = orig;
    btn.disabled  = false;
  });
}

/* ──────────────────────────────────────────────────────────────────
   9. AUTH — Login / Signup  (FIXED — proper AJAX to php/auth.php)
   ────────────────────────────────────────────────────────────────── */

function switchTab(tab) {
  const loginTab  = document.getElementById('loginTab');
  const signupTab = document.getElementById('signupTab');
  const tabLogin  = document.getElementById('tabLogin');
  const tabSignup = document.getElementById('tabSignup');
  const indicator = document.getElementById('authTabIndicator');
  if (!loginTab || !signupTab) return;

  const isLogin = tab === 'login';
  loginTab.style.display   = isLogin ? '' : 'none';
  signupTab.style.display  = isLogin ? 'none' : '';
  tabLogin  && tabLogin.classList.toggle('active', isLogin);
  tabSignup && tabSignup.classList.toggle('active', !isLogin);
  if (indicator) indicator.style.transform = isLogin ? 'translateX(0)' : 'translateX(100%)';

  ['loginError', 'signupError'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = '';
  });
}

function openAuth(tab) {
  const gate  = document.getElementById('authGate');
  const modal = document.getElementById('authModal');

  if (gate && gate.style.display !== 'none') {
    switchTab(tab || 'login');
    return;
  }
  if (modal) {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
}

function closeAuth() {
  const modal = document.getElementById('authModal');
  if (modal) {
    modal.style.display  = 'none';
    document.body.style.overflow = '';
  }
}

function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  const icon = btn.querySelector('i');
  if (icon) icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
}

/* LOGIN (auth gate) */
async function doLogin() {
  const emailEl = document.getElementById('loginEmail');
  const passEl  = document.getElementById('loginPass');
  const errEl   = document.getElementById('loginError');
  const btn     = document.getElementById('loginBtn');
  if (!emailEl || !passEl) return;

  const email    = emailEl.value.trim();
  const password = passEl.value;

  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    if (errEl) errEl.textContent = 'Please enter a valid email address.';
    emailEl.focus(); return;
  }
  if (!password || password.length < 6) {
    if (errEl) errEl.textContent = 'Password must be at least 6 characters.';
    passEl.focus(); return;
  }
  if (errEl) errEl.textContent = '';

  const origHTML = btn ? btn.innerHTML : '';
  if (btn) { btn.innerHTML = '<span>Signing in&#8230;</span> <i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true; }

  try {
    const fd = new FormData();
    fd.append('action',   'login');
    fd.append('email',    email);
    fd.append('password', password);

    const res  = await fetch('php/auth.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      window.location.reload();
    } else {
      if (errEl) errEl.textContent = data.error || 'Invalid email or password.';
      if (btn)  { btn.innerHTML = origHTML; btn.disabled = false; }
    }
  } catch {
    if (errEl) errEl.textContent = 'Network error. Please try again.';
    if (btn)  { btn.innerHTML = origHTML; btn.disabled = false; }
  }
}

/* SIGNUP (auth gate) */
async function doSignup() {
  const nameEl  = document.getElementById('signupName');
  const emailEl = document.getElementById('signupEmail');
  const passEl  = document.getElementById('signupPass');
  const errEl   = document.getElementById('signupError');
  const btn     = document.getElementById('signupBtn');
  if (!nameEl || !emailEl || !passEl) return;

  const name     = nameEl.value.trim();
  const email    = emailEl.value.trim();
  const password = passEl.value;

  if (!name || name.length < 2) {
    if (errEl) errEl.textContent = 'Please enter your full name.';
    nameEl.focus(); return;
  }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    if (errEl) errEl.textContent = 'Please enter a valid email address.';
    emailEl.focus(); return;
  }
  if (!password || password.length < 6) {
    if (errEl) errEl.textContent = 'Password must be at least 6 characters.';
    passEl.focus(); return;
  }
  if (errEl) errEl.textContent = '';

  const origHTML = btn ? btn.innerHTML : '';
  if (btn) { btn.innerHTML = '<span>Creating account&#8230;</span> <i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true; }

  try {
    const fd = new FormData();
    fd.append('action',   'signup');
    fd.append('name',     name);
    fd.append('email',    email);
    fd.append('password', password);

    const res  = await fetch('php/auth.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      window.location.reload();
    } else {
      if (errEl) errEl.textContent = data.error || 'Signup failed. Please try again.';
      if (btn)  { btn.innerHTML = origHTML; btn.disabled = false; }
    }
  } catch {
    if (errEl) errEl.textContent = 'Network error. Please try again.';
    if (btn)  { btn.innerHTML = origHTML; btn.disabled = false; }
  }
}

/* LOGIN (modal) */
async function doLogin2() {
  const emailEl = document.getElementById('loginEmail2');
  const passEl  = document.getElementById('loginPass2');
  const errEl   = document.getElementById('loginError2');
  if (!emailEl || !passEl) return;

  if (!emailEl.value.trim() || !passEl.value) {
    if (errEl) errEl.textContent = 'Please fill in both fields.';
    return;
  }
  if (errEl) errEl.textContent = '';

  try {
    const fd = new FormData();
    fd.append('action',   'login');
    fd.append('email',    emailEl.value.trim());
    fd.append('password', passEl.value);

    const res  = await fetch('php/auth.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      window.location.reload();
    } else {
      if (errEl) errEl.textContent = data.error || 'Invalid credentials.';
    }
  } catch {
    if (errEl) errEl.textContent = 'Network error.';
  }
}

/* LOGOUT */
async function logoutUser() {
  try {
    const fd = new FormData();
    fd.append('action', 'logout');
    await fetch('php/auth.php', { method: 'POST', body: fd });
  } catch {}
  window.location.reload();
}

/* User dropdown */
function toggleUserMenu() {
  document.getElementById('userDropdown')?.classList.toggle('open');
}
document.addEventListener('click', e => {
  const dd  = document.getElementById('userDropdown');
  const btn = document.getElementById('userMenuBtn');
  if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
    dd.classList.remove('open');
  }
});

/* ──────────────────────────────────────────────────────────────────
   10. CHAT WIDGET  (FIXED — newline regex)
   ────────────────────────────────────────────────────────────────── */
const CHAT_POLL_MS = 5000;
let chatOpen      = false;
let chatPollTimer = null;
let lastMsgId     = 0;

/* ✅ FIX: declare newline regex as a RegExp object — avoids
   the "literal newline inside regex" syntax error that occurred
   when the file was saved with a real \n inside the regex literal */
const _NL_REGEX = new RegExp('\\n', 'g');

function initChatWidget() {
  if (!document.getElementById('chatWidget')) return;
  loadChatHistory();
  startChatPoll();
}

function toggleChat() {
  const win   = document.getElementById('chatWindow');
  const badge = document.getElementById('chatBadge');
  if (!win) return;
  chatOpen = !chatOpen;
  win.classList.toggle('open', chatOpen);
  if (chatOpen) {
    if (badge) badge.style.display = 'none';
    scrollChatBottom();
    document.getElementById('chatInput')?.focus();
  }
}

function openChat() { if (!chatOpen) toggleChat(); }

async function sendChatMsg() {
  const input = document.getElementById('chatInput');
  if (!input) return;
  const text = input.value.trim();
  if (!text) return;

  input.value = '';
  input.style.height = 'auto';

  appendChatMsg({ message: text, sender: 'user', created_at: new Date().toISOString() }, true);
  scrollChatBottom();

  try {
    const fd = new FormData();
    fd.append('action',  'send');
    fd.append('message', text);
    const res  = await fetch('php/chat.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.id) lastMsgId = Math.max(lastMsgId, data.id);
  } catch {}
}

async function loadChatHistory() {
  try {
    const res  = await fetch('php/chat.php?action=history');
    const data = await res.json();
    if (!Array.isArray(data.messages)) return;
    data.messages.forEach(m => {
      appendChatMsg(m, false);
      if (m.id) lastMsgId = Math.max(lastMsgId, m.id);
    });
    scrollChatBottom();
  } catch {}
}

function startChatPoll() {
  clearInterval(chatPollTimer);
  chatPollTimer = setInterval(async () => {
    try {
      const res  = await fetch('php/chat.php?action=poll&since=' + lastMsgId);
      const data = await res.json();
      if (!Array.isArray(data.messages) || !data.messages.length) return;

      data.messages.forEach(m => {
        appendChatMsg(m, false);
        if (m.id) lastMsgId = Math.max(lastMsgId, m.id);
      });

      if (!chatOpen) {
        const badge = document.getElementById('chatBadge');
        if (badge) {
          const newAdminMsgs = data.messages.filter(m => m.sender === 'admin').length;
          if (newAdminMsgs > 0) {
            const prev = parseInt(badge.textContent || '0');
            const next = prev + newAdminMsgs;
            badge.textContent   = next > 9 ? '9+' : next;
            badge.style.display = 'flex';
          }
        }
      }
      if (chatOpen) scrollChatBottom();
    } catch {}
  }, CHAT_POLL_MS);
}

function appendChatMsg(m, isOptimistic) {
  const container = document.getElementById('chatWinMessages');
  if (!container) return;
  if (!isOptimistic && m.id && container.querySelector('[data-msg-id="' + m.id + '"]')) return;

  const isUser = m.sender === 'user';
  const row    = document.createElement('div');
  row.className = 'chat-msg-row ' + (isUser ? 'user' : 'admin');
  if (m.id) row.dataset.msgId = m.id;

  const time = m.created_at
    ? new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    : '';

  /* ✅ FIX: use the pre-declared RegExp variable — no literal newline in source */
  const safeText = String(m.message || '').replace(_NL_REGEX, '<br>');

  if (!isUser) {
    row.innerHTML =
      '<div class="chat-msg-av"><i class="fas fa-camera-retro"></i></div>' +
      '<div class="chat-msg-bubble admin">' + safeText +
      '<span class="chat-msg-time">' + time + '</span></div>';
  } else {
    row.innerHTML =
      '<div class="chat-msg-bubble user">' + safeText +
      '<span class="chat-msg-time">' + time + '</span></div>';
  }

  container.appendChild(row);
}

function scrollChatBottom() {
  const c = document.getElementById('chatWinMessages');
  if (c) c.scrollTop = c.scrollHeight;
}

document.addEventListener('keydown', e => {
  if (e.target.id !== 'chatInput') return;
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChatMsg(); }
});

/* ──────────────────────────────────────────────────────────────────
   11. ANIMATED COUNTERS
   ────────────────────────────────────────────────────────────────── */
function initCounters() {
  const counters = document.querySelectorAll('.stat-num[data-count]');
  if (!counters.length) return;

  const obs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el     = entry.target;
      const target = parseInt(el.dataset.count);
      const dur    = 1800;
      const step   = 16;
      const inc    = target / (dur / step);
      let cur      = 0;
      const timer  = setInterval(() => {
        cur += inc;
        if (cur >= target) { cur = target; clearInterval(timer); }
        el.textContent = Math.floor(cur);
      }, step);
      obs.unobserve(el);
    });
  }, { threshold: 0.5 });

  counters.forEach(c => obs.observe(c));
}

/* ──────────────────────────────────────────────────────────────────
   12. TESTIMONIAL AUTO-SCROLL
   ────────────────────────────────────────────────────────────────── */
function initTestimonialStrip() {
  const track = document.getElementById('testiTrack');
  if (!track) return;

  let pos = 0, speed = 0.5;

  (function scroll() {
    pos += speed;
    if (pos >= track.scrollWidth / 2) pos = 0;
    track.style.transform = 'translateX(-' + pos + 'px)';
    requestAnimationFrame(scroll);
  })();

  track.addEventListener('mouseenter', () => { speed = 0; });
  track.addEventListener('mouseleave', () => { speed = 0.5; });
}

/* ──────────────────────────────────────────────────────────────────
   13. BACK TO TOP
   ────────────────────────────────────────────────────────────────── */
function initBackToTop() {
  const btn = document.getElementById('backToTop');
  if (!btn) return;
  window.addEventListener('scroll', () => {
    btn.classList.toggle('visible', window.scrollY > 400);
  }, { passive: true });
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

/* ──────────────────────────────────────────────────────────────────
   14. RIPPLE BUTTONS
   ────────────────────────────────────────────────────────────────── */
function initRippleButtons() {
  document.querySelectorAll('.ripple').forEach(btn => {
    btn.addEventListener('click', function (e) {
      const rect   = this.getBoundingClientRect();
      const ripple = document.createElement('span');
      const size   = Math.max(rect.width, rect.height);
      ripple.style.cssText =
        'position:absolute;width:' + size + 'px;height:' + size + 'px;' +
        'left:' + (e.clientX - rect.left - size / 2) + 'px;' +
        'top:'  + (e.clientY - rect.top  - size / 2) + 'px;' +
        'background:rgba(255,255,255,0.25);border-radius:50%;' +
        'transform:scale(0);animation:rippleAnim 0.6s linear;pointer-events:none;';
      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 650);
    });
  });
}

/* ──────────────────────────────────────────────────────────────────
   15. CUSTOM CURSOR
   ────────────────────────────────────────────────────────────────── */
function initCustomCursor() {
  const cursor = document.getElementById('customCursor');
  const ring   = document.getElementById('customCursorRing');
  if (!cursor || !ring) return;
  if (window.matchMedia('(pointer: coarse)').matches) return;

  let mx = 0, my = 0, rx = 0, ry = 0;

  document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    cursor.style.transform = 'translate(' + mx + 'px,' + my + 'px)';
    // Show cursor on first move
    if (cursor.style.opacity !== '1') {
      cursor.style.opacity = '1';
      ring.style.opacity   = '1';
    }
  });

  (function animRing() {
    rx += (mx - rx) * 0.15;
    ry += (my - ry) * 0.15;
    ring.style.transform = 'translate(' + rx + 'px,' + ry + 'px)';
    requestAnimationFrame(animRing);
  })();

  document.querySelectorAll('a, button, .gallery-item, .filter-btn, .service-card').forEach(el => {
    el.addEventListener('mouseenter', () => { cursor.classList.add('hovered'); ring.classList.add('hovered'); });
    el.addEventListener('mouseleave', () => { cursor.classList.remove('hovered'); ring.classList.remove('hovered'); });
  });

  document.addEventListener('mouseleave', () => { cursor.style.opacity = '0'; ring.style.opacity = '0'; });
  document.addEventListener('mouseenter', () => { cursor.style.opacity = '1'; ring.style.opacity = '1'; });
}

/* ──────────────────────────────────────────────────────────────────
   16. POLYGON CANVAS BACKGROUND
   ────────────────────────────────────────────────────────────────── */
function initPolyCanvas() {
  const canvas = document.getElementById('polyCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let W, H, points;

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
    points = Array.from({ length: Math.floor((W * H) / 18000) }, () => ({
      x:  Math.random() * W, y:  Math.random() * H,
      vx: (Math.random() - 0.5) * 0.3,
      vy: (Math.random() - 0.5) * 0.3,
    }));
  }

  (function draw() {
    ctx.clearRect(0, 0, W, H);
    points.forEach(p => {
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0 || p.x > W) p.vx *= -1;
      if (p.y < 0 || p.y > H) p.vy *= -1;
    });
    const max = 120;
    for (let i = 0; i < points.length; i++) {
      for (let j = i + 1; j < points.length; j++) {
        const dx = points[i].x - points[j].x;
        const dy = points[i].y - points[j].y;
        const d  = Math.sqrt(dx * dx + dy * dy);
        if (d < max) {
          ctx.strokeStyle = 'rgba(139,0,0,' + ((1 - d / max) * 0.12) + ')';
          ctx.lineWidth   = 0.8;
          ctx.beginPath();
          ctx.moveTo(points[i].x, points[i].y);
          ctx.lineTo(points[j].x, points[j].y);
          ctx.stroke();
        }
      }
      ctx.beginPath();
      ctx.arc(points[i].x, points[i].y, 1.5, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(139,0,0,0.18)';
      ctx.fill();
    }
    requestAnimationFrame(draw);
  })();

  window.addEventListener('resize', resize, { passive: true });
  resize();
}

/* ──────────────────────────────────────────────────────────────────
   17. TOAST NOTIFICATIONS
   ────────────────────────────────────────────────────────────────── */
function showToast(msg, type) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = msg;
  toast.className   = 'toast ' + (type || 'success') + ' show';
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove('show'), 4000);
}

/* ──────────────────────────────────────────────────────────────────
   18. FALLBACK: checkSlotAvailability
   ────────────────────────────────────────────────────────────────── */
if (typeof checkSlotAvailability === 'undefined') {
  window.checkSlotAvailability = function () {};
}