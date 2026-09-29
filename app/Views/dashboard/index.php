<?php
if (!empty($selectedCriteria)) {
    $aPlusDays = (int)$selectedCriteria['a_plus_days'];
    $aDays = (int)$selectedCriteria['a_days'];
    $bDays = (int)$selectedCriteria['b_days'];
    $cDays = (int)$selectedCriteria['c_days'];
    $legendItems = [
        ['class' => 'blue', 'label' => 'A+', 'range' => $aPlusDays . '+ days remaining'],
        ['class' => 'green', 'label' => 'A', 'range' => $aDays . '–' . ($aPlusDays - 1) . ' days remaining'],
        ['class' => 'yellow', 'label' => 'B', 'range' => $bDays . '–' . ($aDays - 1) . ' days remaining'],
        ['class' => 'red', 'label' => 'C', 'range' => '1–' . ($bDays - 1) . ' days remaining · C ref ' . $cDays . 'd'],
        ['class' => 'black', 'label' => 'Expired', 'range' => 'Expiry Date and after'],
    ];
} else {
    $legendItems = [
        ['class' => 'blue', 'label' => 'A+', 'range' => 'Division-specific expiry remaining-day range'],
        ['class' => 'green', 'label' => 'A', 'range' => 'Division-specific expiry remaining-day range'],
        ['class' => 'yellow', 'label' => 'B', 'range' => 'Division-specific expiry remaining-day range'],
        ['class' => 'red', 'label' => 'C', 'range' => 'Division-specific expiry remaining-day range'],
        ['class' => 'black', 'label' => 'Expired', 'range' => 'Expiry Date and after'],
    ];
}
?>
<?php
$dashboardNumber = static function ($value): string {
    $number = (float)$value;
    if (abs($number) >= 1000000) {
        return number_format((int)round($number / 1000000)) . ' Million';
    }
    return number_format((int)round($number));
};
$dashboardPlainNumber = static function ($value): string {
    return number_format((int)round((float)$value));
};
$dashboardAgingPeriod = static function ($value): string {
    $days = (int)round((float)$value);
    $sign = $days < 0 ? '-' : '';
    $absDays = abs($days);

    if ($absDays < 30) {
        return $sign . number_format($absDays) . ' Day' . ($absDays === 1 ? '' : 's');
    }

    $months = (int)floor($absDays / 30);
    $remainingDays = $absDays % 30;
    if ($months <= 12) {
        $label = $sign . number_format($months) . ' Month' . ($months === 1 ? '' : 's');
        if ($remainingDays > 0) {
            $label .= ' ' . number_format($remainingDays) . ' Day' . ($remainingDays === 1 ? '' : 's');
        }
        return $label;
    }

    $years = (int)floor($months / 12);
    $remainingMonths = $months % 12;
    $label = $sign . number_format($years) . ' Year' . ($years === 1 ? '' : 's');
    if ($remainingMonths > 0) {
        $label .= ' ' . number_format($remainingMonths) . ' Month' . ($remainingMonths === 1 ? '' : 's');
    }
    return $label;
};
$uomTotals = $metrics['uoms'] ?? [];
$gradeSeries = [
    ['key' => 'A+', 'label' => 'A+', 'class' => 'grade-aplus', 'legend_class' => 'blue', 'color' => '#0b2f6b', 'qty' => (float)($metrics['grades']['A+']['qty'] ?? 0), 'value' => (float)($metrics['grades']['A+']['value'] ?? 0), 'batches' => (int)($metrics['grades']['A+']['batches'] ?? 0), 'uoms' => $metrics['grades']['A+']['uoms'] ?? []],
    ['key' => 'A', 'label' => 'A', 'class' => 'grade-a', 'legend_class' => 'green', 'color' => '#92d050', 'qty' => (float)($metrics['grades']['A']['qty'] ?? 0), 'value' => (float)($metrics['grades']['A']['value'] ?? 0), 'batches' => (int)($metrics['grades']['A']['batches'] ?? 0), 'uoms' => $metrics['grades']['A']['uoms'] ?? []],
    ['key' => 'B', 'label' => 'B', 'class' => 'grade-b', 'legend_class' => 'yellow', 'color' => '#fff200', 'qty' => (float)($metrics['grades']['B']['qty'] ?? 0), 'value' => (float)($metrics['grades']['B']['value'] ?? 0), 'batches' => (int)($metrics['grades']['B']['batches'] ?? 0), 'uoms' => $metrics['grades']['B']['uoms'] ?? []],
    ['key' => 'C', 'label' => 'C', 'class' => 'grade-c', 'legend_class' => 'red', 'color' => '#ff1717', 'qty' => (float)($metrics['grades']['C']['qty'] ?? 0), 'value' => (float)($metrics['grades']['C']['value'] ?? 0), 'batches' => (int)($metrics['grades']['C']['batches'] ?? 0), 'uoms' => $metrics['grades']['C']['uoms'] ?? []],
    ['key' => 'EX', 'label' => 'Expired', 'class' => 'grade-ex', 'legend_class' => 'black', 'color' => '#000000', 'qty' => (float)($metrics['grades']['EX']['qty'] ?? 0), 'value' => (float)($metrics['grades']['EX']['value'] ?? 0), 'batches' => (int)($metrics['grades']['EX']['batches'] ?? 0), 'uoms' => $metrics['grades']['EX']['uoms'] ?? []],
];
$totalBatches = (int)($metrics['batches'] ?? 0);
$maxBatches = 1;
$activeGradeGroups = 0;
foreach ($gradeSeries as $gs) {
    $maxBatches = max($maxBatches, $gs['batches']);
    if ($gs['batches'] > 0) {
        $activeGradeGroups++;
    }
}
$selectedDivisionLabel = !empty($pageDivision) ? (($pageDivision['code'] ?? '') . ' — ' . ($pageDivision['name'] ?? '')) : 'All divisions';
$dashboardWelcomeName = !empty($pageDivision) && !empty($pageDivision['name'])
    ? (string)$pageDivision['name']
    : 'All Division';
