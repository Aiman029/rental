<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole('landlord');
$user = currentUser();
$error = '';
$success = '';

// Handle listing submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $category = trim($_POST['category'] ?? 'rental');
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? 'Taman Bahtera');
    $campus = trim($_POST['campus'] ?? 'KSAJS');
    $distance = trim($_POST['distance'] ?? '');
    $basePrice = (float)($_POST['base_price'] ?? 0);
    $recommendedPax = (int)($_POST['recommended_pax'] ?? 4);
    $priceUnit = trim($_POST['price_unit'] ?? ($category === 'homestay' ? 'malam' : 'bulan'));
    $availability = trim($_POST['availability'] ?? 'Masuk Segera');
    $slotsLeft = trim($_POST['slots_left'] ?? 'Sedia didiami');
    $badgesRaw = trim($_POST['badges'] ?? '');
    $contractDuration = trim($_POST['contract_duration'] ?? '1 Tahun');
    $contractDeposit = trim($_POST['contract_deposit'] ?? '1 Bulan Sewa');
    $contractRules = trim($_POST['contract_rules'] ?? 'Pembersih dan menjaga ketenteraman');
    $pdpa = isset($_POST['pdpa_consent']);

    if (empty($title) || empty($location) || $basePrice <= 0) {
        $error = 'Sila lengkapkan tajuk kediaman, lokasi, dan kadar sewa.';
    } elseif (!$pdpa) {
        $error = 'Persetujuan PDPA 2010 adalah mandatori sebelum mengiklankan kediaman.';
    } else {
        // Process uploaded photos
        $gallery = [];
        if (!empty($_FILES['photos']['name'][0])) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxBytes = 5 * 1024 * 1024;

            if (!is_dir(UPLOAD_PROPERTIES_DIR)) {
                mkdir(UPLOAD_PROPERTIES_DIR, 0755, true);
            }

            $count = count($_FILES['photos']['name']);
            for ($i = 0; $i < min($count, 6); $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['photos']['tmp_name'][$i];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp);
                    finfo_close($finfo);

                    if (in_array($mime, $allowedMimes, true) && $_FILES['photos']['size'][$i] <= $maxBytes) {
                        $ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/png') ? 'png' : 'webp');
                        $fname = 'prop_' . $user['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $dest = UPLOAD_PROPERTIES_DIR . '/' . $fname;
                        if (move_uploaded_file($tmp, $dest)) {
                            $label = ($i === 0) ? 'Pandangan Hadapan' : 'Bilik / Ruang Dalaman';
                            $gallery[] = [
                                'label' => $label,
                                'url' => BASE_URL . '/uploads/properties/' . $fname,
                                'caption' => 'Foto sebenar yang dimuat naik oleh tuan rumah.'
                            ];
                        }
                    }
                }
            }
        }

        // Default placeholder image if no photos uploaded
        if (empty($gallery)) {
            $gallery[] = [
                'label' => 'Pandangan Hadapan',
                'url' => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1000&q=80',
                'caption' => 'Foto contoh kediaman sewa.'
            ];
        }

        // Badges array
        $badges = array_filter(array_map('trim', explode(',', $badgesRaw)));
        if (empty($badges)) {
            $badges = ['Dekat Kampus', 'Lengkap Asas'];
        }

        // Nearby spots
        $nearbySpots = [
            ['name' => 'Kampus UPSI (' . $campus . ')', 'dist' => $distance ?: '5 minit perjalanan'],
            ['name' => 'Kedai Makan & Runcit Terdekat', 'dist' => '2 minit jalan kaki']
        ];

        // Standard inventory checklist
        $sections = [
            [
                'title' => 'Bilik Tidur & Perabot Asas',
                'icon' => 'fa-bed',
                'items' => ['Katil Bujang & Tilam', 'Almari Baju', 'Kipas Siling']
            ],
            [
                'title' => 'Dapur & Ruang Santai',
                'icon' => 'fa-kitchen-set',
                'items' => ['Peti Sejuk', 'Dapur Gas Memasak', 'Mesin Basuh']
            ]
        ];

        $contractInfo = [
            'duration' => $contractDuration,
            'deposit' => $contractDeposit,
            'notice' => 'Notis 1 bulan sebelum tamat sewaan',
            'rules' => $contractRules
        ];

        try {
            $pdo = getDbConnection();
            $ins = $pdo->prepare("
                INSERT INTO properties 
                (landlord_id, category, title, location, campus, distance, base_price, recommended_pax, price_unit, availability, slots_left, badges, nearby_spots, gallery, sections, contract_info, contact_name, contact_phone, contact_whatsapp, status, pdpa_consent)
                VALUES 
                (:lid, :cat, :title, :loc, :campus, :dist, :price, :pax, :punit, :avail, :slots, :badges, :near, :gal, :sec, :contract, :cname, :cphone, :cwa, 'Pending', 1)
            ");
            $ins->execute([
                'lid' => $user['user_id'],
                'cat' => $category,
                'title' => $title,
                'loc' => $location,
                'campus' => $campus,
                'dist' => $distance ?: '1km ke Kampus UPSI',
                'price' => $basePrice,
                'pax' => $recommendedPax,
                'punit' => $priceUnit,
                'avail' => $availability,
                'slots' => $slotsLeft,
                'badges' => json_encode($badges, JSON_UNESCAPED_UNICODE),
                'near' => json_encode($nearbySpots, JSON_UNESCAPED_UNICODE),
                'gal' => json_encode($gallery, JSON_UNESCAPED_UNICODE),
                'sec' => json_encode($sections, JSON_UNESCAPED_UNICODE),
                'contract' => json_encode($contractInfo, JSON_UNESCAPED_UNICODE),
                'cname' => $user['name'],
                'cphone' => $user['phone'],
                'cwa' => clean_whatsapp_number($user['phone'])
            ]);

            // Notification
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'property')");
            $notif->execute([
                $user['user_id'],
                'Iklan Kediaman Baru Diterima',
                'Iklan "' . $title . '" telah dihantar dan sedang menunggu semakan kelulusan oleh Pentadbir Sistem.'
            ]);

            set_flash('success', 'Iklan kediaman anda berjaya dihantar! Iklan akan diterbitkan kepada umum selepas semakan pentadbir.');
            header('Location: ' . BASE_URL . '/public/landlord_dashboard.php');
            exit;
        } catch (Exception $e) {
            error_log('Landlord submit property error: ' . $e->getMessage());
            $error = 'Ralat pangkalan data semasa menghantar iklan: ' . $e->getMessage();
        }
    }
}

