/* script.js for the Wings Cut site.
   Handles the navbar, gallery and lightbox, the booking and contact forms,
   login and signup, the chat widget, and the user dashboard (payments too). */
'use strict';

/* Init */
document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initHeroSlides();
  initHamburger();
  initReveal();
  initGallery();
  initLightbox();
  initBookingForm();
  initContactForm();
  initCounters();
  initTestiTrack();
  initBackToTop();
  initRipple();
  initCursor();
  initPolyCanvas();
  initChatWidget();
  setMinDate();
});

/* NAVBAR scroll effect */
function initNavbar() {
  const nav   = document.getElementById('navbar');
  const links = document.querySelectorAll('.nav-link');
  const sects = document.querySelectorAll('section[id]');
  if (!nav) return;
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
    let cur = '';
    sects.forEach(s => { if (window.scrollY >= s.offsetTop - 130) cur = s.id; });
    links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + cur));
  }, { passive: true });
}

/* Hamburger - LEFT drawer, closes on overlay/link/ESC */
function initHamburger() {
  const btn     = document.getElementById('hamburger');
  const menu    = document.getElementById('navLinks');
  const overlay = document.getElementById('navOverlay');
  if (!btn || !menu) return;

  function openMenu() {
    btn.classList.add('open');
    menu.classList.add('open');
    if (overlay) overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    btn.classList.remove('open');
    menu.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  btn.addEventListener('click', () =>
    btn.classList.contains('open') ? closeMenu() : openMenu()
  );
  if (overlay) overlay.addEventListener('click', closeMenu);
  menu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
}

/* Scroll reveal */
function initReveal() {
  const els = document.querySelectorAll('.reveal-up,.reveal-left,.reveal-right');
  if (!els.length) return;
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const d = parseInt(e.target.dataset.delay || 0);
      setTimeout(() => e.target.classList.add('revealed'), d);
      obs.unobserve(e.target);
    });
  }, { threshold: 0.1 });
  els.forEach(el => obs.observe(el));
}

/* GALLERY filter + lightbox integration */
function initGallery() {
  const btns = document.querySelectorAll('.filter-btn');
  const grid = document.getElementById('masonryGrid');
  const lmBtn= document.getElementById('loadMoreBtn');
  if (!grid) return;

  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      btns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const f = btn.dataset.filter;
      grid.querySelectorAll('.gallery-item').forEach((item, i) => {
        const show = f === 'all' || item.dataset.category === f;
        item.style.display = show ? '' : 'none';
        if (show) {
          item.style.animation = 'none';
          item.offsetHeight; // reflow
          item.style.animation = '';
          item.style.opacity   = '0';
          item.style.transform = 'translateY(20px)';
          setTimeout(() => {
            item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            item.style.opacity    = '1';
            item.style.transform  = 'translateY(0)';
          }, i * 40);
        }
      });
    });
  });

  if (lmBtn) {
    lmBtn.addEventListener('click', () => {
      lmBtn.innerHTML = '<span>All Loaded</span> <i class="fas fa-check"></i>';
      lmBtn.disabled  = true;
      lmBtn.style.opacity = '0.5';
    });
  }
}

/* Lightbox */
function initLightbox() {
  const lb  = document.getElementById('lightbox');
  if (!lb) return;
  const lbImg    = document.getElementById('lightboxImg');
  const lbPH     = document.getElementById('lightboxPlaceholder');
  const lbCat    = document.getElementById('lightboxCat');
  const lbLabel  = document.getElementById('lightboxLabel');
  const backdrop = lb.querySelector('.lightbox-backdrop');
  const closeBtn = lb.querySelector('.lightbox-close');
  const prevBtn  = lb.querySelector('.lightbox-prev');
  const nextBtn  = lb.querySelector('.lightbox-next');
  let items = [], cur = 0;

  function getVisible() {
    return Array.from(document.querySelectorAll('.gallery-item')).filter(el => el.style.display !== 'none');
  }
  function show(i) {
    cur = ((i % items.length) + items.length) % items.length;
    const item = items[cur]; if (!item) return;
    const img  = item.querySelector('img');
    const zoom = item.querySelector('.gallery-zoom');
    const label= zoom ? (zoom.dataset.label || '') : '';
    const cat  = item.dataset.category || '';
    if (lbLabel) lbLabel.textContent = label;
    if (lbCat)   lbCat.textContent   = cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : '';
    const src = img ? img.src : '';
    if (lbImg) { lbImg.src = src; lbImg.style.display = src ? 'block' : 'none'; }
    if (lbPH)  lbPH.style.display = src ? 'none' : 'block';
  }
  function open(idx) {
    items = getVisible(); cur = Math.max(0, Math.min(idx, items.length-1));
    show(cur); lb.classList.add('active'); document.body.style.overflow = 'hidden';
  }
  function close() {
    lb.classList.remove('active'); document.body.style.overflow = '';
    if (lbImg) { lbImg.src = ''; lbImg.style.display = 'none'; }
    if (lbPH)  lbPH.style.display = 'block';
  }

  document.addEventListener('click', e => {
    const z = e.target.closest('.gallery-zoom');
    if (!z) return;
    const item = z.closest('.gallery-item');
    if (!item) return;
    const all = getVisible();
    open(all.indexOf(item));
  });

  if (backdrop) backdrop.addEventListener('click', close);
  if (closeBtn) closeBtn.addEventListener('click', close);
  if (prevBtn)  prevBtn.addEventListener('click',  () => show(cur - 1));
  if (nextBtn)  nextBtn.addEventListener('click',  () => show(cur + 1));
  document.addEventListener('keydown', e => {
    if (!lb.classList.contains('active')) return;
    if (e.key === 'Escape')     close();
    if (e.key === 'ArrowLeft')  show(cur - 1);
    if (e.key === 'ArrowRight') show(cur + 1);
  });
}

