<?php
// Main Footer Include for UpsiRent-I
?>
    <!-- FOOTER -->
    <footer class="mt-auto bg-white border-t border-pinkbrand-100 text-slate-600 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 sm:gap-8">
                <!-- Info Kolum 1 -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-xl pink-gradient flex items-center justify-center text-white font-bold text-sm">
                            <i class="fa-solid fa-house-chimney-heart"></i>
                        </div>
                        <span class="text-lg font-extrabold text-pinkbrand-600">UpsiRent-I</span>
                    </div>
                    <p class="text-slate-500 mb-4 max-w-md leading-relaxed text-xs">
                        Platform carian rumah sewa, bilik, homestay, dan rakan sebilik rasmi universiti berkuasa AI Gemini untuk komuniti pelajar UPSI Tanjung Malim, Proton City dan Behrang.
                    </p>
                    <div class="p-3 bg-pinkbrand-50/60 rounded-xl border border-pinkbrand-100 text-[11px] text-slate-600">
                        <i class="fa-solid fa-shield-halved text-pinkbrand-500 mr-1.5"></i>
                        <strong>Pematuhan PDPA 2010:</strong> Semua data peribadi, kad matrik, dan maklumat perhubungan dilindungi di bawah Akta Perlindungan Data Peribadi 2010 Malaysia.
                    </div>
                </div>

                <!-- Panduan & Utiliti -->
                <div>
                    <h4 class="font-extrabold text-slate-800 text-xs uppercase tracking-wider mb-3">Panduan & Bantuan</h4>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <button type="button" onclick="openInfoModal('bus')" class="hover:text-pinkbrand-600 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-bus text-pinkbrand-400"></i> Jadual Bas Shuttle UPSI
                            </button>
                        </li>
                        <li>
                            <button type="button" onclick="openInfoModal('checklist')" class="hover:text-pinkbrand-600 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-file-shield text-pinkbrand-400"></i> Panduan Elak Scam Sewa
                            </button>
                        </li>
                        <li>
                            <button type="button" onclick="openInfoModal('amenities')" class="hover:text-pinkbrand-600 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-map-location-dot text-pinkbrand-400"></i> Kemudahan Sekitar Kampus
                            </button>
                        </li>
                        <li>
                            <button type="button" onclick="openCalculatorModal()" class="hover:text-pinkbrand-600 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-calculator text-pinkbrand-400"></i> Kalkulator Kongsi Sewa
                            </button>
                        </li>
                        <li>
                            <button type="button" onclick="toggleLargeText()" class="hover:text-pinkbrand-600 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-text-height text-pinkbrand-400"></i>
                                <span id="largeTextFooterLabel">Saiz Tulisan (Besar / Biasa)</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Pautan Pantas & Hak Cipta -->
                <div>
                    <h4 class="font-extrabold text-slate-800 text-xs uppercase tracking-wider mb-3">Akaun & Komuniti</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= BASE_URL ?>/public/roommate_hub.php" class="hover:text-pinkbrand-600 transition">Cari Rakan Bilik (Roommate)</a></li>
                        <li><a href="<?= BASE_URL ?>/public/verify_student.php" class="hover:text-pinkbrand-600 transition">Pengesahan Pelajar (Diskaun 10%)</a></li>
                        <li><a href="<?= BASE_URL ?>/public/landlord_dashboard.php" class="hover:text-pinkbrand-600 transition">Portal Tuan Rumah</a></li>
                        <li><a href="<?= BASE_URL ?>/public/wishlist.php" class="hover:text-pinkbrand-600 transition">Rumah Disimpan</a></li>
                    </ul>
                    <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-400">
                        &copy; <?= date('Y') ?> UpsiRent-I. Hak cipta terpelihara.
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- DOCK NAVIGASI BAWAH (TELEFON SAHAJA) -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-pinkbrand-100 shadow-lg px-2 py-1.5 flex justify-around text-[10px] font-bold">
        <a href="<?= BASE_URL ?>/public/index.php?cat=rental" class="flex flex-col items-center p-1 text-slate-600 hover:text-pinkbrand-600">
            <i class="fa-solid fa-user-graduate text-base mb-0.5"></i>
            <span>Sewa</span>
        </a>
        <a href="<?= BASE_URL ?>/public/index.php?cat=homestay" class="flex flex-col items-center p-1 text-slate-600 hover:text-pinkbrand-600">
            <i class="fa-solid fa-suitcase-rolling text-base mb-0.5"></i>
            <span>Homestay</span>
        </a>
        <a href="<?= BASE_URL ?>/public/roommate_hub.php" class="flex flex-col items-center p-1 text-slate-600 hover:text-pinkbrand-600">
            <i class="fa-solid fa-users text-base mb-0.5"></i>
            <span>Roommate</span>
        </a>
        <button onclick="openGeminiModal()" class="flex flex-col items-center p-1 text-indigo-600 font-extrabold">
            <i class="fa-solid fa-wand-magic-sparkles text-base mb-0.5"></i>
            <span>Gemini AI</span>
        </button>
        <a href="<?= BASE_URL ?>/public/wishlist.php" class="flex flex-col items-center p-1 text-slate-600 hover:text-pinkbrand-600">
            <i class="fa-solid fa-heart text-base mb-0.5"></i>
            <span>Disimpan</span>
        </a>
    </div>

    <!-- CORE SCRIPTS & ACCESSIBILITY LOGIC -->
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const CSRF_TOKEN = '<?= csrf_token() ?>';

        // Toast Notification System
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `p-3.5 rounded-2xl shadow-lg text-xs font-bold text-white transition-all transform duration-300 flex items-center gap-2 pointer-events-auto ${
                type === 'success' ? 'bg-emerald-600' :
                type === 'error' ? 'bg-rose-600' :
                type === 'warning' ? 'bg-amber-500' : 'bg-indigo-600'
            }`;
            
            const icon = type === 'success' ? 'fa-circle-check' :
                         type === 'error' ? 'fa-circle-xmark' :
                         type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info';

            toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-4');
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // Accessibility Large Text Mode (FR023)
        function toggleLargeText() {
            const body = document.body;
            const label = document.getElementById('largeTextLabel');
            const footerLabel = document.getElementById('largeTextFooterLabel');
            body.classList.toggle('large-text-mode');
            const isLarge = body.classList.contains('large-text-mode');
            localStorage.setItem('upsirent_large_text', isLarge ? '1' : '0');
            if (label) {
                label.innerText = isLarge ? ' A-' : ' +A';
            }
            if (footerLabel) {
                footerLabel.innerText = isLarge ? 'Saiz Tulisan (Kembali Saiz Biasa)' : 'Saiz Tulisan (Besar / Biasa)';
            }
            showToast(isLarge ? 'Mod Teks Besar diaktifkan (Mesra Pembacaan)' : 'Saiz teks kembali normal', 'info');
        }

        // Restore Large Text preference
        if (localStorage.getItem('upsirent_large_text') === '1') {
            document.body.classList.add('large-text-mode');
            const label = document.getElementById('largeTextLabel');
            const footerLabel = document.getElementById('largeTextFooterLabel');
            if (label) label.innerText = ' A-';
            if (footerLabel) footerLabel.innerText = 'Saiz Tulisan (Kembali Saiz Biasa)';
        }
    </script>
</body>
</html>
