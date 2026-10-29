<?php
// General Helper Functions & Formatters

/**
 * HTML Escaper shortcut (Security: XSS Prevention)
 */
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency with RM prefix
 */
function format_rm(float|int|string $amount): string {
    return 'RM ' . number_format((float)$amount, 0, '.', ',');
}

/**
 * Calculate price considering 10% student discount (FR010)
 */
function calculate_price(float|int|string $basePrice, bool $isVerified): array {
    $base = (float)$basePrice;
    if ($isVerified) {
        $discounted = round($base * (1 - (STUDENT_DISCOUNT_PERCENT / 100)), 2);
        return [
            'original' => $base,
            'final' => $discounted,
            'has_discount' => true,
            'discount_amount' => round($base - $discounted, 2),
            'percent' => STUDENT_DISCOUNT_PERCENT
        ];
    }
    return [
        'original' => $base,
        'final' => $base,
        'has_discount' => false,
        'discount_amount' => 0,
        'percent' => 0
    ];
}

/**
 * Flash message handling
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flashes(): array {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Send JSON Response
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Sanitize and normalize Malaysian phone number for WhatsApp
 */
function clean_whatsapp_number(string $phone): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($clean, '0')) {
        $clean = '60' . substr($clean, 1);
    } elseif (!str_starts_with($clean, '60') && strlen($clean) >= 9) {
        $clean = '60' . $clean;
    }
    return $clean;
}
