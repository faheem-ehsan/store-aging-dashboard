<?php
$isSuperAdmin = (($user['role'] ?? '') === 'super_admin');

$qty = static function ($value): string {
    $number = (float)$value;
    $formatted = number_format($number, 3, '.', ',');
    return rtrim(rtrim($formatted, '0'), '.');
};

$money = static function ($value): string {
    $number = (float)$value;
    if (abs($number) >= 1000000) {
        $millions = $number / 1000000;
        $formatted = rtrim(rtrim(number_format($millions, 8, '.', ','), '0'), '.');
        return 'Rs/ ' . $formatted . ' Million';
    }
    $formatted = rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    return 'Rs/ ' . $formatted;
};

$varianceClass = static function ($value): string {
    if ($value === null) return 'neutral';
    $number = (float)$value;
    if ($number > 0) return 'positive';
    if ($number < 0) return 'negative';
    return 'neutral';
};

$signedQty = static function ($value) use ($qty): string {
    if ($value === null) return '—';
    $number = (float)$value;
    $prefix = $number > 0 ? '+' : '';
    return $prefix . $qty($number);
};

$signedMoney = static function ($value) use ($money): string {
    if ($value === null) return '—';
    $number = (float)$value;
    $prefix = $number > 0 ? '+' : ($number < 0 ? '−' : '');
    return $prefix . $money(abs($number));
};

$selectedDivisionLabel = $selectedDivision
    ? (($selectedDivision['code'] ?? '') . ' — ' . ($selectedDivision['name'] ?? ''))
    : 'All Divisions';

$reportStatus = null;
if (($mode ?? '') === 'division' && !empty($report)) {
    $reportStatus = !empty($report['verified_at']) ? 'Verified' : 'Pending Verification';
}

$gradeMeta = [
    'A+' => ['label' => 'A+ Grade', 'class' => 'blue'],
    'A' => ['label' => 'A Grade', 'class' => 'green'],
    'B' => ['label' => 'B Grade', 'class' => 'yellow'],
    'C' => ['label' => 'C Grade', 'class' => 'red'],
    'EX' => ['label' => 'Expired', 'class' => 'black'],
];

$baseQuery = [
    'date' => $reportDate,
    'compare_date' => $compareDate,
];
if (!empty($selectedDivisionId)) $baseQuery['division_id'] = (int)$selectedDivisionId;
?>

