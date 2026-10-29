# UpsiRent-I: Testing & Verification Guide

This document records the file additions, XAMPP environment configurations, and testing checklists across each development phase.

---

## Phase 1: Database Foundation, Authentication, RBAC, Property Listings & Rent Calculator

### 1. Files Created / Modified
- `database/schema.sql`: Core relational database schema defining all 7 tables with constraints, indexes, and cascades.
- `database/seed.sql`: Realistic seed data with 5 user accounts across roles, 5 detailed properties, 6 tenant reviews, 4 roommate ads, sample verifications, wishlists, and notifications.
- `config/config.php`: Central application settings, dynamic base URL resolver, session duration, discount rate.
- `config/db.php`: Singleton PDO connection manager with exception mode and prepared statements.
- `config/config.local.php.example`: Local environment template for credentials and Gemini model settings.
- `config/config.local.php`: Active local configuration file (git-ignored).
- `.gitignore`: Ensures secrets (`config.local.php`) and uploaded media are excluded from VCS.
- `includes/csrf.php`: Cryptographic CSRF token generation, input field helpers, and verification.
- `includes/auth.php`: RBAC engine, session management, inactivity timeout, bcrypt password checks, and role helpers.
- `includes/helpers.php`: XSS sanitizers (`e()`), currency formatting (`RM`), student discount calculators, and flash messages.
- `includes/header.php`: Primary navigation header, category switchers, student discount pill, user menu, large text toggle.
- `includes/footer.php`: Responsive footer with links, PDPA disclosure, mobile bottom dock, and toast notification scripts.
- `index.php`: Root directory redirector to `/public/index.php`.
- `public/index.php`: Main portal with live filter bar, dynamic listings grid, property detail modal (photo gallery & inventory checklist), rent calculator with WhatsApp share, and modal overlays.
- `public/login.php`: Secure login page with role-based routing, CSRF protection, and quick-fill test credentials.
- `public/register.php`: Registration page supporting Student and Landlord signups with mandatory PDPA consent.
- `public/logout.php`: Session invalidation and secure cookie purging.
- `public/forgot_password.php`: Password reset request handler generating 24-hour cryptographically secure tokens.
- `public/reset_password.php`: Password reset form validating tokens and updating bcrypt hashes.
- `public/profile.php`: User profile viewer/editor with status indicators and in-app notifications.
- `api/listings.php`: JSON API providing search, filtering (location, availability, price, keyword), and dynamic discount pricing.

### 2. XAMPP Setup Steps for Phase 1
1. **Ensure Apache & MySQL are running** in the XAMPP Control Panel.
2. **Import Database**:
   - Open terminal / command prompt or phpMyAdmin.
   - Run `mysql -u root -p < database/schema.sql` (or via phpMyAdmin import).
   - Run `mysql -u root -p upsirent_db < database/seed.sql`.
3. **Verify Directory**: Ensure the repository is located inside `C:\xampp\htdocs\HACK`.
4. **Access in Browser**: Navigate to `http://localhost/HACK/` or `http://localhost/HACK/public/`.

### 3. Phase 1 Test Checklist
- [x] **Guest Browsing**:
  1. Open `http://localhost/HACK/`.
  2. Confirm 5 property cards load from MySQL via `api/listings.php`.
  3. Filter by Location (e.g., "Taman Bahtera") -> Verify only matching houses appear.
  4. Enter "WiFi" in search input -> Verify filtered results update immediately.
  5. Click "Set Semula Penapis" -> Confirm all 5 listings reappear.
- [x] **Property Modal & Gallery**:
  1. Click "Lihat Bilik Demi Bilik" on any property.
  2. Verify detailed interactive modal opens with photo gallery, room thumbnails, inventory checklist, and landlord contact details.
  3. Click "Peta Google" -> Confirms redirect to Google Maps URL scheme.
- [x] **Rent Calculator**:
  1. Click "Kalkulator Sewa" in the quick utilities bar or from within the property modal.
  2. Adjust the base rent slider to RM 1,500 and pax to 5.
  3. Toggle utility checkboxes (WiFi, TNB, LAP, Coway) -> Verify dynamic per-person cost recalculates.
  4. Click "Kongsi ke WhatsApp" -> Confirms WhatsApp URL scheme (`wa.me/?text=...`) opens with pre-filled summary.
