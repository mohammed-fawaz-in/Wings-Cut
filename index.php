<?php
// Wings Cut Studio - main page.
//
// Business details live in the $site array right below. Change them once
// here and they update in the navbar, contact section, footer and the page
// meta tags, so you never have to hunt through the HTML.
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

$site = [
    'name'      => 'Wings Cut Studio',
    'phone'     => '+91 89514 56272',
    'phone_raw' => '918951456272',      // digits only; used for the tel: and WhatsApp links
    'email'     => 'fawazpawwa07@gmail.com',
    'address'   => '',                  // studio address. The row stays hidden until you fill this in.
    'hours'     => 'Mon - Sat: 10 AM - 7 PM',   // change if your timings are different
    // Full profile links. Anything left empty is simply not shown.
    'instagram' => '',
    'facebook'  => '',
    'youtube'   => '',
    // Google Maps -> Share -> Embed a map -> copy only the src="..." URL.
    'map_embed' => '',
];

$loggedIn  = !empty($_SESSION['user_id']);
$userName  = $_SESSION['user_name']  ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

// Turns "ravi-and-priya_haldi.jpg" into "Ravi And Priya Haldi" for the lightbox caption.
function wc_caption($file) {
    $name = pathinfo($file, PATHINFO_FILENAME);
    $name = preg_replace('/^\d+[-_ ]*/', '', $name);
    $name = ucwords(trim(str_replace(['-', '_'], ' ', $name)));
    return $name !== '' ? $name : 'Wings Cut';
}

// Lists every image inside images/photos/<folder>. To add a photo to the site
// you only have to copy the file into the right folder, nothing else.
function wc_photos($folder) {
    $dir = __DIR__ . '/images/photos/' . $folder;
    if (!is_dir($dir)) return [];
    $files = scandir($dir);
    natsort($files);
    $out = [];
    foreach ($files as $f) {
        if (preg_match('/\.(jpe?g|png|webp)$/i', $f)) {
            $out[] = ['src' => 'images/photos/' . $folder . '/' . rawurlencode($f), 'label' => wc_caption($f)];
        }
    }
    return $out;
}