// Fetch landlord's listings (FR015)
$myProperties = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE landlord_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['user_id']]);
    $myProperties = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Landlord fetch error: ' . $e->getMessage());
}

$pageTitle = 'Papan Pemuka Tuan Rumah';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto my-8 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-house-chimney-user text-pinkbrand-500"></i>
                <span>Papan Pemuka Tuan Rumah (Landlord)</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Selamat kembali, <strong><?= e($user['name']) ?></strong>. Iklankan rumah sewa atau homestay anda kepada ribuan pelajar UPSI.
            </p>
        </div>
        <button type="button" onclick="document.getElementById('newListingSection').scrollIntoView({behavior: 'smooth'})" class="px-5 py-2.5 rounded-2xl pink-gradient text-white font-extrabold text-xs shadow-md shadow-pinkbrand-200 hover:opacity-95 transition flex items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i>
            <span>Hantar Iklan Baru</span>
        </button>
    </div>

    <!-- Listings Management Table (FR015 / FR017) -->
    <div class="bg-white rounded-3xl border border-pinkbrand-100 shadow-sm p-6 sm:p-8 mb-8">
        <h2 class="text-base font-extrabold text-slate-800 mb-1 flex items-center gap-2">
            <i class="fa-solid fa-rectangle-list text-pinkbrand-500"></i> Senarai Iklan Kediaman Saya
        </h2>
        <p class="text-xs text-slate-500 mb-6">Pantau status kelulusan pentadbir bagi setiap iklan yang telah dihantar.</p>

        <?php if (empty($myProperties)): ?>
            <div class="text-center py-10 bg-slate-50 rounded-2xl border border-slate-100">
                <p class="text-xs text-slate-500 mb-2">Anda belum mempunyai sebarang iklan sewaan yang berdaftar.</p>
                <button type="button" onclick="document.getElementById('newListingSection').scrollIntoView({behavior: 'smooth'})" class="text-xs text-pinkbrand-600 font-extrabold underline">
                    Iklankan Rumah Anda Sekarang
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-extrabold">
                            <th class="py-3 px-4">Kediaman</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4">Lokasi</th>
                            <th class="py-3 px-4">Kadar Sewa</th>
                            <th class="py-3 px-4">Kapasiti</th>
                            <th class="py-3 px-4">Status Kelulusan</th>
                            <th class="py-3 px-4">Tarikh Hantar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($myProperties as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    <div class="line-clamp-1"><?= e($p['title']) ?></div>
                                    <?php if ($p['status'] === 'Rejected' && !empty($p['admin_notes'])): ?>
                                        <div class="mt-1 text-[10px] text-rose-700 font-normal">
                                            <strong>Nota Admin:</strong> <?= e($p['admin_notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $p['category'] === 'homestay' ? 'bg-indigo-100 text-indigo-800' : 'bg-pinkbrand-100 text-pinkbrand-800' ?>">
                                        <?= e($p['category']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 font-semibold"><?= e($p['location']) ?></td>
                                <td class="py-3.5 px-4 font-extrabold text-pinkbrand-600">RM <?= round($p['base_price']) ?> / <?= e($p['price_unit']) ?></td>
                                <td class="py-3.5 px-4 text-slate-600"><?= $p['recommended_pax'] ?> Orang</td>
                                <td class="py-3.5 px-4">
                                    <?php if ($p['status'] === 'Approved'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-circle-check"></i> Diterbitkan
                                        </span>
                                    <?php elseif ($p['status'] === 'Pending'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800">
                                            <i class="fa-solid fa-clock"></i> Menunggu Semakan
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800">
                                            <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- New Listing Submission Form (FR013 / UC010) -->
    <div id="newListingSection" class="bg-white rounded-3xl border border-pinkbrand-100 shadow-xl p-6 sm:p-8">
        <h2 class="text-lg font-extrabold text-slate-800 mb-1 flex items-center gap-2">
            <i class="fa-solid fa-plus-circle text-pinkbrand-500"></i> Borang Pengiklanan Kediaman Baru
        </h2>
        <p class="text-xs text-slate-500 mb-6">Lengkapkan maklumat kediaman di bawah. Setiap iklan baru akan melalui moderasi pentadbir sebelum dipaparkan kepada umum.</p>

        <?php if (!empty($error)): ?>
            <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs font-semibold text-rose-700 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/landlord_dashboard.php" enctype="multipart/form-data" class="space-y-5 text-xs">
            <?= csrf_field() ?>

            <!-- Category & Campus -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Kategori Penginapan</label>
                    <select name="category" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                        <option value="rental">Rumah Sewa Pelajar (Bulanan / Semester)</option>
                        <option value="homestay">Homestay UPSI (Harian / Konvokesyen)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Kawasan / Kejiranan</label>
                    <select name="location" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                        <option value="Taman Bahtera">Taman Bahtera (Dekat KSAJS)</option>
                        <option value="Proton City">Proton City (Dekat KSAS)</option>
                        <option value="Taman Universiti">Taman Universiti (Pintu Timur KSAJS)</option>
                        <option value="Behrang 2020">Bandar Behrang 2020</option>
                        <option value="Behrang Stesen">Behrang Stesen</option>
                        <option value="Kalumpang">Kalumpang</option>
                    </select>
                </div>
            </div>

            <!-- Title -->
            <div>
                <label class="block font-bold text-slate-600 uppercase mb-1">Tajuk Iklan Menarik</label>
                <input type="text" name="title" required placeholder="Cth: Rumah Teres 2 Tingkat Taman Bahtera (Berhadapan Pagar UPSI)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>

            <!-- Price & Capacity -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Kadar Sewa (RM)</label>
                    <input type="number" name="base_price" required min="50" step="10" placeholder="Cth: 1200" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Unit Sewaan</label>
                    <select name="price_unit" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                        <option value="bulan">sebulan (Rumah Sewa)</option>
                        <option value="malam">semalam (Homestay)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Kapasiti Pelajar / Tetamu</label>
                    <input type="number" name="recommended_pax" min="1" max="20" value="6" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Kampus Terdekat</label>
                    <select name="campus" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                        <option value="KSAJS">KSAJS (Kampus Induk)</option>
                        <option value="KSAS">KSAS (Proton City)</option>
                    </select>
                </div>
            </div>

            <!-- Distance & Availability -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-600 uppercase mb-1">Jarak / Masa ke Kampus</label>
                    <input type="text" name="distance" placeholder="Cth: 500m jalan kaki ke Pintu Utama KSAJS / 5 minit bas" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-1">Status Kekosongan</label>
                    <select name="availability" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
                        <option value="Masuk Segera">Masuk Segera (Sedia Ada)</option>
                        <option value="Semester Depan">Kemasukan Semester Depan</option>
                    </select>
                </div>
            </div>

            <!-- Badges -->
            <div>
                <label class="block font-bold text-slate-600 uppercase mb-1">Kelebihan / Tag Ciri (Pisahkan dengan tanda koma)</label>
                <input type="text" name="badges" placeholder="Cth: WiFi Percuma, Mesin Basuh, Jalan Kaki ke UPSI, Berhawa Dingin" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-pinkbrand-500">
            </div>

            <!-- Contract & Rules -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                <h4 class="font-extrabold text-slate-800 text-xs flex items-center gap-1.5">
                    <i class="fa-solid fa-file-signature text-indigo-600"></i> Syarat & Terma Kontrak Sewaan
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Tempoh Min Sewaan</label>
                        <input type="text" name="contract_duration" value="1 Tahun Akademik (Minimum 10 Bulan)" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Kadar Deposit</label>
                        <input type="text" name="contract_deposit" value="1 Bulan Sewa + RM 200 Utiliti" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Peraturan Kediaman</label>
                    <input type="text" name="contract_rules" value="Penyewa siswi Muslimah sahaja, tidak merokok & menjaga kebersihan." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold focus:outline-none">
                </div>
            </div>

            <!-- Photos Upload -->
            <div>
                <label class="block font-bold text-slate-600 uppercase mb-1">Muat Naik Foto Kediaman (Sehingga 6 Keping Imej - JPG, PNG, WebP)</label>
                <div class="border-2 border-dashed border-slate-200 rounded-2xl p-5 text-center bg-slate-50 hover:border-pinkbrand-400 transition">
                    <i class="fa-solid fa-images text-2xl text-pinkbrand-500 mb-2"></i>
                    <p class="font-bold text-slate-700">Pilih gambar bilik demi bilik, ruang tamu & dapur</p>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:pink-gradient file:text-white">
                </div>
            </div>

            <!-- Mandatory PDPA Consent Checkbox (FR019 / CR004 / UC010) -->
            <div class="p-3.5 bg-pinkbrand-50/70 border border-pinkbrand-200 rounded-2xl">
                <label class="flex items-start gap-2.5 text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="pdpa_consent" value="1" required class="mt-0.5 rounded text-pinkbrand-600 focus:ring-pinkbrand-500 w-4 h-4">
                    <span>
                        <strong>Persetujuan PDPA 2010 (Tuan Rumah):</strong> Saya mengesahkan bahawa saya adalah pemilik atau wakil sah kediaman ini. Saya membenarkan maklumat nama dan nombor WhatsApp saya dipaparkan kepada pelajar yang berminat mengikut Akta Perlindungan Data Peribadi 2010.
                    </span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 rounded-2xl pink-gradient text-white font-extrabold text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
                Hantar Iklan untuk Moderasi Pentadbir
            </button>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
