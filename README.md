# UpsiRent-I (Portal Rumah Sewa Pelajar & Homestay UPSI)

UpsiRent-I ialah platform digital penginapan dan carian rakan sebilik bagi mahasiswa Universiti Pendidikan Sultan Idris (UPSI) di Tanjung Malim, Proton City, dan Behrang, Perak. Dibina menggunakan Native PHP 8 dan pangkalan data MySQL (PDO) berlandaskan piawaian SRS dan panduan reka bentuk UI visual berkuasa AI.

---

## Ciri-Ciri Utama & Keupayaan Sistem

1. **Carian & Penapisan Pantas (FR003, FR004):**
   - Paparan kategori "Rumah Sewa", "Homestay", dan "Cari Rakan Bilik".
   - Penapisan langsung mengikut Kawasan (Taman Bahtera, Proton City, dsb.), Status Kekosongan, Bajet Maksimum, dan Carian Kata Kunci.
   - Butang set semula tapisan (Reset Filters).

2. **Perincian Kediaman & Galeri Airbnb (FR005, FR006):**
   - Galeri foto bilik demi bilik interaktif dengan lakaran kecil (thumbnails).
   - Senarai semak inventori & kelengkapan (perabot, dobi, keselamatan, internet).
   - Pautan peta menggunakan skema URL Google Maps (`https://www.google.com/maps/search/`).
   - Butang terus menghubungi tuan rumah melalui WhatsApp (`https://wa.me/`).
   - Ulasan penyewa dan alumni UPSI.

3. **Kalkulator Kongsi Sewa (FR007):**
   - Peluncur kadar sewa kasar (RM 200 - RM 2,500) dan bilangan rakan sebilik (1 - 10 orang).
   - Pengiraan kos utiliti bersama (WiFi Unifi, TNB elektrik, LAP air, penapis Coway).
   - Penjana templat teks pra-isi terus ke WhatsApp rakan bilik.

4. **Pengesahan Kad Matrik & Diskaun Pelajar 10% (FR009, FR010):**
   - Pelajar memuat naik Kad Matrik atau Surat Tawaran UPSI dengan persetujuan PDPA 2010.
   - Status permohonan "Pending" dinilai oleh Pentadbir dalam panel moderasi.
   - Apabila diluluskan, akaun dinaik taraf ke "Verified Student" dan diskaun 10% dipotong secara automatik bagi semua iklan sewaan.

5. **Papan Rakan Bilik - Roommate Hub (FR016):**
   - Paparan iklan rakan sebilik yang diluluskan.
   - Tapisan mengikut jantina (Siswa / Siswi) dan kawasan.
   - Borang pengiklanan rakan bilik untuk pelajar dengan semakan PDPA.

6. **Papan Pemuka Tuan Rumah - Landlord Portal (FR013, FR015):**
   - Pengiklanan rumah sewa dan homestay dengan muat naik foto berganda.
   - Penjejakan status kelulusan (Pending, Approved, Rejected).

7. **Panel Moderasi & Kawalan Pengguna Pentadbir (FR021, FR022):**
   - Moderasi permohonan pengesahan pelajar, iklan rumah sewa, dan iklan rakan bilik.
   - Pengurusan pengguna: carian, pertukaran peranan RBAC, penggantungan akaun (`is_active = 0`), dan pemadaman selamat.

8. **Pembantu Pintar Gemini AI (FR011):**
   - Proksi pelayan selamat (cURL) mengelakkan pendedahan kunci API kepada pelayar web.
   - Suntikan konteks kediaman semasa dari pangkalan data ke dalam sistem AI.
   - Enjin sandaran tempatan (*intelligent local fallback*) yang beroperasi serta-merta sekiranya ketiadaan kunci API atau kegagalan rangkaian.

9. **Mod Kebolehcapaian Teks Besar (FR023) & Panduan Pelajar (FR024):**
   - Butang pensuisan saiz teks (+A / A-) dengan simpanan konfigurasi `localStorage`.
   - Modul interaktif jadual bas percuma UPSI, panduan anti-scam sewaan, dan kemudahan awam Tanjung Malim.