$db_gallery=[]; $pdo=null;
try {
    require_once __DIR__.'/php/db.php';
    if($pdo){
        $gStmt=$pdo->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order ASC, created_at DESC LIMIT 20");
        $db_gallery=$gStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(Exception $e){ $db_gallery=[]; }
$use_db_gallery=!empty($db_gallery);

// Gallery categories. A category only shows up once it has at least one photo.
$categories = [
    'wedding'     => 'Wedding',
    'traditional' => 'Traditional',
    'portrait'    => 'Portrait',
    'events'      => 'Events',
    'cinematic'   => 'Cinematic',
];

// Photos uploaded through the admin panel come first, then whatever is sitting
// in the images/photos folders.
$byCat = [];
foreach ($db_gallery as $row) {
    $byCat[$row['category']][] = ['src' => $row['image_path'], 'label' => $row['title']];
}
foreach ($categories as $key => $label) {
    foreach (wc_photos($key) as $ph) $byCat[$key][] = $ph;
}

// Mix the categories so the "All Work" view isn't one long block of the same type.
$galleryItems = [];
$round = 0;
do {
    $added = false;
    foreach ($categories as $key => $label) {
        if (isset($byCat[$key][$round])) {
            $galleryItems[] = $byCat[$key][$round] + ['cat' => $key];
            $added = true;
        }
    }
    $round++;
} while ($added);

// Photos for the hero slideshow, the about section and the service cards.
$heroSlides = wc_photos('hero');
$aboutPhoto = wc_photos('about')[0]['src'] ?? 'images/about-studio.svg';
$svcPhoto1  = $byCat['portrait'][0]['src']  ?? 'images/portrait1.svg';
$svcPhoto2  = $byCat['cinematic'][1]['src'] ?? 'images/cinematic1.svg';
$svcPhoto3  = $byCat['cinematic'][4]['src'] ?? 'images/events1.svg';

$phoneLink = 'tel:+' . $site['phone_raw'];
$waLink    = 'https://wa.me/' . $site['phone_raw'] . '?text=' . rawurlencode("Hi Wings Cut, I'd like to know more about your packages.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Wings Cut Studio | Wedding Photography &amp; Cinematic Videography</title>
<meta name="description" content="Wings Cut Studio: wedding, portrait and event photography with cinematic videography. Call <?=htmlspecialchars($site['phone'])?> to book your date."/>
<meta name="theme-color" content="#8B0000"/>
<meta property="og:type" content="website"/>
<meta property="og:title" content="Wings Cut Studio | Photography &amp; Videography"/>
<meta property="og:description" content="Wedding, portrait and event photography with cinematic videography."/>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%238B0000'/%3E%3Ctext x='32' y='45' font-size='36' text-anchor='middle' fill='white' font-family='Georgia,serif' font-weight='700'%3EW%3C/text%3E%3C/svg%3E"/>
<script type="application/ld+json"><?=json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'LocalBusiness',
    'name'     => $site['name'],
    'telephone'=> '+' . $site['phone_raw'],
    'email'    => $site['email'],
    'description' => 'Wedding, portrait and event photography with cinematic videography.',
], JSON_UNESCAPED_SLASHES)?></script>
<link rel="stylesheet" href="css/style.css"/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<canvas id="polyCanvas"></canvas>
<div class="bg-canvas" id="bgCanvas">
  <div class="bg-shape s1"></div><div class="bg-shape s2"></div>
  <div class="bg-shape s3"></div><div class="bg-orb o1"></div><div class="bg-orb o2"></div>
</div>

<!-- NAVBAR -->
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

    <!-- Mobile overlay (closes drawer on tap) -->
    <div class="nav-overlay" id="navOverlay"></div>

    <ul class="nav-links" id="navLinks">
      <li><a href="#home"    class="nav-link active">Home</a></li>
      <li><a href="#about"   class="nav-link">About</a></li>
      <li><a href="#gallery" class="nav-link">Gallery</a></li>
      <li><a href="#pricing" class="nav-link">Pricing</a></li>
      <li><a href="#booking" class="nav-link">Booking</a></li>
      <li><a href="#contact" class="nav-link">Contact</a></li>
    </ul>

    <div class="nav-actions">
      <?php if($loggedIn): ?>
      <div style="position:relative;z-index:1500;">
        <button class="btn-nav-user" id="userMenuBtn" onclick="toggleUserMenu()">
          <span class="btn-user-av"><?=strtoupper(substr($userName,0,1))?></span>
          <span><?=htmlspecialchars(explode(' ',$userName)[0])?></span>
          <i class="fas fa-chevron-down" style="font-size:10px;opacity:0.6;"></i>
        </button>
        <div class="user-dropdown" id="userDropdown" style="position:absolute;top:calc(100% + 10px);right:0;min-width:220px;background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,0.09);box-shadow:0 16px 48px rgba(0,0,0,0.14);overflow:hidden;z-index:1500;display:none;">
          <div class="user-dd-header">
            <span class="user-dd-name"><?=htmlspecialchars($userName)?></span>
            <span class="user-dd-email"><?=htmlspecialchars($userEmail)?></span>
          </div>
          <button class="user-dd-item" onclick="openUserDashboard();toggleUserMenu()"><i class="fas fa-layout"></i> My Dashboard</button>
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
      <button class="hamburger" id="hamburger" aria-label="Menu" type="button">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<!-- HERO -->
<section id="home" class="hero-section">
  <div class="hero-video-wrap">
    <video autoplay muted loop playsinline class="hero-video" id="heroVideo">
      <source src="images/reels.mp4" type="video/mp4"/>
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
        <div class="hero-img-placeholder hero-slides">
          <div class="img-overlay-tag"><i class="fas fa-camera"></i> Featured Work</div>
          <?php if ($heroSlides): foreach ($heroSlides as $i => $slide): ?>
          <img class="hero-slide<?= $i === 0 ? ' active' : '' ?>" src="<?=$slide['src']?>" alt="Featured work by Wings Cut Studio"<?= $i > 0 ? ' loading="lazy"' : '' ?>/>
          <?php endforeach; else: ?>
          <img class="hero-slide active" src="images/wedding1.svg" alt="Featured work by Wings Cut Studio"/>
          <?php endif; ?>
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

<!-- SERVICES -->
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
        <div class="service-img-placeholder">
          <img src="<?=$svcPhoto1?>" alt="Photography" style="width:100%;height:100%;object-fit:cover" loading="lazy"/>
        </div>
        <h3>Photography</h3>
        <p>From intimate portraits to grand wedding ceremonies, every frame tells a story crafted with precision and artistic vision.</p>
        <a href="#gallery" class="service-link">Explore <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="service-card featured reveal-up" data-delay="150">
        <div class="service-badge">Most Popular</div>
        <div class="service-icon"><i class="fas fa-video"></i></div>
        <div class="service-img-placeholder">
          <img src="<?=$svcPhoto2?>" alt="Videography" style="width:100%;height:100%;object-fit:cover" loading="lazy"/>
        </div>
        <h3>Videography</h3>
        <p>Cinematic films that capture the emotion, energy, and essence of your most special moments in breathtaking 4K quality.</p>
        <a href="#gallery" class="service-link">Explore <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="service-card reveal-up" data-delay="300">
        <div class="service-icon"><i class="fas fa-film"></i></div>
        <div class="service-img-placeholder">
          <img src="<?=$svcPhoto3?>" alt="Editing" style="width:100%;height:100%;object-fit:cover" loading="lazy"/>
        </div>
        <h3>Editing &amp; Cinematics</h3>
        <p>Professional color grading, cinematic cuts, motion graphics, and soundtrack sync for a truly premium final product.</p>
        <a href="#gallery" class="service-link">Explore <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonial-strip">
  <div class="testi-track-wrap">
    <div class="testi-track" id="testiTrack">
      <?php
      $testimonials=[
        ["name"=>"Aditi Sharma","role"=>"Bride","text"=>"Wings Cut made our wedding look like a Bollywood film! Absolutely stunning work.","stars"=>5],
        ["name"=>"Rahul Verma","role"=>"Corporate Client","text"=>"Highly professional team. Delivered our product shoot well before deadline.","stars"=>5],
        ["name"=>"Priya Nair","role"=>"Portrait Client","text"=>"My portfolio shoot was magical. They knew exactly how to capture my best angles.","stars"=>5],
        ["name"=>"Karan Mehta","role"=>"Event Manager","text"=>"Used Wings Cut for 3 events now. Consistently brilliant results every time.","stars"=>5],
        ["name"=>"Sneha Kapoor","role"=>"Bride","text"=>"The cinematic reel they made for us brought tears to everyone's eyes. Priceless.","stars"=>5],
        ["name"=>"Vikram Iyer","role"=>"Artist","text"=>"My album cover shoot exceeded every expectation. These guys are true artists.","stars"=>5],
      ];
      foreach(array_merge($testimonials,$testimonials) as $t): ?>
      <div class="testi-card">
        <div class="testi-stars"><?php for($i=0;$i<$t['stars'];$i++) echo '<i class="fas fa-star"></i>'; ?></div>
        <p>"<?=htmlspecialchars($t['text'])?>"</p>
        <div class="testi-author">
          <div class="testi-avatar"><?=strtoupper(substr($t['name'],0,1))?></div>
          <div><strong><?=htmlspecialchars($t['name'])?></strong><small><?=htmlspecialchars($t['role'])?></small></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ABOUT -->
<section id="about" class="about-section">
  <div class="container">
    <div class="about-grid">
      <div class="about-img-col reveal-left">
        <div class="about-img-wrap">
          <div class="about-img-placeholder">
            <img src="<?=$aboutPhoto?>" alt="Wings Cut Studio"
                 style="width:100%;height:100%;object-fit:cover;display:block;"
                 onerror="this.style.display='none'"/>
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
          <li><i class="fas fa-check-circle"></i> State-of-the-art Sony &amp; Canon gear</li>
          <li><i class="fas fa-check-circle"></i> 48-hr preview turnaround guarantee</li>
          <li><i class="fas fa-check-circle"></i> Custom packages for every budget</li>
          <li><i class="fas fa-check-circle"></i> Post-production in-house studio</li>
        </ul>
        <a href="#booking" class="btn-primary ripple">Work With Us <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    <div class="workflow-strip reveal-up" data-delay="100">
      <h3>Our Workflow</h3>
      <div class="workflow-steps">
        <?php
        $steps=[["fas fa-comments","Consultation"],["fas fa-pencil-ruler","Planning"],["fas fa-camera","Shoot"],["fas fa-magic","Edit"],["fas fa-paper-plane","Delivery"]];
        foreach($steps as $i=>[$ic,$lb]): ?>
        <div class="workflow-step">
          <div class="step-icon"><i class="<?=$ic?>"></i></div>
          <span><?=$lb?></span>
          <?php if($i<4): ?><div class="step-connector"></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- GALLERY -->
<section id="gallery" class="gallery-section">
  <div class="container">
    <div class="section-header reveal-up">
      <span class="section-tag">Portfolio</span>
      <h2>Visual Stories</h2>
      <p>A curated glimpse into our finest work across every category</p>
    </div>
          <?php
      // Nothing uploaded and nothing in the photo folders yet: show the
      // illustrated sample scenes so the section never looks empty.
      if (!$galleryItems) {
          $galleryItems = [
              ['cat'=>'wedding',   'src'=>'images/wedding1.svg',   'label'=>'Wedding Ceremony'],
              ['cat'=>'portrait',  'src'=>'images/portrait1.svg',  'label'=>'Studio Portrait'],
              ['cat'=>'events',    'src'=>'images/events1.svg',    'label'=>'Corporate Event'],
              ['cat'=>'cinematic', 'src'=>'images/cinematic1.svg', 'label'=>'Cinematic Reel'],
          ];
          foreach ($galleryItems as $g) $byCat[$g['cat']][] = $g;
      }
      ?>
    <div class="gallery-filters reveal-up">
      <button class="filter-btn active" data-filter="all">All Work</button>
      <?php foreach ($categories as $key => $label): if (empty($byCat[$key])) continue; ?>
      <button class="filter-btn" data-filter="<?=$key?>"><?=$label?></button>
      <?php endforeach; ?>
    </div>
    <div class="masonry-grid" id="masonryGrid">
      <?php
      // Repeating size pattern gives the grid its uneven, magazine-style look.
      $sizes = ['tall', 'normal', 'wide', 'normal', 'normal', 'tall', 'normal', 'wide'];
      foreach ($galleryItems as $i => $item):
          $size  = $sizes[$i % count($sizes)];
          $cat   = htmlspecialchars($item['cat']);
          $label = htmlspecialchars($item['label']);
      ?>
      <div class="gallery-item <?=$size?>" data-category="<?=$cat?>">
        <img src="<?=htmlspecialchars($item['src'])?>" loading="lazy" alt="<?=$label?>"/>
        <div class="gallery-overlay">
          <div class="gallery-info">
            <span class="gallery-cat"><?=ucfirst($cat)?></span>
            <h4><?=$label?></h4>
          </div>
          <button class="gallery-zoom" data-label="<?=$label?>" aria-label="View larger"><i class="fas fa-expand"></i></button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox">
  <div class="lightbox-backdrop"></div>
  <div class="lightbox-content">
    <button class="lightbox-close"><i class="fas fa-times"></i></button>
    <button class="lightbox-prev"><i class="fas fa-chevron-left"></i></button>
    <div class="lightbox-img-wrap" style="position:relative;">
      <img id="lightboxImg" src="" alt=""/>
      <div class="lightbox-placeholder" id="lightboxPlaceholder"><i class="fas fa-image"></i></div>
      <div class="lightbox-label-bar">
        <span class="lb-cat-tag" id="lightboxCat"></span>
        <span id="lightboxLabel">Image</span>
      </div>
    </div>
    <button class="lightbox-next"><i class="fas fa-chevron-right"></i></button>
  </div>
</div>

<div id="customCursor"></div>
<div id="customCursorRing"></div>

<!-- AUTH GATE — premium split panel -->
<div class="auth-gate" id="authGate" <?=$loggedIn?'style="display:none"':''?>>
  <!-- LEFT: cinematic brand panel -->
  <div class="auth-left">
    <div class="auth-left-orb"></div>
    <div class="auth-left-content">
      <div class="auth-brand">
        <div class="auth-logo-icon"><i class="fas fa-camera-retro"></i></div>
        <div class="auth-logo-text">
          <span class="auth-brand-name">WINGS CUT</span>
          <span class="auth-brand-sub">STUDIO</span>
        </div>
      </div>
      <h1 class="auth-headline">Every moment<br>deserves to<br><em>live forever.</em></h1>
      <p class="auth-tagline">Professional photography &amp; videography for your most precious milestones in life.</p>
      <div class="auth-stats">
        <div class="auth-stat"><span>500+</span><small>Sessions</small></div>
        <div class="auth-stat"><span>98%</span><small>Happy Clients</small></div>
        <div class="auth-stat"><span>5★</span><small>Avg Rating</small></div>
      </div>
    </div>
    <!-- Cinematic gallery strip at bottom -->
    <div class="auth-gallery-strip">
      <img class="ag-img" src="images/wedding1.svg" alt=""/>
      <img class="ag-img" src="images/portrait1.svg" alt=""/>
      <img class="ag-img" src="images/events1.svg" alt=""/>
      <img class="ag-img" src="images/cinematic1.svg" alt=""/>
      <img class="ag-img" src="images/wedding2.svg" alt=""/>
    </div>
  </div>
  <!-- RIGHT: form panel -->
  <div class="auth-right">
    <div class="auth-card">
      <!-- Mobile: show logo since left panel hidden -->
      <div class="auth-mobile-logo">
        <div class="auth-logo-icon"><i class="fas fa-camera-retro"></i></div>
        <div class="auth-logo-text">
          <span class="auth-brand-name">WINGS CUT</span>
          <span class="auth-brand-sub">STUDIO</span>
        </div>
      </div>
      <div class="auth-tabs">
        <button class="auth-tab active" id="tabLogin" onclick="switchTab('login')">Sign In</button>
        <button class="auth-tab" id="tabSignup" onclick="switchTab('signup')">Create Account</button>
        <div class="auth-tab-indicator" id="authTabIndicator"></div>
      </div>
      <!-- LOGIN -->
      <div id="loginTab" class="auth-form-section">
        <p class="auth-form-sub">Welcome back! Sign in to continue.</p>
        <div class="auth-field"><i class="fas fa-envelope"></i><input type="email" id="loginEmail" placeholder="Email address" autocomplete="email"/></div>
        <div class="auth-field"><i class="fas fa-lock"></i><input type="password" id="loginPass" placeholder="Password" autocomplete="current-password"/><button class="auth-eye" onclick="togglePw('loginPass',this)" type="button"><i class="fas fa-eye"></i></button></div>
        <div class="auth-error" id="loginError"></div>
        <button class="auth-submit" id="loginBtn" onclick="doLogin()"><span>Sign In</span><i class="fas fa-arrow-right"></i></button>
      </div>
      <!-- SIGNUP -->
      <div id="signupTab" class="auth-form-section" style="display:none;">
        <p class="auth-form-sub">Join Wings Cut Studio today.</p>
        <div class="auth-field"><i class="fas fa-user"></i><input type="text" id="signupName" placeholder="Full name" autocomplete="name"/></div>
        <div class="auth-field"><i class="fas fa-envelope"></i><input type="email" id="signupEmail" placeholder="Email address" autocomplete="email"/></div>
        <div class="auth-field"><i class="fas fa-lock"></i><input type="password" id="signupPass" placeholder="Password (min 6 chars)" autocomplete="new-password"/><button class="auth-eye" onclick="togglePw('signupPass',this)" type="button"><i class="fas fa-eye"></i></button></div>
        <div class="auth-error" id="signupError"></div>
        <button class="auth-submit" id="signupBtn" onclick="doSignup()"><span>Create Account</span><i class="fas fa-arrow-right"></i></button>
      </div>
    </div>
  </div>
</div>

<!-- CHAT WIDGET (logged in only) -->
<?php if($loggedIn): ?>
<div id="chatWidget">
  <button class="chat-fab" onclick="toggleChat()" id="chatFab" title="Message Wings Cut">
    <i class="fas fa-comments" id="chatFabIcon"></i>
    <span class="chat-fab-badge" id="chatBadge" style="display:none;"></span>
  </button>
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
      <div class="chat-welcome">
        <div class="chat-welcome-av"><i class="fas fa-camera-retro"></i></div>
        <div class="chat-welcome-bubble">Hi <?=htmlspecialchars(explode(' ',$userName)[0])?>! 👋 Welcome to Wings Cut Studio. How can we help you today?</div>
      </div>
    </div>
    <div class="chat-win-input">
      <textarea id="chatInput" placeholder="Write a message..." rows="1" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
      <button class="chat-send-btn" onclick="sendChatMsg()"><i class="fas fa-paper-plane"></i></button>
    </div>
  </div>
</div>

<!-- USER DASHBOARD -->
<div id="userDashboard" class="user-dashboard-overlay" style="display:none">
  <header class="dash-header">
    <div class="dash-header-brand"><i class="fas fa-camera-retro"></i><span>WINGS CUT</span></div>
    <button class="dash-close-btn" onclick="closeUserDashboard()"><i class="fas fa-times"></i> Close</button>
  </header>
  <div class="dash-body">
    <div class="dash-welcome"><h2>Hello, <?=htmlspecialchars(explode(' ',$userName)[0])?>! 👋</h2><p>Manage your bookings and messages.</p></div>
    <div class="dash-grid">
      <div class="dash-card">
        <div class="dash-card-head"><h3><i class="fas fa-calendar-check"></i> My Bookings</h3></div>
        <div class="dash-card-body"><div id="dashBookingList"><p style="color:#aaa;font-size:13px;padding:10px"><i class="fas fa-spinner fa-spin"></i> Loading…</p></div></div>
      </div>
      <div class="dash-card messages-card">
        <div class="dash-card-head"><h3><i class="fas fa-comments"></i> Messages</h3><span style="font-size:11px;color:#aaa">Live</span></div>
        <div class="dash-card-body">
          <div class="dash-chat-messages" id="dashMsgList"></div>
          <div class="dash-chat-input-wrap">
            <textarea id="dashMsgInput" placeholder="Write a message to Wings Cut Studio…" rows="1" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,84)+'px'"></textarea>
            <button class="dash-send-btn" onclick="sendDashMsg()"><i class="fas fa-paper-plane"></i></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment & Delivery Section -->
    <div id="dashPaymentSection" style="margin-top:20px;display:none">
      <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#bbb;margin-bottom:14px;display:flex;align-items:center;gap:6px">
        <i class="fas fa-credit-card" style="color:var(--red)"></i> Payment &amp; Delivery
      </div>
      <div id="dashPaymentCards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px"></div>
    </div>

  </div>
