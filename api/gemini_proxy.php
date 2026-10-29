<?php
// API: Server-Side Gemini AI Chat Proxy with Database Context & Intelligent Local Fallback

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'reply' => 'Kaedah permintaan tidak sah.'], 405);
}

// CSRF check
validate_csrf_or_die();

$query = trim($_POST['query'] ?? '');
if (empty($query)) {
    json_response(['success' => false, 'reply' => 'Sila masukkan soalan atau klausa kontrak anda.'], 400);
}

// 1. Gather active property context from database to enrich AI understanding
$propertySummary = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT property_id, category, title, location, campus, distance, base_price, price_unit, recommended_pax FROM properties WHERE status = 'Approved' LIMIT 10");
    $props = $stmt->fetchAll();
    foreach ($props as $p) {
        $propertySummary[] = sprintf(
            "- [%s] %s (%s, dekat %s, %s): RM%s/%s, muat %d orang.",
            strtoupper($p['category']),
            $p['title'],
            $p['location'],
            $p['campus'],
            $p['distance'],
            number_format($p['base_price'], 0),
            $p['price_unit'],
            $p['recommended_pax']
        );
    }
} catch (Exception $e) {
    error_log('AI Context gather error: ' . $e->getMessage());
}

$contextText = implode("\n", $propertySummary);

// System prompt defining persona and capabilities
$systemInstruction = "Anda ialah Pembantu Maya Pintar UpsiRent-I (Pakar Kediaman & Kontrak Sewaan Pelajar Universiti Pendidikan Sultan Idris - UPSI di Tanjung Malim, Proton City, dan Behrang).
Tugas anda:
1. Menasihati pelajar mengenai rumah sewa yang bersesuaian dengan bajet dan kampus mereka (KSAJS di Pekan Tanjung Malim atau KSAS di Proton City).
2. Mengesan 'red flags' (klausa bahaya/meragukan/scam) dalam draf perjanjian sewa (cth: tiada resit rasmi, deposit tidak munasabah lebih 2 bulan, tuan rumah enggan baiki kerosakan struktur, penahanan deposit sewenang-wenangnya).
3. Mengira dan menganggarkan kos sara hidup bulanan pelajar UPSI (makan minum RM15-20 sehari, tambang bas shuttle percuma jika ada kad matrik, bil utiliti kongsi RM30-50).
4. Menjelaskan diskaun 10% pelajar sah kad matrik UPSI.
Senarai kediaman sedia ada di pangkalan data UpsiRent-I:
" . ($contextText ?: "Tiada maklumat kediaman.") . "

Gaya bahasa: Sopan, mesra pelajar Malaysia, berfakta, padat, dan gunakan format markdown (bold, bullet points) yang kemas.";

// 2. Check if Gemini API Key is configured and attempt API call
$apiKey = GEMINI_API_KEY;
$model = GEMINI_MODEL ?: 'gemini-1.5-flash';
$aiResponse = null;
$source = 'fallback';

if (!empty($apiKey)) {
    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemInstruction . "\n\nSoalan Pengguna: " . $query]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.6,
            'maxOutputTokens' => 800
        ]
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 6,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $rawResult = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$curlErr && $httpCode === 200) {
        $jsonResult = json_decode($rawResult, true);
        if (!empty($jsonResult['candidates'][0]['content']['parts'][0]['text'])) {
            $aiResponse = $jsonResult['candidates'][0]['content']['parts'][0]['text'];
            $source = 'gemini-api';
        }
    } else {
        error_log("Gemini API call failed (HTTP $httpCode): " . ($curlErr ?: $rawResult));
    }
}

// 3. Fallback Engine: If API call fails or no key provided, run intelligent local rule-based advice
if ($aiResponse === null) {
    $aiResponse = generateLocalAiResponse($query, $propertySummary);
    $source = 'fallback';
}

json_response([
    'success' => true,
    'reply' => $aiResponse,
    'source' => $source
]);

/**
 * Intelligent Local Rule-Based Fallback Engine
 * Specifically handles UPSI student questions, contract red flags, and budget estimation
 */
