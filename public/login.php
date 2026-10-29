<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

// If already logged in, redirect
if (isLoggedIn()) {
    $user = currentUser();
    if ($user['role'] === 'admin') {
        header('Location: ' . BASE_URL . '/public/admin/dashboard.php');
    } elseif ($user['role'] === 'landlord') {
        header('Location: ' . BASE_URL . '/public/landlord_dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/public/index.php');
    }
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

if (isset($_GET['error']) && $_GET['error'] === 'timeout') {
    $error = 'Sesi anda telah tamat tempoh akibat tiada aktiviti. Sila log masuk semula.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Sila masukkan alamat e-mel dan kata laluan.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ((int)$user['is_active'] !== 1) {
                    $error = 'Akaun anda telah digantung oleh Pentadbir Sistem. Sila hubungi pihak pentadbiran.';
                } else {
                    loginUser($user);
                    set_flash('success', 'Selamat kembali, ' . $user['name'] . '!');

                    if (!empty($redirect) && str_starts_with($redirect, BASE_URL)) {
                        header('Location: ' . $redirect);
                        exit;
                    }

                    if ($user['role'] === 'admin') {
                        header('Location: ' . BASE_URL . '/public/admin/dashboard.php');
                    } elseif ($user['role'] === 'landlord') {
                        header('Location: ' . BASE_URL . '/public/landlord_dashboard.php');
                    } else {
                        header('Location: ' . BASE_URL . '/public/index.php');
                    }
                    exit;
                }
            } else {
                $error = 'E-mel atau kata laluan tidak tepat. Sila cuba lagi.';
            }
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Ralat pangkalan data semasa proses log masuk.';
        }
    }
}

$pageTitle = 'Log Masuk';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto my-10 p-6 sm:p-8 bg-white border border-pinkbrand-100 rounded-3xl shadow-xl">
    <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl pink-gradient flex items-center justify-center text-white mx-auto mb-3 shadow-md shadow-pinkbrand-200">
            <i class="fa-solid fa-lock text-2xl"></i>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-800">Log Masuk UpsiRent-I</h2>
        <p class="text-xs text-slate-500 mt-1">Akses akaun anda untuk melihat simpanan, mengiklan, atau menyemak sewaan.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation shrink-0"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/public/login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Alamat E-mel</label>
            <div class="relative">
                <i class="fa-solid fa-envelope absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email" placeholder="contoh: pelajar@siswa.upsi.edu.my" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-bold text-slate-600 uppercase">Kata Laluan</label>
                <a href="<?= BASE_URL ?>/public/forgot_password.php" class="text-xs text-pinkbrand-600 hover:underline font-semibold">Lupa Kata Laluan?</a>
            </div>
            <div class="relative">
                <i class="fa-solid fa-key absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>
        </div>

        <button type="submit" class="w-full py-3 rounded-2xl pink-gradient text-white font-extrabold text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
            Log Masuk Sekarang
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
        Belum mempunyai akaun? 
        <a href="<?= BASE_URL ?>/public/register.php" class="text-pinkbrand-600 font-extrabold hover:underline">Daftar Akaun Baru</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