</div>
<?php endif; ?>

<!-- PRICING -->
<section id="pricing" class="pricing-section">
  <div class="container">
    <div class="section-header reveal-up">
      <span class="section-tag">Investment</span>
      <h2>Choose Your Package</h2>
      <p>Transparent pricing crafted for every occasion and budget</p>
    </div>
    <div class="pricing-grid">
      <?php
      $packages=[
        ["name"=>"Essentials","price"=>"₹15,000","period"=>"per event","tag"=>"","color"=>"default","icon"=>"fas fa-seedling",
         "features"=>["4-hour photo coverage","1 professional photographer","200+ edited photos","Online gallery delivery","Basic color grading","1 revision round"],
         "not"=>["Video coverage","Cinematic reel","Same-day preview"]],
        ["name"=>"Signature","price"=>"₹35,000","period"=>"per event","tag"=>"Most Popular","color"=>"featured","icon"=>"fas fa-crown",
         "features"=>["8-hour full coverage","2 photographers + 1 videographer","500+ edited photos","Cinematic highlight reel (5–7 min)","Premium color grading","Same-day preview reel","2 revision rounds","Printed album (50 pages)"],
         "not"=>[]],
        ["name"=>"Elite","price"=>"₹65,000","period"=>"per event","tag"=>"","color"=>"default","icon"=>"fas fa-gem",
         "features"=>["Full-day coverage (12 hrs)","3 photographers + drone operator","1000+ edited photos","Feature-length cinematic film (20+ min)","4K video delivery","Drone aerial footage","Unlimited revisions","Luxury album + USB package","1-year cloud backup"],
         "not"=>[]],
      ];
      foreach($packages as $idx=>$pkg): ?>
      <div class="pricing-card <?=$pkg['color']?> reveal-up" data-delay="<?=$idx*150?>">
        <?php if($pkg['tag']): ?><div class="pricing-badge"><?=$pkg['tag']?></div><?php endif; ?>
        <div class="pricing-icon"><i class="<?=$pkg['icon']?>"></i></div>
        <h3><?=$pkg['name']?></h3>
        <div class="pricing-price"><span class="price-amount"><?=$pkg['price']?></span><span class="price-period"><?=$pkg['period']?></span></div>
        <ul class="pricing-features">
          <?php foreach($pkg['features'] as $f): ?><li class="included"><i class="fas fa-check"></i><?=$f?></li><?php endforeach; ?>
          <?php foreach($pkg['not'] as $f): ?><li class="excluded"><i class="fas fa-times"></i><?=$f?></li><?php endforeach; ?>
        </ul>
        <a href="#booking" class="btn-pricing ripple">Select Package <i class="fas fa-arrow-right"></i></a>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="pricing-note reveal-up"><i class="fas fa-info-circle"></i> All packages are customizable. <a href="#contact">Contact us</a> for tailored quotes.</p>
  </div>
