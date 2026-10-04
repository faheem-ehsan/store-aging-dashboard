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

/**
 * Project-wide human date format: DD/MON/YYYY, e.g. 01/Sep/2026.
 * SQL DATE columns remain native YYYY-MM-DD internally.
 */
function parse_app_date(mixed $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') return null;

    // Canonical project format:
    //   18/Sep/2026
    //
    // Excel often rewrites dates when a CSV is opened/saved, for example:
    //   18-Sep-26
    //   18-Sep-2026
    //   18/Sep/26
    //
    // Accept those Excel-exported forms as INPUT only, then normalize them
    // immediately to SQL YYYY-MM-DD. All application displays still use
    // DD/MON/YYYY through format_app_date().
    if (!preg_match('/^(\d{1,2})[\/\-]([A-Za-z]{3})[\/\-](\d{2}|\d{4})$/', $value, $m)) {
        return null;
    }

    $months = [
        'jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,
        'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12,
    ];

    $month = $months[strtolower($m[2])] ?? null;
    if (!$month) return null;

    $day = (int)$m[1];
    $yearText = $m[3];

    // In this stock-aging system, two-digit Excel years are interpreted
    // as 20xx. Example: 18-Sep-26 => 2026-09-18.
    $year = strlen($yearText) === 2
        ? 2000 + (int)$yearText
        : (int)$yearText;

    if (!checkdate($month, $day, $year)) return null;

    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

function format_app_date(mixed $value, string $fallback = ''): string
{
    $value = trim((string)$value);
    if ($value === '') return $fallback;
    $sql = parse_app_date($value);
    if ($sql !== null) $value = $sql;
    try {
        return (new \DateTimeImmutable($value))->format('d/M/Y');
    } catch (\Throwable $e) {
        return $fallback !== '' ? $fallback : $value;
    }
}

function format_app_datetime(mixed $value, string $fallback = ''): string
{
    $value = trim((string)$value);
    if ($value === '') return $fallback;
    try {
        return (new \DateTimeImmutable($value))->format('d/M/Y h:i:s A');
    } catch (\Throwable $e) {
        return $fallback !== '' ? $fallback : $value;
    }
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
