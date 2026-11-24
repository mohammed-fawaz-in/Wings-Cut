USE wingscut_db;

ALTER TABLE bookings
  ADD COLUMN IF NOT EXISTS total_amount       DECIMAL(10,2) DEFAULT 0.00         AFTER status,
  ADD COLUMN IF NOT EXISTS advance_paid       DECIMAL(10,2) DEFAULT 0.00         AFTER total_amount,
  ADD COLUMN IF NOT EXISTS pending_amount     DECIMAL(10,2) DEFAULT 0.00         AFTER advance_paid,
  ADD COLUMN IF NOT EXISTS payment_status     ENUM('unpaid','partial','paid') DEFAULT 'unpaid' AFTER pending_amount,
  ADD COLUMN IF NOT EXISTS payment_screenshot VARCHAR(300)  DEFAULT NULL          AFTER payment_status,
  ADD COLUMN IF NOT EXISTS payment_verified   TINYINT(1)    DEFAULT 0             AFTER payment_screenshot,
  ADD COLUMN IF NOT EXISTS payment_note       TEXT          DEFAULT NULL          AFTER payment_verified,
  ADD COLUMN IF NOT EXISTS delivery_file      VARCHAR(300)  DEFAULT NULL          AFTER payment_note,
  ADD COLUMN IF NOT EXISTS delivery_status    ENUM('processing','editing','ready','delivered') DEFAULT 'processing' AFTER delivery_file;

CREATE TABLE IF NOT EXISTS payment_history (
  id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  booking_id   INT UNSIGNED  NOT NULL,
  user_id      INT UNSIGNED  DEFAULT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  payment_type ENUM('advance','full','partial') DEFAULT 'advance',
  screenshot   VARCHAR(300)  DEFAULT NULL,
  verified     TINYINT(1)    DEFAULT 0,
  verified_by  VARCHAR(100)  DEFAULT NULL,
  verified_at  DATETIME      DEFAULT NULL,
  note         TEXT          DEFAULT NULL,
  created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_booking (booking_id),
  INDEX idx_user    (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Migration complete!' AS result;
