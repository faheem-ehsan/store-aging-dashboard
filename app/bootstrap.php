<?php
use App\Core\Auth;

$isWebRequest = PHP_SAPI !== 'cli';
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
    || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
$sessionIdleSeconds = max(5, (int)(getenv('SESSION_IDLE_MINUTES') ?: 30)) * 60;

if ($isWebRequest && session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    // Keep server-side session files at least as long as the idle window.
    ini_set('session.gc_maxlifetime', (string)max(1440, $sessionIdleSeconds + 300));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Expire signed-in sessions after a period of inactivity.
if ($isWebRequest) {
    $now = time();
    if (
        !empty($_SESSION['user_id'])
        && isset($_SESSION['last_activity'])
        && $now - (int)$_SESSION['last_activity'] > $sessionIdleSeconds
    ) {
        // Keep the CSRF token so an in-flight form post reaches the login redirect
        // instead of failing with a CSRF error.
        $csrf = $_SESSION['csrf'] ?? null;
        $_SESSION = [];
        session_regenerate_id(true);
        if ($csrf) $_SESSION['csrf'] = $csrf;
        $_SESSION['flash']['error'] = 'Your session expired after inactivity. Please sign in again.';
    }
    $_SESSION['last_activity'] = $now;
}

// Baseline security headers for every application response.
if ($isWebRequest && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    // Views use inline <script>/<style> and inline event handlers, so 'unsafe-inline'
    // is required for now; everything else is locked to this origin.
    header(
        "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; "
        . "style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; "
        . "connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
    );
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}
unset($isWebRequest, $isHttps, $sessionIdleSeconds, $now, $csrf);

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

/**
 * Resolve an encryption secret. The environment variable wins so production
 * keys can live outside the project; the config file is a local fallback and
 * is git-ignored.
 */
function app_secret(string $envName, string $fallbackFile): string
{
    $fromEnv = getenv($envName);
    if ($fromEnv !== false && trim($fromEnv) !== '') return trim($fromEnv);

    $file = ROOT_PATH . '/config/' . $fallbackFile;
    return is_file($file) ? trim((string)require $file) : '';
}

/**
 * Neutralise spreadsheet formula injection in exported CSV cells.
 * Cells whose first meaningful character is = + - @ are prefixed with a quote,
 * except genuine numbers such as -12.5.
 */
function csv_safe(array $row): array
{
    return array_map(static function (mixed $value): mixed {
        if (!is_string($value) || $value === '') return $value;
        $trimmed = ltrim($value, " \t\r\n");
        if ($trimmed === '' || is_numeric($trimmed)) return $value;
        return in_array($trimmed[0], ['=', '+', '-', '@'], true) ? "'" . $value : $value;
    }, $row);
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
