-- ============================================================
-- WINGS CUT — MySQL Database Schema  (v2 — full reset)
-- Run this in phpMyAdmin → wingscut_db → Import
-- ============================================================

CREATE DATABASE IF NOT EXISTS wingscut_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE wingscut_db;

-- Drop tables if resetting
DROP TABLE IF EXISTS gallery;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS testimonials;

-- ============================================================
-- TABLE: bookings
-- ============================================================
CREATE TABLE bookings (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    email         VARCHAR(150)    NOT NULL,
    phone         VARCHAR(20)     NOT NULL,
    package       VARCHAR(50)     NOT NULL,
    booking_date  DATE            NOT NULL,
    event_type    VARCHAR(50)     DEFAULT NULL,
    venue         VARCHAR(300)    DEFAULT NULL,
    time_slot     ENUM('morning','evening') DEFAULT NULL,
    message       TEXT            DEFAULT NULL,
    status        ENUM('pending','confirmed','cancelled','completed')
                                  NOT NULL DEFAULT 'pending',
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email        (email),
    INDEX idx_booking_date (booking_date),
    INDEX idx_status       (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: contact_messages
-- ============================================================
CREATE TABLE contact_messages (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL,
    subject    VARCHAR(200)  DEFAULT 'General Inquiry',
    message    TEXT          NOT NULL,
    is_read    TINYINT(1)    NOT NULL DEFAULT 0,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email   (email),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: gallery
-- ============================================================
CREATE TABLE gallery (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    title      VARCHAR(200)    NOT NULL,
    category   ENUM('wedding','portrait','events','cinematic')
                               NOT NULL DEFAULT 'portrait',
    image_path VARCHAR(300)    NOT NULL,
    is_active  TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order INT             NOT NULL DEFAULT 0,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_category  (category),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: testimonials
-- ============================================================
CREATE TABLE testimonials (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)  NOT NULL,
    role       VARCHAR(100)  DEFAULT NULL,
    content    TEXT          NOT NULL,
    stars      TINYINT       NOT NULL DEFAULT 5,
    is_active  TINYINT(1)    NOT NULL DEFAULT 1,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Wings Cut database ready!' AS message;
