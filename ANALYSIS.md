# UpsiRent-I: System Analysis & Architecture Specification

## 1. Executive Summary
UpsiRent-I is an accommodation and roommate matching platform tailored for Universiti Pendidikan Sultan Idris (UPSI) students and property landlords in Tanjung Malim, Proton City, and Behrang. This document defines the architectural blueprints, page routings, database entities, and technical resolutions for the native PHP 8 / MySQL implementation based on `srs3.pdf` and visual guidelines from `index.html`.

---

## 2. Pages and Routes Architecture

| URL Route | File Path | Access Level | Description |
|-----------|-----------|--------------|-------------|
| `/` or `/index.php` | `public/index.php` | Public (Guest, Student, Landlord, Admin) | Primary portal displaying "Rumah Sewa", "Homestay", "Cari Rakan Bilik", real-time filters (location, availability, price, keyword), hero section, property comparison bar, quick utility buttons, and modal dialogs. |
| `/login.php` | `public/login.php` | Public / Guest | Authentication form with email, password, CSRF token, session regeneration, and role-based redirect. |
| `/register.php` | `public/register.php` | Public / Guest | Registration for Students and Landlords (Admin blocked from public signup). Includes full name, email, phone, password, and mandatory PDPA checkbox. |
| `/logout.php` | `public/logout.php` | Authenticated | Safely terminates session, invalidates session tokens, clears cookies, redirects to homepage. |
| `/profile.php` | `public/profile.php` | Authenticated | View and edit personal profile (name, phone), view account verification status, student matric number, and recent notifications. |
| `/forgot_password.php` | `public/forgot_password.php` | Public | Password reset request with token generation. |
| `/reset_password.php` | `public/reset_password.php` | Public | Enter new password using valid reset token. |
| `/verify_student.php` | `public/verify_student.php` | Student (Registered) | Submit UPSI Student ID card or Offer Letter with mandatory PDPA consent. Status tracks as "Pending" until Admin approval. |
| `/wishlist.php` | `public/wishlist.php` | Student (Registered / Verified) | Persistent database wishlist ("Disimpan"). View, manage, or remove bookmarked properties. |
| `/landlord_dashboard.php`| `public/landlord_dashboard.php` | Landlord | Property management dashboard: submit new listing with details, photos, and PDPA consent; view own submitted properties and approval statuses. |
| `/roommate_hub.php` | `public/roommate_hub.php` | Public (view) / Student (post) | Roommate matching board: view approved roommate ads; post new roommate request with preferences, budget, and PDPA consent. |
| `/admin/dashboard.php` | `public/admin/dashboard.php` | Admin Only | Moderation console: review pending verifications, property listings, and roommate ads. Approve or reject with optional notes; triggers in-app notification. |
| `/admin/users.php` | `public/admin/users.php` | Admin Only | User administration: search users, toggle active/suspended status, modify roles, or delete user accounts (with self-deletion protection). |
| `/api/gemini_proxy.php` | `api/gemini_proxy.php` | Public / Authenticated | Server-side Gemini AI chat proxy via cURL. Injects active DB property context into system prompt. Graceful rule-based local fallback if API key is missing or offline. |
| `/api/wishlist.php` | `api/wishlist.php` | Student | JSON endpoint to toggle wishlist items persistently in MySQL. |
| `/api/listings.php` | `api/listings.php` | Public | JSON endpoint for live property filtering and search. |
| `/api/notifications.php`| `api/notifications.php` | Authenticated | JSON endpoint to read and dismiss in-app status notifications. |

---

## 3. Database Schema & Column Definitions

