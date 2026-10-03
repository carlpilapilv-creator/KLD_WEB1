-- ==========================================================================
-- KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
-- schema.sql — Full schema + seed data
-- ==========================================================================
--
-- USAGE:
--   Fresh install → run this entire file.
--   Existing DB   → skip to "MIGRATION" section at the bottom.
--
-- ==========================================================================

-- ─── DATABASE ───────────────────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `kld_facility_reservation`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kld_facility_reservation`;

-- ─── TABLE 1: users ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `id_number`      VARCHAR(50)  UNIQUE NOT NULL,
    `fullname`       VARCHAR(150) NOT NULL,
    `email`          VARCHAR(150) UNIQUE NOT NULL,
    `contact`        VARCHAR(30)  DEFAULT NULL,
    `department`     VARCHAR(100) DEFAULT NULL,
    `role`           ENUM('Student','Faculty','Student Organization','Admin') DEFAULT 'Student',
    `access_level`   ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user',
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `password_hash`  VARCHAR(255) NOT NULL,
    `archived_at`    DATETIME DEFAULT NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE 2: reservations ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reservations` (
    `id`             VARCHAR(30)  PRIMARY KEY,
    `user_id`        INT          NOT NULL,
    `facility_id`    VARCHAR(50)  NOT NULL,
    `facility_name`  VARCHAR(150) NOT NULL,
    `event_name`     VARCHAR(200) DEFAULT NULL,
    `purpose`        TEXT,
    `booking_date`   DATE         NOT NULL,
    `time_slot`      VARCHAR(50)  NOT NULL,
    `attendees`      INT          DEFAULT 0,
    `equipment`      TEXT,
    `status`         ENUM('Pending','Approved','Rejected','Completed','Cancelled') DEFAULT 'Pending',
    `archived_at`    DATETIME DEFAULT NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE 3: user_preferences ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_preferences` (
    `user_id`                  INT PRIMARY KEY,
    `email_new_reservation`    TINYINT(1) DEFAULT 1,
    `email_status_update`      TINYINT(1) DEFAULT 1,
    `email_reminders`          TINYINT(1) DEFAULT 1,
    `system_new_reservation`   TINYINT(1) DEFAULT 1,
    `system_status_update`     TINYINT(1) DEFAULT 1,
    `updated_at`               TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE 4: password_resets ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT          NOT NULL,
    `token`       VARCHAR(255) NOT NULL,
    `expires_at`  DATETIME     NOT NULL,
    `used`        TINYINT(1)   DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE 5: system_config ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `system_config` (
    `config_key`    VARCHAR(50) PRIMARY KEY,
    `config_value`  VARCHAR(255) NOT NULL,
    `updated_by`    INT DEFAULT NULL,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE 6: facilities ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `facilities` (
    `id`            VARCHAR(50) PRIMARY KEY,
    `donor`         VARCHAR(150) DEFAULT NULL,
    `name`          VARCHAR(150) NOT NULL,
    `category`      VARCHAR(50)  NOT NULL,
    `capacity`      VARCHAR(50)  NOT NULL,
    `location`      VARCHAR(150) NOT NULL,
    `equipment`     VARCHAR(150) DEFAULT NULL,
    `image`         VARCHAR(100) DEFAULT 'assets/images/campus_bg.png',
    `status`        ENUM('Available','Booked','Under Maintenance') DEFAULT 'Available',
    `description`   TEXT,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE 7: otp_codes (NEW) ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT          NOT NULL,
    `code_hash`   VARCHAR(255) NOT NULL,
    `purpose`     ENUM('signup','login','reset') NOT NULL,
    `expires_at`  DATETIME     NOT NULL,
    `used`        TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE 8: audit_logs (NEW) ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT          DEFAULT NULL,
    `action`      VARCHAR(255) NOT NULL,
    `ip_address`  VARCHAR(45)  NOT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SEED: System Config ────────────────────────────────────────────────────
INSERT IGNORE INTO `system_config` (`config_key`, `config_value`) VALUES
    ('maintenance_mode', '0'),
    ('slot_buffer_hrs', '1'),
    ('max_advance_days', '30');

-- ─── SEED: Facilities (13 Official KLD Facilities) ─────────────────────────
INSERT IGNORE INTO `facilities` (`id`, `donor`, `name`, `category`, `capacity`, `location`, `equipment`, `image`, `status`, `description`) VALUES
    ('anatomy-lab', 'HAUSLAND CONSTRUCTION', 'Anatomy and Physiology Laboratory', 'labs', '45 Students', 'Health Sciences Wing, Ground Floor', 'Anatomical Models', 'assets/images/campus_bg.png', 'Available', 'Equipped with anatomical models, skeletal specimens, and dissection tools for health science students.'),
    ('nutrition-lab', 'TYLER JEREMY REMINAJES SANTOS', 'Institute of Nursing Nutrition Laboratory', 'nursing', '40 Students', 'Nursing Building, 2nd Floor', 'Nutrition Tools', 'assets/images/campus_bg.png', 'Available', 'Specialized lab for dietary assessment, nutrition planning, and food safety education for nursing students.'),
    ('microbiology-lab', 'FAMILY OF ENGR. SONNY BOY V. BAGANG & MA. VICTORIA GALANG BAGANG', 'Microbiology Laboratory', 'labs', '40 Students', 'Health Sciences Wing, 2nd Floor', 'Biosafety Equipment', 'assets/images/campus_bg.png', 'Available', 'Research-grade microbiology laboratory with biosafety equipment, microscopes, and culture incubation chambers.'),
    ('nursing-skills-lab', 'PRINCIPIA HUMILIA CORP.', 'Nursing Skills Laboratory', 'nursing', '35 Students', 'Nursing Building, Ground Floor', 'Simulation Manikins', 'assets/images/campus_bg.png', 'Available', 'Clinical simulation lab with hospital-grade manikins, patient care stations, and vital signs monitoring equipment.'),
    ('nursing-lecture', 'NORTHPINE LAND, INC. Hall', 'Nursing Lecture Hall', 'lecture', '140 Persons', 'Nursing Building, 1st Floor', 'Smart Board', 'assets/images/CB2_AVR_2.png', 'Available', 'Dedicated amphitheater-style lecture hall for clinical demonstrations, case presentations, and health science symposiums.'),
    ('avr', 'MANNY VILLAR HALL', 'Audio Visual Room', 'avr', '180 Persons', 'Main Academic Building, 2nd Floor', '4K Projector', 'assets/images/avr.png', 'Available', 'Fully equipped audio-visual amphitheater for seminars, film screenings, academic presentations, and institutional lectures.'),
    ('physics-lab', 'ARTURO CARUNGCONG', 'Physics Laboratory', 'labs', '40 Students', 'Science Building, 3rd Floor', 'Experiment Stations', 'assets/images/campus_bg.png', 'Available', 'Fully equipped physics laboratory with experiment stations, oscilloscopes, and mechanics demonstration apparatus.'),
    ('computer-lab', 'ATTY. LOURDES GANA-BARZAGA', 'Computer Laboratory', 'labs', '50 Workstations', 'Institute of Computing Studies, 3rd Floor', 'Gigabit LAN', 'assets/images/comlab.png', 'Available', 'Modern ICT laboratory with high-performance workstations, gigabit LAN infrastructure, and full software development environments.'),
    ('midwifery-lab', 'CONG. ELPIDIO "PIDI" BARZAGA', 'Institute of Midwifery Skills Laboratory', 'nursing', '30 Students', 'Nursing Building, Ground Floor', 'Birthing Manikins', 'assets/images/campus_bg.png', 'Available', 'Dedicated clinical simulation lab for midwifery students featuring birthing manikins and maternal care training equipment.'),
    ('biology-lab', 'IEMI – GLORIA E. MENDOZA', 'Biology Laboratory', 'labs', '40 Students', 'Science Building, 2nd Floor', 'Microscopes', 'assets/images/campus_bg.png', 'Available', 'Standard biological laboratory equipped with compound microscopes, specimen slides, and botanical testing kits.'),
    ('psychology-lab', 'FRIENDS OF CONG. PIDI BARZAGA', 'Psychology Laboratory', 'labs', '35 Students', 'Academic Hall, 3rd Floor', 'Observation Glass', 'assets/images/campus_bg.png', 'Available', 'Equipped with one-way observation mirrors, psychological testing kits, and behavioral analysis interview suites.'),
    ('gymnasium', 'MACAVINTA HALL', 'KLD Gymnasium', 'sports', '1,200 Persons', 'KLD Sports Complex', 'Hardwood Court', 'assets/images/gym.png', 'Available', 'State-of-the-art multi-purpose gymnasium for university athletic competitions, convocation ceremonies, and campus tournaments.'),
    ('engineering-lab', 'MAXIMO "IMO" SARIGNAYA', 'Engineering Laboratory', 'labs', '40 Students', 'Engineering Building, Ground Floor', 'CAD Workstations', 'assets/images/campus_bg.png', 'Available', 'Hands-on engineering workshop with circuit design stations, mechanical testing equipment, and CAD workstations.');

-- ─── SEED: Superadmin Account ───────────────────────────────────────────────
-- Replace PLACEHOLDER_BCRYPT_HASH with output of:
--   php -r "echo password_hash('YourSecurePassword', PASSWORD_BCRYPT);"
-- role='Admin' keeps main.php admin sidebar working until Day 6 migration.
INSERT IGNORE INTO `users`
    (`id_number`, `fullname`, `email`, `contact`, `department`, `role`, `access_level`, `email_verified`, `password_hash`)
VALUES
    ('SA-0001', 'System Administrator', 'superadmin@kld.edu.ph', NULL, 'Office of the Registrar', 'Admin', 'superadmin', 1, 'PLACEHOLDER_BCRYPT_HASH');


-- ==========================================================================
-- MIGRATION STATEMENTS (for databases that already have data)
-- Run these ONCE against an existing kld_facility_reservation database.
-- ==========================================================================

-- ── Step 1: Add new columns to users ────────────────────────────────────────
ALTER TABLE `users`
    ADD COLUMN `access_level`   ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user' AFTER `role`,
    ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `access_level`,
    ADD COLUMN `archived_at`    DATETIME DEFAULT NULL AFTER `password_hash`;

-- ── Step 2: Backfill access_level for existing Admin-role users ─────────────
UPDATE `users` SET `access_level` = 'admin' WHERE `role` = 'Admin';

-- ── Step 3: Add archived_at to reservations ─────────────────────────────────
ALTER TABLE `reservations`
    ADD COLUMN `archived_at` DATETIME DEFAULT NULL AFTER `status`;

-- ── Step 4: Change CASCADE to RESTRICT on reservations FK ───────────────────
-- Must drop and re-add because ALTER CONSTRAINT is not supported in MySQL 5.7+
-- Find the actual constraint name first:
--   SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
--   WHERE TABLE_SCHEMA='kld_facility_reservation'
--     AND TABLE_NAME='reservations' AND COLUMN_NAME='user_id';
-- Then drop and re-create (using default name pattern below):
ALTER TABLE `reservations` DROP FOREIGN KEY `reservations_ibfk_1`;
ALTER TABLE `reservations`
    ADD CONSTRAINT `fk_reservations_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT;

-- ── Step 5: Change CASCADE to RESTRICT on user_preferences FK ───────────────
ALTER TABLE `user_preferences` DROP FOREIGN KEY `user_preferences_ibfk_1`;
ALTER TABLE `user_preferences`
    ADD CONSTRAINT `fk_user_preferences_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT;

-- ── Step 6: Change CASCADE to RESTRICT on password_resets FK ────────────────
ALTER TABLE `password_resets` DROP FOREIGN KEY `password_resets_ibfk_1`;
ALTER TABLE `password_resets`
    ADD CONSTRAINT `fk_password_resets_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT;

-- ── Step 7: Create new tables ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT          NOT NULL,
    `code_hash`   VARCHAR(255) NOT NULL,
    `purpose`     ENUM('signup','login','reset') NOT NULL,
    `expires_at`  DATETIME     NOT NULL,
    `used`        TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT          DEFAULT NULL,
    `action`      VARCHAR(255) NOT NULL,
    `ip_address`  VARCHAR(45)  NOT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Step 8: Remove demo seeded accounts (archive, not delete) ───────────────
UPDATE `users` SET `archived_at` = NOW()
    WHERE `id_number` IN ('2024-10942', 'FAC-2018-042', 'RSO-2024-005', 'ADM-2020-001')
      AND `archived_at` IS NULL;

-- ── Step 9: Insert superadmin (if not already present) ──────────────────────
INSERT IGNORE INTO `users`
    (`id_number`, `fullname`, `email`, `contact`, `department`, `role`, `access_level`, `email_verified`, `password_hash`)
VALUES
    ('SA-0001', 'System Administrator', 'superadmin@kld.edu.ph', NULL, 'Office of the Registrar', 'Admin', 'superadmin', 1, 'PLACEHOLDER_BCRYPT_HASH');


-- ==========================================================================
-- ROLLBACK NOTES
-- ==========================================================================
--
-- If migration fails, run these statements to restore the previous state:
--
-- 1. Drop new tables:
--    DROP TABLE IF EXISTS `audit_logs`;
--    DROP TABLE IF EXISTS `otp_codes`;
--
-- 2. Remove new columns from users:
--    ALTER TABLE `users`
--        DROP COLUMN `access_level`,
--        DROP COLUMN `email_verified`,
--        DROP COLUMN `archived_at`;
--
-- 3. Remove archived_at from reservations:
--    ALTER TABLE `reservations` DROP COLUMN `archived_at`;
--
-- 4. Restore CASCADE foreign keys:
--    ALTER TABLE `reservations` DROP FOREIGN KEY `fk_reservations_user_id`;
--    ALTER TABLE `reservations`
--        ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;
--
--    ALTER TABLE `user_preferences` DROP FOREIGN KEY `fk_user_preferences_user_id`;
--    ALTER TABLE `user_preferences`
--        ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;
--
--    ALTER TABLE `password_resets` DROP FOREIGN KEY `fk_password_resets_user_id`;
--    ALTER TABLE `password_resets`
--        ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;
--
-- 5. Un-archive demo accounts:
--    UPDATE `users` SET `archived_at` = NULL
--        WHERE `id_number` IN ('2024-10942', 'FAC-2018-042', 'RSO-2024-005', 'ADM-2020-001');
--
-- 6. Delete the superadmin row (if it was freshly inserted):
--    DELETE FROM `users` WHERE `id_number` = 'SA-0001';
--
-- ==========================================================================