$dashboardWelcomeText = 'Welcome to ' . $dashboardWelcomeName . ' Finish Goods Aging System';
$displayRows = array_slice($rows ?? [], 0, 10);
$cumulativePct = 0;
$distributionStops = [];
foreach ($gradeSeries as $gs) {
    $pct = $totalBatches > 0 ? round(($gs['batches'] / $totalBatches) * 100, 2) : 0;
    $distributionStops[] = [
        'label' => $gs['label'],
        'class' => $gs['class'],
        'color' => $gs['color'],
        'batches' => $gs['batches'],
        'value' => $gs['value'],
        'pct' => $pct,
        'from' => $cumulativePct,
        'to' => $cumulativePct + $pct,
    ];
    $cumulativePct += $pct;
}
$gradientParts = [];
foreach ($distributionStops as $stop) {
    $gradientParts[] = $stop['color'] . ' ' . rtrim(rtrim(number_format($stop['from'], 2, '.', ''), '0'), '.') . '% ' . rtrim(rtrim(number_format($stop['to'], 2, '.', ''), '0'), '.') . '%';
}
$donutGradient = !empty($gradientParts) ? 'conic-gradient(' . implode(', ', $gradientParts) . ')' : 'conic-gradient(#e5e7eb 0 100%)';
?>

<div class="dashboard-clean-head">
    <div class="dashboard-clean-title">
        <h1>Dashboard</h1>
        <span class="dashboard-welcome-title"><?= e($dashboardWelcomeText) ?></span>
    </div>

    <div class="dashboard-division-selector">
        <span class="division-selector-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5M8 10h.01M12 10h.01M16 10h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div class="division-selector-content">
            <small>Division</small>
            <?php if ($user['role'] === 'super_admin'): ?>
                <form method="get" action="<?= e(url('/')) ?>" class="dashboard-division-form">
                    <select name="division_id" onchange="this.form.submit()" aria-label="Select division">
                        <option value="">All Divisions</option>
                        <?php foreach ($divisions as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (int)$selectedDivision === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['code']) ?> — <?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else: ?>
                <strong><?= e($selectedDivisionLabel) ?></strong>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dashboard-summary-grid">
    <div class="dashboard-summary-card">
        <span class="summary-icon">📦</span>
        <div>
            <small>Total Items</small>
            <strong><?= $dashboardPlainNumber($metrics['batches']) ?></strong>
            <p>Total batches</p>
        </div>
    </div>
    <div class="dashboard-summary-card">
        <span class="summary-icon">▣</span>
        <div>
            <small>UoM Types</small>
            <strong><?= $dashboardPlainNumber(count($uomTotals)) ?></strong>
            <p>Quantity is separated by unit below</p>
        </div>
    </div>
    <div class="dashboard-summary-card">
        <span class="summary-icon">₨</span>
        <div>
            <small>Retail Stock Value (Rs/)</small>
            <strong><?= $dashboardNumber($metrics['stock_value']) ?></strong>
            <p>Total inventory value</p>
        </div>
    </div>
    <div class="dashboard-summary-card">
        <span class="summary-icon">⚑</span>
        <div>
            <small>Active Grade Groups</small>
            <strong><?= $dashboardPlainNumber($activeGradeGroups) ?></strong>
            <p>Color-based stock groups</p>
        </div>
    </div>