### 3.1 `users`
Stores user credentials, profile information, and RBAC roles.
- `user_id` INT AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(150) NOT NULL
- `email` VARCHAR(150) NOT NULL UNIQUE
- `password_hash` VARCHAR(255) NOT NULL
- `phone` VARCHAR(30) NOT NULL
- `role` ENUM('student', 'landlord', 'admin') NOT NULL DEFAULT 'student'
- `verification_status` ENUM('unverified', 'pending', 'verified', 'rejected') NOT NULL DEFAULT 'unverified'
- `is_active` TINYINT(1) NOT NULL DEFAULT 1
- `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 1
- `reset_token` VARCHAR(100) NULL
- `reset_token_expiry` DATETIME NULL
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 3.2 `student_verifications`
Stores uploaded Student ID or Offer Letter verification requests.
- `verification_id` INT AUTO_INCREMENT PRIMARY KEY
- `student_id` INT NOT NULL (Foreign Key -> `users.user_id` ON DELETE CASCADE)
- `student_name` VARCHAR(150) NOT NULL
- `matric_number` VARCHAR(50) NOT NULL
- `document_type` VARCHAR(50) NOT NULL
- `document_path` VARCHAR(255) NOT NULL
- `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending'
- `admin_notes` TEXT NULL
- `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 1
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- `reviewed_at` DATETIME NULL

### 3.3 `properties`
Stores accommodation listings across "Rumah Sewa" and "Homestay".
- `property_id` INT AUTO_INCREMENT PRIMARY KEY
- `landlord_id` INT NOT NULL (Foreign Key -> `users.user_id` ON DELETE CASCADE)
- `category` ENUM('rental', 'homestay') NOT NULL DEFAULT 'rental'
- `title` VARCHAR(255) NOT NULL
- `location` VARCHAR(100) NOT NULL
- `campus` VARCHAR(50) NOT NULL DEFAULT 'KSAJS'
- `distance` VARCHAR(255) NOT NULL
- `base_price` DECIMAL(10,2) NOT NULL
- `recommended_pax` INT NOT NULL DEFAULT 4
- `price_unit` VARCHAR(100) NOT NULL DEFAULT 'bulan'
- `per_head_note` VARCHAR(255) NULL
- `availability` VARCHAR(50) NOT NULL DEFAULT 'Masuk Segera'
- `slots_left` VARCHAR(100) NULL
- `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00
- `review_count` INT NOT NULL DEFAULT 0
- `badges` JSON NULL
- `map_url` VARCHAR(500) NULL
- `video_tour_url` VARCHAR(500) NULL
- `nearby_spots` JSON NULL
- `gallery` JSON NULL
- `sections` JSON NULL
- `contract_info` JSON NULL
- `contact_name` VARCHAR(150) NULL
- `contact_phone` VARCHAR(30) NULL
- `contact_whatsapp` VARCHAR(30) NULL
- `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending'
- `admin_notes` TEXT NULL
- `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 1
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 3.4 `property_reviews`
Stores tenant and guest reviews for properties.
- `review_id` INT AUTO_INCREMENT PRIMARY KEY
- `property_id` INT NOT NULL (Foreign Key -> `properties.property_id` ON DELETE CASCADE)
- `reviewer_name` VARCHAR(100) NOT NULL
- `reviewer_role` VARCHAR(100) NOT NULL DEFAULT 'Pelajar UPSI'
- `rating` INT NOT NULL DEFAULT 5
- `comment` TEXT NOT NULL
- `review_date` VARCHAR(50) NOT NULL
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### 3.5 `roommate_ads`
Stores student community roommate requests ("Cari Rakan Bilik").
- `ad_id` INT AUTO_INCREMENT PRIMARY KEY
- `student_id` INT NOT NULL (Foreign Key -> `users.user_id` ON DELETE CASCADE)
- `name` VARCHAR(150) NOT NULL
- `gender` VARCHAR(50) NOT NULL
- `faculty` VARCHAR(150) NOT NULL
- `location` VARCHAR(100) NOT NULL
- `budget` DECIMAL(10,2) NOT NULL
- `description` TEXT NOT NULL
- `phone` VARCHAR(30) NOT NULL
- `tags` JSON NULL
- `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending'
- `admin_notes` TEXT NULL
- `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 1
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### 3.6 `wishlists`
Junction table linking students with their bookmarked properties.
- `wishlist_id` INT AUTO_INCREMENT PRIMARY KEY
- `student_id` INT NOT NULL (Foreign Key -> `users.user_id` ON DELETE CASCADE)
- `property_id` INT NOT NULL (Foreign Key -> `properties.property_id` ON DELETE CASCADE)
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- UNIQUE KEY `uq_student_property` (`student_id`, `property_id`)

### 3.7 `notifications`
In-app notification system for moderation decisions and announcements.
- `notification_id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NOT NULL (Foreign Key -> `users.user_id` ON DELETE CASCADE)
- `title` VARCHAR(255) NOT NULL
- `message` TEXT NOT NULL
- `type` ENUM('verification', 'property', 'roommate', 'general') NOT NULL DEFAULT 'general'
- `is_read` TINYINT(1) NOT NULL DEFAULT 0
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

---

## 4. Ambiguities & Inconsistencies Decision Log

1. **Architecture & Stack Discrepancy (SRS Table 3.6 vs SRS 3.1.1, 3.5.3, 3.7 & User Specification)**
   - *Issue*: DC001-DC003 mentioned Google AppSheet and Google Sheets.
   - *Decision*: Adopt Native PHP 8 + MySQL PDO with prepared statements as mandated by the Project Specification and SRS sections 3.1.1, 3.5.3 (Logical Database), and 3.7 (MVC Architecture & bcrypt hashing).
2. **Student Verification Lifecycle & Discount Application (FR009, FR010 vs index.html)**
   - *Issue*: `index.html` mocked student verification with an immediate front-end toggle.
   - *Decision*: In compliance with SRS UC012, UC013, and FR009, student submits proof of enrollment -> record saved with `Pending` status -> Admin reviews and approves in Moderation Dashboard -> User role upgrades to `Verified Student` -> 10% discount is dynamically applied on the backend/rendered output.
3. **Data Moderation Workflow (CR005, FR013, FR016, FR021)**
   - *Issue*: In `index.html`, posted ads were immediately rendered into the DOM.
   - *Decision*: All landlord listings and roommate ads are saved with `Pending` status. Only items with `Approved` status appear on the public portal. The Admin moderation dashboard provides Approve / Reject controls with optional feedback notes.
4. **AI Proxy Integration (FR011, CR003, DC004)**
   - *Issue*: Need resilient AI functionality without exposing secrets.
   - *Decision*: Implement a server-side proxy at `api/gemini_proxy.php` via cURL. The proxy loads the Gemini API key from `config/config.local.php` (git-ignored) and injects dynamic database property context into the prompt. A local rule-based fallback answers student rental questions, lease contract red flags, and cost estimates if the API is offline or keyless.
