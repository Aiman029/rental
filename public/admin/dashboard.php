<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole('admin');
$user = currentUser();
$error = '';
$success = '';

$pdo = getDbConnection();

// Handle Moderation Decisions (Approve / Reject) - UC013
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $actionType = $_POST['action_type'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);
    $decision = $_POST['decision'] ?? ''; // 'approve' or 'reject'
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    try {
        if ($actionType === 'verification') {
            $vStmt = $pdo->prepare("SELECT * FROM student_verifications WHERE verification_id = ?");
            $vStmt->execute([$itemId]);
            $verif = $vStmt->fetch();

            if ($verif) {
                if ($decision === 'approve') {
                    $pdo->prepare("UPDATE student_verifications SET status = 'Approved', reviewed_at = NOW(), admin_notes = :notes WHERE verification_id = :id")
                        ->execute(['notes' => $adminNotes ?: 'Dokumen kad matrik disahkan sah.', 'id' => $itemId]);
                    
                    // Upgrade student role status to verified (FR010 / UC013)
                    $pdo->prepare("UPDATE users SET verification_status = 'verified' WHERE user_id = :uid")
                        ->execute(['uid' => $verif['student_id']]);

                    // Send in-app notification (FR018)
                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'verification')")
                        ->execute([
                            $verif['student_id'],
                            'Pengesahan Kad Matrik Berjaya!',
                            'Tahniah, dokumen kad matrik anda telah disahkan oleh Pentadbir. Anda kini menikmati diskaun automatik 10% untuk semua sewaan di UpsiRent-I!'
                        ]);

                    set_flash('success', 'Pengesahan pelajar bagi ' . $verif['student_name'] . ' berjaya diluluskan.');
                } else {
                    $pdo->prepare("UPDATE student_verifications SET status = 'Rejected', reviewed_at = NOW(), admin_notes = :notes WHERE verification_id = :id")
                        ->execute(['notes' => $adminNotes ?: 'Dokumen tidak memenuhi kriteria pengesahan.', 'id' => $itemId]);
                    
                    $pdo->prepare("UPDATE users SET verification_status = 'rejected' WHERE user_id = :uid")
                        ->execute(['uid' => $verif['student_id']]);

                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'verification')")
                        ->execute([
                            $verif['student_id'],
                            'Pengesahan Kad Matrik Ditolak',
                            'Maaf, permohonan pengesahan pelajar anda ditolak. Sebab: ' . ($adminNotes ?: 'Dokumen tidak jelas/tidak sah. Sila muat naik semula.')
                        ]);

                    set_flash('info', 'Permohonan pengesahan telah ditolak.');
                }
            }
        } elseif ($actionType === 'property') {
            $pStmt = $pdo->prepare("SELECT * FROM properties WHERE property_id = ?");
            $pStmt->execute([$itemId]);
            $prop = $pStmt->fetch();

            if ($prop) {
                if ($decision === 'approve') {
                    $pdo->prepare("UPDATE properties SET status = 'Approved', admin_notes = :notes WHERE property_id = :id")
                        ->execute(['notes' => $adminNotes, 'id' => $itemId]);

                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'property')")
                        ->execute([
                            $prop['landlord_id'],
                            'Iklan Kediaman Diluluskan!',
                            'Iklan "' . $prop['title'] . '" anda telah diluluskan dan kini boleh dilihat oleh semua pelajar di portal awam.'
                        ]);

                    set_flash('success', 'Iklan kediaman "' . $prop['title'] . '" telah diluluskan dan kini aktif.');
                } else {
                    $pdo->prepare("UPDATE properties SET status = 'Rejected', admin_notes = :notes WHERE property_id = :id")
                        ->execute(['notes' => $adminNotes ?: 'Iklan tidak mematuhi syarat penerbitan UpsiRent-I.', 'id' => $itemId]);

                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'property')")
                        ->execute([
                            $prop['landlord_id'],
                            'Iklan Kediaman Ditolak',
                            'Iklan "' . $prop['title'] . '" tidak diluluskan. Sebab: ' . ($adminNotes ?: 'Maklumat tidak lengkap atau meragukan.')
                        ]);

                    set_flash('info', 'Iklan kediaman telah ditolak.');
                }
            }
        } elseif ($actionType === 'roommate') {
            $rmStmt = $pdo->prepare("SELECT * FROM roommate_ads WHERE ad_id = ?");
            $rmStmt->execute([$itemId]);
            $rm = $rmStmt->fetch();

            if ($rm) {
                if ($decision === 'approve') {
                    $pdo->prepare("UPDATE roommate_ads SET status = 'Approved', admin_notes = :notes WHERE ad_id = :id")
                        ->execute(['notes' => $adminNotes, 'id' => $itemId]);

                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'roommate')")
                        ->execute([
                            $rm['student_id'],
                            'Iklan Rakan Bilik Disiarkan!',
                            'Iklan pencarian rakan bilik anda telah diluluskan dan dipaparkan di Roommate Hub.'
                        ]);

                    set_flash('success', 'Iklan rakan bilik "' . $rm['name'] . '" telah diluluskan.');
                } else {
                    $pdo->prepare("UPDATE roommate_ads SET status = 'Rejected', admin_notes = :notes WHERE ad_id = :id")
                        ->execute(['notes' => $adminNotes ?: 'Maklumat iklan tidak bersesuaian.', 'id' => $itemId]);

                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'roommate')")
                        ->execute([
                            $rm['student_id'],
                            'Iklan Rakan Bilik Ditolak',
                            'Iklan rakan bilik anda tidak diluluskan. Sebab: ' . ($adminNotes ?: 'Kandungan iklan tidak mematuhi etika komuniti pelajar.')
                        ]);

                    set_flash('info', 'Iklan rakan bilik telah ditolak.');
                }
            }
        }

        header('Location: ' . BASE_URL . '/public/admin/dashboard.php');
        exit;
    } catch (Exception $e) {
        error_log('Moderation action error: ' . $e->getMessage());
        $error = 'Ralat pangkalan data semasa melaksanakan tindakan moderasi.';
    }
}