/* Booking form */
function setMinDate() {
  const el = document.getElementById('date'); if (!el) return;
  const t = new Date(); t.setDate(t.getDate() + 1);
  el.min = t.toISOString().split('T')[0];
}

function initBookingForm() {
  const form = document.getElementById('bookingForm'); if (!form) return;
  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateBooking()) return;
    const btn  = form.querySelector('.btn-book');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span>Submitting…</span> <i class="fas fa-spinner fa-spin"></i>';
    btn.disabled  = true;
    try {
      const fd  = new FormData(form);
      const code= (document.getElementById('countryCode') || {}).value || '+91';
      fd.set('phone', code + (document.getElementById('phone') || {}).value);
      const res  = await fetch('php/booking.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        form.style.display = 'none';
        const sm = document.getElementById('successMsg');
        const sn = document.getElementById('successName');
        const si = document.getElementById('successId');
        if (sm) sm.style.display = 'block';
        if (sn) sn.textContent = 'Thank you, ' + (fd.get('name') || '') + '!';
        if (si) si.textContent = data.booking_id ? 'Booking ID: #' + data.booking_id : '';
      } else {
        showToast(data.error || 'Booking failed. Please try again.', 'error');
        btn.innerHTML = orig; btn.disabled = false;
      }
    } catch {
      showToast('Network error. Please try again.', 'error');
      btn.innerHTML = orig; btn.disabled = false;
    }
  });
}

function validateBooking() {
  let ok = true;
  [
    { id:'name',      err:'nameError',    chk: v=>v.trim().length>=2,                     msg:'Enter your full name.' },
    { id:'email',     err:'emailError',   chk: v=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),  msg:'Enter a valid email.' },
    { id:'phone',     err:'phoneError',   chk: v=>/^\d{6,15}$/.test(v.replace(/\D/g,'')),msg:'Enter a valid phone number.' },
    { id:'package',   err:'packageError', chk: v=>v!=='',                                 msg:'Select a package.' },
    { id:'date',      err:'dateError',    chk: v=>v!=='',                                 msg:'Choose a date.' },
    { id:'time_slot', err:'slotError',    chk: v=>v!=='',                                 msg:'Select a session time.' },
  ].forEach(r => {
    const el  = document.getElementById(r.id);
    const err = document.getElementById(r.err);
    if (!el) return;
    const pass = r.chk(el.value);
    if (err) err.textContent = pass ? '' : r.msg;
    el.style.borderColor = pass ? '' : '#dc2626';
    if (!pass) ok = false;
  });
  return ok;
}

function resetBookingForm() {
  const form = document.getElementById('bookingForm');
  const sm   = document.getElementById('successMsg');
  if (form) { form.reset(); form.style.display = ''; }
  if (sm)   sm.style.display = 'none';
}

function checkSlotAvailability() {
  const d  = document.getElementById('date');
  const s  = document.getElementById('time_slot');
  const st = document.getElementById('slotStatus');
  const se = document.getElementById('slotError');
  if (!d || !s || !st) return;
  if (!d.value || !s.value) { st.textContent = ''; return; }
  st.textContent = 'Checking availability…'; st.style.color = '#888';
  fetch('php/check_slot.php?date=' + encodeURIComponent(d.value) + '&slot=' + encodeURIComponent(s.value))
    .then(r => r.json()).then(data => {
      if (data.available) {
        st.textContent = '✓ ' + data.remaining + ' slot' + (data.remaining > 1 ? 's' : '') + ' available';
        st.style.color = '#16a34a';
        if (se) se.textContent = ''; s.style.borderColor = '';
      } else {
        st.textContent = '✗ Fully booked — try another date or time.';
        st.style.color = '#dc2626';
        if (se) se.textContent = 'Full'; s.style.borderColor = '#dc2626';
      }
    }).catch(() => { st.textContent = ''; });
}