- [x] **User Registration & Security**:
  1. Click "Daftar" in header.
  2. Try submitting without checking the PDPA checkbox -> Form stops and displays PDPA validation error.
  3. Check the PDPA box, select "Pelajar UPSI / IPT", fill in valid details, and submit -> Redirects to login with success flash.
- [x] **Authentication & Role Redirect**:
  1. Go to `http://localhost/HACK/public/login.php`.
  2. Use quick test account `student@siswa.upsi.edu.my` (`Password123!`) -> Logs in as Registered Student.
  3. Verify header displays user avatar, name, and Wishlist badge.
  4. Log in as `verified@siswa.upsi.edu.my` (`Password123!`) -> Verify "Diskaun 10% Aktif" banner appears, and prices show strikethrough with 10% discount applied!
  5. Click "Log Keluar" -> Session safely cleared and redirected to guest view.

---

## Phase 2: Wishlist, Property Comparison, Student Verification, Landlord Submissions & Roommate Hub

### 1. Files Created / Modified
- `api/wishlist.php`: JSON API handling secure toggle of persistent student wishlists in MySQL with CSRF validation.
- `public/wishlist.php`: Dedicated wishlist view showing student's bookmarked properties, discount prices, and delete actions.
- `public/verify_student.php`: Student ID / Offer letter upload page with strict MIME verification, 5MB ceiling, PDPA consent, and review status tracking.
- `public/landlord_dashboard.php`: Landlord portal for submitting properties with multi-image photo uploads, contract details, and real-time status tracking (Pending, Approved, Rejected).
- `public/roommate_hub.php`: Community roommate matching hub ("Cari Rakan Bilik") with gender/location filtering, direct WhatsApp links, and student submission modal.
- `public/index.php`: Updated with side-by-side comparison modal (2-3 properties) comparing prices, distance, capacity, and terms.

### 2. XAMPP Setup Steps for Phase 2
1. Ensure `uploads/verifications` and `uploads/properties` directories have write permissions in Apache/PHP.
2. Confirm the user accounts `landlord@upsirent.my` and `student@siswa.upsi.edu.my` exist in the `users` table.

### 3. Phase 2 Test Checklist
- [x] **Wishlist Workflow (FR012)**:
  1. Log in as `student@siswa.upsi.edu.my` (`Password123!`).
  2. On homepage, click the heart icon on any property card -> Toast confirms "Kediaman berjaya disimpan ke senarai Wishlist anda!".
  3. Header badge increments. Click "Disimpan" (or go to `http://localhost/HACK/public/wishlist.php`) -> Saved property displays.
  4. Click the trash icon -> Confirms removal from persistent MySQL storage.
- [x] **Property Comparison (FR008)**:
  1. On homepage, click the scale icon ("Banding") on two different property cards.
  2. Floating comparison bar appears at bottom showing "2/3 Kediaman dipilih untuk dibanding".
  3. Click "Bandingkan Sekarang" -> Modal opens displaying side-by-side comparison table (Location, Distance, Rent, Pax, Status).
- [x] **Student Verification Upload (FR009 / UC012)**:
  1. Logged in as `student@siswa.upsi.edu.my` (status: `unverified`), go to `http://localhost/HACK/public/verify_student.php`.
  2. Enter matric number (e.g. `D2024109988`), choose a sample image/PDF file, tick the PDPA checkbox, and click submit.
  3. Status updates to `Pending` and confirmation alert displays: "Permohonan Sedang Disemak".
- [x] **Landlord Listing Submission (FR013 / UC010)**:
  1. Log in as `landlord@upsirent.my` (`Password123!`).
  2. Access `http://localhost/HACK/public/landlord_dashboard.php`.
  3. Fill out the "Borang Pengiklanan Kediaman Baru" form with title, location, rent, capacity, contract terms, and upload a property photo. Tick PDPA consent.
  4. Submit form -> New listing appears in "Senarai Iklan Kediaman Saya" with yellow status badge `Menunggu Semakan` (Pending).
  5. Go to homepage -> Verify the pending listing is NOT visible to public users yet (complying with CR005).
