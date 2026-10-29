<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole('student');
$user = currentUser();
$error = '';
$success = '';

// Check recent verification submissions
$latestVerif = null;
try {
    $pdo = getDbConnection();
    $vStmt = $pdo->prepare("SELECT * FROM student_verifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
    $vStmt->execute([$user['user_id']]);
    $latestVerif = $vStmt->fetch();
} catch (Exception $e) {
    error_log('Verify check error: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $studentName = trim($_POST['student_name'] ?? '');
    $matricNo = strtoupper(trim($_POST['matric_number'] ?? ''));
    $docType = trim($_POST['document_type'] ?? 'Kad Matrik Pelajar');
    $pdpa = isset($_POST['pdpa_consent']);

    if (empty($studentName) || empty($matricNo)) {
        $error = 'Sila lengkapkan nama pelajar dan nombor matrik.';
    } elseif (!$pdpa) {
        $error = 'Persetujuan PDPA 2010 adalah wajib sebelum memuat naik dokumen pengenalan pelajar.';
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Sila pilih fail dokumen yang sah untuk dimuat naik.';
    } else {
        $file = $_FILES['document'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $maxBytes = 5 * 1024 * 1024; // 5 MB

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes, true)) {
            $error = 'Format fail tidak disokong. Sila muat naik fail imej (JPG, PNG, WebP) atau fail PDF.';
        } elseif ($file['size'] > $maxBytes) {
            $error = 'Saiz fail melebihi had maksimum 5MB.';
        } else {
            // Determine safe extension
            $extMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf'
            ];
            $ext = $extMap[$mime] ?? 'bin';
            $newFileName = 'verif_' . $user['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destPath = UPLOAD_VERIFICATIONS_DIR . '/' . $newFileName;
            $dbPath = 'uploads/verifications/' . $newFileName;

            if (!is_dir(UPLOAD_VERIFICATIONS_DIR)) {
                mkdir(UPLOAD_VERIFICATIONS_DIR, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                try {
                    $pdo = getDbConnection();
                    // Insert verification record
                    $ins = $pdo->prepare("
                        INSERT INTO student_verifications 
                        (student_id, student_name, matric_number, document_type, document_path, status, pdpa_consent)
                        VALUES (:uid, :sname, :matric, :dtype, :dpath, 'Pending', 1)
                    ");
                    $ins->execute([
                        'uid' => $user['user_id'],
                        'sname' => $studentName,
                        'matric' => $matricNo,
                        'dtype' => $docType,
                        'dpath' => $dbPath
                    ]);

                    // Update user's verification status
                    $upd = $pdo->prepare("UPDATE users SET verification_status = 'pending' WHERE user_id = ?");
                    $upd->execute([$user['user_id']]);

                    // Notification
                    $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'verification')");
                    $notif->execute([
                        $user['user_id'],
                        'Dokumen Pengesahan Diterima',
                        'Dokumen kad matrik anda telah selamat diterima dan kini sedang disemak oleh Pentadbir Sistem.'
                    ]);

                    set_flash('success', 'Dokumen pengesahan anda berjaya dihantar! Pentadbir akan menyemak permohonan anda dalam tempoh 24 jam.');
                    header('Location: ' . BASE_URL . '/public/verify_student.php');
                    exit;
                } catch (Exception $e) {
                    error_log('Verification save error: ' . $e->getMessage());
                    $error = 'Ralat pangkalan data semasa menyimpan rekod pengesahan.';
                }
            } else {
                $error = 'Gagal menyimpan fail ke pelayan. Sila cuba lagi.';
            }
        }
    }
}

$pageTitle = 'Pengesahan Pelajar UPSI (Diskaun 10%)';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="max-w-3xl mx-auto my-8 px-4 sm:px-6">
    <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-3xl bg-emerald-500 text-white flex items-center justify-center mx-auto mb-3 shadow-lg shadow-emerald-200 text-2xl font-bold">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800">Pengesahan Pelajar UPSI</h1>
        <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mt-1">
            Muat naik Kad Matrik atau Surat Tawaran untuk membuka <strong>Diskaun Automatik 10%</strong> bagi semua sewaan rumah dan homestay.
        </p>
    </div>

    <!-- Status Display Card -->
    <?php if ($user['verification_status'] === 'verified'): ?>
        <div class="p-6 bg-emerald-50 border-2 border-emerald-300 rounded-3xl shadow-sm text-center mb-8">
            <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h3 class="text-lg font-extrabold text-emerald-900">Akaun Anda Telah Disahkan!</h3>
            <p class="text-xs text-emerald-700 mt-1 max-w-md mx-auto">
                Tahniah! Status pelajar anda aktif. Anda kini menikmati potongan harga 10% secara automatik pada semua iklan sewaan di UpsiRent-I.
            </p>
            <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-emerald-200 text-emerald-800 font-bold text-xs shadow-sm">
                <i class="fa-solid fa-id-card"></i>
                <span>Matrik: <?= e($latestVerif['matric_number'] ?? 'Sah') ?></span>
                <span>•</span>
                <span>Disahkan Pada: <?= !empty($latestVerif['reviewed_at']) ? date('d M Y', strtotime($latestVerif['reviewed_at'])) : date('d M Y') ?></span>
            </div>
            <div class="mt-6">
                <a href="<?= BASE_URL ?>/public/index.php" class="px-5 py-2.5 rounded-xl pink-gradient text-white font-extrabold text-xs shadow hover:opacity-95 transition">
                    Cari Rumah Sewa Berdiskaun
                </a>
            </div>
        </div>
    <?php elseif ($user['verification_status'] === 'pending'): ?>
        <div class="p-6 bg-amber-50 border-2 border-amber-300 rounded-3xl shadow-sm text-center mb-8">
            <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-solid fa-clock"></i>
            </div>
            <h3 class="text-lg font-extrabold text-amber-900">Permohonan Sedang Disemak</h3>
            <p class="text-xs text-amber-700 mt-1 max-w-md mx-auto">
                Dokumen pengesahan anda telah diterima pada <strong><?= date('d M Y, h:i A', strtotime($latestVerif['created_at'] ?? 'now')) ?></strong>. Pentadbir kami sedang menyemak kesahihan maklumat matrik anda.
            </p>
            <div class="mt-4 text-xs font-semibold text-amber-800 bg-amber-100/70 py-2 px-4 rounded-xl inline-block">
                No. Matrik: <strong><?= e($latestVerif['matric_number'] ?? '-') ?></strong> | Jenis: <?= e($latestVerif['document_type'] ?? '-') ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Submission Form (Visible if unverified or rejected) -->
    <?php if ($user['verification_status'] === 'unverified' || $user['verification_status'] === 'rejected'): ?>
        <?php if ($user['verification_status'] === 'rejected'): ?>
            <div class="p-4 bg-rose-50 border-2 border-rose-300 rounded-2xl text-xs text-rose-800 mb-6">
                <div class="font-extrabold flex items-center gap-1.5 mb-1">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Permohonan Terdahulu Ditolak</span>
                </div>
                <p>Nota Pentadbir: <em><?= e($latestVerif['admin_notes'] ?? 'Imej tidak jelas atau maklumat matrik tidak sepadan.') ?></em></p>
                <p class="mt-1 font-semibold">Sila muat naik semula dokumen yang jelas di bawah.</p>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-pinkbrand-100 shadow-xl">
            <h3 class="text-base font-extrabold text-slate-800 mb-2 flex items-center gap-2">
                <i class="fa-solid fa-id-card-clip text-pinkbrand-500"></i> Borang Pengesahan Pelajar
            </h3>
            <p class="text-xs text-slate-500 mb-6">Pastikan nama dan gambar kad matrik dapat dibaca dengan jelas oleh pentadbir.</p>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/public/verify_student.php" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nama Penuh Pelajar (Mengikut Kad Matrik)</label>
                    <input type="text" name="student_name" value="<?= e($_POST['student_name'] ?? $user['name']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombor Matrik Pelajar (UPSI)</label>
                        <input type="text" name="matric_number" value="<?= e($_POST['matric_number'] ?? '') ?>" required placeholder="Cth: D20221098877" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold uppercase focus:outline-none focus:border-pinkbrand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Jenis Dokumen Bukti</label>
                        <select name="document_type" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-pinkbrand-500">
                            <option value="Kad Matrik Pelajar">Kad Matrik Pelajar (Fizikal / Digital)</option>
                            <option value="Surat Tawaran Kemasukan UPSI">Surat Tawaran Kemasukan UPSI</option>
                            <option value="Slip Pendaftaran Kursus Sem Semasa">Slip Pendaftaran Kursus Sem Semasa</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Muat Naik Fail Dokumen (JPG, PNG, WebP atau PDF - Maksimum 5MB)</label>
                    <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center hover:border-pinkbrand-400 transition bg-slate-50">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-pinkbrand-500 mb-2"></i>
                        <p class="text-xs font-bold text-slate-700">Pilih fail dokumen dari peranti anda</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Format disokong: JPG, PNG, PDF sehingga 5MB</p>
                        <input type="file" name="document" required accept="image/jpeg,image/png,image/webp,application/pdf" class="mt-3 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:pink-gradient file:text-white hover:file:opacity-90">
                    </div>
                </div>

                <!-- Mandatory PDPA Consent Checkbox (FR019 / CR004) -->
                <div class="p-3.5 bg-pinkbrand-50/70 border border-pinkbrand-200 rounded-2xl">
                    <label class="flex items-start gap-2.5 text-xs text-slate-700 cursor-pointer">
                        <input type="checkbox" name="pdpa_consent" value="1" required class="mt-0.5 rounded text-pinkbrand-600 focus:ring-pinkbrand-500 w-4 h-4">
                        <span>
                            <strong>Persetujuan PDPA 2010:</strong> Saya mengesahkan bahawa maklumat dan dokumen yang dimuat naik adalah sah milik saya, dan saya bersetuju UpsiRent-I memproses data ini bagi kelayakan diskaun pelajar mengikut Akta Perlindungan Data Peribadi 2010.
                        </span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-2xl pink-gradient text-white font-extrabold text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
                    Hantar untuk Pengesahan Admin
                </button>
            </form>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