/* Contact form */
function initContactForm() {
  const form = document.getElementById('contactForm'); if (!form) return;
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const btn  = form.querySelector('[type="submit"]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span>Sending…</span> <i class="fas fa-spinner fa-spin"></i>';
    btn.disabled  = true;
    try {
      const res  = await fetch('php/contact.php', { method: 'POST', body: new FormData(form) });
      const data = await res.json();
      if (data.success) { showToast("Message sent! We'll be in touch soon.", 'success'); form.reset(); }
      else showToast(data.error || 'Failed to send. Please try again.', 'error');
    } catch { showToast('Network error.', 'error'); }
    btn.innerHTML = orig; btn.disabled = false;
  });
}

/* Counters */
function initCounters() {
  const els = document.querySelectorAll('[data-count]');
  if (!els.length) return;
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const el = e.target, target = parseInt(el.dataset.count);
      let cur = 0;
      const inc = target / (1800 / 16);
      const t   = setInterval(() => {
        cur += inc;
        if (cur >= target) { cur = target; clearInterval(t); }
        el.textContent = Math.floor(cur);
      }, 16);
      obs.unobserve(el);
    });
  }, { threshold: 0.5 });
  els.forEach(el => obs.observe(el));
}

/* Testimonial strip */
function initTestiTrack() {
  const track = document.getElementById('testiTrack'); if (!track) return;
  let pos = 0, spd = 0.6;
  (function scroll() {
    pos += spd;
    if (pos >= track.scrollWidth / 2) pos = 0;
    track.style.transform = `translateX(-${pos}px)`;
    requestAnimationFrame(scroll);
  })();
  track.addEventListener('mouseenter', () => spd = 0);
  track.addEventListener('mouseleave', () => spd = 0.6);
}

/* Back to top */
function initBackToTop() {
  const btn = document.getElementById('backToTop'); if (!btn) return;
  window.addEventListener('scroll', () => btn.classList.toggle('visible', window.scrollY > 400), { passive: true });
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

/* Ripple */
function initRipple() {
  document.querySelectorAll('.ripple').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const r  = this.getBoundingClientRect();
      const sz = Math.max(r.width, r.height);
      const sp = document.createElement('span');
      sp.style.cssText = `position:absolute;width:${sz}px;height:${sz}px;` +
        `left:${e.clientX-r.left-sz/2}px;top:${e.clientY-r.top-sz/2}px;` +
        `background:rgba(255,255,255,0.25);border-radius:50%;transform:scale(0);` +
        `animation:rippleAnim 0.6s linear;pointer-events:none;`;
      this.appendChild(sp);
      setTimeout(() => sp.remove(), 660);
    });
  });
}

/* CURSOR - follows the mouse without lag */
function initCursor() {
  const dot  = document.getElementById('customCursor');
  const ring = document.getElementById('customCursorRing');
  if (!dot || !ring) return;
  if (window.matchMedia('(pointer:coarse)').matches) return;

  let mx=0, my=0, rx=0, ry=0, visible=false;

  // Dot: set left/top DIRECTLY in mousemove — no transition on position at all
  document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    dot.style.left = mx + 'px';
    dot.style.top  = my + 'px';
    if (!visible) {
      visible = true;
      dot.style.opacity  = '1';
      ring.style.opacity = '1';
    }
  }, { passive: true });

  // Ring: smooth lerp in rAF only
  (function lerp() {
    rx += (mx - rx) * 0.13;
    ry += (my - ry) * 0.13;
    ring.style.left = rx + 'px';
    ring.style.top  = ry + 'px';
    requestAnimationFrame(lerp);
  })();

  // Hover expand
  const SEL = 'a,button,input,select,textarea,.gallery-item,.filter-btn,.service-card,.pricing-card,.gallery-zoom,[role="button"]';
  function attach(el) {
    if (el._wc) return; el._wc = 1;
    el.addEventListener('mouseenter', () => { dot.classList.add('hov'); ring.classList.add('hov'); });
    el.addEventListener('mouseleave', () => { dot.classList.remove('hov'); ring.classList.remove('hov'); });
  }
  document.querySelectorAll(SEL).forEach(attach);
  new MutationObserver(() => document.querySelectorAll(SEL).forEach(attach))
    .observe(document.body, { childList: true, subtree: true });

  document.addEventListener('mousedown', () => dot.classList.add('click'));
  document.addEventListener('mouseup',   () => dot.classList.remove('click'));
  document.addEventListener('mouseleave', () => { dot.style.opacity='0'; ring.style.opacity='0'; visible=false; });
  document.addEventListener('mouseenter', () => { dot.style.opacity='1'; ring.style.opacity='1'; });
}

