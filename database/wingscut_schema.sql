-- Wings Cut Studio - full database schema for a FRESH install.
--
-- Already running the site on an existing database? Don't run this file.
-- Run wingscut_payment_migration.sql (in this same folder) instead; it only
-- adds the payment and delivery columns.
--
-- Written from the columns the PHP code reads and writes. I haven't been able
-- to run it against a live MySQL server, so if phpMyAdmin complains, the error
-- message will say which line.

CREATE DATABASE IF NOT EXISTS wingscut_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wingscut_db;

CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  password   VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id            INT UNSIGNED DEFAULT NULL,
  name               VARCHAR(100) NOT NULL,
  email              VARCHAR(150) NOT NULL,
  phone              VARCHAR(30)  NOT NULL,
  package            VARCHAR(50)  NOT NULL,
  booking_date       DATE NOT NULL,
  event_type         VARCHAR(50)  DEFAULT NULL,
  venue              VARCHAR(300) DEFAULT NULL,
  time_slot          VARCHAR(20)  DEFAULT NULL,
  message            TEXT DEFAULT NULL,
  status             ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  total_amount       DECIMAL(10,2) DEFAULT 0.00,
  advance_paid       DECIMAL(10,2) DEFAULT 0.00,
  pending_amount     DECIMAL(10,2) DEFAULT 0.00,
  payment_status     ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
  payment_screenshot VARCHAR(300) DEFAULT NULL,
  payment_verified   TINYINT(1) DEFAULT 0,
  payment_note       TEXT DEFAULT NULL,
  delivery_file      VARCHAR(300) DEFAULT NULL,
  delivery_status    ENUM('processing','editing','ready','delivered') DEFAULT 'processing',
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  KEY idx_email (email),
  KEY idx_date (booking_date),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  subject    VARCHAR(200) DEFAULT NULL,
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title      VARCHAR(150) NOT NULL,
  category   VARCHAR(50)  NOT NULL,
  image_path VARCHAR(300) NOT NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  sender     ENUM('user','admin') NOT NULL DEFAULT 'user',
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_history (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id   INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED DEFAULT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  payment_type ENUM('advance','full','partial') DEFAULT 'advance',
  screenshot   VARCHAR(300) DEFAULT NULL,
  verified     TINYINT(1) DEFAULT 0,
  verified_by  VARCHAR(100) DEFAULT NULL,
  verified_at  DATETIME DEFAULT NULL,
  note         TEXT DEFAULT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_booking (booking_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
