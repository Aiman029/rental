<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = currentUser();
$isVerified = isVerifiedStudent();
$activeCategory = $_GET['cat'] ?? 'rental';
if (!in_array($activeCategory, ['rental', 'homestay'], true)) {
    $activeCategory = 'rental';
}

$pageTitle = 'Cari Rumah Sewa Pelajar UPSI & Homestay Tanjung Malim';
require_once __DIR__ . '/../includes/header.php';
?>

    <!-- BAHAGIAN HERO DENGAN KOTAK CARIAN AIRBNB-STYLE TERAPUNG -->
    <section class="relative pt-6 sm:pt-8 pb-10 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <!-- Hero Gradient Box -->
            <div class="bg-gradient-to-br from-pinkbrand-600 via-pinkbrand-500 to-rose-400 rounded-3xl p-6 sm:p-10 text-white shadow-xl shadow-pinkbrand-200/50 relative overflow-hidden">
                <!-- Background decorative glow -->
                <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>

                <div class="max-w-3xl relative z-10">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-extrabold uppercase tracking-wider">
                            <i class="fa-solid fa-shield-heart"></i> Portal Rasmi Kediaman Pelajar UPSI
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-indigo-950/40 backdrop-blur-md text-xs font-bold text-indigo-100 border border-indigo-300/30">
                            <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i> AI Matchmaker Aktif
                        </span>
                    </div>

                    <h1 id="heroTitle" class="text-2xl sm:text-4xl font-extrabold leading-tight mb-2.5">
                        Cari Rumah Sewa Pelajar & Homestay Sekitar UPSI Dengan Mudah & Selamat
                    </h1>
                    <p id="heroSubtitle" class="text-pink-50 text-xs sm:text-sm sm:text-base mb-5 leading-relaxed font-medium">
                        Tengok gambar bilik demi bilik gaya Airbnb, semak senarai inventori kelengkapan, bandingkan kos sewa kongsi, dan semak kontrak dengan Gemini AI!
                    </p>

                    <!-- Pautan Pantas AI Prompt Pill -->
                    <div class="flex flex-wrap items-center gap-1.5 mb-2 text-xs">
                        <span class="text-pink-100 font-bold mr-1 flex items-center gap-1">
                            <i class="fa-solid fa-bolt text-amber-300"></i> Contoh soalan AI:
                        </span>
                        <button type="button" onclick="triggerAiWithQuery('Saya pelajar KSAS bajet bawah RM200 siap WiFi')" class="bg-white/15 hover:bg-white/25 px-3 py-1 rounded-xl text-white font-medium transition backdrop-blur-sm border border-white/20">
                            "Siswi KSAS bajet &lt; RM200"
                        </button>
                        <button type="button" onclick="triggerAiWithQuery('Semak klausa keselamatan kontrak sewa')" class="bg-white/15 hover:bg-white/25 px-3 py-1 rounded-xl text-white font-medium transition backdrop-blur-sm border border-white/20">
                            "Semak klausa kontrak sewa"
                        </button>
                        <button type="button" onclick="triggerAiWithQuery('Kira anggaran kos hidup bulanan pelajar UPSI')" class="bg-white/15 hover:bg-white/25 px-3 py-1 rounded-xl text-white font-medium transition backdrop-blur-sm border border-white/20">
                            "Kira kos sara hidup UPSI"
                        </button>
                    </div>
                </div>

                <!-- KOTAK PENAPIS CARIAN AIRBNB-STYLE TERAPUNG (FR004) -->
                <div class="mt-8 bg-white p-3 sm:p-4 rounded-3xl shadow-2xl text-slate-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 border border-pinkbrand-100 relative z-20">
                    <!-- Kawasan -->
                    <div class="p-2 sm:p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Lokasi / Kawasan</label>
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-location-dot text-pinkbrand-500 text-xs mr-2"></i>
                            <select id="locationFilter" onchange="applyFilters()" class="w-full bg-transparent text-xs sm:text-sm font-bold text-slate-800 focus:outline-none cursor-pointer">
                                <option value="all">Semua Kawasan</option>
                                <option value="Taman Bahtera">Taman Bahtera (KSAJS)</option>
                                <option value="Proton City">Proton City (KSAS)</option>
                                <option value="Taman Universiti">Taman Universiti</option>
                                <option value="Behrang 2020">Bandar Behrang 2020</option>
                                <option value="Behrang Stesen">Behrang Stesen</option>
                                <option value="Kalumpang">Kalumpang</option>
                            </select>
                        </div>
                    </div>

                    <!-- Status Kekosongan -->
                    <div class="p-2 sm:p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Status Kemasukan</label>
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-calendar-check text-pinkbrand-500 text-xs mr-2"></i>
                            <select id="availabilityFilter" onchange="applyFilters()" class="w-full bg-transparent text-xs sm:text-sm font-bold text-slate-800 focus:outline-none cursor-pointer">
                                <option value="all">Semua Status</option>
                                <option value="Masuk Segera">Masuk Segera</option>
                                <option value="Semester Depan">Semester Depan</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bajet Maksimum -->
                    <div class="p-2 sm:p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Bajet Maksimum</label>
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-wallet text-pinkbrand-500 text-xs mr-2"></i>
                            <select id="priceFilter" onchange="applyFilters()" class="w-full bg-transparent text-xs sm:text-sm font-bold text-slate-800 focus:outline-none cursor-pointer">
                                <option value="99999">Semua Harga</option>
                                <option value="200">Bawah RM 200 (Kongsi)</option>
                                <option value="350">Bawah RM 350 (Bilik)</option>
                                <option value="800">Bawah RM 800</option>
                                <option value="1300">Bawah RM 1,300 (Rumah)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Carian Kata Kunci -->
                    <div class="p-2 sm:p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Kemudahan / Acara</label>
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-magnifying-glass text-pinkbrand-500 text-xs mr-2"></i>
                            <input id="searchInput" oninput="applyFilters()" type="text" placeholder="WiFi, Bas, Konvo..." class="w-full bg-transparent text-xs sm:text-sm font-bold text-slate-800 focus:outline-none placeholder-slate-400" />
                        </div>
                    </div>

                    <!-- Butang Tindakan Carian -->
                    <div class="flex items-center">
                        <button type="button" onclick="applyFilters()" class="w-full h-full py-3 px-4 rounded-2xl pink-gradient text-white font-extrabold text-xs sm:text-sm shadow-md shadow-pinkbrand-200 hover:opacity-95 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Tapis Carian</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PUSAT BANTUAN PELAJAR & UTILITI INTERAKTIF (CLEAN CARD ROW) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-4">
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-pinkbrand-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-pinkbrand-500 inline-block"></span>
                        <span>Pusat Bantuan Pelajar & Utiliti Interaktif</span>
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium">Alat kira sewa, jadual bas shuttle, dan panduan keselamatan sewa UPSI</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <!-- Gemini AI Helper -->
                <button type="button" onclick="openGeminiModal()" class="flex items-center gap-3 p-3.5 rounded-2xl bg-indigo-50/70 hover:bg-indigo-100/90 text-left transition border border-indigo-100 group card-hover">
                    <div class="w-10 h-10 rounded-xl ai-gradient text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-wand-magic-sparkles text-sm text-amber-200"></i>
                    </div>
                    <div>
                        <div class="text-xs font-extrabold text-indigo-950">Gemini AI Sewa</div>
                        <div class="text-[11px] text-indigo-600 font-medium">Saranan & Semak</div>
                    </div>
                </button>

                <!-- Kalkulator Kongsi Sewa -->
                <button type="button" onclick="openCalculatorModal()" class="flex items-center gap-3 p-3.5 rounded-2xl bg-rose-50/70 hover:bg-rose-100/90 text-left transition border border-rose-100 group card-hover">
                    <div class="w-10 h-10 rounded-xl pink-gradient text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-calculator text-sm"></i>
                    </div>
                    <div>
                        <div class="text-xs font-extrabold text-slate-800">Kalkulator Sewa</div>
                        <div class="text-[11px] text-slate-500 font-medium">Kira Kos Kongsi</div>
                    </div>
                </button>

                <!-- Laluan Bas UPSI -->
                <button type="button" onclick="openInfoModal('bus')" class="flex items-center gap-3 p-3.5 rounded-2xl bg-blue-50/70 hover:bg-blue-100/90 text-left transition border border-blue-100 group card-hover">
                    <div class="w-10 h-10 rounded-xl bg-blue-500 text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-bus text-sm"></i>
                    </div>
                    <div>
                        <div class="text-xs font-extrabold text-slate-800">Laluan Bas UPSI</div>
                        <div class="text-[11px] text-slate-500 font-medium">Jadual Shuttle</div>
                    </div>
                </button>

                <!-- Panduan Kontrak & Anti Scam -->
                <button type="button" onclick="openInfoModal('checklist')" class="flex items-center gap-3 p-3.5 rounded-2xl bg-emerald-50/70 hover:bg-emerald-100/90 text-left transition border border-emerald-100 group card-hover">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-file-shield text-sm"></i>
                    </div>
                    <div>
                        <div class="text-xs font-extrabold text-slate-800">Elak Scam Sewa</div>
                        <div class="text-[11px] text-slate-500 font-medium">Semak Kontrak</div>
                    </div>
                </button>

                <!-- Diskaun Pelajar 10% -->
                <a href="<?= BASE_URL ?>/public/verify_student.php" class="col-span-2 sm:col-span-1 flex items-center gap-3 p-3.5 rounded-2xl bg-amber-50/70 hover:bg-amber-100/90 text-left transition border border-amber-100 group card-hover">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-id-card-clip text-sm"></i>
                    </div>
                    <div>
                        <div class="text-xs font-extrabold text-slate-800">Diskaun 10%</div>
                        <div class="text-[11px] text-amber-700 font-medium">Kad Matrik UPSI</div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- BANNER STATUS DISKAUN PELAJAR AKTIF (FR010) -->
    <?php if ($isVerified): ?>
    <div id="studentActiveBanner" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
        <div class="bg-emerald-50 border-2 border-emerald-200 rounded-3xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <h4 class="text-xs sm:text-sm font-extrabold text-emerald-900 flex items-center gap-2">
                        <span>Kad Matrik UPSI Sah & Aktif</span>
                        <span class="bg-emerald-200 text-emerald-800 px-2 py-0.5 rounded-full text-[10px] font-bold">Diskaun 10% Diterapkan</span>
                    </h4>
                    <p class="text-[11px] text-emerald-700 mt-0.5">
                        Semua harga sewaan di bawah telah ditolak 10% secara automatik untuk akaun <strong><?= e($user['name']) ?></strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SENARAI KEDIAMAN (GRID UTAMA) -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6 pb-3 border-b border-pinkbrand-100">
            <div>
                <h2 id="sectionHeading" class="text-lg sm:text-xl font-extrabold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid <?= $activeCategory === 'homestay' ? 'fa-suitcase-rolling text-pinkbrand-500' : 'fa-house text-pinkbrand-500' ?>"></i>
                    <span><?= $activeCategory === 'homestay' ? 'Senarai Homestay & Penginapan Konvo UPSI' : 'Senarai Rumah Sewa Pelajar UPSI' ?></span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Kemasukan semester terbaru sekitar Tanjung Malim, Proton City & Behrang</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span id="resultCountBadge" class="bg-pinkbrand-50 text-pinkbrand-700 border border-pinkbrand-200 px-3 py-1 rounded-full font-extrabold">Memuatkan...</span>
                <button type="button" onclick="resetFilters()" class="text-xs font-bold text-slate-500 hover:text-pinkbrand-600 transition flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white border border-slate-200 shadow-sm">
                    <i class="fa-solid fa-rotate-left text-[10px]"></i>
                    <span>Set Semula Penapis</span>
                </button>
            </div>
        </div>

        <!-- GRID KEDIAMAN -->
        <div id="listingsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Rendered dynamically by JavaScript via api/listings.php -->
        </div>

        <!-- KEADAAN KOSONG / NO RESULTS -->
        <div id="noResultsState" class="hidden text-center py-16 px-4 bg-white rounded-3xl border border-pinkbrand-100 shadow-sm my-6">
            <div class="w-16 h-16 rounded-3xl bg-pinkbrand-50 text-pinkbrand-500 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-magnifying-glass-arrow-right"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-800 mb-1">Tiada Kediaman Dijumpai</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-5">
                Tiada rumah atau bilik yang menepati kriteria tapisan anda. Cuba tukar pilihan kawasan atau set semula penapis carian.
            </p>
            <button type="button" onclick="resetFilters()" class="px-5 py-2.5 rounded-2xl pink-gradient text-white text-xs font-extrabold shadow-md shadow-pinkbrand-200 hover:opacity-95 transition">
                <i class="fa-solid fa-rotate-left mr-1.5"></i> Set Semula Semua Penapis
            </button>
        </div>
    </main>

    <!-- BAR PERBANDINGAN TERAPUNG (PHASE 2 / FR008) -->
    <div id="compareBar" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900/95 backdrop-blur-md text-white rounded-3xl px-5 py-3 shadow-2xl flex items-center gap-4 border border-slate-700">
        <div class="text-xs font-semibold">
            <span class="font-extrabold text-pinkbrand-400" id="compareCount">0</span>/3 Kediaman dipilih untuk dibanding
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openCompareModal()" class="px-4 py-2 rounded-2xl pink-gradient text-white text-xs font-extrabold transition shadow-md">
                Bandingkan Sekarang
            </button>
            <button type="button" onclick="clearComparison()" class="text-xs text-slate-400 hover:text-white transition px-2 py-1">
                Batal
            </button>
        </div>
    </div>

    <!-- MODAL DETAIL KEDIAMAN (FR005) -->
    <div id="propertyModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-pinkbrand-100 relative">
            <button type="button" onclick="closePropertyModal()" class="absolute top-4 right-4 z-20 w-9 h-9 rounded-full bg-white/90 shadow text-slate-600 hover:text-rose-600 flex items-center justify-center font-bold text-sm transition">
                ✕
            </button>
            <div id="modalContent">
                <!-- Dynamically populated in JS -->
            </div>
        </div>
    </div>

    <!-- MODAL KALKULATOR SEWA (FR007 / UC008) -->
    <div id="calculatorModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-pinkbrand-100 relative">
            <button type="button" onclick="closeCalculatorModal()" class="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs transition">
                ✕
            </button>
            
            <div class="text-center mb-5">
                <div class="w-12 h-12 rounded-2xl pink-gradient flex items-center justify-center text-white mx-auto mb-2 shadow-md shadow-pinkbrand-200">
                    <i class="fa-solid fa-calculator text-xl"></i>
                </div>
                <h3 class="text-lg font-extrabold text-slate-800">Kalkulator Kongsi Sewa UPSI</h3>
                <p class="text-xs text-slate-500">Anggarkan kos bulanan setiap rakan serumah berserta bil utiliti</p>
            </div>

            <div class="space-y-4 text-xs">
                <!-- Base Rent Slider -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-slate-600">Sewa Kasar Rumah (Bulanan):</span>
                        <span class="text-pinkbrand-600 font-extrabold text-sm" id="calcRentDisplay">RM 1,200</span>
                    </div>
                    <input type="range" id="calcRentInput" min="200" max="2500" step="50" value="1200" oninput="updateCalculator()" class="w-full accent-pink-500 cursor-pointer">
                </div>

                <!-- Pax Slider -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-slate-600">Bilangan Pelajar Berkongsi:</span>
                        <span class="text-slate-800 font-extrabold text-sm" id="calcPaxDisplay">6 Orang</span>
                    </div>
                    <input type="range" id="calcPaxInput" min="1" max="10" step="1" value="6" oninput="updateCalculator()" class="w-full accent-pink-500 cursor-pointer">
                </div>

                <!-- Shared Utilities Checkboxes -->
                <div>
                    <label class="block font-bold text-slate-600 uppercase mb-2">Pilihan Utiliti Dikongsi:</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="p-2.5 rounded-xl border border-slate-200 bg-white flex items-center gap-2 cursor-pointer hover:border-pinkbrand-300 transition">
                            <input type="checkbox" id="calcUtilWifi" value="90" checked onchange="updateCalculator()" class="rounded text-pinkbrand-500">
                            <span>WiFi Unifi (+RM90)</span>
                        </label>
                        <label class="p-2.5 rounded-xl border border-slate-200 bg-white flex items-center gap-2 cursor-pointer hover:border-pinkbrand-300 transition">
                            <input type="checkbox" id="calcUtilElec" value="80" checked onchange="updateCalculator()" class="rounded text-pinkbrand-500">
                            <span>Elektrik TNB (+RM80)</span>
                        </label>
                        <label class="p-2.5 rounded-xl border border-slate-200 bg-white flex items-center gap-2 cursor-pointer hover:border-pinkbrand-300 transition">
                            <input type="checkbox" id="calcUtilWater" value="30" checked onchange="updateCalculator()" class="rounded text-pinkbrand-500">
                            <span>Air LAP (+RM30)</span>
                        </label>
                        <label class="p-2.5 rounded-xl border border-slate-200 bg-white flex items-center gap-2 cursor-pointer hover:border-pinkbrand-300 transition">
                            <input type="checkbox" id="calcUtilCoway" value="65" onchange="updateCalculator()" class="rounded text-pinkbrand-500">
                            <span>Coway/Cuckoo (+RM65)</span>
                        </label>
                    </div>
                </div>

                <!-- Hasil Kiraan -->
                <div class="p-4 rounded-2xl bg-gradient-to-r from-pinkbrand-50 to-pinkbrand-100/70 border border-pinkbrand-200 text-center">
                    <p class="text-xs text-slate-500 font-semibold mb-1">Anggaran Bayaran Setiap Pelajar:</p>
                    <div class="text-3xl font-extrabold text-pinkbrand-600" id="calcPerPersonDisplay">RM 244</div>
                    <p class="text-[11px] text-slate-500 mt-1" id="calcBreakdownNote">Sewa bilik + bahagian utiliti sebulan</p>
                    <div id="calcStudentDiscountNotice" class="<?= $isVerified ? '' : 'hidden' ?> mt-2 py-1 px-2.5 bg-emerald-100 text-emerald-800 rounded-lg text-[10px] font-extrabold">
                        ✓ Diskaun 10% pelajar telah ditolak dari sewa asas!
                    </div>
                </div>

                <!-- Butang Kongsi WhatsApp -->
                <button type="button" onclick="shareCalcResultToWhatsApp()" class="w-full py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs flex items-center justify-center gap-2 shadow-md transition">
                    <i class="fa-brands fa-whatsapp text-lg"></i>
                    <span>Kongsi Anggaran ke WhatsApp Rakan Bilik</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL PERBANDINGAN KEDIAMAN (FR008 / PHASE 2) -->
    <div id="compareModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-5xl w-full p-6 sm:p-8 shadow-2xl border border-pinkbrand-100 relative max-h-[90vh] overflow-y-auto">
            <button type="button" onclick="closeCompareModal()" class="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs transition">
                ✕
            </button>
            <div class="text-center mb-6">
                <h3 class="text-xl font-extrabold text-slate-800">Perbandingan Kediaman Bersebelahan</h3>
                <p class="text-xs text-slate-500 mt-1">Bandingkan harga, kapasiti, jarak ke kampus, dan kemudahan secara serentak</p>
            </div>
            <div id="compareModalContent" class="overflow-x-auto">
                <!-- Dynamically populated in Phase 2 -->
            </div>
        </div>
    </div>

    <!-- MODAL PANDUAN PELAJAR (FR024 / PHASE 3) -->
    <div id="infoModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-pinkbrand-100 relative max-h-[90vh] overflow-y-auto">
            <button type="button" onclick="closeInfoModal()" class="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs transition">
                ✕
            </button>
            <div id="infoModalBody">
                <!-- Filled dynamically -->
            </div>
        </div>
    </div>

    <!-- MODAL GEMINI AI ASSISTANT (FR011 / PHASE 3) -->
    <div id="geminiModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-3 sm:p-6">
        <div class="bg-white rounded-3xl max-w-2xl w-full h-[85vh] flex flex-col shadow-2xl border border-indigo-200 relative overflow-hidden">
            <!-- Modal Header -->
            <div class="ai-gradient text-white p-4 sm:p-5 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-lg shadow-sm">
                        <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold flex items-center gap-2">
                            <span>Pembantu Pintar Gemini AI Sewa</span>
                            <span class="text-[9px] bg-white/20 px-2 py-0.5 rounded-full font-bold">Proxy Selamat</span>
                        </h3>
                        <p class="text-[11px] text-indigo-100 font-medium">Penasihat sewa, semakan kontrak & panduan kos sara hidup UPSI</p>
                    </div>
                </div>
                <button type="button" onclick="closeGeminiModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center font-bold text-xs transition">
                    ✕
                </button>
            </div>

            <!-- Chat Box -->
            <div id="aiChatBox" class="flex-1 p-4 sm:p-6 overflow-y-auto space-y-3.5 bg-slate-50 text-xs">
                <!-- Initial Welcome Message -->
                <div class="flex items-start gap-2.5 max-w-[85%]">
                    <div class="w-8 h-8 rounded-2xl ai-gradient text-white flex items-center justify-center text-xs shrink-0 shadow-sm">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="bg-white p-4 rounded-3xl rounded-tl-none border border-slate-200 shadow-sm leading-relaxed text-slate-700">
                        <p class="font-extrabold text-indigo-950 mb-1 text-sm">Hai! Saya Pembantu AI UpsiRent-I 👋</p>
                        <p>Saya boleh bantu anda:</p>
                        <ul class="list-disc list-inside mt-1 space-y-0.5 text-slate-600 font-medium">
                            <li>Mencari rumah sewa mengikut bajet & kampus (KSAJS / KSAS).</li>
                            <li>Menyemak klausa keselamatan perjanjian sewa (red flags / scam).</li>
                            <li>Mengira bajet sara hidup bulanan pelajar Tanjung Malim.</li>
                        </ul>
                        <p class="mt-2 text-[11px] text-indigo-600 font-bold">Tanya soalan anda di bawah atau klik contoh soalan!</p>
                    </div>
                </div>
            </div>

            <!-- Quick Prompts Pill Container -->
            <div class="px-4 py-2.5 bg-slate-100/90 border-t border-slate-200 flex flex-wrap gap-1.5 text-[11px]">
                <button type="button" onclick="sendQuickPrompt('Bandingkan kos sewa Taman Bahtera vs Proton City')" class="bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 font-semibold transition">
                    Taman Bahtera vs Proton City
                </button>
                <button type="button" onclick="sendQuickPrompt('Apa red flags yang perlu saya elak dalam perjanjian sewa?')" class="bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 font-semibold transition">
                    Red Flags Kontrak Sewa
                </button>
                <button type="button" onclick="sendQuickPrompt('Berapa bajet makan dan sewa sebulan untuk siswi UPSI?')" class="bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 font-semibold transition">
                    Bajet Sebulan Siswa UPSI
                </button>
            </div>

            <!-- Input Bar -->
            <div class="p-3 sm:p-4 bg-white border-t border-slate-200 flex items-center gap-2">
                <input type="text" id="aiUserInput" onkeydown="if(event.key==='Enter') sendAiMessage()" placeholder="Taip soalan sewa anda atau tampal klausa kontrak di sini..." class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-indigo-500">
                <button type="button" id="aiSendBtn" onclick="sendAiMessage()" class="px-5 py-2.5 rounded-2xl ai-gradient text-white font-extrabold text-xs shadow-md transition hover:opacity-95">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Hantar
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN CLIENT-SIDE LOGIC SCRIPT -->
    <script>
        const ACTIVE_CATEGORY = '<?= $activeCategory ?>';
        const IS_VERIFIED = document.body.dataset.isStudentVerified === 'true';
        let allProperties = [];
        let compareList = [];

        document.addEventListener('DOMContentLoaded', () => {
            fetchListings();
        });

        async function fetchListings() {
            try {
                const res = await fetch(`${BASE_URL}/api/listings.php?category=${ACTIVE_CATEGORY}`);
                const data = await res.json();
                if (data.success) {
                    allProperties = data.properties;
                    applyFilters();
                } else {
                    showToast('Ralat memuatkan kediaman: ' + data.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal berhubung dengan pelayan pangkalan data.', 'error');
            }
        }

        function applyFilters() {
            const loc = document.getElementById('locationFilter').value;
            const avail = document.getElementById('availabilityFilter').value;
            const price = parseFloat(document.getElementById('priceFilter').value) || 999999;
            const search = document.getElementById('searchInput').value.trim().toLowerCase();

            const filtered = allProperties.filter(p => {
                const matchLoc = (loc === 'all' || p.location === loc);
                const matchAvail = (avail === 'all' || p.availability === avail);
                const matchPrice = (p.base_price <= price);
                const matchSearch = (!search || 
                    p.title.toLowerCase().includes(search) || 
                    p.location.toLowerCase().includes(search) || 
                    p.distance.toLowerCase().includes(search) ||
                    (p.badges && p.badges.some(b => b.toLowerCase().includes(search)))
                );
                return matchLoc && matchAvail && matchPrice && matchSearch;
            });

            renderGrid(filtered);
        }

        function resetFilters() {
            document.getElementById('locationFilter').value = 'all';
            document.getElementById('availabilityFilter').value = 'all';
            document.getElementById('priceFilter').value = '99999';
            document.getElementById('searchInput').value = '';
            applyFilters();
            showToast('Semua penapis carian telah diset semula.', 'info');
        }

        function renderGrid(properties) {
            const grid = document.getElementById('listingsGrid');
            const noResults = document.getElementById('noResultsState');
            const badge = document.getElementById('resultCountBadge');

            badge.innerText = `${properties.length} Kediaman Dijumpai`;

            if (properties.length === 0) {
                grid.innerHTML = '';
                noResults.classList.remove('hidden');
                return;
            }

            noResults.classList.add('hidden');
            grid.innerHTML = properties.map(p => {
                const img = (p.gallery && p.gallery.length > 0) ? p.gallery[0].url : 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=800&q=80';
                const isCompared = compareList.includes(p.property_id);
                
                let priceHtml = '';
                if (IS_VERIFIED && p.pricing && p.pricing.has_discount) {
                    priceHtml = `
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xs text-slate-400 line-through">RM ${Math.round(p.pricing.original)}</span>
                            <span class="text-lg font-extrabold text-pinkbrand-600">RM ${Math.round(p.pricing.final)}</span>
                            <span class="text-[11px] text-slate-500 font-normal">/${p.price_unit}</span>
                        </div>
                        <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-extrabold">-10% Pelajar Sah</span>
                    `;
                } else {
                    priceHtml = `
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-lg font-extrabold text-pinkbrand-600">RM ${Math.round(p.base_price)}</span>
                            <span class="text-[11px] text-slate-500 font-normal">/${p.price_unit}</span>
                        </div>
                    `;
                }

                const badgesHtml = (p.badges || []).slice(0, 2).map(b => 
                    `<span class="bg-pinkbrand-50 text-pinkbrand-700 px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold border border-pinkbrand-100">${b}</span>`
                ).join(' ');

                return `
                    <div class="bg-white rounded-3xl overflow-hidden border border-pinkbrand-100 shadow-sm card-hover flex flex-col justify-between group">
                        <div>
                            <div class="relative h-48 overflow-hidden bg-slate-100">
                                <img src="${img}" alt="${p.title}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-md px-3 py-1 rounded-xl text-[11px] font-extrabold text-slate-800 shadow">
                                    <i class="fa-solid fa-location-dot text-pinkbrand-500 mr-1"></i> ${p.location}
                                </div>
                                <div class="absolute top-3 right-3 bg-slate-900/80 backdrop-blur-md text-white px-2.5 py-1 rounded-xl text-[10px] font-extrabold flex items-center gap-1 shadow">
                                    <i class="fa-solid fa-star text-amber-400"></i> ${p.rating} (${p.review_count})
                                </div>
                            </div>

                            <div class="p-5">
                                <div class="flex flex-wrap gap-1.5 mb-2.5">
                                    ${badgesHtml}
                                </div>

                                <h3 class="text-sm font-extrabold text-slate-800 line-clamp-2 leading-snug mb-2 hover:text-pinkbrand-600 cursor-pointer transition-colors" onclick="openPropertyModal(${p.property_id})">
                                    ${p.title}
                                </h3>

                                <p class="text-[11px] text-slate-500 flex items-center gap-1.5 mb-3.5 font-medium">
                                    <i class="fa-solid fa-route text-pinkbrand-400 shrink-0"></i>
                                    <span class="truncate">${p.distance}</span>
                                </p>

                                <div class="border-t border-slate-100 pt-3">
                                    ${priceHtml}
                                    ${p.per_head_note ? `<p class="text-[10px] text-slate-400 mt-0.5 font-medium">${p.per_head_note}</p>` : ''}
                                </div>
                            </div>
                        </div>

                        <div class="p-5 pt-0 flex items-center gap-2">
                            <button type="button" onclick="openPropertyModal(${p.property_id})" class="flex-1 py-2.5 rounded-2xl pink-gradient text-white text-xs font-extrabold shadow-sm hover:opacity-95 transition text-center flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-images text-[11px]"></i>
                                <span>Lihat Bilik Demi Bilik</span>
                            </button>
                            <button type="button" onclick="toggleCompare(${p.property_id})" class="p-2.5 rounded-2xl border ${isCompared ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50'} text-xs font-bold transition shadow-sm" title="Bandingkan">
                                <i class="fa-solid fa-scale-balanced"></i>
                            </button>
                            <button type="button" onclick="toggleWishlist(${p.property_id})" class="p-2.5 rounded-2xl border border-slate-200 text-slate-600 hover:text-pinkbrand-500 hover:bg-pinkbrand-50 text-xs font-bold transition shadow-sm" title="Simpan ke Wishlist">
                                <i class="fa-regular fa-heart"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function openPropertyModal(id) {
            const modal = document.getElementById('propertyModal');
            const content = document.getElementById('modalContent');
            content.innerHTML = `<div class="p-12 text-center text-xs text-slate-500"><i class="fa-solid fa-circle-notch fa-spin text-2xl text-pinkbrand-500 mb-2"></i><p>Memuatkan butiran kediaman...</p></div>`;
            modal.classList.remove('hidden');

            try {
                const res = await fetch(`${BASE_URL}/api/listings.php?id=${id}`);
                const data = await res.json();
                if (!data.success) {
                    content.innerHTML = `<div class="p-8 text-center text-xs text-rose-600">${data.message}</div>`;
                    return;
                }

                const p = data.property;
                const gallery = p.gallery || [];
                const mainImg = gallery.length > 0 ? gallery[0].url : '';
                const mainCaption = gallery.length > 0 ? gallery[0].caption : '';
                const mainLabel = gallery.length > 0 ? gallery[0].label : '';

                let priceDisplay = `RM ${Math.round(p.base_price)} / ${p.price_unit}`;
                if (IS_VERIFIED && p.pricing && p.pricing.has_discount) {
                    priceDisplay = `<span class="line-through text-slate-400 text-sm">RM ${Math.round(p.pricing.original)}</span> RM ${Math.round(p.pricing.final)} / ${p.price_unit} <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold ml-1">Diskaun 10% Aktif</span>`;
                }

                const sectionsHtml = (p.sections || []).map(sec => `
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <h4 class="text-xs font-extrabold text-slate-800 flex items-center gap-2 mb-2">
                            <i class="fa-solid ${sec.icon || 'fa-check-circle'} text-pinkbrand-500"></i> ${sec.title}
                        </h4>
                        <ul class="space-y-1 text-xs text-slate-600">
                            ${(sec.items || []).map(it => `<li class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-500 text-[10px]"></i> ${it}</li>`).join('')}
                        </ul>
                    </div>
                `).join('');

                const reviewsHtml = (p.reviews && p.reviews.length > 0) ? p.reviews.map(r => `
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 text-xs">
                        <div class="flex items-center justify-between mb-1">
                            <div>
                                <span class="font-extrabold text-slate-800">${r.reviewer_name}</span>
                                <span class="text-[10px] text-slate-400 ml-1">(${r.reviewer_role})</span>
                            </div>
                            <div class="text-amber-400 text-[11px] font-bold">
                                ${'★'.repeat(r.rating)} <span class="text-slate-400 text-[10px] ml-1">${r.review_date}</span>
                            </div>
                        </div>
                        <p class="text-slate-600 leading-relaxed italic">"${r.comment}"</p>
                    </div>
                `).join('') : '<p class="text-xs text-slate-400 italic">Belum ada ulasan untuk kediaman ini.</p>';

                const waText = encodeURIComponent(`Hai ${p.contact_name}, saya berminat dengan sewaan "${p.title}" di UpsiRent-I. Adakah masih ada kekosongan?`);
                const waUrl = `https://wa.me/${p.contact_whatsapp}?text=${waText}`;

                const mapQuery = encodeURIComponent(p.location + ' Tanjung Malim');
                const mapsUrl = p.map_url || `https://www.google.com/maps/search/?api=1&query=${mapQuery}`;

                content.innerHTML = `
                    <div class="p-6 sm:p-8 space-y-6">
                        <!-- Header Info -->
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-pinkbrand-100 text-pinkbrand-700">${p.category}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">${p.availability}</span>
                                <span class="text-xs font-bold text-slate-500 ml-auto flex items-center gap-1">
                                    <i class="fa-solid fa-star text-amber-400"></i> ${p.rating} (${p.review_count} Ulasan Penyewa)
                                </span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 leading-snug">${p.title}</h2>
                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-location-dot text-pinkbrand-500"></i> ${p.location} • ${p.distance}
                            </p>
                        </div>

                        <!-- Airbnb Room-by-room Photo Gallery -->
                        <div>
                            <div class="relative h-64 sm:h-96 rounded-3xl overflow-hidden bg-slate-900 mb-3 shadow-lg">
                                <img id="activeGalleryImg" src="${mainImg}" alt="Foto Kediaman" class="w-full h-full object-cover">
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-slate-950/80 to-transparent p-4 text-white">
                                    <p id="activeGalleryLabel" class="text-xs font-extrabold uppercase tracking-wider text-pinkbrand-300">${mainLabel}</p>
                                    <p id="activeGalleryCaption" class="text-xs text-slate-200 mt-0.5">${mainCaption}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 overflow-x-auto pb-2">
                                ${gallery.map((g, idx) => `
                                    <img src="${g.url}" onclick="selectGalleryImage(${idx}, '${g.url}', '${g.label.replace(/'/g, "\\'")}', '${g.caption.replace(/'/g, "\\'")}')" class="w-20 h-14 object-cover rounded-xl cursor-pointer border-2 transition hover:opacity-100 ${idx === 0 ? 'border-pinkbrand-500' : 'border-transparent opacity-70'}" data-thumb-idx="${idx}">
                                `).join('')}
                            </div>
                        </div>

                        <!-- Price & Action Bar -->
                        <div class="p-4 sm:p-5 rounded-3xl bg-pinkbrand-50/70 border border-pinkbrand-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <p class="text-xs text-slate-500 font-semibold">Kadar Sewaan Rasmi:</p>
                                <div class="text-xl sm:text-2xl font-extrabold text-pinkbrand-600">${priceDisplay}</div>
                                <p class="text-[11px] text-slate-500 mt-0.5">${p.per_head_note || ''}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                <a href="${waUrl}" target="_blank" rel="noopener noreferrer" class="flex-1 sm:flex-initial px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs flex items-center justify-center gap-2 shadow-md transition">
                                    <i class="fa-brands fa-whatsapp text-lg"></i>
                                    <span>WhatsApp Tuan Rumah</span>
                                </a>
                                <a href="${mapsUrl}" target="_blank" rel="noopener noreferrer" class="px-4 py-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition shadow-sm">
                                    <i class="fa-solid fa-map-location-dot text-pinkbrand-500"></i>
                                    <span>Peta Google</span>
                                </a>
                                <button type="button" onclick="launchCalcForProperty(${p.base_price}, ${p.recommended_pax})" class="px-4 py-3 rounded-2xl pink-gradient text-white font-bold text-xs flex items-center gap-1.5 shadow transition">
                                    <i class="fa-solid fa-calculator"></i>
                                    <span>Kira Kos Kongsi</span>
                                </button>
                            </div>
                        </div>

                        <!-- Inventory Checklist Sections -->
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-list-check text-pinkbrand-500"></i> Senarai Kelengkapan & Inventori
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                ${sectionsHtml}
                            </div>
                        </div>

                        <!-- Contract Terms & Rules -->
                        ${p.contract_info ? `
                            <div class="bg-slate-50 p-5 rounded-3xl border border-slate-200 text-xs">
                                <h3 class="text-sm font-extrabold text-slate-800 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-file-contract text-indigo-600"></i> Terma & Syarat Kontrak Sewaan
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-slate-700">
                                    <div><strong>Tempoh Minimum:</strong> ${p.contract_info.duration || '-'}</div>
                                    <div><strong>Kadar Deposit:</strong> ${p.contract_info.deposit || '-'}</div>
                                    <div><strong>Syarat Notis:</strong> ${p.contract_info.notice || '-'}</div>
                                    <div><strong>Peraturan Kediaman:</strong> ${p.contract_info.rules || '-'}</div>
                                </div>
                            </div>
                        ` : ''}

                        <!-- Landlord Info Card -->
                        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between text-xs">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl pink-gradient flex items-center justify-center text-white font-extrabold text-base shadow-sm">
                                    <i class="fa-solid fa-user-tie"></i>
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-slate-900 text-sm">${p.contact_name}</h4>
                                    <p class="text-[11px] text-slate-500">${p.contact_phone} • Disahkan Tuan Rumah Rasmi</p>
                                </div>
                            </div>
                            <a href="${waUrl}" target="_blank" rel="noopener noreferrer" class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 font-extrabold border border-emerald-200 hover:bg-emerald-100 transition">
                                Hubungi
                            </a>
                        </div>

                        <!-- Reviews Section -->
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-comments text-pinkbrand-500"></i> Ulasan Penyewa & Alumni UPSI
                            </h3>
                            <div class="space-y-3">
                                ${reviewsHtml}
                            </div>
                        </div>
                    </div>
                `;
            } catch (err) {
                console.error(err);
                content.innerHTML = `<div class="p-8 text-center text-xs text-rose-600">Ralat memuatkan maklumat kediaman.</div>`;
            }
        }

        function selectGalleryImage(idx, url, label, caption) {
            document.getElementById('activeGalleryImg').src = url;
            document.getElementById('activeGalleryLabel').innerText = label;
            document.getElementById('activeGalleryCaption').innerText = caption;

            document.querySelectorAll('[data-thumb-idx]').forEach(el => {
                if (parseInt(el.dataset.thumbIdx) === idx) {
                    el.classList.add('border-pinkbrand-500');
                    el.classList.remove('opacity-70', 'border-transparent');
                } else {
                    el.classList.remove('border-pinkbrand-500');
                    el.classList.add('opacity-70', 'border-transparent');
                }
            });
        }

        function closePropertyModal() {
            document.getElementById('propertyModal').classList.add('hidden');
        }

        // Rent Calculator logic (FR007 / UC008)
        let calcBaseRent = 1200;
        let calcPax = 6;

        function openCalculatorModal() {
            document.getElementById('calculatorModal').classList.remove('hidden');
            updateCalculator();
        }

        function closeCalculatorModal() {
            document.getElementById('calculatorModal').classList.add('hidden');
        }

        function launchCalcForProperty(rent, pax) {
            closePropertyModal();
            calcBaseRent = rent;
            calcPax = pax;
            document.getElementById('calcRentInput').value = rent;
            document.getElementById('calcPaxInput').value = pax;
            openCalculatorModal();
        }

        function updateCalculator() {
            calcBaseRent = parseFloat(document.getElementById('calcRentInput').value) || 1200;
            calcPax = parseInt(document.getElementById('calcPaxInput').value) || 1;

            document.getElementById('calcRentDisplay').innerText = `RM ${calcBaseRent.toLocaleString()}`;
            document.getElementById('calcPaxDisplay').innerText = `${calcPax} Orang`;

            let effectiveRent = calcBaseRent;
            if (IS_VERIFIED) {
                effectiveRent = calcBaseRent * 0.90;
                document.getElementById('calcStudentDiscountNotice').classList.remove('hidden');
            } else {
                document.getElementById('calcStudentDiscountNotice').classList.add('hidden');
            }

            let totalUtils = 0;
            if (document.getElementById('calcUtilWifi').checked) totalUtils += parseFloat(document.getElementById('calcUtilWifi').value);
            if (document.getElementById('calcUtilElec').checked) totalUtils += parseFloat(document.getElementById('calcUtilElec').value);
            if (document.getElementById('calcUtilWater').checked) totalUtils += parseFloat(document.getElementById('calcUtilWater').value);
            if (document.getElementById('calcUtilCoway').checked) totalUtils += parseFloat(document.getElementById('calcUtilCoway').value);

            const totalMonthly = effectiveRent + totalUtils;
            const perPerson = Math.round(totalMonthly / calcPax);

            document.getElementById('calcPerPersonDisplay').innerText = `RM ${perPerson}`;
            document.getElementById('calcBreakdownNote').innerText = `(Sewa RM ${Math.round(effectiveRent/calcPax)} + Utiliti RM ${Math.round(totalUtils/calcPax)}) / seorang`;
        }

        function shareCalcResultToWhatsApp() {
            const perPerson = document.getElementById('calcPerPersonDisplay').innerText;
            const note = document.getElementById('calcBreakdownNote').innerText;
            const text = encodeURIComponent(
                `*Anggaran Kos Sewa UpsiRent-I*\n` +
                `Sewa Kasar: RM ${calcBaseRent}\n` +
                `Bilangan Rakan: ${calcPax} orang\n` +
                `Bayaran Seorang: *${perPerson} sebulan*\n` +
                `${note}\n\n` +
                `Dikira menggunakan Kalkulator Pintar UpsiRent-I: ${window.location.origin}${BASE_URL}/public/index.php`
            );
            window.open(`https://wa.me/?text=${text}`, '_blank');
        }

        async function toggleWishlist(propId) {
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
                    const badge = document.getElementById('wishlistCountBadge');
                    if (badge) badge.innerText = data.total_wishlists;
                } else {
                    if (data.login_required) {
                        showToast(data.message, 'warning');
                        setTimeout(() => window.location.href = `${BASE_URL}/public/login.php`, 1200);
                    } else {
                        showToast(data.message, 'error');
                    }
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal mengemaskini senarai simpanan.', 'error');
            }
        }

        function toggleCompare(propId) {
            const idx = compareList.indexOf(propId);
            if (idx > -1) {
                compareList.splice(idx, 1);
                showToast('Kediaman dikeluarkan dari perbandingan.', 'info');
            } else {
                if (compareList.length >= 3) {
                    showToast('Maksimum 3 kediaman boleh dibanding pada satu masa.', 'warning');
                    return;
                }
                compareList.push(propId);
                showToast('Kediaman ditambah untuk dibanding.', 'success');
            }
            updateCompareBar();
            applyFilters();
        }

        function updateCompareBar() {
            const bar = document.getElementById('compareBar');
            const count = document.getElementById('compareCount');
            if (compareList.length > 0) {
                bar.classList.remove('hidden');
                count.innerText = compareList.length;
            } else {
                bar.classList.add('hidden');
            }
        }

        function clearComparison() {
            compareList = [];
            updateCompareBar();
            applyFilters();
        }

        function openCompareModal() {
            if (compareList.length < 2) {
                showToast('Sila pilih sekurang-kurangnya 2 kediaman untuk dibanding.', 'warning');
                return;
            }
            const modal = document.getElementById('compareModal');
            const content = document.getElementById('compareModalContent');
            const items = allProperties.filter(p => compareList.includes(p.property_id));

            content.innerHTML = `
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="p-3 bg-slate-50 font-bold text-slate-500 w-1/4">Ciri Kediaman</th>
                            ${items.map(p => `
                                <th class="p-3 bg-pinkbrand-50/50 font-extrabold text-slate-800 text-center w-1/3">
                                    <div class="h-28 overflow-hidden rounded-2xl mb-2">
                                        <img src="${(p.gallery && p.gallery.length>0)?p.gallery[0].url:''}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="line-clamp-2">${p.title}</div>
                                </th>
                            `).join('')}
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="p-3 font-bold text-slate-600 bg-slate-50">Kawasan & Lokasi</td>
                            ${items.map(p => `<td class="p-3 text-center font-semibold text-slate-700">${p.location}</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-bold text-slate-600 bg-slate-50">Jarak ke Kampus</td>
                            ${items.map(p => `<td class="p-3 text-center font-semibold text-slate-700">${p.distance}</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-bold text-slate-600 bg-slate-50">Sewa Bulanan</td>
                            ${items.map(p => `<td class="p-3 text-center font-extrabold text-pinkbrand-600 text-sm">RM ${Math.round(p.base_price)} / ${p.price_unit}</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-bold text-slate-600 bg-slate-50">Kapasiti Disyorkan</td>
                            ${items.map(p => `<td class="p-3 text-center font-semibold text-slate-700">${p.recommended_pax} Orang Pelajar</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-bold text-slate-600 bg-slate-50">Status Kekosongan</td>
                            ${items.map(p => `<td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">${p.availability}</span></td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-bold text-slate-600 bg-slate-50">Tindakan</td>
                            ${items.map(p => `
                                <td class="p-3 text-center">
                                    <button type="button" onclick="closeCompareModal(); openPropertyModal(${p.property_id})" class="px-4 py-2 rounded-2xl pink-gradient text-white text-xs font-bold shadow">
                                        Lihat Butiran
                                    </button>
                                </td>
                            `).join('')}
                        </tr>
                    </tbody>
                </table>
            `;
            modal.classList.remove('hidden');
        }

        function closeCompareModal() {
            document.getElementById('compareModal').classList.add('hidden');
        }

        const INFO_GUIDES = {
            bus: {
                title: 'Jadual & Laluan Bas Shuttle Percuma UPSI',
                icon: 'fa-bus',
                content: `
                    <div class="space-y-3 text-xs leading-relaxed text-slate-700">
                        <div class="p-3.5 bg-pinkbrand-50 rounded-2xl border border-pinkbrand-100">
                            <h4 class="font-extrabold text-pinkbrand-700 mb-1">Laluan Utama Kampus (KSAJS ⟷ KSAS Proton City)</h4>
                            <p>Bas beroperasi setiap 20 minit dari jam 7:00 pagi sehingga 11:00 malam pada hari kuliah.</p>
                        </div>
                        <ul class="space-y-1.5 list-disc list-inside font-medium">
                            <li><strong>Hentian 1:</strong> Kolej Ungku Omar (KUO) & Pintu Timur KSAJS</li>
                            <li><strong>Hentian 2:</strong> Taman Bahtera (Deretan Kedai Makan Pelajar)</li>
                            <li><strong>Hentian 3:</strong> Proton City (Jalan Pulai / Tasik Embayu)</li>
                            <li><strong>Hentian 4:</strong> Kompleks Akademik KSAS & Dewan Tuanku Canselor</li>
                        </ul>
                        <p class="text-[11px] text-slate-400">Peringatan: Sila tunjukkan Kad Matrik Pelajar UPSI semasa menaiki bas shuttle.</p>
                    </div>
                `
            },
            checklist: {
                title: 'Panduan Elak Scam Sewa & Semak Perjanjian',
                icon: 'fa-file-shield',
                content: `
                    <div class="space-y-3 text-xs leading-relaxed text-slate-700">
                        <div class="p-3.5 bg-rose-50 rounded-2xl border border-rose-200 text-rose-800">
                            <h4 class="font-extrabold mb-1">AMARAN KESELAMATAN SEWA:</h4>
                            <p>Jangan sesekali bayar wang pendahuluan (deposit) sebelum melihat rumah secara fizikal atau video live!</p>
                        </div>
                        <ol class="space-y-2 list-decimal list-inside font-medium">
                            <li><strong>Sahkan Identiti Tuan Rumah:</strong> Pastikan nama pemilik pada bil cukai pintu sepadan dengan penerima bayaran.</li>
                            <li><strong>Periksa Gril & Kunci:</strong> Pastikan rumah mempunyai gril besi di pintu dan tingkap untuk keselamatan peribadi.</li>
                            <li><strong>Dokumen Perjanjian Bertulis:</strong> Minta draf kontrak sewa dan pastikan tempoh sewa serta pemulangan deposit dinyatakan dengan jelas.</li>
                            <li><strong>Semak Guna AI:</strong> Anda boleh menggunakan Pembantu Gemini AI UpsiRent-I untuk menyaring klausa kontrak yang mencurigakan.</li>
                        </ol>
                    </div>
                `
            },
            amenities: {
                title: 'Kemudahan Sekitar Kampus Tanjung Malim',
                icon: 'fa-map-location-dot',
                content: `
                    <div class="space-y-3 text-xs leading-relaxed text-slate-700">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <h4 class="font-extrabold text-slate-800"><i class="fa-solid fa-train text-pinkbrand-500 mr-1.5"></i> Stesen KTM Komuter</h4>
                                <p class="text-[11px] text-slate-500 mt-1">Laluan terus Tanjung Malim - KL Sentral. 5 minit dari KSAJS.</p>
                            </div>
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <h4 class="font-extrabold text-slate-800"><i class="fa-solid fa-hospital text-pinkbrand-500 mr-1.5"></i> Kesihatan & Farmasi</h4>
                                <p class="text-[11px] text-slate-500 mt-1">Klinik Kesihatan Tg Malim, Pusat Kesihatan UPSI, Farmasi 24 Jam.</p>
                            </div>
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <h4 class="font-extrabold text-slate-800"><i class="fa-solid fa-cart-shopping text-pinkbrand-500 mr-1.5"></i> Pasaraya & Runcit</h4>
                                <p class="text-[11px] text-slate-500 mt-1">TF Value-Mart Behrang, Speedmart 99 Taman Bahtera & Proton City.</p>
                            </div>
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <h4 class="font-extrabold text-slate-800"><i class="fa-solid fa-utensils text-pinkbrand-500 mr-1.5"></i> Medan Selera Pelajar</h4>
                                <p class="text-[11px] text-slate-500 mt-1">Restoran Tomyam, Warung Nasi Kak Wok, Dobi Layan Diri 24 Jam.</p>
                            </div>
                        </div>
                    </div>
                `
            }
        };

        function openInfoModal(type) {
            const guide = INFO_GUIDES[type];
            if (!guide) return;
            const modal = document.getElementById('infoModal');
            const body = document.getElementById('infoModalBody');
            body.innerHTML = `
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-2xl pink-gradient text-white flex items-center justify-center text-lg shadow-sm">
                        <i class="fa-solid ${guide.icon}"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800">${guide.title}</h3>
                        <p class="text-[11px] text-slate-400">Panduan rasmi kediaman pelajar UPSI</p>
                    </div>
                </div>
                ${guide.content}
            `;
            modal.classList.remove('hidden');
        }

        function closeInfoModal() {
            document.getElementById('infoModal').classList.add('hidden');
        }

        function openGeminiModal() {
            document.getElementById('geminiModal').classList.remove('hidden');
        }

        function closeGeminiModal() {
            document.getElementById('geminiModal').classList.add('hidden');
        }

        function triggerAiWithQuery(query) {
            openGeminiModal();
            document.getElementById('aiUserInput').value = query;
            sendAiMessage();
        }

        function sendQuickPrompt(prompt) {
            document.getElementById('aiUserInput').value = prompt;
            sendAiMessage();
        }

        async function sendAiMessage() {
            const input = document.getElementById('aiUserInput');
            const box = document.getElementById('aiChatBox');
            const btn = document.getElementById('aiSendBtn');
            const message = input.value.trim();
            if (!message) return;

            const userBubble = document.createElement('div');
            userBubble.className = 'flex items-start gap-2.5 max-w-[85%] ml-auto justify-end';
            userBubble.innerHTML = `
                <div class="bg-pinkbrand-500 text-white p-3.5 rounded-3xl rounded-tr-none shadow-sm leading-relaxed">
                    ${message.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                </div>
            `;
            box.appendChild(userBubble);
            input.value = '';
            box.scrollTop = box.scrollHeight;

            const loadingBubble = document.createElement('div');
            loadingBubble.className = 'flex items-start gap-2.5 max-w-[85%]';
            loadingBubble.innerHTML = `
                <div class="w-8 h-8 rounded-2xl ai-gradient text-white flex items-center justify-center text-xs shrink-0 shadow-sm">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="bg-white p-3.5 rounded-3xl rounded-tl-none border border-slate-200 text-slate-500 italic text-[11px]">
                    <i class="fa-solid fa-circle-notch fa-spin mr-1.5 text-indigo-500"></i> Gemini AI sedang menganalisis data sewa...
                </div>
            `;
            box.appendChild(loadingBubble);
            box.scrollTop = box.scrollHeight;
            btn.disabled = true;

            try {
                const fd = new FormData();
                fd.append('query', message);
                fd.append('csrf_token', CSRF_TOKEN);

                const res = await fetch(`${BASE_URL}/api/gemini_proxy.php`, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                loadingBubble.remove();

                const aiBubble = document.createElement('div');
                aiBubble.className = 'flex items-start gap-2.5 max-w-[85%]';
                
                let text = data.reply || 'Maaf, tiada maklum balas dijana.';
                text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                text = text.replace(/\n/g, '<br>');

                aiBubble.innerHTML = `
                    <div class="w-8 h-8 rounded-2xl ai-gradient text-white flex items-center justify-center text-xs shrink-0 shadow-sm">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="bg-white p-4 rounded-3xl rounded-tl-none border border-slate-200 shadow-sm text-slate-800 leading-relaxed font-medium">
                        ${text}
                        ${data.source === 'fallback' ? '<div class="mt-2.5 pt-2 border-t border-slate-100 text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1"><i class="fa-solid fa-shield-halved text-emerald-500"></i> Penasihat Tempatan UpsiRent-I</div>' : ''}
                    </div>
                `;
                box.appendChild(aiBubble);
                box.scrollTop = box.scrollHeight;
            } catch (err) {
                console.error(err);
                loadingBubble.remove();
                showToast('Ralat semasa berhubung dengan proksi AI.', 'error');
            } finally {
                btn.disabled = false;
            }
        }
    </script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
