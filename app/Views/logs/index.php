<?php
$actionLabels = [
    'LOGIN_SUCCESS' => 'Login Success',
    'LOGIN_FAILED' => 'Login Failed',
    'LOGOUT' => 'Logout',
    'BATCH_CREATE' => 'Batch Created',
    'BATCH_UPDATE' => 'Batch Updated',
    'BATCH_DELETE' => 'Batch Deleted',
    'CSV_IMPORT' => 'CSV Import',
    'USER_CREATE' => 'User Created',
    'USER_UPDATE' => 'User Updated',
    'USER_STATUS' => 'User Status Changed',
    'DIVISION_CREATE' => 'Division Created',
    'DIVISION_STATUS' => 'Division Status Changed',
    'GRADE_CRITERIA_UPDATE' => 'Grading Criteria Updated',
];
$actionClass = static function (string $action): string {
    if ($action === 'LOGIN_FAILED' || $action === 'BATCH_DELETE') return 'danger';
    if (str_starts_with($action, 'LOGIN') || $action === 'LOGOUT') return 'auth';
    if (str_starts_with($action, 'BATCH') || $action === 'CSV_IMPORT') return 'stock';
    if (str_starts_with($action, 'USER')) return 'user';
    if (str_starts_with($action, 'DIVISION') || str_starts_with($action, 'GRADE_')) return 'division';
    return 'default';
};
$query = $_GET;
unset($query['page']);
?>

<?php if (!$tableAvailable): ?>
<div class="flash error">System Logs needs the database migration. Import <b>database/migrations/003_add_audit_logs.sql</b> once in phpMyAdmin.</div>
<?php endif; ?>

<div class="log-metrics">
    <div class="metric"><span>Events Today</span><strong><?= number_format($stats['today']) ?></strong><small>all logged activity</small></div>
    <div class="metric warning"><span>Failed Logins</span><strong><?= number_format($stats['failed_logins']) ?></strong><small>all-time attempts</small></div>
    <div class="metric safe"><span>Stock Changes</span><strong><?= number_format($stats['stock_changes']) ?></strong><small>create / update / delete</small></div>
    <div class="metric amber"><span>CSV Imports</span><strong><?= number_format($stats['imports']) ?></strong><small>import operations</small></div>
</div>

<div class="card log-filter-card">
    <div class="card-head">
        <div><h2>Audit Trail</h2><p>Super Admin only. Operational activity is recorded with user, date/time and source IP.</p></div>
        <?php if ($tableAvailable): ?><a class="btn" href="<?= e(url('/logs/export?' . http_build_query($query))) ?>">Export CSV</a><?php endif; ?>
    </div>
    <form method="get" action="<?= e(url('/logs')) ?>" class="log-filter-grid">
        <label>Search
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="User, email, description, batch...">
        </label>
        <label>Action
            <select name="action">
                <option value="">All actions</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e($actionLabels[$action] ?? ucwords(strtolower(str_replace('_',' ', $action)))) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>User
            <select name="user_id">
                <option value="">All users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= (int)$filters['user_id'] === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Division
            <select name="division_id">
                <option value="">All divisions</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= (int)$filters['division_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['code']) ?> — <?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>From
            <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
        </label>
        <label>To
            <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
        </label>
        <div class="log-filter-actions">
            <button class="btn primary" type="submit">Filter</button>
            <a class="btn ghost" href="<?= e(url('/logs')) ?>">Reset</a>
        </div>
    </form>
</div>

<div class="card flush log-table-card">
    <div class="card-head padded">
        <div><h2>Activity Log</h2><p><?= number_format($total) ?> matching event<?= $total === 1 ? '' : 's' ?>. Newest activity is shown first.</p></div>
    </div>
    <div class="table-wrap">
        <table class="log-table">
            <thead><tr><th>Date / Time</th><th>User</th><th>Division</th><th>Action</th><th>Description</th><th>IP Address</th><th>Details</th></tr></thead>
            <tbody>
            <?php if (!$logs): ?><tr><td colspan="7" class="empty"><?= $tableAvailable ? 'No log entries match the selected filters.' : 'Run the log migration to start recording system activity.' ?></td></tr><?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <?php $details = !empty($log['details_json']) ? json_decode($log['details_json'], true) : null; ?>
                <tr>
                    <td class="log-time"><strong><?= e(date('d M Y', strtotime($log['created_at']))) ?></strong><small><?= e(date('h:i:s A', strtotime($log['created_at']))) ?></small></td>
                    <td><strong><?= e($log['actor_name'] ?: 'Unknown / Guest') ?></strong><small class="log-subtext"><?= e($log['actor_email'] ?: '—') ?></small></td>
                    <td><?= $log['division_code'] ? '<span class="division-tag">' . e($log['division_code']) . '</span>' : '<span class="subtle">All / N/A</span>' ?></td>
                    <td><span class="log-action <?= e($actionClass($log['action'])) ?>"><?= e($actionLabels[$log['action']] ?? $log['action']) ?></span></td>
                    <td class="log-description"><?= e($log['description']) ?></td>
                    <td><?= e($log['ip_address'] ?: '—') ?></td>
                    <td>
                        <?php if (is_array($details) && $details): ?>
                            <details class="log-details"><summary>View</summary><pre><?= e(json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre></details>
                        <?php else: ?><span class="subtle">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div class="log-pagination">
            <?php if ($page > 1): $prev = $query; $prev['page'] = $page - 1; ?><a class="btn ghost" href="<?= e(url('/logs?' . http_build_query($prev))) ?>">Previous</a><?php endif; ?>
            <span>Page <?= number_format($page) ?> of <?= number_format($pages) ?></span>
            <?php if ($page < $pages): $next = $query; $next['page'] = $page + 1; ?><a class="btn ghost" href="<?= e(url('/logs?' . http_build_query($next))) ?>">Next</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