</section>

<!-- BOOKING -->
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
        <div id="successMsg" style="display:none;text-align:center;padding:48px 30px;">
          <div style="font-size:60px;color:#22c55e;margin-bottom:16px;animation:popIn 0.5s cubic-bezier(0.34,1.56,0.64,1);"><i class="fas fa-check-circle"></i></div>
          <h3 style="font-size:22px;font-weight:700;margin-bottom:8px;">Booking Received! 🎉</h3>
          <p id="successName" style="font-size:16px;color:var(--red);font-weight:600;margin-bottom:8px;"></p>
          <p id="successId" style="font-size:13px;color:#888;margin-bottom:16px;letter-spacing:1px;"></p>
          <p style="color:#666;line-height:1.6;max-width:380px;margin:0 auto 20px;">We've received your request and will confirm within 24 hours.</p>
          <button onclick="resetBookingForm()" style="background:var(--red);color:white;border:none;padding:12px 28px;border-radius:99px;font-size:14px;font-weight:600;cursor:pointer;"><i class="fas fa-plus"></i> Make Another Booking</button>
        </div>
        <form class="booking-form" id="bookingForm" novalidate>
          <div class="form-row">
            <div class="form-group"><label for="name">Full Name *</label><input type="text" id="name" name="name" placeholder="Your full name" required/><span class="form-error" id="nameError"></span></div>
            <div class="form-group"><label for="email">Email Address *</label><input type="email" id="email" name="email" placeholder="your@email.com" required/><span class="form-error" id="emailError"></span></div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="phone">Phone Number *</label>
              <div class="phone-wrap">
                <select id="countryCode" name="country_code" class="country-code-select">
                  <option value="+91">🇮🇳 +91</option><option value="+1">🇺🇸 +1</option>
                  <option value="+44">🇬🇧 +44</option><option value="+971">🇦🇪 +971</option>
                  <option value="+60">🇲🇾 +60</option><option value="+94">🇱🇰 +94</option>
                </select>
                <input type="tel" id="phone" name="phone" placeholder="9876543210" required maxlength="10" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"/>
              </div>
              <span class="form-error" id="phoneError"></span>
            </div>
            <div class="form-group"><label for="package">Select Package *</label><select id="package" name="package" required><option value="">Choose a package</option><option value="essentials">Essentials — ₹15,000</option><option value="signature">Signature — ₹35,000</option><option value="elite">Elite — ₹65,000</option><option value="custom">Custom Package</option></select><span class="form-error" id="packageError"></span></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label for="date">Preferred Date *</label><input type="date" id="date" name="date" required onchange="checkSlotAvailability()"/><span class="form-error" id="dateError"></span></div>
            <div class="form-group"><label for="event_type">Event Type</label><select id="event_type" name="event_type"><option value="">Select event type</option><option value="wedding">Wedding</option><option value="portrait">Portrait / Fashion</option><option value="corporate">Corporate / Product</option><option value="birthday">Birthday / Celebration</option><option value="other">Other</option></select></div>
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
            <div class="form-group"><label for="venue">Event Venue / Address</label><input type="text" id="venue" name="venue" placeholder="Hall or venue name, city"/></div>
          </div>
          <div class="form-group full"><label for="message">Additional Message</label><textarea id="message" name="message" rows="4" placeholder="Tell us more about your event, vision, or any special requirements..."></textarea></div>
          <input type="hidden" name="csrf_token" value="<?=bin2hex(random_bytes(16))?>"/>
          <button type="submit" class="btn-primary btn-book ripple"><span>Confirm Booking</span> <i class="fas fa-calendar-check"></i></button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- CONTACT -->