<section class="daily-report-filter-card">
    <div class="daily-report-filter-head">
        <div>
            <span class="daily-report-kicker">DAILY CLOSING STOCK</span>
            <h2>Stock Quantity & Value Reconciliation</h2>
            <p>Permanent daily snapshots for stock cross-checking and variance review.</p>
        </div>

        <div class="daily-report-filter-actions">
            <?php if (!empty($canGenerateToday)): ?>
            <form method="post" action="<?= e(url('/daily-stock-reports/generate')) ?>" onsubmit="return confirm('Generate or refresh today\\'s stock snapshot?')">
                <?= csrf_field() ?>
                <input type="hidden" name="report_date" value="<?= e($reportDate) ?>">
                <input type="hidden" name="compare_date" value="<?= e($compareDate) ?>">
                <input type="hidden" name="division_id" value="<?= (int)($selectedDivisionId ?? 0) ?>">
                <button class="btn primary" type="submit">
                    <?= (($mode ?? '') === 'all') ? 'Generate All Divisions' : (!empty($report) ? 'Refresh Today' : 'Generate Today') ?>
                </button>
            </form>
            <?php endif; ?>

            <a class="btn ghost" href="<?= e(url('/daily-stock-reports/export?' . http_build_query([
                'date' => $reportDate,
                'division_id' => (int)($selectedDivisionId ?? 0),
            ]))) ?>">Export CSV</a>

            <button class="btn ghost" type="button" onclick="window.print()">Print / PDF</button>
        </div>
    </div>

    <form method="get" action="<?= e(url('/daily-stock-reports')) ?>" class="daily-report-filters" id="dailyReportFilterForm">
        <label>
            <span>Report Date</span>
            <input type="text" id="dailyReportDateDisplay" value="<?= e(format_app_date($reportDate)) ?>" placeholder="DD/MON/YYYY" autocomplete="off">
            <input type="hidden" name="date" id="dailyReportDateSql" value="<?= e($reportDate) ?>">
        </label>

        <?php if ($isSuperAdmin): ?>
        <label>
            <span>Division</span>
            <select name="division_id" id="dailyReportDivision">
                <option value="">All Divisions</option>
                <?php foreach ($divisions as $division): ?>
                    <option value="<?= (int)$division['id'] ?>" <?= ((int)($selectedDivisionId ?? 0) === (int)$division['id']) ? 'selected' : '' ?>>
                        <?= e($division['code'] . ' — ' . $division['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php else: ?>
        <label>
            <span>Division</span>
            <input type="text" value="<?= e($selectedDivisionLabel) ?>" readonly>
        </label>
        <input type="hidden" name="division_id" value="<?= (int)$selectedDivisionId ?>">
        <?php endif; ?>

        <label>
            <span>Compare With</span>
            <input type="text" id="dailyCompareDateDisplay" value="<?= e(format_app_date($compareDate)) ?>" placeholder="DD/MON/YYYY" autocomplete="off">
            <input type="hidden" name="compare_date" id="dailyCompareDateSql" value="<?= e($compareDate) ?>">
        </label>

        <div class="daily-report-view-actions">
            <button class="btn primary" type="submit">View Report</button>
            <a class="btn ghost" href="<?= e(url('/daily-stock-reports')) ?>">Today</a>
        </div>
    </form>

    <div class="daily-report-context-strip">
        <span><small>DATE</small><b><?= e(format_app_date($reportDate)) ?></b></span>
        <span><small>DIVISION</small><b><?= e($selectedDivisionLabel) ?></b></span>
        <span><small>COMPARE</small><b><?= e(format_app_date($compareDate)) ?></b></span>
        <?php if ($reportStatus !== null): ?>
            <span class="report-status-context <?= !empty($report['verified_at']) ? 'verified' : 'pending' ?>">
                <small>STATUS</small><b><?= e($reportStatus) ?></b>
            </span>
        <?php endif; ?>
    </div>
</section>

<section class="daily-report-summary-grid">
    <?php if (($mode ?? '') === 'all'): ?>
        <article class="daily-summary-card">
            <span>Division Reports</span>
            <strong><?= number_format((int)($summary['available_reports'] ?? 0)) ?> / <?= number_format((int)($summary['divisions'] ?? 0)) ?></strong>
            <small>Snapshots available for selected date</small>
        </article>
    <?php else: ?>
        <article class="daily-summary-card">
            <span>Total Batches</span>
            <strong><?= number_format((int)($summary['total_batches'] ?? 0)) ?></strong>
            <small>Saved in this daily snapshot</small>
        </article>
    <?php endif; ?>

    <article class="daily-summary-card">
        <span>Total Stock Quantity</span>
        <strong><?= e($qty($summary['total_quantity'] ?? 0)) ?></strong>
        <small>Closing quantity for selected scope</small>
    </article>

    <article class="daily-summary-card">
        <span>Total Stock Retail Value</span>
        <strong><?= e($money($summary['total_stock_value'] ?? 0)) ?></strong>
        <small>Closing stock value for selected scope</small>
    </article>

    <article class="daily-summary-card variance <?= e($varianceClass($summary['qty_variance'] ?? null)) ?>">
        <span>Quantity Variance</span>
        <strong><?= e($signedQty($summary['qty_variance'] ?? null)) ?></strong>
        <small>Compared with <?= e(format_app_date($compareDate)) ?></small>
    </article>

    <article class="daily-summary-card variance <?= e($varianceClass($summary['value_variance'] ?? null)) ?>">
        <span>Value Variance</span>
        <strong><?= e($signedMoney($summary['value_variance'] ?? null)) ?></strong>
        <small>Compared with <?= e(format_app_date($compareDate)) ?></small>
    </article>
</section>

<?php if (($mode ?? '') === 'all'): ?>

<section class="card daily-report-section">
    <div class="daily-report-section-head">
        <div>
            <h3>Division Daily Summary</h3>
            <p>One closing snapshot per division and date. Missing snapshots are clearly identified.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="aging-table daily-report-table">
            <thead>
            <tr>
                <th>Division</th>
                <th>Status</th>
                <th>Batches</th>
                <th>Quantity</th>
                <th>Stock Retail Value</th>
                <th>Qty Variance</th>
                <th>Value Variance</th>
                <th>Verified By</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($divisionRows as $row): ?>
                <?php $r = $row['report']; ?>
                <tr>
                    <td>
                        <strong><?= e($row['division']['code']) ?></strong>
                        <small class="daily-table-sub"><?= e($row['division']['name']) ?></small>
                    </td>
                    <td>
                        <?php if (!$r): ?>
                            <span class="daily-status missing">Missing</span>
                        <?php elseif (!empty($r['verified_at'])): ?>
                            <span class="daily-status verified">Verified</span>
                        <?php else: ?>
                            <span class="daily-status pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $r ? number_format((int)$r['total_batches']) : '—' ?></td>
                    <td><?= $r ? e($qty($r['total_quantity'])) : '—' ?></td>
                    <td><?= $r ? e($money($r['total_stock_value'])) : '—' ?></td>
                    <td class="daily-variance <?= e($varianceClass($row['qty_variance'])) ?>"><?= e($signedQty($row['qty_variance'])) ?></td>
                    <td class="daily-variance <?= e($varianceClass($row['value_variance'])) ?>"><?= e($signedMoney($row['value_variance'])) ?></td>
                    <td>
                        <?php if ($r && !empty($r['verified_at'])): ?>
                            <strong><?= e($r['verified_by_name'] ?? 'User') ?></strong>
                            <small class="daily-table-sub"><?= e(format_app_datetime($r['verified_at'])) ?></small>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn tiny" href="<?= e(url('/daily-stock-reports?' . http_build_query([
                            'date' => $reportDate,
                            'compare_date' => $compareDate,
                            'division_id' => (int)$row['division']['id'],
                        ]))) ?>">Open</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php else: ?>

<?php if (empty($report)): ?>
<section class="card daily-report-empty">
    <div class="daily-empty-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none"><path d="M7 4h10a2 2 0 0 1 2 2v14H5V6a2 2 0 0 1 2-2Zm2-2h6v4H9V2Zm-1 8h8M8 14h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <h3>No daily snapshot for <?= e(format_app_date($reportDate)) ?></h3>
    <p>
        <?php if (!empty($canGenerateToday)): ?>
            Generate today’s snapshot to preserve the current stock quantity and value for reconciliation.
        <?php else: ?>
            Historical reports can only be viewed when a snapshot was saved on that date. Current stock is never back-dated.
        <?php endif; ?>
    </p>
</section>
<?php else: ?>

<section class="daily-report-grade-grid">
    <?php foreach ($gradeMeta as $code => $meta): ?>
        <?php $g = $gradeSummary[$code] ?? ['batches'=>0,'quantity'=>0,'stock_value'=>0]; ?>
        <article class="daily-grade-card <?= e($meta['class']) ?>">
            <header>
                <strong><?= e($meta['label']) ?></strong>
                <span><?= number_format((int)($g['batches'] ?? 0)) ?> Batches</span>
            </header>
            <div>
                <small>Quantity</small>
                <b><?= e($qty($g['quantity'] ?? 0)) ?></b>
            </div>
            <div>
                <small>Stock Value</small>
                <b><?= e($money($g['stock_value'] ?? 0)) ?></b>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="card daily-report-section">
    <div class="daily-report-section-head">
        <div>
            <h3>Daily Product Variance</h3>
            <p>
                <?= !empty($compareReport)
                    ? 'Product-level comparison against ' . e(format_app_date($compareReport['report_date'])) . '.'
                    : 'No earlier snapshot is available for product-level comparison.' ?>
            </p>
        </div>
        <?php if (!empty($compareReport)): ?>
            <span class="daily-compare-badge">Baseline: <?= e(format_app_date($compareReport['report_date'])) ?></span>
        <?php endif; ?>
    </div>

    <div class="table-wrap daily-variance-wrap">
        <table class="aging-table daily-report-table">
            <thead>
            <tr>
                <th>Product Code</th>
                <th>Product Name</th>
                <th>Previous Qty</th>
                <th>Current Qty</th>
                <th>Qty Difference</th>
                <th>Previous Value</th>
                <th>Current Value</th>
                <th>Value Difference</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$productVariance): ?>
                <tr><td colspan="8" class="empty">No product variance data available.</td></tr>
            <?php endif; ?>
            <?php foreach ($productVariance as $row): ?>
                <tr>
                    <td><strong><?= e($row['product_code']) ?></strong></td>
                    <td><?= e($row['product_name']) ?></td>
                    <td><?= e($qty($row['previous_qty'])) ?></td>
                    <td><?= e($qty($row['current_qty'])) ?></td>
                    <td class="daily-variance <?= e($varianceClass($row['qty_variance'])) ?>"><?= e($signedQty($row['qty_variance'])) ?></td>
                    <td><?= e($money($row['previous_value'])) ?></td>
                    <td><?= e($money($row['current_value'])) ?></td>
                    <td class="daily-variance <?= e($varianceClass($row['value_variance'])) ?>"><?= e($signedMoney($row['value_variance'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card daily-report-section">
    <div class="daily-report-section-head daily-detail-head">
        <div>
            <h3>Detailed Stock Records</h3>
            <p>Exact batch-level stock saved in this historical snapshot.</p>
        </div>

        <div class="daily-snapshot-meta">
            <span><small>Generated</small><b><?= e(format_app_datetime($report['generated_at'])) ?></b></span>
            <span><small>Type</small><b><?= e(ucfirst((string)$report['generation_type'])) ?></b></span>
        </div>
    </div>

    <form method="get" action="<?= e(url('/daily-stock-reports')) ?>" class="daily-detail-filters">
        <input type="hidden" name="date" value="<?= e($reportDate) ?>">
        <input type="hidden" name="compare_date" value="<?= e($compareDate) ?>">
        <input type="hidden" name="division_id" value="<?= (int)$selectedDivisionId ?>">

        <label>
            <span>Search Snapshot</span>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Product, batch, location, UoM...">
        </label>

        <label>
            <span>Rows per page</span>
            <select name="per_page" onchange="this.form.submit()">
                <?php foreach ([25,50,100] as $size): ?>
                    <option value="<?= $size ?>" <?= ((int)$perPage === $size) ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <button class="btn ghost" type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="btn ghost" href="<?= e(url('/daily-stock-reports?' . http_build_query($baseQuery))) ?>">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table class="aging-table daily-report-table">
            <thead>
            <tr>
                <th>Product Code</th>
                <th>Product Name</th>
                <th>Current Stock</th>
                <th>UoM</th>
                <th>Retail Stock Value</th>
                <th>Batch No</th>
                <th>Manufacturing Date</th>
                <th>Expiry Date</th>
                <th>Grade</th>
                <th>Stock Location</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
                <tr><td colspan="10" class="empty">No stock records found in this snapshot.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><strong><?= e($item['product_code']) ?></strong></td>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e($qty($item['current_qty'])) ?></td>
                    <td><?= e($item['uom']) ?></td>
                    <td><?= e($money($item['stock_value'])) ?></td>
                    <td><?= e($item['batch_no']) ?></td>
                    <td><?= e(format_app_date($item['manufacture_date'])) ?></td>
                    <td><?= e(format_app_date($item['expiry_date'])) ?></td>
                    <td><span class="daily-grade-pill grade-<?= e(strtolower(str_replace('+','plus',$item['grade_code']))) ?>"><?= e($item['grade_name']) ?></span></td>
                    <td><?= e($item['stock_location'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="daily-pagination">
        <span>Showing <?= number_format($itemCount ? (($page - 1) * $perPage + 1) : 0) ?>–<?= number_format(min($page * $perPage, $itemCount)) ?> of <?= number_format($itemCount) ?> records</span>
        <div>
            <?php if ($page > 1): ?>
                <a class="btn tiny" href="<?= e(url('/daily-stock-reports?' . http_build_query(array_merge($baseQuery, [
                    'q' => $q,
                    'per_page' => $perPage,
                    'page' => $page - 1,
                ])))) ?>">Previous</a>
            <?php endif; ?>
            <span class="daily-page-chip">Page <?= number_format($page) ?> / <?= number_format($pageCount) ?></span>
            <?php if ($page < $pageCount): ?>
                <a class="btn tiny" href="<?= e(url('/daily-stock-reports?' . http_build_query(array_merge($baseQuery, [
                    'q' => $q,
                    'per_page' => $perPage,
                    'page' => $page + 1,
                ])))) ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="card daily-verification-card">
    <div>
        <span class="daily-report-kicker">DAILY VERIFICATION</span>
        <?php if (!empty($report['verified_at'])): ?>
            <h3>Verified by <?= e($report['verified_by_name'] ?? 'User') ?></h3>
            <p><?= e(format_app_datetime($report['verified_at'])) ?><?= !empty($report['verification_note']) ? ' · ' . e($report['verification_note']) : '' ?></p>
        <?php else: ?>
            <h3>Pending Division Stock Verification</h3>
            <p>Confirm this saved quantity and value after completing the daily stock cross-check.</p>
        <?php endif; ?>
    </div>

    <?php if (empty($report['verified_at'])): ?>
    <form method="post" action="<?= e(url('/daily-stock-reports/verify')) ?>" onsubmit="return confirm('Verify and lock this daily stock report?')">
        <?= csrf_field() ?>
        <input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>">
        <input type="text" name="verification_note" maxlength="500" placeholder="Optional verification note">
        <button class="btn primary" type="submit">Verify Daily Stock</button>
    </form>
    <?php else: ?>
        <span class="daily-verified-seal">✓ VERIFIED</span>
    <?php endif; ?>
