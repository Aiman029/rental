# UpsiRent-I: SRS vs index.html Conflicts Log

This document enumerates the discrepancies identified between the Software Requirements Specification (`srs3.pdf`) and the visual reference prototype (`index.html`), detailing the decisions applied in favor of the SRS.

---

### Conflict 1: Static Hardcoded Client-Side State vs Relational Database Backend
* **index.html**: Hardcodes sample property listings and roommate ads in client-side JavaScript arrays (`const properties = [...]`, `const roommates = [...]`). Filter state, wishlist, and verification are stored in memory or transient `localStorage`.
* **SRS Requirement**: Sections 3.1.1, 3.5.3 (Logical Database), FR001–FR022 specify a relational database (MySQL via PDO) where users, properties, roommate ads, verifications, and wishlists are stored persistently.
* **Resolution**: Replaced static client-side storage with MySQL tables (`users`, `properties`, `roommate_ads`, `student_verifications`, `wishlists`, `notifications`). The visual style, card layout, and color schemes from `index.html` are preserved, but all data is rendered dynamically from MySQL.

---

### Conflict 2: Immediate Frontend Verification vs Asynchronous Admin Moderation
* **index.html**: Clicking "Sahkan ID Pelajar" immediately updates `isStudentVerified = true` in JavaScript memory and updates prices client-side without document processing.
* **SRS Requirement**: Sections 2.2.1.4, 2.3.1, 3.4.2.12 (UC012), and 3.4.2.13 (UC013) specify that a student must submit an ID or Offer Letter document with PDPA consent, creating a `Pending` record for Admin review. The 10% discount is applied only when the administrator approves the submission and upgrades the user to `Verified Student`.
* **Resolution**: Followed the SRS workflow. Registered students submit proof of enrollment to `/verify_student.php`. Admins review the document via the Moderation Dashboard (`/admin/dashboard.php`). Upon approval, the account's `verification_status` becomes `verified`, and the system automatically calculates and renders the 10% discount.

---

### Conflict 3: Instant Listing Publication vs Mandatory Admin Moderation
* **index.html**: Submitting a property or roommate ad adds an item immediately to the in-memory array and renders it in the public feed.
* **SRS Requirement**: CR005, FR013, FR016, FR021 state that all property listings and roommate advertisements must undergo manual moderation by an Admin before publication.
* **Resolution**: All submissions are saved to MySQL with status `Pending`. Only properties and roommate ads with status `Approved` appear in the public listing feeds. Submitters can track pending items in their dashboard, while Admins approve or reject them with optional notes.

---

### Conflict 4: In-Browser Gemini API Key Entry vs Secure Server-Side Proxy
* **index.html**: Includes an input field for users to enter their Gemini API key directly into the browser and saves it to `localStorage`.
* **SRS Requirement**: CR003, DC004, and Section 3.1.4 explicitly forbid exposing API keys to the browser, mandating server-side execution.
* **Resolution**: Removed client-side API key entry from the user interface. API requests are dispatched to `api/gemini_proxy.php`, which reads the API key securely from `config/config.local.php` on the server and falls back to an intelligent local response generator if no key is configured.

---

### Conflict 5: AppSheet/Google Sheets Constraints (DC001–DC003) vs Full-Stack Specifications
* **srs3.pdf Section 3.6**: Table 3.6 contains constraint entries DC001, DC002, and DC003 citing Google AppSheet and Google Sheets.
* **SRS Core & Prompt Specifications**: Section 3.1.1, Section 3.5.3, Section 3.7, and the user prompt dictate a native PHP 8 backend running on XAMPP with a MySQL database using PDO prepared statements and bcrypt password encryption.
* **Resolution**: Followed the full-stack architecture defined in Sections 3.1.1, 3.5.3, 3.7, and the prompt specification.
