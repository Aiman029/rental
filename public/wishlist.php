<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'student') {
    set_flash('error', 'Halaman senarai simpanan hanya untuk peranan pelajar.');
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

$isVerified = isVerifiedStudent();
$savedProperties = [];

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT p.*, w.wishlist_id, w.created_at AS saved_at
        FROM wishlists w
        JOIN properties p ON w.property_id = p.property_id
        WHERE w.student_id = :student_id AND p.status = 'Approved'
        ORDER BY w.created_at DESC
    ");
    $stmt->execute(['student_id' => $user['user_id']]);
    $savedProperties = $stmt->fetchAll();

    foreach ($savedProperties as &$p) {
        $p['badges'] = json_decode($p['badges'] ?? '[]', true);
        $p['gallery'] = json_decode($p['gallery'] ?? '[]', true);
        $p['pricing'] = calculate_price($p['base_price'], $isVerified);
    }
} catch (Exception $e) {
    error_log('Wishlist page error: ' . $e->getMessage());
}

$pageTitle = 'Kediaman Disimpan (Wishlist)';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-8">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-heart text-pinkbrand-500"></i>
                <span>Kediaman Pilihan Saya (Wishlist)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Senarai rumah sewa dan homestay yang anda simpan untuk rujukan akan datang</p>
        </div>
        <a href="<?= BASE_URL ?>/public/index.php" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:text-pinkbrand-600 font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Carian</span>
        </a>
    </div>

    <?php if (empty($savedProperties)): ?>
        <div class="text-center py-16 px-4 bg-white rounded-3xl border border-pinkbrand-100 shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-pinkbrand-50 text-pinkbrand-500 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-regular fa-heart"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-800 mb-1">Tiada Kediaman Disimpan</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-5">
                Anda belum menyimpan sebarang rumah sewa. Klik ikon hati pada mana-mana kad rumah untuk menyimpannya di sini.
            </p>
            <a href="<?= BASE_URL ?>/public/index.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl pink-gradient text-white text-xs font-extrabold shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Teroka Rumah Sewa Sekarang</span>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($savedProperties as $p): 
                $img = !empty($p['gallery'][0]['url']) ? $p['gallery'][0]['url'] : 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=800&q=80';
            ?>
                <div class="bg-white rounded-3xl overflow-hidden border border-pinkbrand-100 shadow-sm card-hover flex flex-col justify-between" id="wishlist-card-<?= $p['property_id'] ?>">
                    <div>
                        <div class="relative h-48 overflow-hidden bg-slate-100">
                            <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" class="w-full h-full object-cover">
                            <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded-xl text-[11px] font-extrabold text-slate-800 shadow">
                                <i class="fa-solid fa-location-dot text-pinkbrand-500 mr-1"></i> <?= e($p['location']) ?>
                            </div>
                            <button type="button" onclick="removeFromWishlist(<?= $p['property_id'] ?>)" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 hover:bg-rose-50 text-rose-600 flex items-center justify-center text-xs shadow transition" title="Padam dari Wishlist">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>

                        <div class="p-4 sm:p-5">
                            <div class="flex flex-wrap gap-1 mb-2">
                                <?php foreach (array_slice($p['badges'] ?? [], 0, 2) as $badge): ?>
                                    <span class="bg-pinkbrand-50 text-pinkbrand-700 px-2 py-0.5 rounded-md text-[10px] font-bold border border-pinkbrand-100"><?= e($badge) ?></span>
                                <?php endforeach; ?>
                            </div>

                            <h3 class="text-sm font-extrabold text-slate-800 line-clamp-2 leading-snug mb-1.5 hover:text-pinkbrand-600 cursor-pointer">
                                <?= e($p['title']) ?>
                            </h3>

                            <p class="text-[11px] text-slate-500 flex items-center gap-1 mb-3">
                                <i class="fa-solid fa-route text-pinkbrand-400 shrink-0"></i>
                                <span class="truncate"><?= e($p['distance']) ?></span>
                            </p>

                            <div class="border-t border-slate-100 pt-3">
                                <?php if ($isVerified && $p['pricing']['has_discount']): ?>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-xs text-slate-400 line-through">RM <?= round($p['pricing']['original']) ?></span>
                                        <span class="text-lg font-extrabold text-pinkbrand-600">RM <?= round($p['pricing']['final']) ?></span>
                                        <span class="text-[11px] text-slate-500 font-normal">/<?= e($p['price_unit']) ?></span>
                                    </div>
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold">-10% Pelajar Sah</span>
                                <?php else: ?>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-lg font-extrabold text-pinkbrand-600">RM <?= round($p['base_price']) ?></span>
                                        <span class="text-[11px] text-slate-500 font-normal">/<?= e($p['price_unit']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 pt-0 flex items-center gap-2">
                        <a href="<?= BASE_URL ?>/public/index.php" class="flex-1 py-2.5 rounded-xl pink-gradient text-white text-xs font-extrabold shadow-sm hover:opacity-95 transition text-center">
                            Lihat di Laman Utama
                        </a>
                        <button type="button" onclick="removeFromWishlist(<?= $p['property_id'] ?>)" class="p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:text-rose-600 hover:bg-rose-50 text-xs font-bold transition" title="Padam">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
async function removeFromWishlist(propId) {
    if (!confirm('Adakah anda pasti ingin memadamkan kediaman ini daripada senarai Wishlist?')) return;

    try {
        const fd = new FormData();
        fd.append('property_id', propId);
        fd.append('csrf_token', CSRF_TOKEN);

        const res = await fetch(`${BASE_URL}/api/wishlist.php`, {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            const card = document.getElementById(`wishlist-card-${propId}`);
            if (card) {
                card.remove();
            }
            const badge = document.getElementById('wishlistCountBadge');
            if (badge) badge.innerText = data.total_wishlists;
            
            // Reload page if no cards left
            if (data.total_wishlists === 0) {
                setTimeout(() => location.reload(), 800);
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Ralat memadamkan kediaman.', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