// Fetch pending items
$pendingVerifs = $pdo->query("SELECT v.*, u.email AS student_email FROM student_verifications v JOIN users u ON v.student_id = u.user_id WHERE v.status = 'Pending' ORDER BY v.created_at ASC")->fetchAll();
$pendingProps = $pdo->query("SELECT p.*, u.name AS landlord_name, u.email AS landlord_email FROM properties p JOIN users u ON p.landlord_id = u.user_id WHERE p.status = 'Pending' ORDER BY p.created_at ASC")->fetchAll();
$pendingRoommates = $pdo->query("SELECT r.*, u.email AS student_email FROM roommate_ads r JOIN users u ON r.student_id = u.user_id WHERE r.status = 'Pending' ORDER BY r.created_at ASC")->fetchAll();

// Statistics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProps = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'Approved'")->fetchColumn();
$totalVerified = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE verification_status = 'verified'")->fetchColumn();

$pageTitle = 'Panel Moderasi Pentadbir (Admin)';
require_once __DIR__ . '/../../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 mb-2">
                <i class="fa-solid fa-shield-check"></i> Kawalan Keselamatan & Moderasi SRS UC013
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800">Papan Pemuka Moderasi Pentadbir</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Semak dokumen pengesahan pelajar, kelulusan iklan kediaman, dan iklan rakan sebilik.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/public/admin/users.php" class="px-4 py-2.5 rounded-2xl bg-white border border-slate-200 hover:border-purple-300 text-slate-700 font-extrabold text-xs shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-users-gear text-purple-600"></i>
                <span>Urus Pengguna</span>
            </a>
            <a href="<?= BASE_URL ?>/public/index.php" class="px-4 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-700 font-bold text-xs shadow-sm hover:text-pinkbrand-600 transition flex items-center gap-1.5">
                <i class="fa-solid fa-globe"></i>
                <span>Laman Awam</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
        <div class="p-5 rounded-3xl bg-white border border-purple-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-800"><?= $totalUsers ?></div>
                <div class="text-[11px] text-slate-400 font-bold uppercase">Jumlah Pengguna</div>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-white border border-emerald-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-800"><?= $totalVerified ?></div>
                <div class="text-[11px] text-slate-400 font-bold uppercase">Pelajar Sah (10%)</div>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-white border border-pinkbrand-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-pinkbrand-100 text-pinkbrand-700 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-house-chimney-check"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-800"><?= $totalProps ?></div>
                <div class="text-[11px] text-slate-400 font-bold uppercase">Kediaman Aktif</div>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-white border border-amber-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-800"><?= count($pendingVerifs) + count($pendingProps) + count($pendingRoommates) ?></div>
                <div class="text-[11px] text-slate-400 font-bold uppercase">Menunggu Semakan</div>
            </div>
        </div>
    </div>

    <!-- MODERATION SECTION 1: PENDING STUDENT VERIFICATIONS (FR009 / UC013) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-check text-purple-600"></i>
                    <span>Semakan Kad Matrik & Pengesahan Pelajar</span>
                </h2>
                <p class="text-xs text-slate-500">Kelulusan akan mengaktifkan diskaun 10% automatik untuk pelajar berkenaan.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800">
                <?= count($pendingVerifs) ?> Menunggu Kelulusan
            </span>
        </div>

        <?php if (empty($pendingVerifs)): ?>
            <p class="text-xs text-slate-400 py-6 text-center italic">Tiada permohonan pengesahan pelajar yang sedang menunggu semakan.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pendingVerifs as $v): ?>
                    <div class="p-4 sm:p-5 rounded-2xl border border-slate-200 bg-slate-50/70 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 text-xs">
                        <div class="space-y-1">
                            <div class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                                <span><?= e($v['student_name']) ?></span>
                                <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-[10px]">Matrik: <?= e($v['matric_number']) ?></span>
                            </div>
                            <div class="text-slate-500">
                                E-mel: <?= e($v['student_email']) ?> • Jenis: <strong><?= e($v['document_type']) ?></strong> • Tarikh: <?= date('d M Y, h:i A', strtotime($v['created_at'])) ?>
                            </div>
                            <div class="pt-1">
                                <a href="<?= BASE_URL ?>/<?= e($v['document_path']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold hover:bg-indigo-100 transition">
                                    <i class="fa-solid fa-file-image"></i>
                                    <span>Buka & Periksa Dokumen Pengenalan</span>
                                </a>
                            </div>
                        </div>

                        <!-- Action Form -->
                        <form method="POST" action="<?= BASE_URL ?>/public/admin/dashboard.php" class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full md:w-auto">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action_type" value="verification">
                            <input type="hidden" name="item_id" value="<?= $v['verification_id'] ?>">

                            <input type="text" name="admin_notes" placeholder="Nota justifikasi (jika tolak)" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none w-full sm:w-48">

                            <button type="submit" name="decision" value="approve" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition whitespace-nowrap shadow-sm">
                                <i class="fa-solid fa-check mr-1"></i> Luluskan
                            </button>
                            <button type="submit" name="decision" value="reject" onclick="return confirm('Adakah anda pasti ingin menolak permohonan ini?')" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs transition whitespace-nowrap shadow-sm">
                                <i class="fa-solid fa-xmark mr-1"></i> Tolak
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODERATION SECTION 2: PENDING PROPERTY LISTINGS (FR013 / UC013) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-house-chimney-window text-pinkbrand-500"></i>
                    <span>Semakan Iklan Kediaman Tuan Rumah</span>
                </h2>
                <p class="text-xs text-slate-500">Iklan yang diluluskan akan serta-merta dipaparkan di portal carian awam.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800">
                <?= count($pendingProps) ?> Menunggu Kelulusan
            </span>
        </div>

        <?php if (empty($pendingProps)): ?>
            <p class="text-xs text-slate-400 py-6 text-center italic">Tiada iklan kediaman baru yang sedang menunggu semakan pentadbir.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pendingProps as $p): ?>
                    <div class="p-4 sm:p-5 rounded-2xl border border-slate-200 bg-slate-50/70 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 text-xs">
                        <div class="space-y-1 max-w-xl">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $p['category'] === 'homestay' ? 'bg-indigo-100 text-indigo-800' : 'bg-pinkbrand-100 text-pinkbrand-800' ?>">
                                    <?= e($p['category']) ?>
                                </span>
                                <span class="font-extrabold text-slate-900 text-sm"><?= e($p['title']) ?></span>
                            </div>
                            <div class="text-slate-600">
                                <strong>Kawasan:</strong> <?= e($p['location']) ?> • <strong>Sewa:</strong> RM <?= round($p['base_price']) ?>/<?= e($p['price_unit']) ?> • <strong>Kapasiti:</strong> <?= $p['recommended_pax'] ?> orang
                            </div>
                            <div class="text-slate-500 text-[11px]">
                                Tuan Rumah: <strong><?= e($p['landlord_name']) ?></strong> (<?= e($p['contact_phone']) ?>) • Dihantar: <?= date('d M Y', strtotime($p['created_at'])) ?>
                            </div>
                        </div>

                        <!-- Action Form -->
                        <form method="POST" action="<?= BASE_URL ?>/public/admin/dashboard.php" class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full md:w-auto">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action_type" value="property">
                            <input type="hidden" name="item_id" value="<?= $p['property_id'] ?>">

                            <input type="text" name="admin_notes" placeholder="Nota justifikasi (jika tolak)" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none w-full sm:w-48">

                            <button type="submit" name="decision" value="approve" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition whitespace-nowrap shadow-sm">
                                <i class="fa-solid fa-check mr-1"></i> Luluskan
                            </button>
                            <button type="submit" name="decision" value="reject" onclick="return confirm('Adakah anda pasti ingin menolak iklan kediaman ini?')" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs transition whitespace-nowrap shadow-sm">
                                <i class="fa-solid fa-xmark mr-1"></i> Tolak
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODERATION SECTION 3: PENDING ROOMMATE ADS (FR016 / UC013) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-users-viewfinder text-indigo-600"></i>
                    <span>Semakan Iklan Rakan Bilik (Roommate Hub)</span>
                </h2>
                <p class="text-xs text-slate-500">Iklan rakan bilik yang disahkan akan diterbitkan pada papan komuniti pelajar.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800">
                <?= count($pendingRoommates) ?> Menunggu Kelulusan
            </span>
        </div>

        <?php if (empty($pendingRoommates)): ?>
            <p class="text-xs text-slate-400 py-6 text-center italic">Tiada iklan rakan bilik yang sedang menunggu kelulusan pentadbir.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pendingRoommates as $rm): ?>
                    <div class="p-4 sm:p-5 rounded-2xl border border-slate-200 bg-slate-50/70 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 text-xs">
                        <div class="space-y-1 max-w-xl">
                            <div class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                                <span><?= e($rm['name']) ?></span>
                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-200 text-slate-700"><?= e($rm['gender']) ?></span>
                            </div>
                            <div class="text-slate-600">
                                <strong>Kawasan:</strong> <?= e($rm['location']) ?> • <strong>Bajet:</strong> RM <?= round($rm['budget']) ?>/bulan • <strong>Fakulti:</strong> <?= e($rm['faculty']) ?>
                            </div>
                            <p class="text-slate-500 italic">"<?= e($rm['description']) ?>"</p>
                        </div>

                        <!-- Action Form -->
                        <form method="POST" action="<?= BASE_URL ?>/public/admin/dashboard.php" class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full md:w-auto">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action_type" value="roommate">
                            <input type="hidden" name="item_id" value="<?= $rm['ad_id'] ?>">

                            <input type="text" name="admin_notes" placeholder="Nota justifikasi (jika tolak)" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none w-full sm:w-48">

                            <button type="submit" name="decision" value="approve" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition whitespace-nowrap shadow-sm">
                                <i class="fa-solid fa-check mr-1"></i> Luluskan
                            </button>
                            <button type="submit" name="decision" value="reject" onclick="return confirm('Adakah anda pasti ingin menolak iklan rakan bilik ini?')" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs transition whitespace-nowrap shadow-sm">
                                <i class="fa-solid fa-xmark mr-1"></i> Tolak
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