/* Poly canvas */
function initPolyCanvas() {
  const cv = document.getElementById('polyCanvas'); if (!cv) return;
  const ctx = cv.getContext('2d');
  let W, H, pts;
  function resize() {
    W = cv.width  = window.innerWidth;
    H = cv.height = window.innerHeight;
    pts = Array.from({ length: Math.floor(W*H/24000) }, () => ({
      x: Math.random()*W, y: Math.random()*H,
      vx: (Math.random()-.5)*.2, vy: (Math.random()-.5)*.2
    }));
  }
  (function draw() {
    ctx.clearRect(0,0,W,H);
    pts.forEach(p => {
      p.x+=p.vx; p.y+=p.vy;
      if(p.x<0||p.x>W) p.vx*=-1;
      if(p.y<0||p.y>H) p.vy*=-1;
    });
    for(let i=0;i<pts.length;i++) for(let j=i+1;j<pts.length;j++) {
      const dx=pts[i].x-pts[j].x, dy=pts[i].y-pts[j].y, d=Math.hypot(dx,dy);
      if(d<130){
        ctx.strokeStyle=`rgba(139,0,0,${(1-d/130)*0.07})`;
        ctx.lineWidth=0.5;
        ctx.beginPath(); ctx.moveTo(pts[i].x,pts[i].y); ctx.lineTo(pts[j].x,pts[j].y); ctx.stroke();
      }
    }
    pts.forEach(p=>{
      ctx.beginPath(); ctx.arc(p.x,p.y,1.2,0,Math.PI*2);
      ctx.fillStyle='rgba(139,0,0,0.1)'; ctx.fill();
    });
    requestAnimationFrame(draw);
  })();
  window.addEventListener('resize', resize, { passive:true }); resize();
}

/* Toast */
function showToast(msg, type='success') {
  const t = document.getElementById('toast'); if (!t) return;
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show';
  clearTimeout(t._tm);
  t._tm = setTimeout(() => t.classList.remove('show'), 4000);
}

/* Auth */
function switchTab(tab) {
  const lt=document.getElementById('loginTab'), st=document.getElementById('signupTab');
  const tl=document.getElementById('tabLogin'), ts=document.getElementById('tabSignup');
  const ind=document.getElementById('authTabIndicator');
  if(tab==='login'){
    if(lt)lt.style.display=''; if(st)st.style.display='none';
    if(tl)tl.classList.add('active'); if(ts)ts.classList.remove('active');
    if(ind)ind.classList.remove('right');
  } else {
    if(lt)lt.style.display='none'; if(st)st.style.display='';
    if(tl)tl.classList.remove('active'); if(ts)ts.classList.add('active');
    if(ind)ind.classList.add('right');
  }
}
function openAuth(tab) {
  const g=document.getElementById('authGate'); if(g){ g.style.display='flex'; if(tab) switchTab(tab); }
}
function closeAuth() {
  const g=document.getElementById('authGate'); if(g) g.style.display='none';
}
function togglePw(id,btn) {
  const el=document.getElementById(id); if(!el) return;
  const p = el.type==='password';
  el.type = p ? 'text' : 'password';
  btn.querySelector('i').className = p ? 'fas fa-eye-slash' : 'fas fa-eye';
}

async function doSignup() {
  const n=document.getElementById('signupName'), em=document.getElementById('signupEmail'),
        p=document.getElementById('signupPass'), err=document.getElementById('signupError'),
        btn=document.getElementById('signupBtn');
  if(!n||!em||!p) return;
  if(err) err.textContent='';
  if(n.value.trim().length<2)    { if(err)err.textContent='Enter your full name.'; return; }
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value)) { if(err)err.textContent='Enter a valid email.'; return; }
  if(p.value.length<6)           { if(err)err.textContent='Password must be 6+ characters.'; return; }
  const orig=btn?btn.innerHTML:'';
  if(btn){ btn.innerHTML='<span>Creating…</span><i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true; }
  try {
    const fd=new FormData();
    fd.append('action','signup'); fd.append('name',n.value.trim());
    fd.append('email',em.value.trim()); fd.append('password',p.value);
    const res=await fetch('php/user_auth.php',{method:'POST',body:fd});
    const data=await res.json();
    if(data.success){ window.location.reload(); }
    else{ if(err)err.textContent=data.error||'Signup failed.'; if(btn){btn.innerHTML=orig;btn.disabled=false;} }
  } catch{ if(err)err.textContent='Network error.'; if(btn){btn.innerHTML=orig;btn.disabled=false;} }
}

