<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

$error = '';
$prefillRole = $_GET['role'] ?? 'student';
if (!in_array($prefillRole, ['student', 'landlord'], true)) {
    $prefillRole = 'student';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? 'student');
    $pdpa = isset($_POST['pdpa_consent']);

    // Admin accounts CANNOT be created via public registration
    if ($role !== 'student' && $role !== 'landlord') {
        $role = 'student';
    }

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Sila lengkapkan semua ruangan mandatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format e-mel tidak sah.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata laluan mestilah sekurang-kurangnya 6 aksara.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Pengesahan kata laluan tidak sepadan.';
    } elseif (!$pdpa) {
        // PDPA consent mandatory check (FR019 / UC001)
        $error = 'Anda wajib bersetuju dengan syarat pematuhan PDPA 2010 sebelum mendaftar.';
    } else {
        try {
            $pdo = getDbConnection();

            // Check duplicate email
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
            $checkStmt->execute(['email' => $email]);
            if ($checkStmt->fetch()) {
                $error = 'Alamat e-mel ini telah didaftarkan. Sila gunakan e-mel lain atau log masuk.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $cleanPhone = clean_whatsapp_number($phone);

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, phone, role, verification_status, is_active, pdpa_consent)
                    VALUES (:name, :email, :hash, :phone, :role, 'unverified', 1, 1)
                ");
                $insertStmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'hash' => $passwordHash,
                    'phone' => $cleanPhone,
                    'role' => $role
                ]);

                $newUserId = (int)$pdo->lastInsertId();

                // Send welcome notification
                $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'general')");
                $notifStmt->execute([
                    $newUserId,
                    'Selamat Datang ke UpsiRent-I!',
                    'Akaun anda telah berjaya dicipta. Jika anda pelajar, sila kemukakan kad matrik untuk menikmati diskaun 10%.'
                ]);

                set_flash('success', 'Pendaftaran akaun berjaya! Sila log masuk dengan akaun baru anda.');
                header('Location: ' . BASE_URL . '/public/login.php');
                exit;
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $error = 'Ralat pangkalan data semasa pendaftaran akaun.';
        }
    }
}

$pageTitle = 'Daftar Akaun Baru';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-lg mx-auto my-10 p-6 sm:p-8 bg-white border border-pinkbrand-100 rounded-3xl shadow-xl">
    <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl pink-gradient flex items-center justify-center text-white mx-auto mb-3 shadow-md shadow-pinkbrand-200">
            <i class="fa-solid fa-user-plus text-2xl"></i>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-800">Daftar Akaun UpsiRent-I</h2>
        <p class="text-xs text-slate-500 mt-1">Sertai komuniti kediaman pelajar UPSI Tanjung Malim & Proton City</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation shrink-0"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/public/register.php" class="space-y-4">
        <?= csrf_field() ?>

        <!-- Role Selection -->
        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Pilih Peranan Akaun</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer border-2 rounded-2xl p-3 flex flex-col items-center gap-1.5 transition hover:border-pinkbrand-400 has-[:checked]:border-pinkbrand-500 has-[:checked]:bg-pinkbrand-50">
                    <input type="radio" name="role" value="student" <?= ($prefillRole === 'student') ? 'checked' : '' ?> class="sr-only">
                    <i class="fa-solid fa-graduation-cap text-pinkbrand-500 text-lg"></i>
                    <span class="text-xs font-extrabold text-slate-800">Pelajar UPSI / IPT</span>
                    <span class="text-[10px] text-slate-500 text-center">Cari rumah & rakan bilik</span>
                </label>
                <label class="cursor-pointer border-2 rounded-2xl p-3 flex flex-col items-center gap-1.5 transition hover:border-pinkbrand-400 has-[:checked]:border-pinkbrand-500 has-[:checked]:bg-pinkbrand-50">
                    <input type="radio" name="role" value="landlord" <?= ($prefillRole === 'landlord') ? 'checked' : '' ?> class="sr-only">
                    <i class="fa-solid fa-house-chimney text-slate-700 text-lg"></i>
                    <span class="text-xs font-extrabold text-slate-800">Tuan Rumah / Ejen</span>
                    <span class="text-[10px] text-slate-500 text-center">Iklan rumah & homestay</span>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nama Penuh</label>
            <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required placeholder="contoh: Muhammad Danial bin Faris" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Alamat E-mel</label>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="contoh: nama@siswa.upsi.edu.my" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombor Telefon / WhatsApp</label>
            <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required placeholder="contoh: 0191234567" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Kata Laluan</label>
                <input type="password" name="password" required placeholder="Minimum 6 aksara" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Sahkan Kata Laluan</label>
                <input type="password" name="confirm_password" required placeholder="Ulang kata laluan" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>
        </div>

        <!-- Mandatory PDPA Consent Checkbox (FR019 / CR004 / DC005) -->
        <div class="p-3.5 bg-pinkbrand-50/70 border border-pinkbrand-200 rounded-2xl">
            <label class="flex items-start gap-2.5 text-xs text-slate-700 cursor-pointer">
                <input type="checkbox" name="pdpa_consent" value="1" required class="mt-0.5 rounded text-pinkbrand-600 focus:ring-pinkbrand-500 w-4 h-4">
                <span>
                    <strong>Persetujuan PDPA 2010:</strong> Saya dengan ini membenarkan UpsiRent-I mengumpul dan memproses maklumat peribadi saya bagi tujuan carian kediaman, verifikasi pelajar, dan hubungan sewaan selaras dengan Akta Perlindungan Data Peribadi 2010.
                </span>
            </label>
        </div>

        <button type="submit" class="w-full py-3 rounded-2xl pink-gradient text-white font-extrabold text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
            Daftar Akaun
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
        Sudah mempunyai akaun? 
        <a href="<?= BASE_URL ?>/public/login.php" class="text-pinkbrand-600 font-extrabold hover:underline">Log Masuk di Sini</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
