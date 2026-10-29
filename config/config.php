<?php
// Global Application Configuration

// Load local configuration overrides if available
$localConfigPath = __DIR__ . '/config.local.php';
$localConfig = file_exists($localConfigPath) ? require $localConfigPath : [];

// Database config with fallback defaults
define('DB_HOST', $localConfig['db']['host'] ?? '127.0.0.1');
define('DB_PORT', $localConfig['db']['port'] ?? '3306');
define('DB_NAME', $localConfig['db']['name'] ?? 'upsirent_db');
define('DB_USER', $localConfig['db']['user'] ?? 'root');
define('DB_PASS', $localConfig['db']['pass'] ?? '');
define('DB_CHARSET', $localConfig['db']['charset'] ?? 'utf8mb4');

// Gemini AI config
define('GEMINI_API_KEY', $localConfig['gemini']['api_key'] ?? '');
define('GEMINI_MODEL', $localConfig['gemini']['model'] ?? 'gemini-1.5-flash');

// Application constants
define('APP_NAME', 'UpsiRent-I');
define('APP_VERSION', '1.0.0');
define('STUDENT_DISCOUNT_PERCENT', 10);
define('SESSION_LIFETIME_SECONDS', 1800); // 30 minutes inactivity timeout

// Upload directory paths
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('UPLOAD_VERIFICATIONS_DIR', UPLOAD_DIR . '/verifications');
define('UPLOAD_PROPERTIES_DIR', UPLOAD_DIR . '/properties');

// Dynamic base URL detection for XAMPP
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If running in /public or /public/admin, strip to app root
    $root = preg_replace('#/public(/.*)?$#', '', $scriptDir);
    $root = rtrim($root, '/');
    define('BASE_URL', $root);
}
