<?php
/**
 * Cooperativa FLB — Autenticación y sesiones
 */
require_once __DIR__ . '/db.php';

// ── Iniciar sesión de forma segura ──
function startSession() {
    if (session_status() === PHP_SESSION_ACTIVE) return; // ya está activa
    // Prevenir "headers already sent"
    if (!headers_sent()) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

startSession();

// ── Helpers de usuario ──
function getCurrentUser(): ?array {
    if (!empty($_SESSION['user_id'])) {
        return dbFetch('SELECT * FROM users WHERE id=? AND status="approved"', [$_SESSION['user_id']]);
    }
    return null;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(string $redirect = '/login.php'): array {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireLogin('/login.php');
    if ($user['role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
    return $user;
}

function loginUser(string $email, string $password): array {
    $user = dbFetch('SELECT * FROM users WHERE email=?', [strtolower(trim($email))]);
    if (!$user) return ['ok' => false, 'msg' => 'Email o contraseña incorrectos.'];
    if (!password_verify($password, $user['password']))
        return ['ok' => false, 'msg' => 'Email o contraseña incorrectos.'];
    if ($user['status'] === 'pending')
        return ['ok' => false, 'msg' => 'Tu cuenta está pendiente de aprobación por un administrador.'];
    if ($user['status'] === 'rejected')
        return ['ok' => false, 'msg' => 'Tu registro fue rechazado. Motivo: ' . ($user['rejection_reason'] ?? 'Sin especificar')];

    if (!headers_sent()) session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['theme']   = $user['theme'] ?? 'dark';
    return ['ok' => true, 'user' => $user];
}

function logoutUser(): void {
    startSession();
    $_SESSION = [];
    if (!headers_sent() && ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'],
            $p['secure'] ?? false,
            $p['httponly'] ?? true
        );
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

function csrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrf(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    return !empty($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrfToken() . '">';
}

function adminLog(int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void {
    try {
        dbInsert('admin_logs', [
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'details'     => $details,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {
        // Log silencioso, no interrumpir flujo
        error_log('adminLog error: ' . $e->getMessage());
    }
}

function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $row = dbFetch('SELECT setting_value FROM settings WHERE setting_key=?', [$key]);
            $cache[$key] = $row ? $row['setting_value'] : $default;
        } catch (Exception $e) {
            $cache[$key] = $default;
        }
    }
    return $cache[$key];
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function money(float $amount): string {
    return '$' . number_format($amount, 0, ',', '.');
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}
