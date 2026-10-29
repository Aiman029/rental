# UpsiRent-I: Engineering Assumptions

This document records the architectural and operational interpretations adopted for UpsiRent-I, referencing specific sections of `srs3.pdf`.

---

### 1. Technology & Execution Runtime
* **SRS Reference**: Section 3.1.1, Section 3.5.3, Section 3.7 vs Section 3.6 (DC001–DC003).
* **Assumption**: The project is implemented using Native PHP 8 and a MySQL relational database managed via PDO prepared statements running in a local XAMPP environment. References in Table 3.6 to Google AppSheet/Sheets are deemed draft artifacts superseded by the architectural requirements in 3.1.1, 3.5.3 (Logical Database), and the technical constraints of the project prompt.

### 2. Student Verification & Discount Logic
* **SRS Reference**: Section 2.3.1 (User Classes Definition), Section 3.3 (FR009, FR010), Section 3.4.2.12 (UC012), Section 3.4.2.13 (UC013).
* **Assumption**: A registered student must submit proof of enrollment (Student ID / Offer Letter) with mandatory PDPA consent. The verification enters a `Pending` state. Only upon administrative approval does the user's status become `verified` (Verified Student), at which point a 10% discount is applied to rental listings and reflected in calculations across the platform.

### 3. Moderation Lifecycle for Listings & Roommate Ads
* **SRS Reference**: Section 2.4 (CR005), Section 3.4.2.10 (UC010), Section 3.4.2.11 (UC011), Section 3.4.2.13 (UC013).
* **Assumption**: Any newly submitted property listing or roommate ad is created with status `Pending`. Only records marked `Approved` by an administrator are returned in public searches and listings. The submitter can track their submission status via their respective dashboard, and an in-app notification is dispatched upon approval or rejection.

### 4. File Upload Storage & PDPA Compliance
* **SRS Reference**: Section 2.4 (CR004), Section 3.1.3 (Communication Interfaces), Section 3.6 (DC005).
* **Assumption**: Uploaded student verification documents and landlord property photos are validated on the server for permitted MIME types (image/jpeg, image/png, image/webp, application/pdf) and capped at 5MB. Files are renamed using cryptographic uniqueness to prevent directory traversal and collision, and are stored in dedicated directories (`uploads/verifications/` and `uploads/properties/`). Verification files are accessible only to administrators for review.

### 5. Gemini AI Server Proxy & Local Fallback
* **SRS Reference**: Section 2.2.1.5, Section 3.1.4, Section 3.3 (FR011), Section 3.4.2.9 (UC009).
* **Assumption**: The Gemini AI integration is mediated exclusively through a backend PHP endpoint (`api/gemini_proxy.php`) via cURL. The API key is stored in `config/config.local.php` (git-ignored) and the model name is configurable (defaulting to `gemini-1.5-flash` or `gemini-2.5-flash`). If the key is not set or network errors occur, an intelligent local rule-based engine generates expert responses for UPSI rental questions, tenancy contract red flag screening, and student living cost calculations.

### 6. WhatsApp and Google Maps Redirection
* **SRS Reference**: Section 2.4 (Assumption 1), Section 3.1.3 (Communication Interfaces), Section 3.4.2.7 (UC007), Section 3.4.2.8 (UC008).
* **Assumption**: Deep linking uses standard web schemes: `https://wa.me/<phone>?text=<encoded_text>` for contacting landlords and sharing rent calculations, and `https://www.google.com/maps/search/?api=1&query=<encoded_query>` for viewing property locations.
