-- ========================================================
-- UpsiRent-I Database Schema
-- Compatible with MySQL 8.0+ / MariaDB 10.4+
-- ========================================================

CREATE DATABASE IF NOT EXISTS `upsirent_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `upsirent_db`;

-- 1. Users Table (RBAC: student, landlord, admin)
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `role` ENUM('student', 'landlord', 'admin') NOT NULL DEFAULT 'student',
    `verification_status` ENUM('unverified', 'pending', 'verified', 'rejected') NOT NULL DEFAULT 'unverified',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 1,
    `reset_token` VARCHAR(100) NULL,
    `reset_token_expiry` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_role` (`role`),
    INDEX `idx_verification` (`verification_status`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Properties Table (Accommodations: rental, homestay)
CREATE TABLE IF NOT EXISTS `properties` (
    `property_id` INT AUTO_INCREMENT PRIMARY KEY,
    `landlord_id` INT NOT NULL,
    `category` ENUM('rental', 'homestay') NOT NULL DEFAULT 'rental',
    `title` VARCHAR(255) NOT NULL,
    `location` VARCHAR(100) NOT NULL,
    `campus` VARCHAR(50) NOT NULL DEFAULT 'KSAJS',
    `distance` VARCHAR(255) NOT NULL,
    `base_price` DECIMAL(10,2) NOT NULL,
    `recommended_pax` INT NOT NULL DEFAULT 4,
    `price_unit` VARCHAR(100) NOT NULL DEFAULT 'bulan',
    `per_head_note` VARCHAR(255) NULL,
    `availability` VARCHAR(50) NOT NULL DEFAULT 'Masuk Segera',
    `slots_left` VARCHAR(100) NULL,
    `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    `review_count` INT NOT NULL DEFAULT 0,
    `badges` JSON NULL,
    `map_url` VARCHAR(500) NULL,
    `video_tour_url` VARCHAR(500) NULL,
    `nearby_spots` JSON NULL,
    `gallery` JSON NULL,
    `sections` JSON NULL,
    `contract_info` JSON NULL,
    `contact_name` VARCHAR(150) NULL,
    `contact_phone` VARCHAR(30) NULL,
    `contact_whatsapp` VARCHAR(30) NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `admin_notes` TEXT NULL,
    `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`landlord_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`),
    INDEX `idx_location` (`location`),
    INDEX `idx_price` (`base_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Property Reviews Table
CREATE TABLE IF NOT EXISTS `property_reviews` (
    `review_id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NOT NULL,
    `reviewer_name` VARCHAR(100) NOT NULL,
    `reviewer_role` VARCHAR(100) NOT NULL DEFAULT 'Pelajar UPSI',
    `rating` INT NOT NULL DEFAULT 5,
    `comment` TEXT NOT NULL,
    `review_date` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`property_id`) ON DELETE CASCADE,
    INDEX `idx_prop_review` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Roommate Advertisements Table ("Cari Rakan Bilik")
CREATE TABLE IF NOT EXISTS `roommate_ads` (
    `ad_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `gender` VARCHAR(50) NOT NULL,
    `faculty` VARCHAR(150) NOT NULL,
    `location` VARCHAR(100) NOT NULL,
    `budget` DECIMAL(10,2) NOT NULL,
    `description` TEXT NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `tags` JSON NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `admin_notes` TEXT NULL,
    `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_roommate_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Student Verification Submissions Table
CREATE TABLE IF NOT EXISTS `student_verifications` (
    `verification_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(150) NOT NULL,
    `matric_number` VARCHAR(50) NOT NULL,
    `document_type` VARCHAR(50) NOT NULL,
    `document_path` VARCHAR(255) NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `admin_notes` TEXT NULL,
    `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_verif_status` (`status`),
    INDEX `idx_verif_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Wishlists Table ("Disimpan" persistent student wishlist)
CREATE TABLE IF NOT EXISTS `wishlists` (
    `wishlist_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `property_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`property_id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_student_property` (`student_id`, `property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('verification', 'property', 'roommate', 'general') NOT NULL DEFAULT 'general',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
