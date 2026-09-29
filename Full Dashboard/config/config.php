<?php
$envBaseUrl = getenv('APP_BASE_URL');

// Auto-detect the subfolder for common XAMPP/WAMP installs.
// Examples:
//   /store-aging-dashboard/index.php        -> /store-aging-dashboard
//   /store-aging-dashboard/public/index.php -> /store-aging-dashboard/public
//   /index.php                               -> ''
if ($envBaseUrl !== false && trim($envBaseUrl) !== '') {
    $baseUrl = rtrim(trim($envBaseUrl), '/');
} else {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    $baseUrl = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\')
        ? ''
        : rtrim($scriptDir, '/');
}

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'Stock Aging Dashboard',
        'base_url' => $baseUrl,
        'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Karachi',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'store_aging',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
];