---

## Keperluan Sistem (Tech Stack)

* **Pelayan Web & PHP:** Apache + PHP 8.0 ke atas (diuji pada PHP 8.4 di XAMPP / Laragon).
* **Pangkalan Data:** MySQL 8.0+ atau MariaDB 10.4+.
* **Frontend:** Tailwind CSS (CDN), Font Awesome 6, Google Fonts (Plus Jakarta Sans), Vanilla JavaScript.
* **Protokol Luar:** URL Scheme WhatsApp (`wa.me`), Google Maps Search.

---

## Panduan Pemasangan & Persediaan XAMPP

### Langkah 1: Penempatan Folder Projek
Pastikan keseluruhan folder projek diletakkan di dalam direktori `htdocs` XAMPP anda:
```
C:\xampp\htdocs\HACK\
```

### Langkah 2: Mulakan Servis Apache & MySQL
1. Buka **XAMPP Control Panel**.
2. Klik butang **Start** pada modul **Apache** dan **MySQL**.

### Langkah 3: Import Pangkalan Data
1. Buka pelayar web dan akses phpMyAdmin di `http://localhost/phpmyadmin/`, atau buka Command Prompt/Terminal.
2. Cipta pangkalan data bernama `upsirent_db` dan import skema:
   ```bash
   mysql -u root -p < C:\xampp\htdocs\HACK\database\schema.sql
   ```
3. Import data awal (seed data):
   ```bash
   mysql -u root -p upsirent_db < C:\xampp\htdocs\HACK\database\seed.sql
   ```

### Langkah 4: Konfigurasi Kunci Gemini AI (Pilihan)
1. Salin fail `config/config.local.php.example` kepada `config/config.local.php` (fail ini telah diabaikan dalam `.gitignore` demi keselamatan):
   ```bash
   copy config\config.local.php.example config\config.local.php
   ```
2. Sekiranya anda mempunyai kunci Google Gemini API, masukkan nilai kunci di dalam ruangan `'api_key' => 'KUNCI_ANDA'`.
3. *Nota:* Sekiranya dibiarkan kosong, sistem secara automatik menggunakan enjin sandaran tempatan yang telah diprogramkan sepenuhnya dengan garis panduan sewaan UPSI.

### Langkah 5: Akses Aplikasi
Buka pelayar web anda dan layari:
```
http://localhost/HACK/
```
(Aplikasi akan memajukan laluan secara automatik ke `http://localhost/HACK/public/index.php`).

---

## Maklumat Akaun Ujian (Kata Laluan Standard: `Password123!`)

> [!TIP]
> Pengguna atau penilai disarankan untuk **mendaftar akaun baharu** melalui pautan **Daftar Akaun Baru** (`/public/register.php`) bagi menguji aliran sebenar pendaftaran peranan pelajar/tuan rumah berserta persetujuan PDPA.
> 
> Akaun sedia ada (seed) di bawah disediakan khas untuk tujuan penilaian fungsi khusus (seperti panel Admin moderasi):

| Peranan (Role) | E-mel | Status Pengesahan | Keistimewaan / Tujuan Ujian |
|----------------|-------|-------------------|-----------------------------|
| **Pentadbir (Admin)** | `admin@upsi.edu.my` | Verified | Moderasi permohonan, semak dokumen pelajar, urus akaun pengguna |
| **Tuan Rumah (Landlord)** | `landlord@upsirent.my` | Unverified | Iklankan rumah sewa & homestay, pantau status kelulusan |
| **Tuan Rumah 2** | `puan.salmah@upsirent.my` | Unverified | Menguruskan kediaman Proton City |
| **Pelajar Biasa (Student)**| `student@siswa.upsi.edu.my` | Unverified | Uji muat naik kad matrik untuk mohon diskaun 10%, wishlist |
| **Pelajar Sah (Verified)**| `verified@siswa.upsi.edu.my` | Verified | Uji paparan harga potongan automatik 10% di laman utama |

