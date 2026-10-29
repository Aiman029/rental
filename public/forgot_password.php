<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

$message = '';
$error = '';
$resetUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Sila masukkan alamat e-mel anda.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT user_id, name, email FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + 86400); // 24 hours expiry

                $updateStmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE user_id = :id");
                $updateStmt->execute([
                    'token' => $token,
                    'expiry' => $expiry,
                    'id' => $user['user_id']
                ]);

                $resetUrl = BASE_URL . '/public/reset_password.php?token=' . $token;
                $message = 'Pautan penetapan semula kata laluan telah dijana dengan selamat (Sah selama 24 jam).';
            } else {
                // To prevent email enumeration, still display generic message
                $message = 'Sekiranya e-mel tersebut berdaftar, arahan penetapan semula kata laluan telah dihantar.';
            }
        } catch (Exception $e) {
            error_log('Forgot password error: ' . $e->getMessage());
            $error = 'Ralat pangkalan data semasa proses tetapan semula kata laluan.';
        }
    }
}

$pageTitle = 'Lupa Kata Laluan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto my-10 p-6 sm:p-8 bg-white border border-pinkbrand-100 rounded-3xl shadow-xl">
    <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl pink-gradient flex items-center justify-center text-white mx-auto mb-3 shadow-md shadow-pinkbrand-200">
            <i class="fa-solid fa-key text-2xl"></i>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-800">Lupa Kata Laluan?</h2>
        <p class="text-xs text-slate-500 mt-1">Masukkan alamat e-mel berdaftar anda untuk menetapkan semula kata laluan akaun.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation shrink-0"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800">
            <p class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span><?= e($message) ?></span>
            </p>
            <?php if (!empty($resetUrl)): ?>
                <div class="mt-3 p-3 bg-white border border-emerald-300 rounded-xl text-xs text-slate-700">
                    <p class="font-bold text-emerald-700 mb-1">Simulasi Pautan Emel (Persekitaran Tempatan):</p>
                    <a href="<?= $resetUrl ?>" class="break-all text-pinkbrand-600 font-bold underline hover:text-pinkbrand-700">
                        <?= $resetUrl ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/public/forgot_password.php" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Alamat E-mel</label>
            <div class="relative">
                <i class="fa-solid fa-envelope absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="contoh: student@siswa.upsi.edu.my" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>
        </div>

        <button type="submit" class="w-full py-3 rounded-2xl pink-gradient text-white font-extrabold text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
            Hantar Pautan Penetapan
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
        Ingat kata laluan anda? 
        <a href="<?= BASE_URL ?>/public/login.php" class="text-pinkbrand-600 font-extrabold hover:underline">Kembali ke Log Masuk</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