async function doLogin() {
  const em=document.getElementById('loginEmail'), p=document.getElementById('loginPass'),
        err=document.getElementById('loginError'), btn=document.getElementById('loginBtn');
  if(!em||!p) return;
  if(err) err.textContent='';
  if(!em.value.trim()){ if(err)err.textContent='Enter your email.'; return; }
  if(!p.value)        { if(err)err.textContent='Enter your password.'; return; }
  const orig=btn?btn.innerHTML:'';
  if(btn){ btn.innerHTML='<span>Signing in…</span><i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true; }
  try {
    const fd=new FormData();
    fd.append('action','login'); fd.append('email',em.value.trim()); fd.append('password',p.value);
    const res=await fetch('php/user_auth.php',{method:'POST',body:fd});
    const data=await res.json();
    if(data.success){ window.location.reload(); }
    else{ if(err)err.textContent=data.error||'Incorrect email or password.'; if(btn){btn.innerHTML=orig;btn.disabled=false;} }
  } catch{ if(err)err.textContent='Network error.'; if(btn){btn.innerHTML=orig;btn.disabled=false;} }
}

async function logoutUser() {
  try { const fd=new FormData(); fd.append('action','logout'); await fetch('php/user_auth.php',{method:'POST',body:fd}); } catch{}
  window.location.reload();
}

function toggleUserMenu() {
  const dd = document.getElementById('userDropdown');
  if (!dd) return;
  const isOpen = dd.classList.contains('open');
  if (isOpen) {
    dd.classList.remove('open');
    dd.style.display = 'none';
  } else {
    dd.classList.add('open');
    dd.style.display = 'block';
  }
}
document.addEventListener('click', e => {
  const dd  = document.getElementById('userDropdown');
  const btn = document.getElementById('userMenuBtn');
  const wrap = btn ? btn.closest('div') : null;
  if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
    dd.classList.remove('open');
    dd.style.display = 'none';
  }
});

/* Chat widget */
let chatOpen=false, chatPoll=null;
// Persist last seen message ID across soft refreshes
let lastChatId = parseInt(sessionStorage.getItem('wc_lastChatId') || '0');

function initChatWidget() {
  if(!document.getElementById('chatWidget')) return;
  loadChatHistory(); startChatPoll();
}
function toggleChat() {
  const w=document.getElementById('chatWindow'); if(!w) return;
  chatOpen=!chatOpen; w.classList.toggle('open',chatOpen);
  if(chatOpen){
    const b=document.getElementById('chatBadge'); if(b)b.style.display='none';
    scrollChatBottom(); document.getElementById('chatInput')?.focus();
  }
}
function openChat(){ if(!chatOpen) toggleChat(); }

async function sendChatMsg() {
  const inp=document.getElementById('chatInput'); if(!inp) return;
  const txt=inp.value.trim(); if(!txt) return;
  inp.value=''; inp.style.height='auto';
  appendChatMsg({message:txt,sender:'user',created_at:new Date().toISOString()},true);
  scrollChatBottom();
  try {
    const fd=new FormData(); fd.append('action','send'); fd.append('message',txt);
    const res=await fetch('php/chat.php',{method:'POST',body:fd});
    const d=await res.json(); if(d.id) lastChatId=Math.max(lastChatId,d.id);
  } catch{}
}
async function loadChatHistory() {
  try {
    const res=await fetch('php/chat.php?action=history'); const d=await res.json();
    if(!Array.isArray(d.messages)) return;
    d.messages.forEach(m=>{appendChatMsg(m,false);if(m.id){lastChatId=Math.max(lastChatId,m.id);sessionStorage.setItem('wc_lastChatId',lastChatId);}});
    scrollChatBottom();
  } catch{}
}
function startChatPoll() {
  clearInterval(chatPoll);
  chatPoll=setInterval(async()=>{
    try {
      const res=await fetch('php/chat.php?action=poll&since='+lastChatId);
      const d=await res.json();
      if(!Array.isArray(d.messages)||!d.messages.length) return;
      d.messages.forEach(m=>{appendChatMsg(m,false);if(m.id)lastChatId=Math.max(lastChatId,m.id);});
      if(!chatOpen){
        const badge=document.getElementById('chatBadge');
        const n=d.messages.filter(m=>m.sender==='admin').length;
        if(n>0&&badge){const p=parseInt(badge.textContent||'0');badge.textContent=(p+n)>9?'9+':(p+n);badge.style.display='flex';}
      }
      if(chatOpen) scrollChatBottom();
    } catch{}
  },4000);
}
function appendChatMsg(m,opt) {
  const c=document.getElementById('chatWinMessages'); if(!c) return;
  if(!opt&&m.id&&c.querySelector('[data-cid="'+m.id+'"]')) return;
  const isU=m.sender==='user';
  const row=document.createElement('div');
  row.className='chat-msg-row'+(isU?' user':'');
  if(m.id) row.dataset.cid=m.id;
  const t=m.created_at?new Date(m.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}):'';
  const txt=esc(m.message||'').replace(/\n/g,'<br>');
  row.innerHTML=isU
    ?`<div class="chat-msg-bubble user">${txt}<span class="chat-msg-time">${t}</span></div>`
    :`<div class="chat-msg-av"><i class="fas fa-camera-retro"></i></div><div class="chat-msg-bubble admin">${txt}<span class="chat-msg-time">${t}</span></div>`;
  c.appendChild(row);
}
function scrollChatBottom(){ const c=document.getElementById('chatWinMessages'); if(c) c.scrollTop=c.scrollHeight; }
document.addEventListener('keydown',e=>{
  if(e.target.id==='chatInput'&&e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendChatMsg();}
});

