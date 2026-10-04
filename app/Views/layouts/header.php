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
$isAdminHome = $requestPath === $normalizePath(url('/admin'));
$isAdminChild = ($me['role'] ?? '') === 'super_admin' && (
    str_starts_with($requestPath, $normalizePath(url('/users')))
    || str_starts_with($requestPath, $normalizePath(url('/divisions')))
    || str_starts_with($requestPath, $normalizePath(url('/grade-criteria')))
    || str_starts_with($requestPath, $normalizePath(url('/logs')))
    || str_starts_with($requestPath, $normalizePath(url('/market-return/mapping')))
    || str_starts_with($requestPath, $normalizePath(url('/admin/email-notifications')))
);
$isAdminActive = $isAdminHome || $isAdminChild;
$isMarketReturnActive = str_starts_with($requestPath, $normalizePath(url('/market-return'))) && !$isAdminChild;
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
        <?php
        $sidebarDivisionLabel = $me['role'] === 'super_admin'
            ? 'SUPER ADMIN'
            : ((($me['role'] ?? '') === 'user' && empty($me['division_id']))
                ? 'ALL DIVISIONS'
                : ($me['division_code'] ?? 'DIVISION'));
        ?>
        <div class="division-chip"><?= e($sidebarDivisionLabel) ?></div>
        <nav>
            <a class="nav-link<?= $isDashboardActive ? ' active' : '' ?>" href="<?= e(url('/')) ?>"<?= $isDashboardActive ? ' aria-current="page"' : '' ?>>
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-4.5v-6h-5v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a class="nav-link<?= $isStocksActive ? ' active' : '' ?>" href="<?= e(url('/stocks')) ?>"<?= $isStocksActive ? ' aria-current="page"' : '' ?>>
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 7.5 12 4l7 3.5-7 3.5-7-3.5Zm0 0V16.5L12 20l7-3.5V7.5M5 12l7 3.5 7-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="nav-label">Finish Goods Aging</span>
            </a>
            <a class="nav-link<?= $isMarketReturnActive ? ' active' : '' ?>" href="<?= e(url('/market-return')) ?>"<?= $isMarketReturnActive ? ' aria-current="page"' : '' ?>>
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M7 7h10M7 12h10M7 17h6M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="nav-label">Market Returns</span>
            </a>

            <?php if ($me['role'] === 'super_admin'): ?>
                <a class="nav-link<?= $isAdminActive ? ' active' : '' ?>" href="<?= e(url('/admin')) ?>"<?= $isAdminActive ? ' aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3.5 14 5l2.5-.2.8 2.4 2.1 1.4-1 2.3.6 2.4-2.3 1-.9 2.4-2.5-.3-2 1.6-2-1.6-2.5.3-.9-2.4-2.3-1 .6-2.4-1-2.3 2.1-1.4.8-2.4L10 5l2-1.5Zm0 5.5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="nav-label">Administration</span>
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
                    <small><?= e(($me['role'] ?? '') === 'user'
                        ? (empty($me['division_id']) ? 'Read-only User · All Divisions' : 'Read-only User')
                        : ucwords(str_replace('_',' ', (string)$me['role'])) ) ?></small>
                    <span><?= e(date('d/M/Y')) ?></span>
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
                    <?php if (($me['role'] ?? '') === 'super_admin' && $isAdminChild): ?>
                        <a class="admin-back-link" href="<?= e(url('/admin')) ?>">← Administration</a>
                    <?php endif; ?>
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