function generateLocalAiResponse(string $query, array $properties): string {
    $q = mb_strtolower($query);

    // Scenario A: Contract screening / Red flags / Scam
    if (str_contains($q, 'kontrak') || str_contains($q, 'klausa') || str_contains($q, 'scam') || str_contains($q, 'red flag') || str_contains($q, 'deposit') || str_contains($q, 'perjanjian')) {
        return "🔍 **Analisis Keselamatan Kontrak Sewaan (Anti-Scam):**\n\n" .
               "Berdasarkan garis panduan sewaan pelajar UPSI, sila perhatikan **Red Flags** berikut:\n" .
               "1. **Tuntutan Bayaran Sebelum Lawatan Fizikal:** Jangan sekali-kali bayar deposit penuh ('booking fee') tanpa melawat rumah atau menerima pengesahan geran.\n" .
               "2. **Kadar Deposit Berlebihan:** Amalan standard di Tanjung Malim ialah **1 atau 2 bulan sewa + separuh bulan utiliti**. Elakkan kontrak yang meminta deposit lebih 3 bulan tanpa justifikasi.\n" .
               "3. **Tanggungjawab Pembaikan Kerosakan:** Pastikan kontrak menyatakan **tuan rumah bertanggungjawab** ke atas kerosakan struktur (bumbung bocor, paip utama, pendawaian) manakala penyewa hanya menanggung barang pakai habis.\n" .
               "4. **Klausa Pemulangan Deposit:** Mesti ada klausa jelas bahawa deposit dipulangkan dalam tempoh maksimum **14 hingga 30 hari** selepas serahan kunci.\n" .
               "5. **Resit & Tandatangan Bertulis:** Pastikan setiap bayaran mempunyai resit rasmi dan perjanjian disetem (LHDN) untuk keselamatan undang-undang.";
    }

    // Scenario B: Cost of living / Budget estimation
    if (str_contains($q, 'kos') || str_contains($q, 'bajet') || str_contains($q, 'hidup') || str_contains($q, 'anggaran') || str_contains($q, 'makan')) {
        return "💰 **Anggaran Kos Sara Hidup Bulanan Pelajar UPSI (Tanjung Malim):**\n\n" .
               "Berikut adalah pecahan bajet realistik untuk seorang pelajar di luar kampus:\n\n" .
               "• **Sewa Bilik (Kongsi 2-3 orang):** RM 160 – RM 220 sebulan *(Bilik Single: RM 300 - RM 400)*.\n" .
               "• **Utiliti (WiFi, TNB, LAP Air):** RM 30 – RM 50 sebulan (dikongsi).\n" .
               "• **Makanan & Minuman:** RM 15 – RM 20 sehari (Kafeteria & medan selera Taman Bahtera) = ~RM 450 – RM 600 sebulan.\n" .
               "• **Pengangkutan:** **PERCUMA** menggunakan Bas Shuttle UPSI (KSAJS ⟷ KSAS Proton City) jika memiliki Kad Matrik, atau RM 40 sebulan untuk petrol motosikal.\n" .
               "• **Keperluan Akademik & Fotostat:** RM 30 – RM 50 sebulan.\n\n" .
               "💡 **Jumlah Anggaran:** Sekitar **RM 680 – RM 950 sebulan**. Dapatkan diskaun 10% untuk sewa di UpsiRent-I dengan mengesahkan kad matrik anda!";
    }

    // Scenario C: Location advice (KSAJS vs KSAS / Proton City vs Taman Bahtera)
    if (str_contains($q, 'ksas') || str_contains($q, 'proton city') || str_contains($q, 'bahtera') || str_contains($q, 'ksajs') || str_contains($q, 'kampus')) {
        return "📍 **Panduan Memilih Lokasi Rumah Sewa Mengikut Kampus UPSI:**\n\n" .
               "• **Kampus Sultan Abdul Jalil Shah (KSAJS - Kampus Induk):**\n" .
               "  - **Taman Bahtera & Taman Universiti:** Pilihan terbaik kerana berada dalam jarak berjalan kaki (5–10 minit) ke pintu gerbang UPSI. Mudah akses ke kedai makan 24 jam dan Stesen KTM.\n\n" .
               "• **Kampus Sultan Azlan Shah (KSAS - Proton City):**\n" .
               "  - **Proton City:** Terletak bersebelahan fakulti sukan, sains, muzik, dan dewan konvokesyen. Suasana damai, moden, dan dilalui bas shuttle percuma setiap 20 minit.\n" .
               "  - **Bandar Behrang 2020:** Sesuai untuk pelajar yang mempunyai kenderaan sendiri atau berkongsi kereta, menawarkan harga sewa rumah semi-D yang lebih luas dan murah.";
    }

    // Scenario D: Recommend house / General inquiry
    $recList = !empty($properties) ? implode("\n", array_slice($properties, 0, 3)) : "- Rumah Teres Taman Bahtera (RM 1,200/bln, KSAJS)\n- Rumah Teres Proton City (RM 950/bln, KSAS)";

    return "👋 **Saranan Kediaman UpsiRent-I:**\n\n" .
           "Terima kasih atas pertanyaan anda! Berikut antara pilihan kediaman aktif yang disahkan oleh pentadbir kami:\n\n" .
           $recList . "\n\n" .
           "✨ **Tips Berguna:** Anda boleh menggunakan fungsi **'Lihat Bilik Demi Bilik'** untuk melihat foto bilik sebenar, atau gunakan **'Kalkulator Sewa'** kami untuk mengira bahagian bayaran setiap orang.";
}
