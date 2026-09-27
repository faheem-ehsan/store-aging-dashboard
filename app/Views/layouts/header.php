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
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand-block">
            <img class="brand-logo" src="<?= e(url('/assets/img/qarshi-logo.png')) ?>" alt="Qarshi logo">
            <div class="brand-text">
                <div class="brand-title">QARSHI</div>
                <span>Stock Aging Dashboard</span>
            </div>
        </div>
        <div class="division-chip"><?= e($me['role'] === 'super_admin' ? 'SUPER ADMIN' : ($me['division_code'] ?? 'DIVISION')) ?></div>
        <nav>
            <a class="nav-link" href="<?= e(url('/')) ?>">Dashboard</a>
            <a class="nav-link" href="<?= e(url('/stocks')) ?>">Stock Aging Dashboard</a>
            <a class="nav-link" href="<?= e(url('/market-return')) ?>">Market Return Store</a>
            <?php if ($me['role'] === 'super_admin'): ?>
                <a class="nav-link" href="<?= e(url('/users')) ?>">Users</a>
                <a class="nav-link" href="<?= e(url('/divisions')) ?>">Divisions</a>
                <a class="nav-link" href="<?= e(url('/grade-criteria')) ?>">Grading Criteria</a>
                <a class="nav-link" href="<?= e(url('/logs')) ?>">System Logs</a>
            <?php endif; ?>
        </nav>
        <form class="logout" method="post" action="<?= e(url('/logout')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="link-button">Sign out</button>
        </form>
    </aside>
    <main class="main <?= (($title ?? '') === 'Dashboard') ? 'dashboard-main' : '' ?>">
        <?php
        $nameParts = preg_split('/\s+/', trim((string)$me['name'])) ?: [];
        $avatarInitials = '';
        foreach (array_slice($nameParts, 0, 2) as $part) {
            if ($part !== '') $avatarInitials .= strtoupper(substr($part, 0, 1));
        }
        if ($avatarInitials === '') $avatarInitials = 'U';
        ?>
        <?php if (($title ?? '') === 'Dashboard'): ?>
            <div class="dashboard-hero">
                <img src="<?= e(url('/assets/img/dashboard-header-banner.png')) ?>" alt="Dashboard header banner">
            </div>
        <?php endif; ?>
        <header class="topbar">
            <div>
                <div class="page-title-row">
                    <h1><?= e($title ?? '') ?></h1>
                    <?php if (($title ?? '') === 'Dashboard' && !empty($pageDivision)): ?>
                        <span class="dashboard-division-badge" title="Selected division">
                            <span class="dashboard-division-code"><?= e($pageDivision['code']) ?></span>
                            <span class="dashboard-division-separator">—</span>
                            <span class="dashboard-division-name"><?= e($pageDivision['name']) ?></span>
                        </span>
                    <?php endif; ?>
                </div>
                <p><?= e($me['name']) ?> · <?= e(ucwords(str_replace('_',' ', $me['role']))) ?></p>
            </div>
            <div class="topbar-right">
                <div class="dashboard-user" title="<?= e($me['name']) ?>">
                    <?php if (!empty($me['avatar_path'])): ?>
                        <img src="<?= e(url($me['avatar_path'])) ?>" alt="<?= e($me['name']) ?> profile picture">
                    <?php else: ?>
                        <span><?= e($avatarInitials) ?></span>
                    <?php endif; ?>
                </div>
                <div class="today"><?= e(date('d M Y')) ?></div>
            </div>
        </header>
        <?php if ($m = flash('success')): ?><div class="flash success"><?= e($m) ?></div><?php endif; ?>
        <?php if ($m = flash('error')): ?><div class="flash error"><?= e($m) ?></div><?php endif; ?>
<?php else: ?>
<div class="guest-shell">
    <?php if ($m = flash('error')): ?><div class="flash error login-flash"><?= e($m) ?></div><?php endif; ?>
<?php endif; ?>