- [x] **Roommate Hub (FR016 / UC011)**:
  1. Open `http://localhost/HACK/public/roommate_hub.php`.
  2. Test filtering by Gender ("Siswi" / "Siswa") and Location.
  3. Click "WhatsApp Rakan Bilik" -> Verify it triggers the `wa.me` URL scheme with pre-filled message.
  4. Logged in as a student, click "Iklankan Pencarian Rakan Bilik", fill details, tick PDPA, and submit -> Saved with `Pending` status.

---

## Phase 3: Admin Moderation Dashboard, User Management, Gemini AI Assistant & Info Guides

### 1. Files Created / Modified
- `api/gemini_proxy.php`: Server-side cURL proxy communicating with Google Gemini API with real-time property context injection and rule-based local fallback engine.
- `public/admin/dashboard.php`: Restricted admin console for moderating pending student verifications, landlord property submissions, and roommate ads, with automated notification dispatching.
- `public/admin/users.php`: User management interface allowing admins to inspect accounts, search, toggle account suspension (`is_active`), modify roles, and delete users safely.
- `includes/header.php` & `includes/footer.php`: Verified integration with large text accessibility mode (`toggleLargeText()`), toast notifications, and guidance modals.
- `public/index.php`: Integrated Gemini AI chat modal, prompt pills, info guide modals (UPSI Bus, anti-scam, amenities).

### 2. XAMPP Setup Steps for Phase 3
1. **API Key Setup (Optional)**:
   - To use live Google Gemini API, open `config/config.local.php` and set `'api_key' => 'YOUR_ACTUAL_KEY'`.
   - If left empty, the intelligent local fallback engine operates transparently.
2. Confirm cURL extension is enabled in PHP (`extension=curl` in `php.ini` - default enabled in XAMPP).

### 3. Phase 3 Test Checklist
- [x] **Gemini AI Assistant & Fallback (FR011 / UC009)**:
  1. Open homepage and click "Tanya Gemini AI" or the hero prompt "Semak klausa kontrak sewa".
  2. Gemini modal opens. Ask: *"Apa red flags dalam kontrak sewa?"*
  3. AI returns detailed advice identifying safety clauses, deposit benchmarks in Tanjung Malim, and maintenance terms.
  4. Ask: *"Berapa bajet sara hidup pelajar UPSI sebulan?"* -> AI provides monthly breakdown (RM 680 - RM 950).
- [x] **Admin Moderation Console (FR021 / UC013)**:
  1. Log in as `admin@upsi.edu.my` (`Password123!`).
  2. Click "Panel Admin" in header (navigates to `http://localhost/HACK/public/admin/dashboard.php`).
  3. View the three moderation queues:
     - Student Verifications (review uploaded matric card/letter, click "Luluskan" -> updates role to Verified Student, triggering 10% discount).
     - Property Listings (inspect property specs, click "Luluskan" -> immediately publishes listing to public portal).
     - Roommate Ads (inspect ad details, click "Luluskan" -> immediately visible on Roommate Hub).
- [x] **User Management & Account Suspension (FR022 / UC002: 5b)**:
  1. In admin area, navigate to `http://localhost/HACK/public/admin/users.php`.
  2. Search for a user (e.g. `student@siswa.upsi.edu.my`).
  3. Click the user suspend icon (toggles `is_active` to 0).
  4. Attempt to log in with that student account in another window -> Login is blocked with error message: *"Akaun anda telah digantung oleh Pentadbir Sistem."*
  5. As admin, click "Aktifkan Semula" -> Account is immediately restored.
  6. Attempt to delete own admin account -> Guard prevents self-deletion.
- [x] **Info Guides & Accessibility (FR023, FR024)**:
  1. Click "Laluan Bas UPSI" in footer or quick utility bar -> Modal opens displaying KSAJS ⟷ KSAS shuttle routes and schedules.
  2. Click "Elak Scam Sewa" -> Modal opens with contract safety checklist.
  3. Click "+A" in top navigation bar -> Entire UI activates large text accessibility mode, persisted across pages via `localStorage`. Click "A-" to return to standard size.

---


