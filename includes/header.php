<?php
// Main Header Include for UpsiRent-I
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user = currentUser();
$isVerified = isVerifiedStudent();
$wishlistCount = 0;
if ($user && $user['role'] === 'student') {
    try {
        $pdo = getDbConnection();
        $wStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE student_id = ?");
        $wStmt->execute([$user['user_id']]);
        $wishlistCount = (int)$wStmt->fetchColumn();
    } catch (Exception $e) {
        $wishlistCount = 0;
    }
}
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>UpsiRent-I - Portal Rumah Sewa & Homestay UPSI</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        pinkbrand: {
                            50: '#fff0f6',
                            100: '#ffe3ee',
                            200: '#ffc6de',
                            300: '#ff98c3',
                            400: '#ff5ca0',
                            500: '#ff2e84',
                            600: '#e61266',
                            700: '#bf064f',
                            800: '#9e0943',
                            900: '#830c3b',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .pink-gradient {
            background: linear-gradient(135deg, #FF2E84 0%, #FF6B9D 55%, #FFA3C2 100%);
        }
        .ai-gradient {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 50%, #EC4899 100%);
        }
        .soft-pink-bg {
            background: linear-gradient(180deg, #FFF5F9 0%, #FFFFFF 100%);
        }
        .card-hover {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 24px -6px rgba(255, 46, 132, 0.15), 0 6px 10px -4px rgba(255, 46, 132, 0.08);
        }
        .thumb-active {
            border: 3px solid #FF2E84;
            transform: scale(1.03);
        }
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.92; transform: scale(1.03); }
        }
        .ai-pulse {
            animation: pulse-subtle 3s infinite ease-in-out;
        }
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #fdf2f8;
        }
        ::-webkit-scrollbar-thumb {
            background: #f472b6;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #db2777;
        }
        
        /* Accessibility Large Text Mode (FR023) */
        body.large-text-mode {
            font-size: 115% !important;
        }
        body.large-text-mode h1 { font-size: 2.2rem !important; }
        body.large-text-mode h2 { font-size: 1.7rem !important; }
        body.large-text-mode h3 { font-size: 1.35rem !important; }
        body.large-text-mode p, body.large-text-mode span, body.large-text-mode button, body.large-text-mode input, body.large-text-mode select {
            font-size: 1.05rem !important;
        }
    </style>
</head>

