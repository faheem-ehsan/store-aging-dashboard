<?php
use App\Core\Auth;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const APP_PATH = __DIR__;
const ROOT_PATH = __DIR__ . '/..';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

$GLOBALS['app_config'] = require ROOT_PATH . '/config/config.php';
date_default_timezone_set($GLOBALS['app_config']['app']['timezone']);

function app_config(?string $key = null): array
{
    if ($key === null) return $GLOBALS['app_config'];
    return $GLOBALS['app_config'][$key] ?? [];
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = '/'): string
{
    $base = app_config('app')['base_url'] ?? '';
    $path = '/' . ltrim($path, '/');
    return ($base ?: '') . ($path === '//' ? '/' : $path);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF token mismatch.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function auth_user(): ?array
{
    return Auth::user();
}