/* User dashboard */
let dashOpen=false, dashPoll=null;
let dashLastId = parseInt(sessionStorage.getItem('wc_dashLastId') || '0');

function openUserDashboard() {
  const ov=document.getElementById('userDashboard'); if(!ov) return;
  ov.style.display='flex'; document.body.style.overflow='hidden';
  dashOpen=true; loadDashBookings(); loadDashMessages(); startDashPoll();
}
function closeUserDashboard() {
  const ov=document.getElementById('userDashboard'); if(ov) ov.style.display='none';
  document.body.style.overflow=''; dashOpen=false; clearInterval(dashPoll);
}
async function loadDashBookings() {
  const list=document.getElementById('dashBookingList'); if(!list) return;
  list.innerHTML='<p style="color:#bbb;font-size:13px;padding:8px"><i class="fas fa-spinner fa-spin"></i> Loading…</p>';
  try {
    const res=await fetch('php/user_bookings.php'); const d=await res.json();
    if(!d.success||!d.bookings||!d.bookings.length){
      list.innerHTML='<div class="dash-empty"><i class="fas fa-calendar-times"></i><p>No bookings yet.</p><a href="#booking" onclick="closeUserDashboard()">Book your first session →</a></div>';
      return;
    }
    list.innerHTML=d.bookings.map(b=>`
      <div class="dash-booking-item">
        <div class="dash-booking-top">
          <span class="dash-booking-pkg">${esc(b.package)}</span>
          <span class="dash-booking-status status-${esc(b.status)}">${esc(b.status)}</span>
        </div>
        <div class="dash-booking-info">
          <span><i class="fas fa-calendar"></i>${esc(b.booking_date)}</span>
          <span><i class="fas fa-clock"></i>${b.time_slot||'—'}</span>
          ${b.event_type?`<span><i class="fas fa-tag"></i>${esc(b.event_type)}</span>`:''}
        </div>
      </div>`).join('');
  } catch{ list.innerHTML='<p style="color:#dc2626;font-size:13px;padding:8px">Could not load bookings.</p>'; }
}
async function loadDashMessages() {
  const c=document.getElementById('dashMsgList'); if(!c) return;
  try {
    const res=await fetch('php/chat.php?action=history'); const d=await res.json();
    if(!Array.isArray(d.messages)) return;
    c.innerHTML='';
    d.messages.forEach(m=>{appendDashMsg(m);if(m.id)dashLastId=Math.max(dashLastId,m.id);});
    c.scrollTop=c.scrollHeight;
  } catch{}
}
function startDashPoll() {
  clearInterval(dashPoll);
  dashPoll=setInterval(async()=>{
    if(!dashOpen) return;
    try {
      const res=await fetch('php/chat.php?action=poll&since='+dashLastId);
      const d=await res.json();
      if(!Array.isArray(d.messages)||!d.messages.length) return;
      d.messages.forEach(m=>{appendDashMsg(m);if(m.id)dashLastId=Math.max(dashLastId,m.id);});
      const c=document.getElementById('dashMsgList'); if(c) c.scrollTop=c.scrollHeight;
    } catch{}
  },3000);
}
function appendDashMsg(m) {
  const c=document.getElementById('dashMsgList'); if(!c) return;
  if(m.id&&c.querySelector('[data-did="'+m.id+'"]')) return;
  const isU=m.sender==='user';
  const row=document.createElement('div');
  row.className='dash-msg-row'+(isU?' user-msg':'');
  if(m.id) row.dataset.did=m.id;
  const t=m.created_at?new Date(m.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}):'';
  const txt=esc(m.message||'').replace(/\n/g,'<br>');
  row.innerHTML=isU
    ?`<div class="dash-msg-av user"><i class="fas fa-user"></i></div><div class="dash-msg-bubble user-b">${txt}<span class="dash-msg-time">${t}</span></div>`
    :`<div class="dash-msg-av studio"><i class="fas fa-camera-retro"></i></div><div class="dash-msg-bubble studio">${txt}<span class="dash-msg-time">${t}</span></div>`;
  c.appendChild(row);
}
async function sendDashMsg() {
  const inp=document.getElementById('dashMsgInput'); if(!inp) return;
  const txt=inp.value.trim(); if(!txt) return;
  inp.value='';
  appendDashMsg({message:txt,sender:'user',created_at:new Date().toISOString()});
  const c=document.getElementById('dashMsgList'); if(c) c.scrollTop=c.scrollHeight;
  try {
    const fd=new FormData(); fd.append('action','send'); fd.append('message',txt);
    const res=await fetch('php/chat.php',{method:'POST',body:fd});
    const d=await res.json(); if(d.id){dashLastId=Math.max(dashLastId,d.id);sessionStorage.setItem('wc_dashLastId',dashLastId);}
  } catch{}
}
document.addEventListener('keydown',e=>{
  if(e.target.id==='dashMsgInput'&&e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendDashMsg();}
});

