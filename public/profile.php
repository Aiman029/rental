<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

requireAuth();
$user = currentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($phone)) {
        $error = 'Sila lengkapkan nama dan nombor telefon.';
    } else {
        try {
            $pdo = getDbConnection();
            $cleanPhone = clean_whatsapp_number($phone);
            $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone WHERE user_id = :id");
            $stmt->execute([
                'name' => $name,
                'phone' => $cleanPhone,
                'id' => $user['user_id']
            ]);

            set_flash('success', 'Profil anda berjaya dikemaskini.');
            header('Location: ' . BASE_URL . '/public/profile.php');
            exit;
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $error = 'Ralat pangkalan data semasa mengemaskini profil.';
        }
    }
}

// Fetch user's notifications (FR018)
$notifications = [];
try {
    $pdo = getDbConnection();
    $nStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $nStmt->execute([$user['user_id']]);
    $notifications = $nStmt->fetchAll();

    // Mark as read
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['user_id']]);
} catch (Exception $e) {
    $notifications = [];
}

$pageTitle = 'Profil Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto my-8 px-4 sm:px-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Sidebar Info -->
        <div class="bg-white p-6 rounded-3xl border border-pinkbrand-100 shadow-sm text-center">
            <div class="w-20 h-20 rounded-3xl pink-gradient flex items-center justify-center text-white mx-auto mb-4 text-3xl font-extrabold shadow-lg shadow-pinkbrand-200">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <h2 class="text-xl font-extrabold text-slate-800"><?= e($user['name']) ?></h2>
            <p class="text-xs text-slate-500 mb-3"><?= e($user['email']) ?></p>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold capitalize mb-4 <?= 
                $user['role'] === 'admin' ? 'bg-purple-100 text-purple-700' :
                ($user['role'] === 'landlord' ? 'bg-amber-100 text-amber-800' : 'bg-pinkbrand-100 text-pinkbrand-700')
            ?>">
                <i class="fa-solid fa-user-shield"></i>
                <span>Peranan: <?= e($user['role']) ?></span>
            </div>

            <!-- Student Verification Status Pill (FR009 / FR010) -->
            <?php if ($user['role'] === 'student'): ?>
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-left text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Status Pengesahan:</span>
                        <?php if ($user['verification_status'] === 'verified'): ?>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-extrabold">Sah (Diskaun 10%)</span>
                        <?php elseif ($user['verification_status'] === 'pending'): ?>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-extrabold">Dalam Semakan</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-extrabold">Belum Disahkan</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($user['verification_status'] !== 'verified'): ?>
                        <a href="<?= BASE_URL ?>/public/verify_student.php" class="block w-full text-center py-2 rounded-xl bg-pinkbrand-500 hover:bg-pinkbrand-600 text-white font-bold transition">
                            Hantar Kad Matrik
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Form & Notifications -->
        <div class="md:col-span-2 space-y-6">
            <!-- Profile Settings (UC004) -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-pinkbrand-100 shadow-sm">
                <h3 class="text-lg font-extrabold text-slate-800 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-user-gear text-pinkbrand-500"></i> Kemaskini Maklumat Profil
                </h3>
                <p class="text-xs text-slate-500 mb-6">Urus nama paparan dan nombor telefon yang dipautkan ke akaun anda.</p>

                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/public/profile.php" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nama Penuh</label>
                        <input type="text" name="name" value="<?= e($user['name']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Alamat E-mel (Kekal / Tidak Boleh Diubah)</label>
                        <input type="email" value="<?= e($user['email']) ?>" disabled class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-sm font-semibold text-slate-500 cursor-not-allowed">
                        <p class="text-[10px] text-slate-400 mt-1">E-mel utama tidak boleh diubah secara terus demi keselamatan akaun (SRS UC004).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombor Telefon / WhatsApp</label>
                        <input type="text" name="phone" value="<?= e($user['phone']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-6 py-2.5 rounded-2xl pink-gradient text-white font-extrabold text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- In-App Notifications (FR018) -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-pinkbrand-100 shadow-sm">
                <h3 class="text-lg font-extrabold text-slate-800 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-bell text-pinkbrand-500"></i> Notifikasi Dalam Sistem
                </h3>
                <p class="text-xs text-slate-500 mb-4">Pemberitahuan terkini mengenai status semakan, pengesahan, dan pengumuman.</p>

                <?php if (empty($notifications)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center italic">Tiada notifikasi pada masa ini.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($notifications as $n): ?>
                            <div class="p-3.5 rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-slate-50 transition">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-extrabold text-slate-800"><?= e($n['title']) ?></span>
                                    <span class="text-[10px] text-slate-400"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></span>
                                </div>
                                <p class="text-xs text-slate-600"><?= e($n['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
