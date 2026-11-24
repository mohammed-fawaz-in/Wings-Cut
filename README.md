# Wings Cut Studio

A full-stack Photography and Videography Studio Management System featuring a public portfolio website, a customer portal, and an admin dashboard.

## Features
- **Public Website:** Dynamic photo gallery with category filters, pricing packages, online session booking, and contact form.
- **Customer Portal:** Secure login to track bookings, chat with the studio, submit UPI payments, and download final edited ZIP deliverables.
- **Admin Dashboard:** Manage bookings, gallery uploads, client chat, payment approvals, and file deliveries.

## Tech Stack
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Backend:** Core PHP
- **Database:** MySQL

## Local Setup (XAMPP)
1. Install **XAMPP** and start **Apache** and **MySQL** from the control panel.
2. Place this project folder inside `C:\xampp\htdocs\wingscut`.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), create a database, and import `database/wingscut_schema.sql`.
4. Verify database credentials in `php/db.php` (defaults to `root` with an empty password).
5. Visit `http://localhost/wingscut/` in your browser (Admin panel: `http://localhost/wingscut/admin/login.php`).