</section>

<?php endif; ?>
<?php endif; ?>

<?php if ($isSuperAdmin): ?>
<section class="daily-auto-note">
    <strong>Automatic Daily Closing Snapshot</strong>
    <span>Use <code>bin/install_daily_stock_report_task.bat</code> once on the XAMPP server to schedule the closing snapshot every day at 11:59 PM.</span>
</section>
<?php endif; ?>

<script>
(function () {
    const form = document.getElementById('dailyReportFilterForm');
    const reportDisplay = document.getElementById('dailyReportDateDisplay');
    const reportSql = document.getElementById('dailyReportDateSql');
    const compareDisplay = document.getElementById('dailyCompareDateDisplay');
    const compareSql = document.getElementById('dailyCompareDateSql');
    const division = document.getElementById('dailyReportDivision');

    const months = {
        jan:'01',feb:'02',mar:'03',apr:'04',may:'05',jun:'06',
        jul:'07',aug:'08',sep:'09',oct:'10',nov:'11',dec:'12'
    };

    function toSql(displayValue) {
        const match = String(displayValue || '').trim().match(/^(\d{1,2})[\/\-]([A-Za-z]{3})[\/\-](\d{4})$/);
        if (!match) return null;
        const month = months[match[2].toLowerCase()];
        if (!month) return null;
        const day = String(parseInt(match[1],10)).padStart(2,'0');
        return match[3] + '-' + month + '-' + day;
    }

    function syncDate(display, hidden) {
        if (!display || !hidden) return true;
        const sql = toSql(display.value);
        if (!sql) {
            display.setCustomValidity('Use DD/MON/YYYY, for example 04/Oct/2026.');
            display.reportValidity();
            return false;
        }
        display.setCustomValidity('');
        hidden.value = sql;
        return true;
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            const ok1 = syncDate(reportDisplay, reportSql);
            const ok2 = syncDate(compareDisplay, compareSql);
            if (!ok1 || !ok2) event.preventDefault();
        });
    }

    [reportDisplay, compareDisplay].forEach(function (input) {
        if (!input) return;
        input.addEventListener('blur', function () {
            syncDate(
                input,
                input === reportDisplay ? reportSql : compareSql
            );
        });
    });

    if (division && form) {
        division.addEventListener('change', function () {
            if (syncDate(reportDisplay, reportSql) && syncDate(compareDisplay, compareSql)) {
                form.submit();
            }
        });
    }
})();
</script>
