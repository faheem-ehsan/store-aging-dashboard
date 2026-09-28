<?php $me = auth_user(); $appName = app_config('app')['name'] ?? 'Stock Aging Dashboard'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? $appName) ?> - <?= e($appName) ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body>
<?php if ($me): ?>
<?php
$nameParts = preg_split('/\s+/', trim((string)$me['name'])) ?: [];
$avatarInitials = '';
foreach (array_slice($nameParts, 0, 2) as $part) {
    if ($part !== '') $avatarInitials .= strtoupper(substr($part, 0, 1));
}
if ($avatarInitials === '') $avatarInitials = 'U';

// Keep the correct sidebar section highlighted on every page inside that module.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$normalizePath = static function (string $path): string {
    $path = rtrim($path, '/');
    return $path === '' ? '/' : $path;
};
$requestPath = $normalizePath($requestPath);
$dashboardPath = $normalizePath(url('/'));
$isDashboardActive = $requestPath === $dashboardPath;
$isStocksActive = str_starts_with($requestPath, $normalizePath(url('/stocks')));
$isMarketReturnActive = str_starts_with($requestPath, $normalizePath(url('/market-return')));
$isUsersActive = str_starts_with($requestPath, $normalizePath(url('/users')));
$isDivisionsActive = str_starts_with($requestPath, $normalizePath(url('/divisions')));
$isGradesActive = str_starts_with($requestPath, $normalizePath(url('/grade-criteria')));
$isLogsActive = str_starts_with($requestPath, $normalizePath(url('/logs')));
?>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand-block">
            <img class="brand-logo" src="<?= e(url('/assets/img/qarshi-logo.png')) ?>" alt="Qarshi logo">
            <div class="brand-text single-line">
                <span>Stock Aging Dashboard</span>
            </div>
        </div>
        <div class="division-chip"><?= e($me['role'] === 'super_admin' ? 'SUPER ADMIN' : ($me['division_code'] ?? 'DIVISION')) ?></div>
        <nav>
            <a class="nav-link<?= $isDashboardActive ? ' active' : '' ?>" href="<?= e(url('/')) ?>"<?= $isDashboardActive ? ' aria-current="page"' : '' ?>>
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-4.5v-6h-5v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a class="nav-link<?= $isStocksActive ? ' active' : '' ?>" href="<?= e(url('/stocks')) ?>"<?= $isStocksActive ? ' aria-current="page"' : '' ?>>
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 7.5 12 4l7 3.5-7 3.5-7-3.5Zm0 0V16.5L12 20l7-3.5V7.5M5 12l7 3.5 7-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="nav-label">Stock Aging Dashboard</span>
            </a>
            <a class="nav-link<?= $isMarketReturnActive ? ' active' : '' ?>" href="<?= e(url('/market-return')) ?>"<?= $isMarketReturnActive ? ' aria-current="page"' : '' ?>>
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M7 7h10M7 12h10M7 17h6M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="nav-label">Market Return Store</span>
            </a>
            <?php if ($me['role'] === 'super_admin'): ?>
                <a class="nav-link<?= $isUsersActive ? ' active' : '' ?>" href="<?= e(url('/users')) ?>"<?= $isUsersActive ? ' aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M16 19a4 4 0 0 0-8 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 6a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75M5 19a4 4 0 0 1 3-3.87" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="nav-label">Users</span>
                </a>
                <a class="nav-link<?= $isDivisionsActive ? ' active' : '' ?>" href="<?= e(url('/divisions')) ?>"<?= $isDivisionsActive ? ' aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 6a2 2 0 0 1 2-2h4v6H4V6Zm10-2h4a2 2 0 0 1 2 2v4h-6V4ZM4 14h6v6H6a2 2 0 0 1-2-2v-4Zm10 0h6v4a2 2 0 0 1-2 2h-4v-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="nav-label">Divisions</span>
                </a>
                <a class="nav-link<?= $isGradesActive ? ' active' : '' ?>" href="<?= e(url('/grade-criteria')) ?>"<?= $isGradesActive ? ' aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 6v6l4 2M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="nav-label">Grading Criteria</span>
                </a>
                <a class="nav-link<?= $isLogsActive ? ' active' : '' ?>" href="<?= e(url('/logs')) ?>"<?= $isLogsActive ? ' aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M8 7h8M8 11h8M8 15h5M6 4h12a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="nav-label">System Logs</span>
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-account-zone">
            <div class="sidebar-user-profile" title="<?= e($me['name']) ?>">
                <div class="sidebar-user-avatar">
                    <?php if (!empty($me['avatar_path'])): ?>
                        <img src="<?= e(url($me['avatar_path'])) ?>" alt="<?= e($me['name']) ?> profile picture">
                    <?php else: ?>
                        <span><?= e($avatarInitials) ?></span>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-copy">
                    <strong><?= e($me['name']) ?></strong>
                    <small><?= e(ucwords(str_replace('_',' ', $me['role']))) ?></small>
                    <span><?= e(date('d M Y')) ?></span>
                </div>
            </div>
            <form class="logout" method="post" action="<?= e(url('/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="link-button"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M15 17l5-5-5-5M20 12H9M11 20H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="nav-label">Sign out</span></button>
            </form>
        </div>
    </aside>
    <main class="main <?= (($title ?? '') === 'Dashboard') ? 'dashboard-main' : '' ?>">
        <?php if (($title ?? '') !== 'Dashboard'): ?>
        <header class="topbar">
            <div>
                <div class="page-title-row">
                    <h1><?= e($title ?? '') ?></h1>
                </div>
            </div>
        </header>
        <?php endif; ?>
        <?php if ($m = flash('success')): ?><div class="flash success"><?= e($m) ?></div><?php endif; ?>
        <?php if ($m = flash('error')): ?><div class="flash error"><?= e($m) ?></div><?php endif; ?>
<?php else: ?>
<div class="guest-shell">
    <?php if ($m = flash('error')): ?><div class="flash error login-flash"><?= e($m) ?></div><?php endif; ?>
<?php endif; ?>