</div>

<div class="dashboard-uom-section">
    <div class="dashboard-uom-head">
        <div>
            <h2>Stock Quantity by Unit</h2>
            <p>Each unit of measure is shown separately so unlike units are never combined.</p>
        </div>
        <span class="uom-count-badge"><?= number_format(count($uomTotals)) ?> UoM<?= count($uomTotals) === 1 ? '' : 's' ?></span>
    </div>
    <div class="dashboard-uom-grid">
        <?php if (empty($uomTotals)): ?>
            <div class="dashboard-uom-empty">No stock quantity is available for the selected division.</div>
        <?php endif; ?>
        <?php foreach ($uomTotals as $uomCode => $uomMetric): ?>
            <div class="dashboard-uom-card">
                <div class="uom-card-top">
                    <span class="uom-code"><?= e($uomCode) ?></span>
                    <span class="uom-lines"><?= number_format((int)$uomMetric['batches']) ?> batch<?= (int)$uomMetric['batches'] === 1 ? '' : 'es' ?></span>
                </div>
                <strong><?= $dashboardNumber($uomMetric['qty']) ?></strong>
                <small>Quantity</small>
                <div class="uom-card-value">Rs/ <?= $dashboardNumber($uomMetric['value']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="dashboard-analytics-grid">
    <div class="card dashboard-analytics-card">
        <div class="card-head analytics-head">
            <div>
                <h2>Batches by Aging Group</h2>
            </div>
            <span class="analytics-badge">Batches</span>
        </div>
        <div class="aging-bars-panel">
            <?php foreach ($gradeSeries as $gs):
                $height = max(14, (int)round(($gs['batches'] / $maxBatches) * 100));
            ?>
                <div class="aging-bar-col">
                    <span class="bar-value"><?= number_format($gs['batches']) ?></span>
                    <div class="aging-bar-track">
                        <div class="aging-bar-fill <?= e($gs['class']) ?>" style="height: <?= $height ?>%;"></div>
                    </div>
                    <strong><?= e($gs['label']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card dashboard-analytics-card">
        <div class="card-head analytics-head">
            <div>
                <h2>Aging Group Distribution</h2>
            </div>
        </div>
        <div class="distribution-panel">
            <div class="distribution-donut" style="background: <?= e($donutGradient) ?>;">
                <div class="distribution-donut-hole">
                    <strong><?= number_format($totalBatches) ?></strong>
                    <span>Total Batches</span>
                </div>
            </div>
            <div class="distribution-list">
                <?php foreach ($distributionStops as $stop): ?>
                    <div class="distribution-item">
                        <span class="distribution-swatch <?= e($stop['class']) ?>"></span>
                        <div class="distribution-copy">
                            <strong><?= e($stop['label']) ?></strong>
                            <small><?= number_format($stop['batches']) ?> batch<?= $stop['batches'] === 1 ? '' : 'es' ?></small>
                        </div>
                        <b><?= number_format($stop['pct'], 0) ?>%</b>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-grade-section-head">
    <div>
        <h2>Grade Summary</h2>
    </div>
</div>

<div class="metric-grid dashboard-grade-metrics dashboard-grade-metrics-refreshed">
    <?php foreach ($gradeSeries as $gs): ?>
        <div class="metric <?= e($gs['class']) ?> grade-click-card" role="button" tabindex="0" data-grade="<?= e($gs['key']) ?>" data-grade-label="<?= e($gs['label']) ?>" aria-label="Open <?= e($gs['label']) ?> grade stock details">
            <div class="grade-card-topline">
                <span class="grade-card-title"><?= e($gs['label']) ?></span>
                <span class="grade-uom-count"><?= number_format(count($gs['uoms'])) ?> UoM</span>
            </div>
            <div class="grade-uom-list">
                <?php if (empty($gs['uoms'])): ?>
                    <div class="grade-uom-empty">No stock</div>
                <?php else: ?>
                    <?php foreach ($gs['uoms'] as $uomCode => $uomMetric): ?>
                        <div class="grade-uom-row">
                            <span><?= e($uomCode) ?></span>
                            <strong><?= $dashboardNumber($uomMetric['qty']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="grade-value-line"><span class="grade-currency-label">Rs/</span><span class="grade-stock-value"><?= $dashboardNumber($gs['value']) ?></span></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grade-legend">
    <?php foreach ($legendItems as $item): ?>
        <span>
            <span class="grade-bar <?= e($item['class']) ?>"></span>
            <b><?= e($item['label']) ?></b>
            <small><?= e($item['range']) ?></small>
        </span>
    <?php endforeach; ?>
    <?php if ($user['role'] === 'super_admin'): ?>
        <a class="btn ghost grade-criteria-link" href="<?= e(url('/grade-criteria')) ?>">Manage Division Criteria</a>
    <?php endif; ?>
</div>


<?php
$modalDivisions = [];
$modalLocations = [];
foreach (($rows ?? []) as $modalRow) {
    $dCode = trim((string)($modalRow['division_code'] ?? ''));
    $loc = trim((string)($modalRow['receiving_store'] ?? ''));
    if ($dCode !== '') $modalDivisions[$dCode] = $dCode;
    if ($loc !== '') $modalLocations[$loc] = $loc;
}
ksort($modalDivisions);
ksort($modalLocations);
?>
<div class="grade-modal" id="gradeStockModal" aria-hidden="true">
    <div class="grade-modal-backdrop" data-grade-modal-close></div>
    <section class="grade-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gradeModalTitle">
        <div class="grade-modal-head">
            <div>
                <span class="grade-modal-kicker">Grade Stock Details</span>
                <h2 id="gradeModalTitle"><span id="gradeModalName">A+</span> Grade</h2>
                <p>All batches for the selected Dashboard division. Use the filters below to narrow the table.</p>
            </div>
            <button type="button" class="grade-modal-close" data-grade-modal-close aria-label="Close">×</button>
        </div>

        <div class="grade-modal-summary">
            <div><span>No. of Batches</span><strong id="gradeModalBatchCount">0</strong></div>
            <div><span>UoM Types</span><strong id="gradeModalQty">0</strong></div>
            <div><span>Retail Stock Value</span><strong id="gradeModalValue">Rs/ 0</strong></div>
        </div>
        <div class="grade-modal-uoms" id="gradeModalUoms"></div>

        <div class="grade-modal-filters">
            <label>Search
                <input type="search" id="gradeModalSearch" placeholder="Product, batch, location...">
            </label>
            <label>Division
                <select id="gradeModalDivision">
                    <option value="">All divisions</option>
                    <?php foreach ($modalDivisions as $divisionCode): ?>
                        <option value="<?= e($divisionCode) ?>"><?= e($divisionCode) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Stock Location
                <select id="gradeModalLocation">
                    <option value="">All locations</option>
                    <?php foreach ($modalLocations as $location): ?>
                        <option value="<?= e($location) ?>"><?= e($location) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="button" class="btn ghost" id="gradeModalReset">Clear Filters</button>
        </div>

        <div class="grade-modal-table-wrap">
            <table class="aging-table grade-modal-table">
                <thead><tr>
                    <th>Division</th><th>Product Code</th><th>Product Name</th><th>Current Stock</th><th>UoM</th><th>Retail Stock Value</th><th>Batch No</th><th>Manufacturing Date</th><th>Expiry Date</th><th>Aging</th><th>Aging Remaining</th><th>Stock Location</th><th>Grade</th>
                </tr></thead>
                <tbody id="gradeModalRows">
                <?php foreach (($rows ?? []) as $r):
                    $searchText = strtolower(trim(implode(' ', [
                        $r['division_code'] ?? '', $r['material_code'] ?? '', $r['material_description'] ?? '',
                        $r['batch_no'] ?? '', $r['receiving_store'] ?? '', $r['uom'] ?? ''
                    ])));
                ?>
                    <tr class="grade-modal-data-row"
                        data-grade="<?= e($r['grade']['grade_code']) ?>"
                        data-division="<?= e($r['division_code']) ?>"
                        data-location="<?= e($r['receiving_store'] ?? '') ?>"
                        data-search="<?= e($searchText) ?>"
                        data-qty="<?= e((string)(float)$r['current_qty']) ?>"
                        data-uom="<?= e(strtoupper(trim((string)($r['uom'] ?? ''))) ?: 'UNSPECIFIED') ?>"
                        data-value="<?= e((string)(float)($r['stock_value'] ?? 0)) ?>">
                        <td><span class="division-tag"><?= e($r['division_code']) ?></span></td>
                        <td><?= e($r['material_code']) ?></td>
                        <td><?= e($r['material_description']) ?></td>
                        <td class="num"><?= $dashboardNumber((float)$r['current_qty']) ?></td>
                        <td><?= e($r['uom'] ?? '') ?></td>
                        <td class="num">Rs/ <?= $dashboardNumber((float)($r['stock_value'] ?? 0)) ?></td>
                        <td><?= e($r['batch_no']) ?></td>
                        <td><?= e(date('d.m.Y', strtotime($r['manufacture_date']))) ?></td>
                        <td><?= e(date('d.m.Y', strtotime($r['expiry_date']))) ?></td>
                        <td class="num aging-period"><?= e($dashboardAgingPeriod($r['aging_days'])) ?></td>
                        <td class="num aging-period <?= $r['remaining_days'] <= 0 ? 'text-danger' : '' ?>"><?= e($dashboardAgingPeriod($r['remaining_days'])) ?></td>
                        <td><?= e($r['receiving_store'] ?? '') ?></td>
                        <td><span class="grade-bar <?= e($r['grade']['color_class']) ?>"></span></td>
                    </tr>
                <?php endforeach; ?>
                <tr id="gradeModalEmpty" hidden><td colspan="13" class="empty">No batches match the selected filters.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="grade-modal-foot">
            <span id="gradeModalResultText">Showing 0 batches</span>
            <button type="button" class="btn" data-grade-modal-close>Close</button>
        </div>
    </section>
</div>

<script>
(function () {
    const modal = document.getElementById('gradeStockModal');
    if (!modal) return;

    const cards = document.querySelectorAll('.grade-click-card');
    const rows = Array.from(modal.querySelectorAll('.grade-modal-data-row'));
    const empty = document.getElementById('gradeModalEmpty');
    const title = document.getElementById('gradeModalName');
    const search = document.getElementById('gradeModalSearch');
    const division = document.getElementById('gradeModalDivision');
    const location = document.getElementById('gradeModalLocation');
    const reset = document.getElementById('gradeModalReset');
    const countEl = document.getElementById('gradeModalBatchCount');
    const qtyEl = document.getElementById('gradeModalQty');
    const valueEl = document.getElementById('gradeModalValue');
    const uomsEl = document.getElementById('gradeModalUoms');
    const resultText = document.getElementById('gradeModalResultText');
    let activeGrade = '';
    let lastFocused = null;

    function dashboardNumber(value) {
        const number = Number(value || 0);
        if (Math.abs(number) >= 1000000) {
            return Math.round(number / 1000000).toLocaleString() + ' Million';
        }
        return Math.round(number).toLocaleString();
    }

    function applyFilters() {
        const q = search.value.trim().toLowerCase();
        const d = division.value;
        const l = location.value;
        let count = 0, value = 0;
        const uomTotals = {};

        rows.forEach(function (row) {
            const visible = row.dataset.grade === activeGrade &&
                (!q || row.dataset.search.includes(q)) &&
                (!d || row.dataset.division === d) &&
                (!l || row.dataset.location === l);
            row.hidden = !visible;
            if (visible) {
                count += 1;
                const uom = row.dataset.uom || 'UNSPECIFIED';
                uomTotals[uom] = (uomTotals[uom] || 0) + parseFloat(row.dataset.qty || '0');
                value += parseFloat(row.dataset.value || '0');
            }
        });

        empty.hidden = count !== 0;
        countEl.textContent = count.toLocaleString();
        qtyEl.textContent = Object.keys(uomTotals).length.toLocaleString();
        valueEl.textContent = 'Rs/ ' + dashboardNumber(value);
        uomsEl.innerHTML = '';
        Object.keys(uomTotals).sort().forEach(function (uom) {
            const chip = document.createElement('div');
            chip.className = 'grade-modal-uom-chip';
            chip.innerHTML = '<span>' + uom + '</span><strong>' + dashboardNumber(uomTotals[uom]) + '</strong>';
            uomsEl.appendChild(chip);
        });
        resultText.textContent = 'Showing ' + count.toLocaleString() + ' batch' + (count === 1 ? '' : 'es');
    }

    function openModal(card) {
        activeGrade = card.dataset.grade || '';
        title.textContent = card.dataset.gradeLabel || activeGrade;
        search.value = '';
        division.value = '';
        location.value = '';
        applyFilters();
        lastFocused = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        window.setTimeout(function () { search.focus(); }, 20);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () { openModal(card); });
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openModal(card);
            }
        });
    });

    [search, division, location].forEach(function (control) {
        control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
    });
    reset.addEventListener('click', function () {
        search.value = '';
        division.value = '';
        location.value = '';
        applyFilters();
        search.focus();
    });
    modal.querySelectorAll('[data-grade-modal-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
})();
</script>