---

## Struktur Folder Projek

```
HACK/
├── api/
│   ├── gemini_proxy.php      # Proksi cURL Gemini AI + Fallback engine
│   ├── listings.php          # API carian & tapisan kediaman berdiskaun
│   └── wishlist.php          # API simpanan kediaman pelajar
├── config/
│   ├── config.php            # Konfigurasi aplikasi & resolusi BASE_URL
│   ├── db.php                # Pengurus sambungan PDO MySQL
│   ├── config.local.php      # Tetapan sensitif tempatan (git-ignored)
│   └── config.local.php.example
├── database/
│   ├── schema.sql            # Skema 7 jadual pangkalan data relasional
│   └── seed.sql              # Data permulaan realistik akaun & kediaman
├── includes/
│   ├── auth.php              # Pengurusan sesi, RBAC, dan keselamatan
│   ├── csrf.php              # Perlindungan token CSRF
│   ├── helpers.php           # Sanitasi XSS & kalkulator mata wang
│   ├── header.php            # Navigasi atas, penunjuk diskaun & mod teks
│   └── footer.php            # Navigasi bawah, panduan, dan skrip toast
├── public/
│   ├── admin/
│   │   ├── dashboard.php     # Panel moderasi pentadbir (UC013)
│   │   └── users.php         # Pengurusan akaun & status pengguna (FR022)
│   ├── forgot_password.php   # Permohonan token penetapan kata laluan
│   ├── index.php             # Laman utama carian, galeri & kalkulator sewa
│   ├── landlord_dashboard.php# Portal tuan rumah & iklan baru
│   ├── login.php             # Borang log masuk selamat dengan semakan status
│   ├── logout.php            # Pemusnahan sesi selamat
│   ├── profile.php           # Kemaskini profil & notifikasi sistem
│   ├── register.php          # Pendaftaran pelajar / tuan rumah (PDPA)
│   ├── reset_password.php    # Borang penetapan semula kata laluan
│   ├── roommate_hub.php      # Pusat komuniti rakan sebilik
│   ├── verify_student.php    # Muat naik bukti pendaftaran pelajar (10%)
│   └── wishlist.php          # Paparan kediaman disimpan
├── uploads/
│   ├── properties/           # Foto muat naik kediaman
│   └── verifications/        # Dokumen kad matrik/surat tawaran
├── .gitignore
├── ANALYSIS.md               # Analisis komprehensif SRS, laluan & skema
├── ASSUMPTIONS.md            # Rekod andaian kejuruteraan
├── CONFLICTS.md              # Log penyelesaian konflik SRS vs index.html
├── index.php                 # Pemaju pautan punca ke /public/
├── README.md
└── TESTING.md                # Senarai semak & log ujian terperinci
```

---

## Ciri Keselamatan (Security Implementation)

* **Bcrypt Password Hashing:** Menggunakan fungsi `password_hash($pwd, PASSWORD_BCRYPT)` dan `password_verify()`.
* **Perlindungan Serangan SQL Injection:** 100% pertanyaan pangkalan data menggunakan PDO prepared statements dengan parameter terikat.
* **Perlindungan Serangan CSRF:** Setiap borang POST menyertakan token CSRF kriptografi yang disahkan pada pelayan web sebelum pemprosesan data.
* **Perlindungan Serangan XSS:** Semua data dinamik disanitasi menggunakan pembantu `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
* **Pematuhan PDPA 2010:** Setiap borang yang mengumpul maklumat peribadi mempunyai kotak pengesahan persetujuan mandatori pada klien dan pelayan.
* **Kawalan Akses Berasaskan Peranan (RBAC):** Disemak pada setiap titik capaian halaman dan fail API (`requireAuth()`, `requireRole()`), bukan sekadar menyembunyikan butang antaramuka.
* **Keselamatan Kunci Rahsia:** Kunci API Google Gemini disimpan pada fail pelayan `config.local.php` yang disenaraikan di dalam `.gitignore` dan dipanggil secara proksi cURL di bahagian pelayan sahaja.