/* Helper */
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

/* Payment & delivery system */

// UPI config — update these in production
const UPI_ID   = 'wingscut@upi';
const UPI_NAME = 'Wings Cut Studio';

/* Called when dashboard opens — load payment info for all bookings */
async function loadDashPayments(bookings) {
  const section = document.getElementById('dashPaymentSection');
  const cards   = document.getElementById('dashPaymentCards');
  if (!section || !cards || !bookings || !bookings.length) return;

  // Filter bookings that have amount set or payment status
  const relevant = bookings.filter(b =>
    b.total_amount > 0 || b.payment_status !== 'unpaid' ||
    b.payment_screenshot || b.delivery_file
  );
  if (!relevant.length) { section.style.display = 'none'; return; }

  section.style.display = 'block';
  cards.innerHTML = relevant.map(b => buildPayCard(b)).join('');
}

function buildPayCard(b) {
  const pkg      = esc(b.package || '');
  const date     = b.booking_date || '';
  const total    = parseFloat(b.total_amount || 0);
  const adv      = parseFloat(b.advance_paid || 0);
  const pend     = parseFloat(b.pending_amount || 0);
  const pstat    = b.payment_status || 'unpaid';
  const dstat    = b.delivery_status || 'processing';
  const pverif   = b.payment_verified == 1;
  const pscr     = b.payment_screenshot || '';
  const dfile    = b.delivery_file || '';
  const bid      = b.id;

  // Payment amount boxes (only if total set)
  const amtHtml = total > 0 ? `
    <div class="pay-amounts">
      <div class="pay-amount-box"><strong>₹${fmtAmt(total)}</strong><span>Total</span></div>
      <div class="pay-amount-box"><strong>₹${fmtAmt(adv)}</strong><span>Advance</span></div>
      <div class="pay-amount-box"><strong style="color:${pend>0?'#dc2626':'#16a34a'}">₹${fmtAmt(pend)}</strong><span>Pending</span></div>
    </div>` : '';

  // UPI QR section (show when payment pending/partial)
  const upiHtml = (pstat !== 'paid' && total > 0) ? `
    <div class="pay-upi-section">
      <div class="pay-upi-title"><i class="fas fa-qrcode"></i> Pay via UPI</div>
      <div class="pay-qr-placeholder" style="overflow:hidden;padding:0">
        <img src="images/upi-qr.png" alt="UPI QR" style="width:100%;height:100%;object-fit:contain"
             onerror="this.style.display='none';this.parentElement.innerHTML='<i class=&quot;fas fa-qrcode&quot;></i>'"/>
      </div>
      <div style="font-size:11px;color:#aaa;margin-bottom:6px">Scan with any UPI app</div>
      <div class="pay-upi-id">${UPI_ID}</div>
      <div style="font-size:11px;color:#888;margin-bottom:10px">
        ${pend > 0 ? `Pay advance: <strong style="color:var(--red)">₹${fmtAmt(pend>adv?pend:total)}</strong>` : 'Payment complete'}
      </div>
    </div>` : '';

  // Screenshot section
  let ssHtml = '';
  if (pscr && !pverif) {
    ssHtml = `<div class="pay-verify-badge pending"><i class="fas fa-clock"></i>Screenshot uploaded — Awaiting Admin Verification</div>`;
  } else if (pscr && pverif) {
    ssHtml = `<div class="pay-verify-badge verified"><i class="fas fa-check-circle"></i>Payment Verified!</div>`;
  }

  // Actions
  let actHtml = '<div class="pay-actions">';

  // Upload screenshot (show if unpaid/partial and not yet verified)
  if (pstat !== 'paid' && !pverif && total > 0) {
    actHtml += `
      <div class="pay-upload-area" onclick="document.getElementById('ssInput_${bid}').click()">
        <input type="file" id="ssInput_${bid}" accept="image/*" onchange="uploadScreenshot(${bid},this)"/>
        <i class="fas fa-camera"></i>
        <p>${pscr ? 'Upload new screenshot' : 'Upload payment screenshot'}</p>
      </div>
      <button class="pay-action-btn pay-action-primary" onclick="markPaid(${bid})">
        <i class="fas fa-check-circle"></i> I Have Paid — Upload Screenshot
      </button>`;
  }

  // Download button (show when delivery ready)
  if (dfile && (dstat === 'ready' || dstat === 'delivered')) {
    actHtml += `
      <a href="php/delivery.php?booking_id=${bid}" class="pay-action-btn pay-action-download">
        <i class="fas fa-download"></i> Download Your Files
      </a>`;
  }

  actHtml += `<div class="pay-msg" id="payMsg_${bid}"></div></div>`;

  // Delivery status
  const delIcons = { processing:'⚙️', editing:'✂️', ready:'✅', delivered:'📦' };
  const delLabels = { processing:'Processing', editing:'Editing', ready:'Ready for Download', delivered:'Delivered' };
  const delHtml = `
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;font-size:13px">
      <span>${delIcons[dstat]||'📁'}</span>
      <span style="color:#666">Delivery: <strong>${delLabels[dstat]||dstat}</strong></span>
    </div>`;

  return `
    <div class="pay-card" id="payCard_${bid}">
      <div class="pay-card-header">
        <div>
          <div class="pay-card-pkg">${pkg}</div>
          <div class="pay-card-date"><i class="fas fa-calendar" style="color:var(--red);font-size:10px"></i> ${date}</div>
        </div>
        <span class="pay-badge pay-badge-${pstat}">${pstat}</span>
      </div>
      ${amtHtml}
      ${delHtml}
      ${upiHtml}
      ${ssHtml}
      ${actHtml}
    </div>`;
}

