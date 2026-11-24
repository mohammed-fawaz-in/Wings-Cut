# Wings Cut Studio

Website for a photography and videography studio. There's a public site
(gallery, pricing, booking, contact), a login for customers (dashboard, chat,
payments, file downloads) and an admin panel for running it all.

Built with plain PHP, MySQL, JavaScript and CSS. No framework. It's meant to
run on XAMPP.

## Running it on your computer

1. Install XAMPP and start **Apache** and **MySQL** from its control panel.
2. Copy this folder to `C:\xampp\htdocs\wingscut`. (From GitHub: run
   `git clone <repo-url> wingscut` inside `C:\xampp\htdocs`.)
3. Open phpMyAdmin and import one SQL file:
   - brand new database: `database/wingscut_schema.sql`
   - already have the site's database: `database/wingscut_payment_migration.sql`
     (it only adds the payment and delivery columns)
4. Visit `http://localhost/wingscut/`. The admin panel is at
   `http://localhost/wingscut/admin/login.php`.

Database settings are in `php/db.php`. The defaults (user `root`, empty
password) are what XAMPP ships with.

## Putting your own details in

Everything about the business is in the `$site` array at the top of
`index.php`: phone, email, address, opening hours, Instagram/Facebook/YouTube
links and a Google Maps embed. Anything you leave empty is hidden instead of
showing a blank row. Phone and email are already filled in.

Two payment settings live elsewhere:

- `UPI_ID` near the bottom of `js/script.js`: your real UPI id.
- `images/upi-qr.png`: a screenshot of your UPI QR code. Until it exists the
  page shows a plain QR icon.

## Adding photos

You don't need to edit any code. The page reads these folders:

| Folder                      | Where it shows up                          |
|-----------------------------|--------------------------------------------|
| `images/photos/wedding/`    | gallery, "Wedding" filter                  |
| `images/photos/traditional/`| gallery, "Traditional" filter              |
| `images/photos/portrait/`   | gallery, "Portrait" filter, Photography card |
| `images/photos/events/`     | gallery, "Events" filter                   |
| `images/photos/cinematic/`  | gallery, "Cinematic" filter, Videography card |
| `images/photos/hero/`       | the slideshow in the top section           |
| `images/photos/about/`      | the picture in the About section           |

A filter button only appears once its folder has a photo in it. The file name
becomes the caption, so `ravi-and-priya-haldi.jpg` is shown as "Ravi And Priya
Haldi". Resize big camera files to around 1600px wide first or the page gets
slow.

Photos uploaded through Admin -> Gallery are shown first, ahead of the folders.

The photos currently in the folders are placeholders, see
`images/photos/SOURCES.txt` for where they came from. Swap in your own work
before showing the site to real clients.

The hero video is not included. Put your reel at `images/reels.mp4` and it will
play behind the top section.

## Payments and delivery

1. A customer books a session.
2. In admin go to **Payments**, open the booking, and set the total amount and
   any advance already received.
3. The customer sees the amounts on their dashboard, pays by UPI, taps
   "I Have Paid" and uploads a screenshot.
4. In admin, check the screenshot and approve or reject it. Approving adds the
   amount to the advance and works out the pending balance.
5. When the edit is done, upload the ZIP under Client Delivery and set the
   status to Ready.
6. The customer gets a download button. Files are served through
   `php/delivery.php`, which checks the logged-in user owns that booking, so
   nobody can guess a link to someone else's files.

Delivery ZIPs are big. PHP's default upload limit is a couple of MB, so raise
`upload_max_filesize` and `post_max_size` in `php.ini` (XAMPP: Config button
next to Apache), then restart Apache.

## Admin password

The admin password is stored as a hash in `admin/includes/pw.php`. To change
it, generate a new hash and paste it in:

    php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"

Do this before the site is reachable by anyone else.

## Where things are

- `index.php`: the public page. Config, gallery and hero data are built at the
  top, the HTML follows.
- `css/style.css`, `js/script.js`: styling and behaviour for the public page.
- `php/`: small JSON endpoints the page talks to (booking, contact, chat,
  login, payments, delivery).
- `admin/`: the admin panel. Each page is one PHP file. `admin/ajax/` holds the
  endpoints for live chat.
- `uploads/`: payment screenshots and delivery ZIPs. Has an `.htaccess` that
  stops people browsing it directly.

## Before it goes public

- [ ] Change the admin password (above)
- [ ] Give the MySQL user a password and update `php/db.php`
- [ ] Replace the placeholder photos with your own
- [ ] The numbers (350+ projects, 120+ clients, awards) and the six
      testimonials in `index.php` are sample text. Replace them with real
      ones or remove them.
- [ ] Fill in address and social links in `$site`
- [ ] Check the prices and package contents in the pricing section
- [ ] Add your UPI QR image and UPI id
- [ ] Use HTTPS once it's on a real host

## What has and hasn't been tested

PHP files were syntax-checked and the main page was rendered end to end to
check the gallery, slideshow and contact details. The booking, payment and
delivery flow has not been run against a live database, so go through it once
by hand: book, set amounts, upload a screenshot, approve, upload a ZIP,
download it.
