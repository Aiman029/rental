<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = currentUser();
$error = '';
$success = '';

// Handle posting a roommate ad (FR016 / UC011)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('student');
    validate_csrf_or_die();

    $name = trim($_POST['name'] ?? '');
    $gender = trim($_POST['gender'] ?? 'Siswi (Perempuan)');
    $faculty = trim($_POST['faculty'] ?? '');
    $location = trim($_POST['location'] ?? 'Taman Bahtera');
    $budget = (float)($_POST['budget'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $tagsRaw = trim($_POST['tags'] ?? '');
    $pdpa = isset($_POST['pdpa_consent']);

    if (empty($name) || empty($faculty) || empty($desc) || empty($phone) || $budget <= 0) {
        $error = 'Sila lengkapkan semua maklumat pencarian rakan bilik.';
    } elseif (!$pdpa) {
        $error = 'Persetujuan PDPA 2010 adalah mandatori untuk memaparkan iklan rakan bilik.';
    } else {
        $tags = array_filter(array_map('trim', explode(',', $tagsRaw)));
        if (empty($tags)) {
            $tags = ['Pembersih', 'Pelajar UPSI'];
        }

        try {
            $pdo = getDbConnection();
            $cleanPhone = clean_whatsapp_number($phone);

            $ins = $pdo->prepare("
                INSERT INTO roommate_ads 
                (student_id, name, gender, faculty, location, budget, description, phone, tags, status, pdpa_consent)
                VALUES 
                (:sid, :name, :gender, :fac, :loc, :budget, :desc, :phone, :tags, 'Pending', 1)
            ");
            $ins->execute([
                'sid' => $user['user_id'],
                'name' => $name,
                'gender' => $gender,
                'fac' => $faculty,
                'loc' => $location,
                'budget' => $budget,
                'desc' => $desc,
                'phone' => $cleanPhone,
                'tags' => json_encode($tags, JSON_UNESCAPED_UNICODE)
            ]);

            // Notification
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'roommate')");
            $notif->execute([
                $user['user_id'],
                'Iklan Rakan Bilik Dihantar',
                'Iklan pencarian rakan bilik anda telah selamat disimpan dan sedang menunggu kelulusan oleh Pentadbir Sistem.'
            ]);

            set_flash('success', 'Iklan rakan bilik anda telah dihantar! Iklan akan disiarkan kepada umum selepas semakan kelulusan oleh pentadbir.');
            header('Location: ' . BASE_URL . '/public/roommate_hub.php');
            exit;
        } catch (Exception $e) {
            error_log('Roommate post error: ' . $e->getMessage());
            $error = 'Ralat pangkalan data semasa menghantar iklan rakan bilik.';
        }
    }
}

// Fetch approved roommate ads
$filterGender = $_GET['gender'] ?? 'all';
$filterLoc = $_GET['location'] ?? 'all';

$ads = [];
$myAds = [];

try {
    $pdo = getDbConnection();

    $sql = "SELECT * FROM roommate_ads WHERE status = 'Approved'";
    $params = [];

    if ($filterGender !== 'all' && !empty($filterGender)) {
        $sql .= " AND gender LIKE :gender";
        $params['gender'] = "%$filterGender%";
    }
    if ($filterLoc !== 'all' && !empty($filterLoc)) {
        $sql .= " AND location = :location";
        $params['location'] = $filterLoc;
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ads = $stmt->fetchAll();

    foreach ($ads as &$ad) {
        $ad['tags'] = json_decode($ad['tags'] ?? '[]', true);
    }

    // If logged in student, fetch their own ads
    if ($user && $user['role'] === 'student') {
        $myStmt = $pdo->prepare("SELECT * FROM roommate_ads WHERE student_id = ? ORDER BY created_at DESC");
        $myStmt->execute([$user['user_id']]);
        $myAds = $myStmt->fetchAll();
    }
} catch (Exception $e) {
    error_log('Roommate fetch error: ' . $e->getMessage());
}

$pageTitle = 'Pusat Rakan Bilik (Roommate Hub)';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-users text-pinkbrand-500"></i>
                <span>Pusat Rakan Bilik (Roommate Hub UPSI)</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Papan komuniti rasmi untuk siswa & siswi UPSI mencari rakan sebilik atau mengisi kekosongan rumah sewa.
            </p>
        </div>
        <div>
            <?php if ($user && $user['role'] === 'student'): ?>
                <button type="button" onclick="openRoommateModal()" class="px-5 py-2.5 rounded-2xl pink-gradient text-white font-extrabold text-xs shadow-md shadow-pinkbrand-200 hover:opacity-95 transition flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Iklankan Pencarian Rakan Bilik</span>
                </button>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/public/login.php?redirect=<?= urlencode(BASE_URL . '/public/roommate_hub.php') ?>" class="px-5 py-2.5 rounded-2xl pink-gradient text-white font-extrabold text-xs shadow-md shadow-pinkbrand-200 hover:opacity-95 transition flex items-center gap-2">
                    <i class="fa-solid fa-user-lock"></i>
                    <span>Log Masuk Pelajar untuk Iklan</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Student's Own Ads Status Section (FR017) -->
    <?php if (!empty($myAds)): ?>
        <div class="bg-white rounded-3xl border border-pinkbrand-100 p-5 mb-8 shadow-sm">
            <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                <i class="fa-solid fa-clipboard-user text-pinkbrand-500"></i> Iklan Rakan Bilik Saya (Status Pengesahan)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <?php foreach ($myAds as $ma): ?>
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <div class="font-extrabold text-slate-800"><?= e($ma['name']) ?> • <?= e($ma['location']) ?></div>
                            <div class="text-[11px] text-slate-500">Bajet: RM <?= round($ma['budget']) ?>/bulan</div>
                            <?php if ($ma['status'] === 'Rejected' && !empty($ma['admin_notes'])): ?>
                                <div class="text-[10px] text-rose-600 mt-1 font-semibold">Nota: <?= e($ma['admin_notes']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($ma['status'] === 'Approved'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">Diterbitkan</span>
                            <?php elseif ($ma['status'] === 'Pending'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800">Menunggu Kelulusan</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800">Ditolak</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-pinkbrand-100 shadow-sm flex flex-wrap items-center justify-between gap-3 mb-6">
        <form method="GET" action="<?= BASE_URL ?>/public/roommate_hub.php" class="flex flex-wrap items-center gap-3 text-xs">
            <div>
                <label class="font-bold text-slate-400 uppercase text-[10px] block mb-0.5">Jantina</label>
                <select name="gender" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold">
                    <option value="all" <?= ($filterGender === 'all') ? 'selected' : '' ?>>Semua Jantina</option>
                    <option value="Siswi" <?= ($filterGender === 'Siswi') ? 'selected' : '' ?>>Siswi (Perempuan)</option>
                    <option value="Siswa" <?= ($filterGender === 'Siswa') ? 'selected' : '' ?>>Siswa (Lelaki)</option>
                </select>
            </div>
            <div>
                <label class="font-bold text-slate-400 uppercase text-[10px] block mb-0.5">Lokasi</label>
                <select name="location" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold">
                    <option value="all" <?= ($filterLoc === 'all') ? 'selected' : '' ?>>Semua Lokasi</option>
                    <option value="Taman Bahtera" <?= ($filterLoc === 'Taman Bahtera') ? 'selected' : '' ?>>Taman Bahtera</option>
                    <option value="Proton City" <?= ($filterLoc === 'Proton City') ? 'selected' : '' ?>>Proton City</option>
                    <option value="Taman Universiti" <?= ($filterLoc === 'Taman Universiti') ? 'selected' : '' ?>>Taman Universiti</option>
                    <option value="Behrang 2020" <?= ($filterLoc === 'Behrang 2020') ? 'selected' : '' ?>>Behrang 2020</option>
                </select>
            </div>
            <?php if ($filterGender !== 'all' || $filterLoc !== 'all'): ?>
                <div class="pt-3">
                    <a href="<?= BASE_URL ?>/public/roommate_hub.php" class="text-pinkbrand-600 font-bold hover:underline">
                        Set Semula
                    </a>
                </div>
            <?php endif; ?>
        </form>

        <span class="text-xs font-bold text-slate-500 bg-pinkbrand-50 px-3 py-1 rounded-full">
            <?= count($ads) ?> Iklan Rakan Bilik Aktif
        </span>
    </div>

    <!-- Roommate Cards Grid -->
    <?php if (empty($ads)): ?>
        <div class="text-center py-16 px-4 bg-white rounded-3xl border border-pinkbrand-100 shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-pinkbrand-50 text-pinkbrand-500 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-users-slash"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-800 mb-1">Tiada Iklan Rakan Bilik</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-5">
                Belum ada iklan bagi tapisan ini. Jadilah yang pertama mengiklankan pencarian rakan bilik!
            </p>
            <?php if ($user && $user['role'] === 'student'): ?>
                <button type="button" onclick="openRoommateModal()" class="px-5 py-2.5 rounded-xl pink-gradient text-white text-xs font-extrabold shadow hover:opacity-95 transition">
                    Iklankan Sekarang
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($ads as $ad): 
                $waText = urlencode("Hai " . $ad['name'] . ", saya melihat iklan anda di UpsiRent-I Roommate Hub untuk kawasan " . $ad['location'] . ". Adakah masih mencari rakan bilik?");
                $waUrl = "https://wa.me/" . clean_whatsapp_number($ad['phone']) . "?text=" . $waText;
            ?>
                <div class="bg-white rounded-3xl p-5 border border-pinkbrand-100 shadow-sm card-hover flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold <?= str_contains($ad['gender'], 'Siswi') ? 'bg-pinkbrand-100 text-pinkbrand-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= e($ad['gender']) ?>
                                </span>
                                <h3 class="text-base font-extrabold text-slate-800 mt-2"><?= e($ad['name']) ?></h3>
                                <p class="text-[11px] text-slate-500 font-semibold"><?= e($ad['faculty']) ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-base font-extrabold text-pinkbrand-600">RM <?= round($ad['budget']) ?></span>
                                <span class="text-[10px] text-slate-400 block">/seorang/bulan</span>
                            </div>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-[11px] text-slate-600 mb-3 flex items-center gap-1.5 font-semibold">
                            <i class="fa-solid fa-location-dot text-pinkbrand-500"></i>
                            <span><?= e($ad['location']) ?></span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed mb-4 italic">
                            "<?= e($ad['description']) ?>"
                        </p>

                        <div class="flex flex-wrap gap-1 mb-4">
                            <?php foreach ($ad['tags'] ?? [] as $t): ?>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold">#<?= e($t) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <a href="<?= $waUrl ?>" target="_blank" rel="noopener noreferrer" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                            <i class="fa-brands fa-whatsapp text-base"></i>
                            <span>WhatsApp Rakan Bilik</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modal Post Roommate Ad (UC011) -->
    <div id="roommateModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-pinkbrand-100 relative max-h-[90vh] overflow-y-auto">
            <button type="button" onclick="closeRoommateModal()" class="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs transition">
                ✕
            </button>

            <div class="text-center mb-5">
                <div class="w-12 h-12 rounded-2xl pink-gradient flex items-center justify-center text-white mx-auto mb-2 shadow-md shadow-pinkbrand-200">
                    <i class="fa-solid fa-user-group text-xl"></i>
                </div>
                <h3 class="text-lg font-extrabold text-slate-800">Iklankan Pencarian Rakan Bilik</h3>
                <p class="text-xs text-slate-500">Iklan anda akan ditapis oleh Pentadbir sebelum disiarkan</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/public/roommate_hub.php" class="space-y-4 text-xs">
                <?= csrf_field() ?>

                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Nama Panggilan / Kumpulan</label>
                    <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required placeholder="Cth: Aina & Syuhada" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-600 uppercase mb-1">Kategori Jantina</label>
                        <select name="gender" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                            <option value="Siswi (Perempuan)">Siswi (Perempuan)</option>
                            <option value="Siswa (Lelaki)">Siswa (Lelaki)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 uppercase mb-1">Kawasan Rumah</label>
                        <select name="location" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                            <option value="Taman Bahtera">Taman Bahtera (Dekat KSAJS)</option>
                            <option value="Proton City">Proton City (Dekat KSAS)</option>
                            <option value="Taman Universiti">Taman Universiti</option>
                            <option value="Behrang 2020">Bandar Behrang 2020</option>
                            <option value="Behrang Stesen">Behrang Stesen</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Fakulti & Semester</label>
                    <input type="text" name="faculty" required placeholder="Cth: Fakulti Bahasa & Komunikasi (FBK) • Sem 3" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-600 uppercase mb-1">Anggaran Bajet Bulanan (RM)</label>
                        <input type="number" name="budget" required min="50" step="10" placeholder="Cth: 180" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 uppercase mb-1">Nombor WhatsApp</label>
                        <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" required placeholder="Cth: 0198877665" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Keterangan / Kriteria Rakan Bilik</label>
                    <textarea name="description" rows="3" required placeholder="Cth: Mencari 2 orang siswi Muslimah pembersih, tidak merokok, rumah ada WiFi..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Ciri-ciri Utama (Pisahkan dengan koma)</label>
                    <input type="text" name="tags" placeholder="Cth: Pembersih, Tak Merokok, Ada Motor, Rajin Study" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                </div>

                <!-- Mandatory PDPA Consent Checkbox (FR019 / CR004 / UC011) -->
                <div class="p-3.5 bg-pinkbrand-50/70 border border-pinkbrand-200 rounded-2xl">
                    <label class="flex items-start gap-2 text-xs text-slate-700 cursor-pointer">
                        <input type="checkbox" name="pdpa_consent" value="1" required class="mt-0.5 rounded text-pinkbrand-600 focus:ring-pinkbrand-500 w-4 h-4">
                        <span>
                            <strong>Persetujuan PDPA 2010:</strong> Saya membenarkan nama, fakulti, dan nombor WhatsApp saya dipaparkan di portal UpsiRent-I bagi tujuan pencarian rakan sebilik.
                        </span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-2xl pink-gradient text-white font-extrabold text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
                    Hantar Iklan Rakan Bilik
                </button>
            </form>
        </div>
    </div>
</main>

<script>
function openRoommateModal() {
    document.getElementById('roommateModal').classList.remove('hidden');
}
function closeRoommateModal() {
    document.getElementById('roommateModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