<section id="contact" class="contact-section">
  <div class="container">
    <div class="section-header reveal-up">
      <span class="section-tag">Get In Touch</span>
      <h2>Let's Talk</h2>
      <p>Have a question or want to discuss your project? We'd love to hear from you.</p>
    </div>
    <div class="contact-grid">
      <div class="contact-form-col reveal-left" data-delay="0">
        <form class="contact-form" id="contactForm" novalidate>
          <div class="form-row">
            <div class="form-group"><label>Name *</label><input type="text" name="c_name" placeholder="Your name" required/></div>
            <div class="form-group"><label>Email *</label><input type="email" name="c_email" placeholder="your@email.com" required/></div>
          </div>
          <div class="form-group full"><label>Subject</label><input type="text" name="c_subject" placeholder="What's this about?"/></div>
          <div class="form-group full"><label>Message *</label><textarea name="c_message" rows="5" placeholder="Your message..." required></textarea></div>
          <button type="submit" class="btn-primary ripple">Send Message <i class="fas fa-paper-plane"></i></button>
        </form>
      </div>
      <div class="contact-info-col reveal-right" data-delay="150">
        <div class="contact-info-card">
          <?php if ($site['address']): ?>
          <div class="contact-detail"><div class="cd-icon"><i class="fas fa-map-marker-alt"></i></div><div><strong>Studio Address</strong><p><?=nl2br(htmlspecialchars($site['address']))?></p></div></div>
          <?php endif; ?>
          <div class="contact-detail"><div class="cd-icon"><i class="fas fa-phone"></i></div><div><strong>Phone</strong><p><a href="<?=$phoneLink?>"><?=htmlspecialchars($site['phone'])?></a></p></div></div>
          <div class="contact-detail"><div class="cd-icon"><i class="fas fa-envelope"></i></div><div><strong>Email</strong><p><a href="mailto:<?=htmlspecialchars($site['email'])?>"><?=htmlspecialchars($site['email'])?></a></p></div></div>
          <div class="contact-detail"><div class="cd-icon"><i class="fas fa-clock"></i></div><div><strong>Hours</strong><p><?=htmlspecialchars($site['hours'])?></p></div></div>
          <a href="<?=$waLink?>" target="_blank" rel="noopener" class="whatsapp-btn ripple"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
          <?php if ($site['instagram'] || $site['facebook'] || $site['youtube']): ?>
          <div class="social-icons">
            <?php if ($site['instagram']): ?><a href="<?=htmlspecialchars($site['instagram'])?>" target="_blank" rel="noopener" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
            <?php if ($site['facebook']):  ?><a href="<?=htmlspecialchars($site['facebook'])?>"  target="_blank" rel="noopener" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
            <?php if ($site['youtube']):   ?><a href="<?=htmlspecialchars($site['youtube'])?>"   target="_blank" rel="noopener" class="social-icon" aria-label="YouTube"><i class="fab fa-youtube"></i></a><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php if ($site['map_embed']): ?>
        <div class="map-card"><iframe src="<?=htmlspecialchars($site['map_embed'])?>" width="100%" height="220" style="border:0;border-radius:14px" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Studio location"></iframe></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-top"><div class="container"><div class="footer-grid">
    <div class="footer-brand">
      <div class="footer-logo"><i class="fas fa-camera-retro"></i><div><span class="footer-logo-title">WINGS CUT</span><span class="footer-logo-sub">STUDIO</span></div></div>
      <p>We craft visual stories that outlast time. Every frame is a testament to craftsmanship, passion, and purpose.</p>
      <?php if ($site['instagram'] || $site['facebook'] || $site['youtube']): ?>
      <div class="footer-social">
        <?php if ($site['instagram']): ?><a href="<?=htmlspecialchars($site['instagram'])?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
        <?php if ($site['facebook']):  ?><a href="<?=htmlspecialchars($site['facebook'])?>"  target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
        <?php if ($site['youtube']):   ?><a href="<?=htmlspecialchars($site['youtube'])?>"   target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="#home">Home</a></li><li><a href="#about">About Us</a></li><li><a href="#gallery">Gallery</a></li><li><a href="#pricing">Pricing</a></li><li><a href="#booking">Book Now</a></li></ul></div>
    <div class="footer-col"><h4>Services</h4><ul><li><a href="#gallery">Wedding Photography</a></li><li><a href="#gallery">Portrait Sessions</a></li><li><a href="#booking">Corporate Events</a></li><li><a href="#gallery">Cinematic Films</a></li><li><a href="#pricing">Drone Filming</a></li></ul></div>
    <div class="footer-col"><h4>Contact</h4><ul class="footer-contact-list">
      <?php if ($site['address']): ?><li><i class="fas fa-map-marker-alt"></i><?=htmlspecialchars($site['address'])?></li><?php endif; ?>
      <li><i class="fas fa-phone"></i><a href="<?=$phoneLink?>"><?=htmlspecialchars($site['phone'])?></a></li>
      <li><i class="fas fa-envelope"></i><a href="mailto:<?=htmlspecialchars($site['email'])?>"><?=htmlspecialchars($site['email'])?></a></li>
    </ul></div>
  </div></div></div>
  <div class="footer-bottom"><div class="container"><p>&copy; <?=date('Y')?> WINGS CUT Studio. All rights reserved. Crafted with <i class="fas fa-heart"></i> for visual storytelling.</p></div></div>
</footer>

<button class="back-to-top" id="backToTop"><i class="fas fa-chevron-up"></i></button>
<div class="toast" id="toast"></div>

<script src="js/script.js"></script>
<script>
function checkSlotAvailability() {
  const d=document.getElementById('date'), s=document.getElementById('time_slot'),
        st=document.getElementById('slotStatus'), se=document.getElementById('slotError');
  if(!d||!s||!st) return;
  if(!d.value||!s.value){st.textContent='';return;}
  st.textContent='Checking availability...'; st.style.color='#888';
  fetch('php/check_slot.php?date='+encodeURIComponent(d.value)+'&slot='+encodeURIComponent(s.value))
    .then(r=>r.json()).then(data=>{
      if(data.available){st.textContent='✓ '+data.remaining+' slot'+(data.remaining>1?'s':'')+' available';st.style.color='#16a34a';if(se)se.textContent='';s.style.borderColor='';}
      else{st.textContent='✗ Session fully booked. Try another date or time.';st.style.color='#dc2626';if(se)se.textContent='Session full';s.style.borderColor='#dc2626';}
    }).catch(()=>{st.textContent='';});
}
</script>
</body>
</html>
