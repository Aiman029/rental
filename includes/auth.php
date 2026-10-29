<?php
// Authentication & RBAC Helper Library

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/csrf.php';

// Safe session initialization
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Inactivity timeout check (UC002 / UC003)
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME_SECONDS)) {
        logoutUser();
        header('Location: ' . BASE_URL . '/public/login.php?error=timeout');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Check if current user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Retrieve current authenticated user record with updated database status
 */
function currentUser(): ?array {
    static $cachedUser = null;

    if ($cachedUser !== null) {
        return $cachedUser;
    }

    if (!isLoggedIn()) {
        return null;
    }

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT user_id, name, email, phone, role, verification_status, is_active, created_at FROM users WHERE user_id = :id AND is_active = 1 LIMIT 1");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            // User deleted or suspended
            logoutUser();
            return null;
        }

        $cachedUser = $user;
        return $cachedUser;
    } catch (PDOException $e) {
        error_log('Auth currentUser error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Check if the user has a specific role or belongs to allowed roles
 */
function hasRole(string|array $roles): bool {
    $user = currentUser();
    if (!$user) {
        return false;
    }

    if (is_array($roles)) {
        return in_array($user['role'], $roles, true);
    }

    return $user['role'] === $roles;
}

/**
 * Check if current user is a student with verified status (eligible for 10% discount)
 */
function isVerifiedStudent(): bool {
    $user = currentUser();
    if (!$user) {
        return false;
    }
    return $user['role'] === 'student' && $user['verification_status'] === 'verified';
}

/**
 * Require authentication, redirect to login page if unauthenticated
 */
function requireAuth(?string $redirectUrl = null): void {
    if (!isLoggedIn() || !currentUser()) {
        $target = $redirectUrl ?? (BASE_URL . '/public/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        header('Location: ' . $target);
        exit;
    }
}

/**
 * Require specific role or list of roles, die with 403 or redirect
 */
function requireRole(string|array $allowedRoles, ?string $redirectUrl = null): void {
    requireAuth();

    if (!hasRole($allowedRoles)) {
        http_response_code(403);
        if ($redirectUrl) {
            header('Location: ' . $redirectUrl);
            exit;
        }
        die('Akses Ditolak: Anda tidak mempunyai kebenaran (RBAC) untuk mengakses halaman ini.');
    }
}

/**
 * Login user into session with session regeneration
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['last_activity'] = time();
    // Refresh CSRF token for fresh session
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Safely logout user and destroy session
 */
function logoutUser(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    @session_destroy();
}
