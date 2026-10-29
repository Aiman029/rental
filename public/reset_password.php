<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = false;

if (empty($token)) {
    $error = 'Token penetapan semula tidak sah atau tiada.';
} else {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT user_id, email, reset_token_expiry FROM users WHERE reset_token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Token penetapan semula tidak sah atau telah digunakan.';
        } elseif (strtotime($user['reset_token_expiry']) < time()) {
            $error = 'Token penetapan semula telah tamat tempoh (melebihi 24 jam). Sila pohon pautan baru.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validate_csrf_or_die();

            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($password) || strlen($password) < 6) {
                $error = 'Kata laluan mestilah sekurang-kurangnya 6 aksara.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Pengesahan kata laluan tidak sepadan.';
            } else {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $update = $pdo->prepare("UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = :id");
                $update->execute([
                    'hash' => $newHash,
                    'id' => $user['user_id']
                ]);

                $success = true;
                set_flash('success', 'Kata laluan anda telah berjaya dikemaskini. Sila log masuk dengan kata laluan baru.');
                header('Location: ' . BASE_URL . '/public/login.php');
                exit;
            }
        }
    } catch (Exception $e) {
        error_log('Reset password error: ' . $e->getMessage());
        $error = 'Ralat pangkalan data semasa menetapkan kata laluan baru.';
    }
}

$pageTitle = 'Tetapkan Kata Laluan Baru';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto my-10 p-6 sm:p-8 bg-white border border-pinkbrand-100 rounded-3xl shadow-xl">
    <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl pink-gradient flex items-center justify-center text-white mx-auto mb-3 shadow-md shadow-pinkbrand-200">
            <i class="fa-solid fa-shield-keyhole text-2xl"></i>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-800">Kata Laluan Baru</h2>
        <p class="text-xs text-slate-500 mt-1">Masukkan kata laluan baru yang kukuh untuk akaun anda.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation shrink-0"></i>
            <span><?= e($error) ?></span>
        </div>
        <div class="text-center mt-4">
            <a href="<?= BASE_URL ?>/public/forgot_password.php" class="text-xs text-pinkbrand-600 font-bold hover:underline">
                Pohon Pautan Baru
            </a>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/public/reset_password.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Kata Laluan Baru</label>
                <input type="password" name="password" required placeholder="Minimum 6 aksara" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Sahkan Kata Laluan Baru</label>
                <input type="password" name="confirm_password" required placeholder="Ulang kata laluan baru" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>

            <button type="submit" class="w-full py-3 rounded-2xl pink-gradient text-white font-extrabold text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
                Simpan Kata Laluan Baru
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