<body class="soft-pink-bg font-sans text-slate-800 min-h-screen flex flex-col pb-16 md:pb-0" data-is-student-verified="<?= $isVerified ? 'true' : 'false' ?>" data-base-url="<?= BASE_URL ?>">

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- FLASH MESSAGES ALERT -->
    <?php if (!empty($flashes)): ?>
        <div class="max-w-7xl mx-auto px-4 pt-3 w-full">
            <?php foreach ($flashes as $flash): 
                $bg = $flash['type'] === 'success' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 
                     ($flash['type'] === 'error' ? 'bg-rose-50 border-rose-300 text-rose-800' : 'bg-blue-50 border-blue-300 text-blue-800');
                $icon = $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-info');
            ?>
                <div class="mb-2 p-3.5 rounded-2xl border flex items-center justify-between <?= $bg ?> shadow-sm">
                    <div class="flex items-center gap-2 text-xs sm:text-sm font-semibold">
                        <i class="fa-solid <?= $icon ?>"></i>
                        <span><?= e($flash['message']) ?></span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-xs opacity-70 hover:opacity-100 font-bold ml-4">✕</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- BAR PENGUMUMAN ATAS -->
    <div class="pink-gradient text-white text-[11px] sm:text-xs py-2 px-4 text-center font-semibold shadow-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-graduation-cap"></i>
        <span><strong>Istimewa Pelajar UPSI:</strong> Sahkan Kad Matrik untuk nikmati <strong>Diskaun 10% Automatik</strong> + Khidmat Pembantu Pintar Gemini AI Percuma!</span>
    </div>

    <!-- BAR NAVIGASI UTAMA (CLEAN & TERATUR) -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-pinkbrand-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            
            <!-- Logo & Identiti -->
            <a href="<?= BASE_URL ?>/public/index.php" class="flex items-center gap-3 shrink-0 group">
                <div class="w-11 h-11 rounded-2xl pink-gradient flex items-center justify-center text-white shadow-md shadow-pinkbrand-200 group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-house-chimney-heart text-xl"></i>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-pinkbrand-600 to-pinkbrand-400 bg-clip-text text-transparent">UpsiRent-I</span>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold text-white bg-indigo-600 flex items-center gap-1">
                            <i class="fa-solid fa-wand-magic-sparkles text-[8px]"></i> AI
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 font-bold hidden sm:block">Tanjung Malim • Proton City • Behrang</p>
                </div>
            </a>

            <!-- Pilihan Kategori Utama (Segmented Nav Pills) -->
            <nav class="hidden md:flex items-center bg-slate-100/80 p-1.5 rounded-2xl border border-slate-200/70">
                <a href="<?= BASE_URL ?>/public/index.php?cat=rental" id="tab-rental" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= (!isset($_GET['cat']) || $_GET['cat'] === 'rental') ? 'bg-white text-pinkbrand-600 shadow-sm' : 'text-slate-600 hover:text-pinkbrand-600' ?>">
                    <i class="fa-solid fa-user-graduate mr-1.5"></i>Rumah Sewa
                </a>
                <a href="<?= BASE_URL ?>/public/index.php?cat=homestay" id="tab-homestay" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= (isset($_GET['cat']) && $_GET['cat'] === 'homestay') ? 'bg-white text-pinkbrand-600 shadow-sm' : 'text-slate-600 hover:text-pinkbrand-600' ?>">
                    <i class="fa-solid fa-suitcase-rolling mr-1.5"></i>Homestay
                </a>
                <a href="<?= BASE_URL ?>/public/roommate_hub.php" id="tab-roommate" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= (isset($_GET['cat']) && $_GET['cat'] === 'roommate') ? 'bg-white text-pinkbrand-600 shadow-sm' : 'text-slate-600 hover:text-pinkbrand-600' ?>">
                    <i class="fa-solid fa-users mr-1.5"></i>Cari Rakan Bilik
                </a>
            </nav>

            <!-- Kumpulan Tindakan Kanan (Tersusun & Kemas) -->
            <div class="flex items-center gap-2">
                <!-- Gemini AI Button (UC009 / FR011) -->
                <button type="button" onclick="openGeminiModal()" class="flex items-center gap-1.5 px-3 py-2 sm:px-3.5 sm:py-2.5 rounded-2xl ai-gradient text-white font-extrabold text-xs shadow-md shadow-indigo-100 hover:opacity-95 transition ai-pulse" title="Tanya Pembantu AI Gemini">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i>
                    <span class="hidden lg:inline">Tanya Gemini AI</span>
                    <span class="lg:hidden text-[11px]">AI</span>
                </button>

                <!-- Simpanan (Wishlist / FR012) -->
                <a href="<?= BASE_URL ?>/public/wishlist.php" id="wishlistHeaderBtn" class="flex items-center gap-1.5 px-3 py-2 sm:px-3.5 sm:py-2.5 rounded-2xl bg-slate-50 hover:bg-pinkbrand-50 border border-slate-200 text-slate-700 font-bold text-xs transition" title="Senarai Disimpan">
                    <i class="fa-solid fa-heart text-pinkbrand-500"></i>
                    <span id="wishlistCountBadge" class="bg-pinkbrand-500 text-white text-[10px] px-1.5 py-0.2 rounded-full font-extrabold"><?= $wishlistCount ?></span>
                </a>

                <!-- Status Pengesahan / Butang Diskaun -->
                <?php if ($isVerified): ?>
                    <span class="hidden sm:inline-flex items-center gap-1 px-3 py-2 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-800 font-extrabold text-xs">
                        <i class="fa-solid fa-badge-check text-emerald-600"></i>
                        <span>Diskaun 10% Aktif</span>
                    </span>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/public/verify_student.php" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl border border-pinkbrand-200 bg-pinkbrand-50 hover:bg-pinkbrand-100 text-pinkbrand-700 font-extrabold text-xs transition">
                        <i class="fa-solid fa-id-card-clip"></i>
                        <span>Sahkan ID (10%)</span>
                    </a>
                <?php endif; ?>

                <!-- Iklankan Rumah (Tuan Rumah) -->
                <?php if ($user && $user['role'] === 'landlord'): ?>
                    <a href="<?= BASE_URL ?>/public/landlord_dashboard.php" class="hidden lg:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-700 hover:text-pinkbrand-600 transition">
                        <i class="fa-solid fa-plus-circle text-pinkbrand-500"></i>
                        <span>Papan Tuan Rumah</span>
                    </a>
                <?php elseif (!$user): ?>
                    <a href="<?= BASE_URL ?>/public/login.php" class="hidden lg:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-700 hover:text-pinkbrand-600 transition">
                        <i class="fa-solid fa-plus-circle text-pinkbrand-500"></i>
                        <span>Iklankan Rumah</span>
                    </a>
                <?php endif; ?>

                <!-- Divider halus -->
                <div class="hidden sm:block h-6 w-px bg-slate-200 mx-0.5"></div>

                <!-- User Account Dropdown ATAU Butang Log Masuk / Daftar -->
                <?php if ($user): ?>
                    <div class="relative group">
                        <button type="button" class="flex items-center gap-2 p-1.5 pl-2.5 rounded-2xl bg-pinkbrand-50 hover:bg-pinkbrand-100 border border-pinkbrand-200 text-xs font-bold text-slate-800 transition">
                            <span class="max-w-[90px] truncate"><?= e(explode(' ', $user['name'])[0]) ?></span>
                            <div class="w-8 h-8 rounded-xl bg-pinkbrand-500 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 mr-1"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-3xl shadow-2xl py-2 hidden group-hover:block z-50">
                            <div class="px-4 py-2.5 border-b border-slate-100">
                                <p class="text-xs font-extrabold text-slate-800 truncate"><?= e($user['name']) ?></p>
                                <p class="text-[10px] text-slate-400 capitalize font-semibold"><?= e($user['email']) ?></p>
                                <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-extrabold uppercase <?= 
                                    $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' :
                                    ($user['role'] === 'landlord' ? 'bg-amber-100 text-amber-800' : 'bg-pinkbrand-100 text-pinkbrand-800')
                                ?>">
                                    <i class="fa-solid fa-user-shield"></i> <?= e($user['role']) ?> • <?= e($user['verification_status']) ?>
                                </div>
                            </div>

                            <div class="py-1">
                                <a href="<?= BASE_URL ?>/public/profile.php" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-pinkbrand-50 hover:text-pinkbrand-600 transition">
                                    <i class="fa-solid fa-user-gear text-slate-400 w-4"></i> Profil Saya
                                </a>

                                <?php if ($user['role'] === 'student'): ?>
                                    <a href="<?= BASE_URL ?>/public/verify_student.php" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-pinkbrand-50 hover:text-pinkbrand-600 transition">
                                        <i class="fa-solid fa-id-card text-emerald-500 w-4"></i> Pengesahan Pelajar (Diskaun 10%)
                                    </a>
                                    <a href="<?= BASE_URL ?>/public/wishlist.php" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-pinkbrand-50 hover:text-pinkbrand-600 transition">
                                        <i class="fa-solid fa-heart text-pinkbrand-500 w-4"></i> Rumah Disimpan
                                    </a>
                                <?php elseif ($user['role'] === 'landlord'): ?>
                                    <a href="<?= BASE_URL ?>/public/landlord_dashboard.php" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-pinkbrand-50 hover:text-pinkbrand-600 transition">
                                        <i class="fa-solid fa-building-circle-check text-amber-500 w-4"></i> Papan Tuan Rumah & Iklan
                                    </a>
                                <?php elseif ($user['role'] === 'admin'): ?>
                                    <a href="<?= BASE_URL ?>/public/admin/dashboard.php" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-50 transition">
                                        <i class="fa-solid fa-shield-check text-purple-500 w-4"></i> Panel Moderasi Admin
                                    </a>
                                    <a href="<?= BASE_URL ?>/public/admin/users.php" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-50 transition">
                                        <i class="fa-solid fa-users-gear text-purple-500 w-4"></i> Pengurusan Pengguna
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="border-t border-slate-100 pt-1 mt-1">
                                <a href="<?= BASE_URL ?>/public/logout.php" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
                                    <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Log Keluar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/public/login.php" class="flex items-center gap-1.5 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-xs font-extrabold text-slate-800 transition shadow-sm hover:border-pinkbrand-300">
                        <i class="fa-solid fa-arrow-right-to-bracket text-pinkbrand-500 text-xs"></i>
                        <span>Log Masuk</span>
                    </a>
                    <a href="<?= BASE_URL ?>/public/register.php" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl pink-gradient text-white text-xs font-extrabold shadow-md shadow-pinkbrand-200 transition hover:opacity-95">
                        <i class="fa-solid fa-user-plus text-xs"></i>
                        <span>Daftar</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pilihan Kategori Versi Telefon -->
        <div class="flex md:hidden px-4 pb-2.5 gap-1.5">
            <a href="<?= BASE_URL ?>/public/index.php?cat=rental" id="mob-tab-rental" class="flex-1 py-1.5 text-center rounded-xl text-xs font-bold <?= (!isset($_GET['cat']) || $_GET['cat'] === 'rental') ? 'pink-gradient text-white shadow-sm' : 'bg-slate-100 text-slate-600' ?>">
                <i class="fa-solid fa-user-graduate mr-1"></i>Sewa
            </a>
            <a href="<?= BASE_URL ?>/public/index.php?cat=homestay" id="mob-tab-homestay" class="flex-1 py-1.5 text-center rounded-xl text-xs font-bold <?= (isset($_GET['cat']) && $_GET['cat'] === 'homestay') ? 'pink-gradient text-white shadow-sm' : 'bg-slate-100 text-slate-600' ?>">
                <i class="fa-solid fa-suitcase-rolling mr-1"></i>Homestay
            </a>
            <a href="<?= BASE_URL ?>/public/roommate_hub.php" id="mob-tab-roommate" class="flex-1 py-1.5 text-center rounded-xl text-xs font-bold <?= (isset($_GET['cat']) && $_GET['cat'] === 'roommate') ? 'pink-gradient text-white shadow-sm' : 'bg-slate-100 text-slate-600' ?>">
                <i class="fa-solid fa-users mr-1"></i>Roommate
            </a>
        </div>
    </header>
