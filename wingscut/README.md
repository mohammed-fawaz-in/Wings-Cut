# WINGS CUT — Photography & Videography Website
## Mohammed Fawaz

A fully responsive, production-grade photography and videography studio website
built with PHP, MySQL, HTML5, CSS3, and Vanilla JavaScript.

---

## 📁 File Structure

```
wingscut/
├── index.php               ← Main website (all sections)
├── .htaccess               ← Apache security & performance config
├── css/
│   └── style.css           ← Complete master stylesheet
├── js/
│   └── script.js           ← All interactivity & animations
├── php/
│   ├── db.php              ← Database connection (PDO)
│   ├── booking.php         ← Booking form backend
│   └── contact.php         ← Contact form backend (AJAX)
├── database/
│   └── schema.sql          ← MySQL table definitions
└── images/                 ← Add your media files here
    ├── hero-reel.mp4       ← Hero background video (optional)
    └── (your images)
```

---

## ⚙️ Setup Instructions

### 1. Requirements
- PHP 7.4+ (PHP 8.x recommended)
- MySQL 5.7+ / MariaDB 10.3+
- Apache web server (XAMPP, WAMP, LAMP, or live hosting)

### 2. Database Setup
```bash
# Option A: Command line
mysql -u root -p < database/schema.sql

# Option B: phpMyAdmin
# 1. Open phpMyAdmin
# 2. Create a new database named: wingscut_db
# 3. Click "Import" and upload database/schema.sql
```

### 3. Configure Database Credentials
Edit `php/db.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'wingscut_db');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
```

### 4. Deploy to XAMPP (local)
1. Copy the entire `wingscut/` folder to `C:/xampp/htdocs/`
2. Start Apache and MySQL in XAMPP Control Panel
3. Visit: `http://localhost/wingscut/`

### 5. Add Your Media
- Place your hero video at: `images/hero-reel.mp4`
- Replace placeholder divs with `<img>` tags pointing to your images
- Recommended image sizes:
  - Hero: 1600×900px
  - Gallery: 800×600px (tall: 800×1000px)
  - About/Team: 800×1000px

---

## 🎨 Customization

### Colors (css/style.css)
```css
:root {
  --red:       #8B0000;   /* Primary accent */
  --white:     #ffffff;
  --grey-lt:   #EEEEEE;
}
```

### Studio Info (index.php)
Search and replace:
- `WINGS CUT` → Your studio name
- `+91 98765 43210` → Your phone
- `hello@wingscut.studio` → Your email
- `123 Creative Hub, Koregaon Park, Pune` → Your address
- WhatsApp link: Update the phone number in the `wa.me` URL

### Google Maps (index.php)
Find the map section and replace the placeholder with your actual embed:
```html
<iframe src="https://www.google.com/maps/embed?pb=YOUR_EMBED_URL"
        width="100%" height="220" style="border:0;"
        allowfullscreen="" loading="lazy"></iframe>
```

### Pricing (index.php)
Update the `$packages` array with your actual pricing.

---

## 🔧 Features

| Feature | Status |
|---------|--------|
| Animated loading screen | ✅ |
| Glass-style navbar with scroll effect | ✅ |
| Hero section with video background | ✅ |
| Scroll-triggered reveal animations | ✅ |
| Auto-scrolling testimonial strip | ✅ |
| Masonry gallery with filter | ✅ |
| Lightbox image viewer | ✅ |
| Pricing cards (3 tiers) | ✅ |
| Booking form with JS validation | ✅ |
| PHP + MySQL booking storage | ✅ |
| AJAX contact form | ✅ |
| WhatsApp quick button | ✅ |
| Animated background shapes | ✅ |
| Counter animations | ✅ |
| Ripple button effects | ✅ |
| Back-to-top button | ✅ |
| Fully responsive (mobile/tablet/desktop) | ✅ |
| Security headers (.htaccess) | ✅ |

---

## 📧 Email Setup (Optional)
To enable confirmation emails, uncomment the `mail()` calls in:
- `php/booking.php` (line with `@mail`)
- `php/contact.php` (line with `@mail`)

And configure your server's mail settings (SMTP recommended for production).

---

## 🎓  Project Info
- **Project Name:** WINGS CUT — Photography & Videography Studio Website
- **Tech Stack:** HTML5, CSS3, JavaScript (Vanilla), PHP 8, MySQL
- **Fonts:** Cormorant Garamond (Display) + Outfit (Body)
- **Icons:** Font Awesome 6
- **Animations:** Pure CSS + IntersectionObserver API

---

*Built with passion for visual storytelling.*
