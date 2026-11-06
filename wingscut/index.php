<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$booking_success = isset($_SESSION['booking_success']) ? $_SESSION['booking_success'] : false;
if ($booking_success) unset($_SESSION['booking_success']);
$loggedIn  = !empty($_SESSION['user_id']);
$userName  = $_SESSION['user_name']  ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

// Load gallery from DB if available
$db_gallery = [];
try {
    require_once __DIR__ . '/php/db.php';
    $gStmt = $pdo->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order ASC, created_at DESC LIMIT 20");
    $db_gallery = $gStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $db_gallery = []; }
$use_db_gallery = !empty($db_gallery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>WINGS CUT — Photography & Videography Studio</title>
  <meta name="description" content="WINGS CUT — Professional Photography & Videography Studio. Capturing moments that live forever."/>
  <link rel="stylesheet" href="css/style.css"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<!-- No loader — smooth page reveal via CSS/JS -->

<!-- Polygon animated background -->
<canvas id="polyCanvas"></canvas>

<!-- ========== ANIMATED BACKGROUND ========== -->
<div class="bg-canvas" id="bgCanvas">
  <div class="bg-shape s1"></div>
  <div class="bg-shape s2"></div>
  <div class="bg-shape s3"></div>
  <div class="bg-orb o1"></div>
  <div class="bg-orb o2"></div>
</div>

<!-- ========== NAVBAR ========== -->
<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <div class="nav-logo">
      <div class="logo-mark">
        <span class="logo-icon"><i class="fas fa-camera-retro"></i></span>
        <div class="logo-text-wrap">
          <span class="logo-title">WINGS CUT</span>
          <span class="logo-sub">STUDIO</span>
        </div>
      </div>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="#home" class="nav-link active">Home</a></li>
      <li><a href="#about" class="nav-link">About</a></li>
      <li><a href="#gallery" class="nav-link">Gallery</a></li>
      <li><a href="#pricing" class="nav-link">Pricing</a></li>
      <li><a href="#booking" class="nav-link">Booking</a></li>
      <li><a href="#contact" class="nav-link">Contact</a></li>
    </ul>
    <div class="nav-actions">
      <?php if($loggedIn): ?>
      <div style="position:relative;">
        <button class="btn-nav-user" id="userMenuBtn" onclick="toggleUserMenu()">
          <span class="btn-user-av"><?= strtoupper(substr($userName,0,1)) ?></span>
          <span><?= htmlspecialchars(explode(' ',$userName)[0]) ?></span>
          <i class="fas fa-chevron-down" style="font-size:10px;opacity:0.6;"></i>
        </button>
        <div class="user-dropdown" id="userDropdown">
          <div class="user-dd-header">
            <span class="user-dd-name"><?= htmlspecialchars($userName) ?></span>
            <span class="user-dd-email"><?= htmlspecialchars($userEmail) ?></span>
          </div>
          <a href="#" onclick="openChat();toggleUserMenu();return false;" class="user-dd-item"><i class="fas fa-comments"></i> My Messages</a>
          <a href="#booking" onclick="toggleUserMenu();" class="user-dd-item"><i class="fas fa-calendar"></i> Book a Session</a>
          <div class="user-dd-divider"></div>
          <button class="user-dd-item user-dd-logout" onclick="logoutUser()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
      </div>
      <?php else: ?>
      <button class="btn-nav-login" onclick="openAuth('login')">
        <i class="fas fa-user"></i> Login
      </button>
      <?php endif; ?>
      <a href="#contact" class="btn-nav-contact">Get In Touch</a>
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<!-- ========== HOME / HERO ========== -->
<section id="home" class="hero-section">
  <div class="hero-video-wrap">
    <video autoplay muted loop playsinline class="hero-video" id="heroVideo">
      <!-- Replace src with actual video file -->
      <source src="images/hero-reel.mp4" type="video/mp4"/>
    </video>
    <div class="hero-video-overlay"></div>
  </div>

  <div class="hero-content container">
    <div class="hero-left reveal-left">
      <span class="hero-tag"><i class="fas fa-circle"></i> Est. 2024 — Premium Studio</span>
      <h1 class="hero-title">
        <span class="title-line">WINGS</span>
        <span class="title-line accent">CUT</span>
      </h1>
      <p class="hero-sub">Professional Photography &amp; Videography Studio</p>
      <p class="hero-tagline" style="opacity:0;visibility:hidden;">"Capturing moments that live forever."</p>
      <div class="hero-btns">
        <a href="#booking" class="btn-primary ripple">Book Now <i class="fas fa-arrow-right"></i></a>
        <a href="#gallery" class="btn-outline">View Work <i class="fas fa-play"></i></a>
      </div>
      <div class="hero-stats">
        <div class="stat"><span class="stat-num" data-count="350">0</span><span>+</span><small>Projects Done</small></div>
        <div class="stat-divider"></div>
        <div class="stat"><span class="stat-num" data-count="5">0</span><span>+</span><small>Years Exp.</small></div>
        <div class="stat-divider"></div>
        <div class="stat"><span class="stat-num" data-count="120">0</span><span>+</span><small>Happy Clients</small></div>
      </div>
    </div>
    <div class="hero-right reveal-right">
      <div class="hero-img-frame">
        <div class="hero-img-placeholder">
          <div class="img-overlay-tag"><i class="fas fa-camera"></i> Featured Work</div>
          <div class="img-placeholder-inner">
            <i class="fas fa-image"></i>
            <span>Hero Image</span>
          </div>
        </div>
        <div class="hero-float-card">
          <i class="fas fa-star"></i>
          <div><strong>5.0</strong><small>Rating</small></div>
        </div>
        <div class="hero-float-card2">
          <i class="fas fa-award"></i>
          <div><strong>Award</strong><small>Winner 2024</small></div>
        </div>
      </div>
    </div>
  </div>

  <div class="scroll-indicator">
    <span>Scroll</span>
    <div class="scroll-line"></div>
  </div>
</section>

<!-- ========== SERVICE CARDS (Below Hero) ========== -->
<section class="services-strip">
  <div class="container">
    <div class="section-header reveal-up">
      <span class="section-tag">What We Do</span>
      <h2>Our Core Services</h2>
      <p>End-to-end visual storytelling from shoot to final delivery</p>
    </div>
    <div class="services-grid">
      <div class="service-card reveal-up" data-delay="0">
        <div class="service-icon"><i class="fas fa-camera"></i></div>
        <div class="service-img-placeholder"><i class="fas fa-image"></i></div>
        <h3>Photography</h3>
        <p>From intimate portraits to grand wedding ceremonies, every frame tells a story crafted with precision and artistic vision.</p>
        <a href="#gallery" class="service-link">Explore <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="service-card featured reveal-up" data-delay="150">
        <div class="service-badge">Most Popular</div>
        <div class="service-icon"><i class="fas fa-video"></i></div>
        <div class="service-img-placeholder"><i class="fas fa-image"></i></div>
        <h3>Videography</h3>
        <p>Cinematic films that capture the emotion, energy, and essence of your most special moments in breathtaking 4K quality.</p>
        <a href="#gallery" class="service-link">Explore <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="service-card reveal-up" data-delay="300">
        <div class="service-icon"><i class="fas fa-film"></i></div>
        <div class="service-img-placeholder"><i class="fas fa-image"></i></div>
        <h3>Editing &amp; Cinematics</h3>
        <p>Professional color grading, cinematic cuts, motion graphics, and soundtrack sync for a truly premium final product.</p>
        <a href="#gallery" class="service-link">Explore <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</section>

<!-- ========== TESTIMONIAL STRIP ========== -->
<section class="testimonial-strip">
  <div class="testi-track-wrap">
    <div class="testi-track" id="testiTrack">
      <?php
      $testimonials = [
        ["name"=>"Aditi Sharma","role"=>"Bride","text"=>"Wings Cut made our wedding look like a Bollywood film! Absolutely stunning work.","stars"=>5],
        ["name"=>"Rahul Verma","role"=>"Corporate Client","text"=>"Highly professional team. Delivered our product shoot well before deadline.","stars"=>5],
        ["name"=>"Priya Nair","role"=>"Portrait Client","text"=>"My portfolio shoot was magical. They knew exactly how to capture my best angles.","stars"=>5],
        ["name"=>"Karan Mehta","role"=>"Event Manager","text"=>"Used Wings Cut for 3 events now. Consistently brilliant results every time.","stars"=>5],
        ["name"=>"Sneha Kapoor","role"=>"Bride","text"=>"The cinematic reel they made for us brought tears to everyone's eyes. Priceless.","stars"=>5],
        ["name"=>"Vikram Iyer","role"=>"Artist","text"=>"My album cover shoot exceeded every expectation. These guys are true artists.","stars"=>5],
      ];
      // Duplicate for infinite loop
      $all = array_merge($testimonials, $testimonials);
      foreach($all as $t): ?>
      <div class="testi-card">
        <div class="testi-stars">
          <?php for($i=0;$i<$t['stars'];$i++) echo '<i class="fas fa-star"></i>'; ?>
        </div>
        <p>"<?= htmlspecialchars($t['text']) ?>"</p>
        <div class="testi-author">
          <div class="testi-avatar"><?= strtoupper(substr($t['name'],0,1)) ?></div>
          <div><strong><?= htmlspecialchars($t['name']) ?></strong><small><?= htmlspecialchars($t['role']) ?></small></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ========== ABOUT SECTION ========== -->
<section id="about" class="about-section">
  <div class="container">
    <div class="about-grid">
      <div class="about-img-col reveal-left">
        <div class="about-img-wrap">
          <div class="about-img-placeholder">
            <i class="fas fa-image"></i>
            <span>Studio / Team Photo</span>
          </div>
          <div class="about-img-accent"></div>
          <div class="about-badge">
            <span class="badge-num">5+</span>
            <span class="badge-txt">Years of Excellence</span>
          </div>
        </div>
      </div>
      <div class="about-text-col reveal-right">
        <span class="section-tag">Our Story</span>
        <h2>Born From Passion.<br/> Built on <span class="accent-text">Craft.</span></h2>
        <p class="about-intro">Wings Cut began as a creative vision in 2019 — a belief that every moment deserves to be immortalized with artistry, intention, and heart. Today, we're one of the region's most trusted visual studios.</p>
        <p>From intimate family sessions to grand cinematic wedding films, our team brings technical mastery and emotional intelligence to every project. We don't just take photos — we build visual legacies.</p>
        <ul class="why-us-list">
          <li><i class="fas fa-check-circle"></i> Award-winning cinematography team</li>
          <li><i class="fas fa-check-circle"></i> State-of-the-art Sony & Canon gear</li>
          <li><i class="fas fa-check-circle"></i> 48-hr preview turnaround guarantee</li>
          <li><i class="fas fa-check-circle"></i> Custom packages for every budget</li>
          <li><i class="fas fa-check-circle"></i> Post-production in-house studio</li>
        </ul>
        <a href="#booking" class="btn-primary ripple">Work With Us <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>

    <!-- Workflow Strip -->
    <div class="workflow-strip reveal-up" data-delay="100">
      <h3>Our Workflow</h3>
      <div class="workflow-steps">
        <?php
        $steps = [
          ["icon"=>"fas fa-comments","label"=>"Consultation"],
          ["icon"=>"fas fa-pencil-ruler","label"=>"Planning"],
          ["icon"=>"fas fa-camera","label"=>"Shoot"],
          ["icon"=>"fas fa-magic","label"=>"Edit"],
          ["icon"=>"fas fa-paper-plane","label"=>"Delivery"],
        ];
        foreach($steps as $i => $step): ?>
        <div class="workflow-step">
          <div class="step-icon"><i class="<?= $step['icon'] ?>"></i></div>
          <span><?= $step['label'] ?></span>
          <?php if($i < count($steps)-1): ?><div class="step-connector"></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ========== GALLERY SECTION ========== -->
<section id="gallery" class="gallery-section">
  <div class="container">
    <div class="section-header reveal-up" data-delay="0">
      <span class="section-tag">Portfolio</span>
      <h2>Visual Stories</h2>
      <p>A curated glimpse into our finest work across every category</p>
    </div>

    <div class="gallery-filters reveal-up">
      <button class="filter-btn active" data-filter="all">All</button>
      <button class="filter-btn" data-filter="wedding">Wedding</button>
      <button class="filter-btn" data-filter="portrait">Portrait</button>
      <button class="filter-btn" data-filter="events">Events</button>
      <button class="filter-btn" data-filter="cinematic">Cinematic</button>
    </div>

    <div class="masonry-grid" id="masonryGrid">
      <?php
      // Sizes cycle for visual variety
      $sizes = ['tall','wide','normal','normal','tall','wide','normal','normal','wide','tall','normal','normal'];
      if ($use_db_gallery):
        foreach($db_gallery as $idx => $item):
          $size = $sizes[$idx % count($sizes)];
      ?>
      <div class="gallery-item <?= $size ?>" data-category="<?= htmlspecialchars($item['category']) ?>" data-index="<?= $idx ?>">
        <img data-src="<?= htmlspecialchars($item['image_path']) ?>" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" loading="lazy" decoding="async"
             alt="<?= htmlspecialchars($item['title']) ?>"
             style="width:100%;height:100%;object-fit:cover;display:block;"/>
        <div class="gallery-overlay">
          <div class="gallery-info">
            <span class="gallery-cat"><?= ucfirst(htmlspecialchars($item['category'])) ?></span>
            <h4><?= htmlspecialchars($item['title']) ?></h4>
          </div>
          <button class="gallery-zoom" data-label="<?= htmlspecialchars($item['title']) ?>"><i class="fas fa-expand"></i></button>
        </div>
      </div>
      <?php endforeach;
      else:
        // Fallback placeholders until images are uploaded
        $gallery_items = [
          ["cat"=>"wedding","size"=>"tall","label"=>"Wedding Ceremony"],
          ["cat"=>"portrait","size"=>"wide","label"=>"Studio Portrait"],
          ["cat"=>"events","size"=>"normal","label"=>"Corporate Event"],
          ["cat"=>"cinematic","size"=>"tall","label"=>"Cinematic Reel"],
          ["cat"=>"wedding","size"=>"normal","label"=>"Wedding Reception"],
          ["cat"=>"portrait","size"=>"normal","label"=>"Outdoor Portrait"],
        ];
        foreach($gallery_items as $idx => $item): ?>
      <div class="gallery-item <?= $item['size'] ?>" data-category="<?= $item['cat'] ?>" data-index="<?= $idx ?>">
        <div class="gallery-img-placeholder"><i class="fas fa-image"></i></div>
        <div class="gallery-overlay">
          <div class="gallery-info">
            <span class="gallery-cat"><?= ucfirst($item['cat']) ?></span>
            <h4><?= $item['label'] ?></h4>
          </div>
          <button class="gallery-zoom" data-label="<?= $item['label'] ?>"><i class="fas fa-expand"></i></button>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="gallery-load-more reveal-up">
      <button class="btn-load-more ripple" id="loadMoreBtn">
        <span>Load More</span> <i class="fas fa-plus"></i>
      </button>
    </div>
  </div>
</section>

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
  <div class="lightbox-backdrop"></div>
  <div class="lightbox-content">
    <button class="lightbox-close"><i class="fas fa-times"></i></button>
    <button class="lightbox-prev"><i class="fas fa-chevron-left"></i></button>
    <div class="lightbox-img-wrap" style="position:relative;">
      <!-- Real image (shown when DB gallery) -->
      <img id="lightboxImg" src="" alt="" />
      <!-- Placeholder (shown when no real image) -->
      <div class="lightbox-placeholder" id="lightboxPlaceholder">
        <i class="fas fa-image"></i>
      </div>
      <!-- Label bar -->
      <div class="lightbox-label-bar">
        <span class="lb-cat-tag" id="lightboxCat"></span>
        <span id="lightboxLabel">Image</span>
      </div>
    </div>
    <button class="lightbox-next"><i class="fas fa-chevron-right"></i></button>
  </div>
</div>

<!-- Custom Cursor -->
<div id="customCursor"></div>
<div id="customCursorRing"></div>

<!-- ═══ AUTH GATE — full screen, shown if not logged in ═══ -->
<div class="auth-gate" id="authGate" <?= $loggedIn ? 'style="display:none"' : '' ?>>
  <!-- Animated background -->
  <div class="auth-gate-bg">
    <div class="auth-bg-shape s1"></div>
    <div class="auth-bg-shape s2"></div>
    <div class="auth-bg-shape s3"></div>
  </div>

  <!-- Left panel — branding -->
  <div class="auth-left">
    <div class="auth-brand">
      <div class="auth-logo-icon"><i class="fas fa-camera-retro"></i></div>
      <div class="auth-logo-text">
        <span class="auth-brand-name">WINGS CUT</span>
        <span class="auth-brand-sub">STUDIO</span>
      </div>
    </div>
    <h1 class="auth-headline">Every moment<br>deserves to<br><em>live forever.</em></h1>
    <p class="auth-tagline">Professional photography & videography for your most precious milestones.</p>
    <div class="auth-stats">
      <div class="auth-stat"><span>500+</span><small>Sessions</small></div>
      <div class="auth-stat"><span>98%</span><small>Happy Clients</small></div>
      <div class="auth-stat"><span>5★</span><small>Rating</small></div>
    </div>
  </div>

  <!-- Right panel — form -->
  <div class="auth-right">
    <div class="auth-card" id="authCard">

      <!-- Mobile-only logo -->
      <div class="auth-mobile-logo">
        <div class="auth-logo-icon"><i class="fas fa-camera-retro"></i></div>
        <div>
          <span class="auth-brand-name">WINGS CUT</span>
          <span class="auth-brand-sub">STUDIO</span>
        </div>
      </div>

      <!-- Tab switcher -->
      <div class="auth-tabs">
        <button class="auth-tab active" id="tabLogin"  onclick="switchTab('login')">Sign In</button>
        <button class="auth-tab"        id="tabSignup" onclick="switchTab('signup')">Create Account</button>
        <div class="auth-tab-indicator" id="authTabIndicator"></div>
      </div>

      <!-- LOGIN -->
      <div id="loginTab" class="auth-form-section">
        <p class="auth-form-sub">Welcome back! Sign in to continue.</p>
        <div class="auth-field">
          <i class="fas fa-envelope"></i>
          <input type="email" id="loginEmail" placeholder="Email address" autocomplete="email"/>
        </div>
        <div class="auth-field">
          <i class="fas fa-lock"></i>
          <input type="password" id="loginPass" placeholder="Password" autocomplete="current-password"/>
          <button class="auth-eye" onclick="togglePw('loginPass',this)" type="button"><i class="fas fa-eye"></i></button>
        </div>
        <div class="auth-error" id="loginError"></div>
        <button class="auth-submit" id="loginBtn" onclick="doLogin()">
          <span>Sign In</span><i class="fas fa-arrow-right"></i>
        </button>
      </div>

      <!-- SIGNUP -->
      <div id="signupTab" class="auth-form-section" style="display:none;">
        <p class="auth-form-sub">Join Wings Cut Studio today.</p>
        <div class="auth-field">
          <i class="fas fa-user"></i>
          <input type="text" id="signupName" placeholder="Full name" autocomplete="name"/>
        </div>
        <div class="auth-field">
          <i class="fas fa-envelope"></i>
          <input type="email" id="signupEmail" placeholder="Email address" autocomplete="email"/>
        </div>
        <div class="auth-field">
          <i class="fas fa-lock"></i>
          <input type="password" id="signupPass" placeholder="Password (min 6 chars)" autocomplete="new-password"/>
          <button class="auth-eye" onclick="togglePw('signupPass',this)" type="button"><i class="fas fa-eye"></i></button>
        </div>
        <div class="auth-error" id="signupError"></div>
        <button class="auth-submit" id="signupBtn" onclick="doSignup()">
          <span>Create Account</span><i class="fas fa-arrow-right"></i>
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ═══ AUTH MODAL (for already-logged-in users clicking Login again) ═══ -->
<div class="wc-modal-overlay" id="authModal" style="display:none;">
  <div class="wc-modal-box">
    <button class="wc-modal-close" onclick="closeAuth()"><i class="fas fa-times"></i></button>
    <div id="loginTab2">
      <div class="wc-modal-logo"><i class="fas fa-camera-retro"></i></div>
      <h2 class="wc-modal-title">Sign In</h2>
      <div class="wc-form-group"><label>Email</label><input type="email" id="loginEmail2" placeholder="your@email.com"/></div>
      <div class="wc-form-group"><label>Password</label><input type="password" id="loginPass2" placeholder="••••••••"/></div>
      <div class="wc-error" id="loginError2"></div>
      <button class="wc-btn" onclick="doLogin2()">Sign In</button>
    </div>
  </div>
</div>

<!-- ═══ USER CHAT WIDGET ═══ -->
<?php if($loggedIn): ?>
<div id="chatWidget">
  <!-- FAB button -->
  <button class="chat-fab" onclick="toggleChat()" id="chatFab" title="Message Wings Cut">
    <i class="fas fa-comments" id="chatFabIcon"></i>
    <span class="chat-fab-badge" id="chatBadge" style="display:none;"></span>
  </button>

  <!-- Chat window -->
  <div class="chat-window" id="chatWindow">
    <div class="chat-win-header">
      <div class="chat-win-av"><i class="fas fa-camera-retro"></i></div>
      <div class="chat-win-info">
        <div class="chat-win-name">Wings Cut Studio</div>
        <div class="chat-win-status"><span class="chat-online-dot"></span> Typically replies within 24h</div>
      </div>
      <button class="chat-win-close" onclick="toggleChat()"><i class="fas fa-chevron-down"></i></button>
    </div>

    <div class="chat-win-messages" id="chatWinMessages">
      <!-- Welcome message -->
      <div class="chat-welcome">
        <div class="chat-welcome-av"><i class="fas fa-camera-retro"></i></div>
        <div class="chat-welcome-bubble">
          Hi <?= htmlspecialchars(explode(' ',$userName)[0]) ?>! 👋 Welcome to Wings Cut Studio.
          How can we help you today?
        </div>
      </div>
    </div>

    <div class="chat-win-input">
      <textarea id="chatInput" placeholder="Write a message..." rows="1"
        oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
      <button class="chat-send-btn" onclick="sendChatMsg()">
        <i class="fas fa-paper-plane"></i>
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ========== PRICING SECTION ========== -->
<section id="pricing" class="pricing-section">
  <div class="container">
    <div class="section-header reveal-up">
      <span class="section-tag">Investment</span>
      <h2>Choose Your Package</h2>
      <p>Transparent pricing crafted for every occasion and budget</p>
    </div>
    <div class="pricing-grid">
      <?php
      $packages = [
        [
          "name"=>"Essentials","price"=>"₹15,000","period"=>"per event","tag"=>"",
          "color"=>"default","icon"=>"fas fa-seedling",
          "features"=>[
            "4-hour photo coverage","1 professional photographer","200+ edited photos",
            "Online gallery delivery","Basic color grading","1 revision round"
          ],
          "not"=>["Video coverage","Cinematic reel","Same-day preview"]
        ],
        [
          "name"=>"Signature","price"=>"₹35,000","period"=>"per event","tag"=>"Most Popular",
          "color"=>"featured","icon"=>"fas fa-crown",
          "features"=>[
            "8-hour full coverage","2 photographers + 1 videographer","500+ edited photos",
            "Cinematic highlight reel (5–7 min)","Premium color grading",
            "Same-day preview reel","2 revision rounds","Printed album (50 pages)"
          ],
          "not"=>[]
        ],
        [
          "name"=>"Elite","price"=>"₹65,000","period"=>"per event","tag"=>"",
          "color"=>"default","icon"=>"fas fa-gem",
          "features"=>[
            "Full-day coverage (12 hrs)","3 photographers + drone operator","1000+ edited photos",
            "Feature-length cinematic film (20+ min)","4K video delivery","Drone aerial footage",
            "Unlimited revisions","Luxury album + USB package","1-year cloud backup"
          ],
          "not"=>[]
        ],
      ];
      foreach($packages as $pkg): ?>
      <div class="pricing-card <?= $pkg['color'] ?> reveal-up" data-delay="<?= $idx*150 ?>">
        <?php if($pkg['tag']): ?><div class="pricing-badge"><?= $pkg['tag'] ?></div><?php endif; ?>
        <div class="pricing-icon"><i class="<?= $pkg['icon'] ?>"></i></div>
        <h3><?= $pkg['name'] ?></h3>
        <div class="pricing-price">
          <span class="price-amount"><?= $pkg['price'] ?></span>
          <span class="price-period"><?= $pkg['period'] ?></span>
        </div>
        <ul class="pricing-features">
          <?php foreach($pkg['features'] as $f): ?>
          <li class="included"><i class="fas fa-check"></i><?= $f ?></li>
          <?php endforeach; ?>
          <?php foreach($pkg['not'] as $f): ?>
          <li class="excluded"><i class="fas fa-times"></i><?= $f ?></li>
          <?php endforeach; ?>
        </ul>
        <a href="#booking" class="btn-pricing ripple">Select Package <i class="fas fa-arrow-right"></i></a>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="pricing-note reveal-up"><i class="fas fa-info-circle"></i> All packages are customizable. <a href="#contact">Contact us</a> for tailored quotes.</p>
  </div>
</section>

<!-- ========== BOOKING SECTION ========== -->
<section id="booking" class="booking-section">
  <div class="container">
    <div class="booking-wrap">
      <div class="booking-info reveal-left" data-delay="0">
        <span class="section-tag">Reserve Your Date</span>
        <h2>Book a Session</h2>
        <p>Fill in your details and we'll confirm availability within 24 hours. Dates fill up fast — especially during wedding season!</p>
        <div class="booking-highlights">
          <div class="bh-item"><i class="fas fa-clock"></i><span>24-hr Confirmation</span></div>
          <div class="bh-item"><i class="fas fa-lock"></i><span>Secure Booking</span></div>
          <div class="bh-item"><i class="fas fa-calendar-check"></i><span>Flexible Dates</span></div>
          <div class="bh-item"><i class="fas fa-headset"></i><span>Dedicated Support</span></div>
        </div>
      </div>
      <div class="booking-form-wrap reveal-right" data-delay="150">
        <!-- AJAX success message — shown by JS, hidden by default -->
        <div id="successMsg" style="display:none;text-align:center;padding:48px 30px;">
          <div style="font-size:60px;color:#22c55e;margin-bottom:16px;animation:popIn 0.5s cubic-bezier(0.34,1.56,0.64,1);">
            <i class="fas fa-check-circle"></i>
          </div>
          <h3 style="font-size:22px;font-weight:700;margin-bottom:8px;">Booking Received! 🎉</h3>
          <p id="successName" style="font-size:16px;color:var(--red);font-weight:600;margin-bottom:8px;"></p>
          <p id="successId"   style="font-size:13px;color:#888;margin-bottom:16px;letter-spacing:1px;"></p>
          <p style="color:#666;line-height:1.6;max-width:380px;margin:0 auto 20px;">
            We've received your request and will confirm within 24 hours.
          </p>
          <button onclick="resetBookingForm()" style="background:var(--red);color:white;border:none;padding:12px 28px;border-radius:99px;font-size:14px;font-weight:600;cursor:pointer;">
            <i class="fas fa-plus"></i> Make Another Booking
          </button>
        </div>
        <?php /* form always shown, toggled by JS */ ?>
        <form class="booking-form" id="bookingForm" novalidate>
          <div class="form-row">
            <div class="form-group">
              <label for="name">Full Name *</label>
              <input type="text" id="name" name="name" placeholder="Your full name" required/>
              <span class="form-error" id="nameError"></span>
            </div>
            <div class="form-group">
              <label for="email">Email Address *</label>
              <input type="email" id="email" name="email" placeholder="your@email.com" required/>
              <span class="form-error" id="emailError"></span>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="phone">Phone Number *</label>
              <div class="phone-wrap">
                <select id="countryCode" name="country_code" class="country-code-select">
                  <option value="+91">🇮🇳 +91</option>
                  <option value="+1">🇺🇸 +1</option>
                  <option value="+44">🇬🇧 +44</option>
                  <option value="+61">🇦🇺 +61</option>
                  <option value="+971">🇦🇪 +971</option>
                  <option value="+65">🇸🇬 +65</option>
                  <option value="+60">🇲🇾 +60</option>
                  <option value="+94">🇱🇰 +94</option>
                  <option value="+977">🇳🇵 +977</option>
                  <option value="+880">🇧🇩 +880</option>
                  <option value="+92">🇵🇰 +92</option>
                </select>
                <input type="tel" id="phone" name="phone" placeholder="9876543210" required
                  maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                  oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                  onblur="if(this.value.length>0&&this.value.length!==10){this.style.borderColor='#dc2626';document.getElementById('phoneError').textContent='Must be exactly 10 digits';}else{this.style.borderColor='';document.getElementById('phoneError').textContent='';}"/>
              </div>
              <span class="form-error" id="phoneError"></span>
            </div>
            <div class="form-group">
              <label for="package">Select Package *</label>
              <select id="package" name="package" required>
                <option value="">Choose a package</option>
                <option value="essentials">Essentials — ₹15,000</option>
                <option value="signature">Signature — ₹35,000</option>
                <option value="elite">Elite — ₹65,000</option>
                <option value="custom">Custom Package</option>
              </select>
              <span class="form-error" id="packageError"></span>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="date">Preferred Date *</label>
              <input type="date" id="date" name="date" required onchange="checkSlotAvailability()"/>
              <span class="form-error" id="dateError"></span>
            </div>
            <div class="form-group">
              <label for="event_type">Event Type</label>
              <select id="event_type" name="event_type">
                <option value="">Select event type</option>
                <option value="wedding">Wedding</option>
                <option value="portrait">Portrait / Fashion</option>
                <option value="corporate">Corporate / Product</option>
                <option value="birthday">Birthday / Celebration</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="time_slot">Session Time *</label>
              <select id="time_slot" name="time_slot" required onchange="checkSlotAvailability()">
                <option value="">Select session time</option>
                <option value="morning">🌅 Morning (10:00 AM – 5:00 PM)</option>
                <option value="evening">🌆 Evening (5:00 PM – 12:00 AM)</option>
              </select>
              <span class="form-error" id="slotError"></span>
              <span id="slotStatus" style="font-size:12px;margin-top:4px;display:block;"></span>
            </div>
            <div class="form-group">
              <label for="venue">Event Venue / Address</label>
              <input type="text" id="venue" name="venue" placeholder="e.g. City Hall, Ballari or Home Studio"/>
            </div>
          </div>
          <div class="form-group full">
            <label for="message">Additional Message</label>
            <textarea id="message" name="message" rows="4" placeholder="Tell us more about your event, vision, or any special requirements..."></textarea>
          </div>
          <input type="hidden" name="csrf_token" value="<?= bin2hex(random_bytes(16)) ?>"/>
          <button type="submit" class="btn-primary btn-book ripple">
            <span>Confirm Booking</span> <i class="fas fa-calendar-check"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ========== CONTACT SECTION ========== -->
<section id="contact" class="contact-section">
  <div class="container">
    <div class="section-header reveal-up">
      <span class="section-tag">Get In Touch</span>
      <h2>Let's Talk</h2>
      <p>Have a question or want to discuss your project? We'd love to hear from you.</p>
    </div>
    <div class="contact-grid">
      <!-- Contact Form -->
      <div class="contact-form-col reveal-left" data-delay="0">
        <form action="php/contact.php" method="POST" class="contact-form" id="contactForm" novalidate>
          <div class="form-row">
            <div class="form-group">
              <label>Name *</label>
              <input type="text" name="c_name" placeholder="Your name" required/>
            </div>
            <div class="form-group">
              <label>Email *</label>
              <input type="email" name="c_email" placeholder="your@email.com" required/>
            </div>
          </div>
          <div class="form-group full">
            <label>Subject</label>
            <input type="text" name="c_subject" placeholder="What's this about?"/>
          </div>
          <div class="form-group full">
            <label>Message *</label>
            <textarea name="c_message" rows="5" placeholder="Your message..." required></textarea>
          </div>
          <button type="submit" class="btn-primary ripple">Send Message <i class="fas fa-paper-plane"></i></button>
        </form>
      </div>

      <!-- Contact Info -->
      <div class="contact-info-col reveal-right" data-delay="150">
        <div class="contact-info-card">
          <div class="contact-detail">
            <div class="cd-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div><strong>Studio Address</strong><p>123 Creative Hub, Koregaon Park,<br/>Pune, Maharashtra 411001</p></div>
          </div>
          <div class="contact-detail">
            <div class="cd-icon"><i class="fas fa-phone"></i></div>
            <div><strong>Phone</strong><p>+91 98765 43210</p></div>
          </div>
          <div class="contact-detail">
            <div class="cd-icon"><i class="fas fa-envelope"></i></div>
            <div><strong>Email</strong><p>hello@wingscut.studio</p></div>
          </div>
          <div class="contact-detail">
            <div class="cd-icon"><i class="fas fa-clock"></i></div>
            <div><strong>Hours</strong><p>Mon–Sat: 10 AM – 7 PM</p></div>
          </div>

          <!-- WhatsApp Button -->
          <a href="https://wa.me/919876543210?text=Hi%20Wings%20Cut!%20I'd%20like%20to%20book%20a%20session." target="_blank" class="whatsapp-btn ripple">
            <i class="fab fa-whatsapp"></i> Chat on WhatsApp
          </a>

          <!-- Social Icons -->
          <div class="social-icons">
            <a href="#" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-icon" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
            <a href="#" class="social-icon" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>

        <!-- Map -->
        <div class="map-card">
          <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <span>Google Maps</span>
            <small>Replace with actual iframe</small>
          </div>
          <!-- Uncomment and replace with actual embed:
          <iframe src="https://www.google.com/maps/embed?pb=..." width="100%" height="220" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
          -->
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="footer">
  <div class="footer-top">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="footer-logo">
            <i class="fas fa-camera-retro"></i>
            <div>
              <span class="footer-logo-title">WINGS CUT</span>
              <span class="footer-logo-sub">STUDIO</span>
            </div>
          </div>
          <p>We craft visual stories that outlast time. Every frame is a testament to craftsmanship, passion, and purpose.</p>
          <div class="footer-social">
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#gallery">Gallery</a></li>
            <li><a href="#pricing">Pricing</a></li>
            <li><a href="#booking">Book Now</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Services</h4>
          <ul>
            <li><a href="#">Wedding Photography</a></li>
            <li><a href="#">Portrait Sessions</a></li>
            <li><a href="#">Corporate Events</a></li>
            <li><a href="#">Cinematic Films</a></li>
            <li><a href="#">Drone Filming</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Contact</h4>
          <ul class="footer-contact-list">
            <li><i class="fas fa-map-marker-alt"></i>Pune, Maharashtra</li>
            <li><i class="fas fa-phone"></i>+91 98765 43210</li>
            <li><i class="fas fa-envelope"></i>hello@wingscut.studio</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?= date('Y') ?> WINGS CUT Studio. All rights reserved. Crafted with <i class="fas fa-heart"></i> for visual storytelling.</p>
    </div>
  </div>
</footer>

<!-- ========== BACK TO TOP ========== -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
  <i class="fas fa-chevron-up"></i>
</button>

<!-- ========== CONTACT TOAST ========== -->
<div class="toast" id="toast"></div>

<script src="js/script.js"></script>
<script>
// ── Check slot availability via AJAX ──────────────────
function checkSlotAvailability() {
  const dateEl   = document.getElementById('date');
  const slotEl   = document.getElementById('time_slot');
  const statusEl = document.getElementById('slotStatus');
  const slotErr  = document.getElementById('slotError');

  // Guard — elements may not exist on success page
  if (!dateEl || !slotEl || !statusEl) return;

  const date = dateEl.value;
  const slot = slotEl.value;
  if (!date || !slot) { statusEl.textContent = ''; return; }

  statusEl.textContent = 'Checking availability...';
  statusEl.style.color = '#888';

  fetch('php/check_slot.php?date=' + encodeURIComponent(date) + '&slot=' + encodeURIComponent(slot))
    .then(r => r.json())
    .then(data => {
      if (data.available) {
        statusEl.textContent = '✓ ' + data.remaining + ' slot' + (data.remaining > 1 ? 's' : '') + ' available';
        statusEl.style.color = '#16a34a';
        if (slotErr) slotErr.textContent = '';
        slotEl.style.borderColor = '';
      } else {
        statusEl.textContent = '✗ Session fully booked. Try another date or time.';
        statusEl.style.color = '#dc2626';
        if (slotErr) slotErr.textContent = 'Session full';
        slotEl.style.borderColor = '#dc2626';
      }
    })
    .catch(() => { statusEl.textContent = ''; });
}
</script>
</body>
</html>