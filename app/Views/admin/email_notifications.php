<?php
$settings = $settings ?? [];
$divisions = $divisions ?? [];
$recentAlerts = $recentAlerts ?? [];
$encryption = $settings['smtp_encryption'] ?? 'tls';
$enabled = !empty($settings['is_enabled']);
$includeAdmins = !empty($settings['include_division_admins']);
$testMode = !empty($settings['test_mode']);
$testRecipient = trim((string)($settings['test_recipient'] ?? ($user['email'] ?? '')));
$lastRunAt = !empty($settings['last_run_at']) ? format_app_datetime($settings['last_run_at']) : 'Not run yet';
?>

<div class="grade-email-page">
    <section class="grade-email-intro card">
        <div>
            <span class="admin-hub-kicker">SUPER ADMIN</span>
            <h2>Grade Email Alerts</h2>
            <p>Automatic 1-day advance notification before a batch moves to its next grade.</p>
        </div>
        <div class="grade-email-intro-statuses">
            <?php if ($testMode): ?>
                <div class="grade-email-status is-test"><span></span>Test Mode</div>
            <?php endif; ?>
            <div class="grade-email-status <?= $enabled ? 'is-on' : 'is-off' ?>">
                <span></span><?= $enabled ? 'Enabled' : 'Disabled' ?>
            </div>
        </div>
    </section>

    <form method="post" action="<?= e(url('/admin/email-notifications/save')) ?>">
        <?= csrf_field() ?>

        <section class="card grade-email-section">
            <div class="grade-email-section-head">
                <div>
                    <h3>Notification Rules</h3>
                    <p>Alerts are grouped by Division and sent once per batch/grade transition.</p>
                </div>
            </div>

            <div class="grade-email-rule-row">
                <label class="grade-email-toggle">
                    <input type="checkbox" name="is_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                    <span></span>
                    <b>Enable automatic grade alerts</b>
                </label>
                <div class="grade-email-fixed-rule"><small>Alert Lead</small><strong>1 Day Before</strong></div>
                <div class="grade-email-fixed-rule"><small>Coverage</small><strong>A+ → A → B → C → Expired</strong></div>
            </div>


            <div class="grade-email-test-mode-box <?= $testMode ? 'is-active' : '' ?>">
                <label class="grade-email-toggle">
                    <input type="checkbox" name="test_mode" value="1" <?= $testMode ? 'checked' : '' ?>>
                    <span></span>
                    <b>Test Mode</b>
                </label>
                <div class="grade-email-test-mode-copy">
                    <strong>Safe testing</strong>
                    <small>When enabled, all automatic grade alerts go only to the Test Recipient. Division Heads and Value Chain / Supply Marketing recipients are not emailed.</small>
                </div>
                <label class="grade-email-test-recipient">Test Recipient
                    <input type="email" name="test_recipient" value="<?= e($testRecipient) ?>" placeholder="your.email@qarshi.com" <?= $testMode ? 'required' : '' ?>>
                </label>
            </div>
        </section>

        <section class="card grade-email-section">
            <div class="grade-email-section-head">
                <div>
                    <h3>Email Server (SMTP)</h3>
                    <p>SMTP credentials are stored encrypted. Leave Password blank to keep the saved password.</p>
                </div>
                <span class="grade-email-password-state"><?= !empty($settings['smtp_password_set']) ? 'Password saved' : 'No password saved' ?></span>
            </div>

            <div class="grade-email-smtp-grid">
                <label>SMTP Host
                    <input name="smtp_host" value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.office365.com" required>
                </label>
                <label>Port
                    <input type="number" min="1" max="65535" name="smtp_port" value="<?= e((string)($settings['smtp_port'] ?? 587)) ?>" required>
                </label>
                <label>Encryption
                    <select name="smtp_encryption">
                        <option value="tls" <?= $encryption === 'tls' ? 'selected' : '' ?>>STARTTLS / TLS</option>
                        <option value="ssl" <?= $encryption === 'ssl' ? 'selected' : '' ?>>SSL / SMTPS</option>
                        <option value="none" <?= $encryption === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                </label>
                <label>SMTP Username
                    <input name="smtp_username" value="<?= e($settings['smtp_username'] ?? '') ?>" autocomplete="off">
                </label>
                <label>SMTP Password
                    <input type="password" name="smtp_password" value="" autocomplete="new-password" placeholder="<?= !empty($settings['smtp_password_set']) ? 'Saved — leave blank to keep' : 'Enter SMTP password' ?>">
                </label>
                <label>From Email
                    <input type="email" name="from_email" value="<?= e($settings['from_email'] ?? '') ?>" placeholder="stockaging@qarshi.com" required>
                </label>
                <label class="grade-email-span-2">From Name
                    <input name="from_name" value="<?= e($settings['from_name'] ?? 'Qarshi Stock Aging Dashboard') ?>" required>
                </label>
            </div>
        </section>

        <section class="card grade-email-section">
            <div class="grade-email-section-head">
                <div>
                    <h3>Recipients</h3>
                    <p>Division Heads receive only their Division alerts. Global recipients receive alerts for every Division.</p>
                </div>
            </div>

            <label class="grade-email-toggle grade-email-admin-toggle">
                <input type="checkbox" name="include_division_admins" value="1" <?= $includeAdmins ? 'checked' : '' ?>>
                <span></span>
                <b>Automatically include active Division Admin users</b>
            </label>

            <div class="grade-email-global-recipient">
                <label>Value Chain / Supply Marketing Head Email(s)
                    <textarea name="global_recipients" rows="2" placeholder="head1@qarshi.com, head2@qarshi.com"><?= e($settings['global_recipients'] ?? '') ?></textarea>
                    <small>Separate multiple addresses with comma, semicolon or a new line.</small>
                </label>
            </div>

            <div class="grade-email-division-table-wrap">
                <table class="grade-email-division-table">
                    <thead><tr><th>Division</th><th>Division Head Email(s)</th><th>Active Division Admin Email(s)</th></tr></thead>
                    <tbody>
                    <?php foreach ($divisions as $division): ?>
                        <tr>
                            <td><b><?= e($division['code']) ?></b><span><?= e($division['name']) ?></span></td>
                            <td>
                                <textarea name="division_recipients[<?= (int)$division['id'] ?>]" rows="2" placeholder="division.head@qarshi.com"><?= e($division['recipient_emails']) ?></textarea>
                            </td>
                            <td class="grade-email-admin-emails"><?= e($division['division_admin_emails'] ?: 'None') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grade-email-save-row">
            <a class="btn ghost" href="<?= e(url('/admin')) ?>">Back to Administration</a>
            <button class="btn primary" type="submit">Save Email Settings</button>
        </div>
    </form>

    <section class="card grade-email-section">
        <div class="grade-email-section-head">
            <div>
                <h3>Test & Automation</h3>
                <p>Save settings first, then test SMTP or run today's grade check.</p>
            </div>
            <div class="grade-email-last-run"><small>Last Scheduler Run</small><strong><?= e($lastRunAt) ?></strong><span><?= e($settings['last_run_summary'] ?? '') ?></span></div>
        </div>

        <div class="grade-email-actions-grid grade-email-actions-grid-3">
            <form method="post" action="<?= e(url('/admin/email-notifications/test')) ?>" class="grade-email-action-card">
                <?= csrf_field() ?>
                <div><strong>SMTP Test</strong><span>Verifies server login and delivery.</span></div>
                <input type="email" name="test_email" value="<?= e($testRecipient) ?>" required>
                <button class="btn ghost" type="submit">Send SMTP Test</button>
            </form>

            <form method="post" action="<?= e(url('/admin/email-notifications/sample')) ?>" class="grade-email-action-card">
                <?= csrf_field() ?>
                <div><strong>Sample Grade Alert</strong><span>Sends the real grade-alert layout with sample data.</span></div>
                <input type="email" name="test_email" value="<?= e($testRecipient) ?>" required>
                <button class="btn ghost" type="submit">Send Sample Alert</button>
            </form>

            <form method="post" action="<?= e(url('/admin/email-notifications/run')) ?>" class="grade-email-action-card">
                <?= csrf_field() ?>
                <div><strong>Run Grade Check Now</strong><span><?= $testMode ? 'Test Mode: due alerts go only to the Test Recipient.' : 'Checks today’s 1-day advance transitions and sends due alerts.' ?></span></div>
                <button class="btn ghost" type="submit">Run Now</button>
            </form>
        </div>

        <div class="grade-email-scheduler-box">
            <div>
                <strong>Automatic Daily Schedule</strong>
                <span>Run the scheduler once per day with Windows Task Scheduler or cron. Recommended: 08:00 AM.</span>
            </div>
            <code><?= e($schedulerCommand ?? '') ?></code>
            <small>Windows helper included: <b>bin/install_grade_email_task.bat</b></small>
        </div>
    </section>

    <section class="card grade-email-section">
        <div class="grade-email-section-head"><div><h3>Recent Alert History</h3><p>Successful and failed batch notifications.</p></div></div>
        <div class="table-wrap">
            <table class="aging-table grade-email-history">
                <thead><tr><th>Date</th><th>Division</th><th>Product</th><th>Batch</th><th>Grade Change</th><th>Change Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$recentAlerts): ?><tr><td colspan="7" class="empty">No grade alert emails have been processed yet.</td></tr><?php endif; ?>
                <?php foreach ($recentAlerts as $alert): ?>
                    <tr title="<?= e($alert['error_message'] ?? '') ?>">
                        <td><?= e(format_app_datetime($alert['sent_at'] ?: $alert['updated_at'])) ?></td>
                        <td><?= e($alert['division_code'] ?? '—') ?></td>
                        <td><b><?= e($alert['product_code'] ?? '') ?></b><br><small><?= e($alert['product_name'] ?? '') ?></small></td>
                        <td><?= e($alert['batch_no'] ?? '') ?></td>
                        <td><?= e(($alert['current_grade'] ?? '') . ' → ' . ($alert['next_grade'] ?? '')) ?></td>
                        <td><?= e(format_app_date($alert['transition_date'] ?? '', '—')) ?></td>
                        <td><span class="grade-email-log-status <?= ($alert['status'] ?? '') === 'sent' ? 'sent' : 'failed' ?>"><?= e(ucfirst($alert['status'] ?? '')) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