function fmtAmt(n) {
  return parseFloat(n||0).toLocaleString('en-IN',{maximumFractionDigits:0});
}

async function uploadScreenshot(bid, input) {
  const file = input.files[0]; if (!file) return;
  const msg  = document.getElementById('payMsg_' + bid);
  if (msg) { msg.style.display='block'; msg.className='pay-msg ok'; msg.textContent='Uploading…'; }

  const fd = new FormData();
  fd.append('action', 'upload_screenshot');
  fd.append('booking_id', bid);
  fd.append('screenshot', file);
  fd.append('amount', 0); // user doesn't know exact amount

  try {
    const res  = await fetch('php/payment.php', { method:'POST', body:fd });
    const data = await res.json();
    if (msg) {
      msg.className = 'pay-msg ' + (data.success ? 'ok' : 'err');
      msg.textContent = data.success
        ? '✓ Screenshot uploaded! Admin will verify and update your payment status.'
        : '✗ ' + (data.error || 'Upload failed.');
    }
    if (data.success) {
      // Refresh the card after 2s
      setTimeout(() => loadDashBookings(), 2000);
    }
  } catch(e) {
    if (msg) { msg.className='pay-msg err'; msg.textContent='✗ Network error. Try again.'; }
  }
}

/* Patch loadDashBookings to also load payments */
const _origLoadDashBookings = loadDashBookings;
loadDashBookings = async function() {
  const list = document.getElementById('dashBookingList'); if (!list) return;
  list.innerHTML='<p style="color:#bbb;font-size:13px;padding:8px"><i class="fas fa-spinner fa-spin"></i> Loading…</p>';
  try {
    const res  = await fetch('php/user_bookings.php');
    const data = await res.json();
    if (!data.success || !data.bookings || !data.bookings.length) {
      list.innerHTML='<div class="dash-empty"><i class="fas fa-calendar-times"></i><p>No bookings yet.</p><a href="#booking" onclick="closeUserDashboard()">Book your first session →</a></div>';
      return;
    }
    list.innerHTML = data.bookings.map(b=>`
      <div class="dash-booking-item">
        <div class="dash-booking-top">
          <span class="dash-booking-pkg">${esc(b.package)}</span>
          <span class="dash-booking-status status-${esc(b.status)}">${esc(b.status)}</span>
        </div>
        <div class="dash-booking-info">
          <span><i class="fas fa-calendar"></i>${esc(b.booking_date)}</span>
          <span><i class="fas fa-clock"></i>${b.time_slot||'—'}</span>
          ${b.event_type?`<span><i class="fas fa-tag"></i>${esc(b.event_type)}</span>`:''}
        </div>
      </div>`).join('');

    // Load payment cards
    loadDashPayments(data.bookings);
  } catch(e) {
    list.innerHTML='<p style="color:#dc2626;font-size:13px;padding:8px">Could not load bookings.</p>';
  }
};


async function markPaid(bid) {
  try {
    const fd=new FormData(); fd.append('action','mark_paid'); fd.append('booking_id',bid);
    await fetch('php/payment.php',{method:'POST',body:fd});
  } catch(e){}
  document.getElementById('ssInput_'+bid)?.click();
}

/* Hero slideshow. Swaps the .active class every few seconds; the fade itself
   is done in CSS. If there's only one photo it just stays put. */
function initHeroSlides() {
  const slides = document.querySelectorAll('.hero-slide');
  if (slides.length < 2) return;
  let current = 0;
  setInterval(() => {
    slides[current].classList.remove('active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('active');
  }, 5000);
}
