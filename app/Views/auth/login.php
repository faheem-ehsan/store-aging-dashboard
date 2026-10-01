<div class="login-card branded-login">
    <img class="login-logo" src="<?= e(url('/assets/img/qarshi-logo.png')) ?>" alt="Qarshi logo">
    <h1>Stock Aging Dashboard</h1>
    <p>Division-based stock, batch, grading and expiry control.</p>
    <form method="post" action="<?= e(url('/login')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <label>Division
            <select name="division_id" required>
                <option value="super">Super Admin</option>
                <option value="all">All Divisions — Read-only User</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= (int)$d['id'] ?>"><?= e($d['code']) ?> — <?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Email
            <input type="email" name="email" autocomplete="username" required>
        </label>
        <label>Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn primary" type="submit">Login</button>
    </form>
    <div class="login-note">Default setup login: admin@example.com / Admin@123. Change it after first login.</div>
</div>
