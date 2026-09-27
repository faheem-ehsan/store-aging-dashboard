<?php
if (!empty($selectedCriteria)) {
    $apDays = (int)$selectedCriteria['a_plus_days'];
    $aDays = (int)$selectedCriteria['a_days'];
    $bDays = (int)$selectedCriteria['b_days'];
    $cDays = (int)$selectedCriteria['c_days'];
    $legendItems = [
        ['class' => 'blue', 'label' => 'A+', 'range' => 'First ' . $apDays . ' day(s) from MFG'],
        ['class' => 'green', 'label' => 'A', 'range' => 'After A+ through day ' . $aDays],
        ['class' => 'yellow', 'label' => 'B', 'range' => 'After A through day ' . $bDays],
        ['class' => 'red', 'label' => 'C', 'range' => 'After B; C limit day ' . $cDays . ', then until expiry'],
        ['class' => 'black', 'label' => 'Expired', 'range' => 'Expiry Date and after'],
    ];
} else {
    $legendItems = [
        ['class' => 'blue', 'label' => 'A+', 'range' => 'Division-specific MFG day period'],
        ['class' => 'green', 'label' => 'A', 'range' => 'Division-specific MFG day period'],
        ['class' => 'yellow', 'label' => 'B', 'range' => 'Division-specific MFG day period'],
        ['class' => 'red', 'label' => 'C', 'range' => 'Division-specific MFG day period until expiry'],
        ['class' => 'black', 'label' => 'Expired', 'range' => 'Expiry Date and after'],
    ];
}
?>
<div class="toolbar">
    <div class="toolbar-left">
        <span class="subtle">Grades are calculated from Date of Manufacturer. FEFO dispatch priority remains driven by Expiry Date.</span>
    </div>
    <?php if ($user['role'] === 'super_admin'): ?>
    <form method="get" action="<?= e(url('/')) ?>" class="inline-form">
        <select name="division_id" onchange="this.form.submit()">
            <option value="">All divisions</option>
            <?php foreach ($divisions as $d): ?>
                <option value="<?= (int)$d['id'] ?>" <?= (int)$selectedDivision === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['code']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>
</div>

<div class="metric-grid dashboard-grade-metrics">
    <div class="metric metric-neutral">
        <span>Total Batches</span>
        <strong><?= number_format($metrics['batches']) ?></strong>
        <small>active stock lots</small>
    </div>
    <div class="metric value-total metric-neutral">
        <span>Current Stock Value</span>
        <strong>Rs/ <?= number_format($metrics['stock_value'], 2) ?></strong>
        <small>all selected grades</small>
    </div>
    <div class="metric grade-aplus grade-click-card" role="button" tabindex="0" data-grade="A+" data-grade-label="A+" aria-label="Open A+ grade stock details">
        <span class="grade-card-title">A+</span>
        <div class="grade-qty-line"><span class="grade-metric-label">QTY</span><strong class="grade-qty-value"><?= number_format($metrics['grades']['A+']['qty'], 3) ?></strong></div>
        <div class="grade-value-line"><span class="grade-currency-label">Rs/</span><span class="grade-stock-value"><?= number_format($metrics['grades']['A+']['value'], 2) ?></span></div>
    </div>
    <div class="metric grade-a grade-click-card" role="button" tabindex="0" data-grade="A" data-grade-label="A" aria-label="Open A grade stock details">
        <span class="grade-card-title">A</span>
        <div class="grade-qty-line"><span class="grade-metric-label">QTY</span><strong class="grade-qty-value"><?= number_format($metrics['grades']['A']['qty'], 3) ?></strong></div>
        <div class="grade-value-line"><span class="grade-currency-label">Rs/</span><span class="grade-stock-value"><?= number_format($metrics['grades']['A']['value'], 2) ?></span></div>
    </div>
    <div class="metric grade-b grade-click-card" role="button" tabindex="0" data-grade="B" data-grade-label="B" aria-label="Open B grade stock details">
        <span class="grade-card-title">B</span>
        <div class="grade-qty-line"><span class="grade-metric-label">QTY</span><strong class="grade-qty-value"><?= number_format($metrics['grades']['B']['qty'], 3) ?></strong></div>
        <div class="grade-value-line"><span class="grade-currency-label">Rs/</span><span class="grade-stock-value"><?= number_format($metrics['grades']['B']['value'], 2) ?></span></div>
    </div>
    <div class="metric grade-c grade-click-card" role="button" tabindex="0" data-grade="C" data-grade-label="C" aria-label="Open C grade stock details">
        <span class="grade-card-title">C</span>
        <div class="grade-qty-line"><span class="grade-metric-label">QTY</span><strong class="grade-qty-value"><?= number_format($metrics['grades']['C']['qty'], 3) ?></strong></div>
        <div class="grade-value-line"><span class="grade-currency-label">Rs/</span><span class="grade-stock-value"><?= number_format($metrics['grades']['C']['value'], 2) ?></span></div>
    </div>
    <div class="metric grade-ex grade-click-card" role="button" tabindex="0" data-grade="EX" data-grade-label="Expired" aria-label="Open expired stock details">
        <span class="grade-card-title">Expired</span>
        <div class="grade-qty-line"><span class="grade-metric-label">QTY</span><strong class="grade-qty-value"><?= number_format($metrics['grades']['EX']['qty'], 3) ?></strong></div>
        <div class="grade-value-line"><span class="grade-currency-label">Rs/</span><span class="grade-stock-value"><?= number_format($metrics['grades']['EX']['value'], 2) ?></span></div>
    </div>
</div>

<?php $canManageDashboard = in_array($user['role'], ['super_admin', 'division_admin'], true); ?>
<div class="card dashboard-stock-card">
    <div class="card-head">
        <div>
            <h2>Stock Aging Dashboard</h2>
            <p>Complete stock view using the same columns as the Stock Aging Dashboard. Records remain in FEFO order by Expiry Date.</p>
        </div>
        <a class="btn" href="<?= e(url('/stocks' . (!empty($selectedDivision) ? '?division_id=' . (int)$selectedDivision : ''))) ?>">Open Stock Aging Dashboard</a>
    </div>
    <div class="table-wrap">
        <table class="aging-table dispatch-stock-table dashboard-stock-table">
            <thead><tr>
                <th>Division</th>
                <th>Product Code</th>
                <th>Product Name</th>
                <th>Current Stock</th>
                <th>UoM</th>
                <th>Stock Value</th>
                <th>Batch No</th>
                <th>Manufacturing Date</th>
                <th>Expiry Date</th>
                <th>Aging (Days)</th>
                <th>Aging Remaining (Days)</th>
                <th>Stock Location</th>
                <th>Grade</th>
                <?php if ($canManageDashboard): ?><th>Action</th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="14" class="empty">No stock batches found for the selected division.</td></tr>
            <?php endif; ?>
            <?php foreach (($rows ?? []) as $r): ?>
                <tr>
                    <td><span class="division-tag"><?= e($r['division_code']) ?></span></td>
                    <td><?= e($r['material_code']) ?></td>
                    <td><?= e($r['material_description']) ?></td>
                    <td class="num"><?= number_format((float)$r['current_qty'], 3) ?></td>
                    <td><?= e($r['uom'] ?? '') ?></td>
                    <td class="num">Rs/ <?= number_format((float)($r['stock_value'] ?? 0), 2) ?></td>
                    <td><?= e($r['batch_no']) ?></td>
                    <td><?= e(date('d.m.Y', strtotime($r['manufacture_date']))) ?></td>
                    <td><?= e(date('d.m.Y', strtotime($r['expiry_date']))) ?></td>
                    <td class="num"><?= number_format($r['aging_days']) ?></td>
                    <td class="num <?= $r['remaining_days'] <= 0 ? 'text-danger' : '' ?>"><?= number_format($r['remaining_days']) ?></td>
                    <td><?= e($r['receiving_store'] ?? '') ?></td>
                    <td>
                        <span class="grade-bar-wrap" title="<?= e($r['grade']['grade_code']) ?> · <?= e($r['grade']['criteria_text']) ?>">
                            <span class="grade-bar <?= e($r['grade']['color_class']) ?>"></span>
                            <span class="sr-only">Grade <?= e($r['grade']['grade_code']) ?> - <?= e($r['grade']['grade_name']) ?></span>
                        </span>
                    </td>
                    <?php if ($canManageDashboard): ?><td class="actions">
                        <a href="<?= e(url('/stocks/edit?id=' . (int)$r['id'])) ?>">Edit</a>
                        <form method="post" action="<?= e(url('/stocks/delete')) ?>" onsubmit="return confirm('Delete this batch?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="danger-link" type="submit">Delete</button>
                        </form>
                    </td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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
            <div><span>Current Stock Qty.</span><strong id="gradeModalQty">0.000</strong></div>
            <div><span>Stock Value</span><strong id="gradeModalValue">Rs/ 0.00</strong></div>
        </div>

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
                    <th>Division</th><th>Product Code</th><th>Product Name</th><th>Current Stock</th><th>UoM</th><th>Stock Value</th><th>Batch No</th><th>Manufacturing Date</th><th>Expiry Date</th><th>Aging (Days)</th><th>Aging Remaining (Days)</th><th>Stock Location</th><th>Grade</th>
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
                        data-value="<?= e((string)(float)($r['stock_value'] ?? 0)) ?>">
                        <td><span class="division-tag"><?= e($r['division_code']) ?></span></td>
                        <td><?= e($r['material_code']) ?></td>
                        <td><?= e($r['material_description']) ?></td>
                        <td class="num"><?= number_format((float)$r['current_qty'], 3) ?></td>
                        <td><?= e($r['uom'] ?? '') ?></td>
                        <td class="num">Rs/ <?= number_format((float)($r['stock_value'] ?? 0), 2) ?></td>
                        <td><?= e($r['batch_no']) ?></td>
                        <td><?= e(date('d.m.Y', strtotime($r['manufacture_date']))) ?></td>
                        <td><?= e(date('d.m.Y', strtotime($r['expiry_date']))) ?></td>
                        <td class="num"><?= number_format($r['aging_days']) ?></td>
                        <td class="num <?= $r['remaining_days'] <= 0 ? 'text-danger' : '' ?>"><?= number_format($r['remaining_days']) ?></td>
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
    const resultText = document.getElementById('gradeModalResultText');
    let activeGrade = '';
    let lastFocused = null;

    function number(value, decimals) {
        return Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: decimals, maximumFractionDigits: decimals});
    }

    function applyFilters() {
        const q = search.value.trim().toLowerCase();
        const d = division.value;
        const l = location.value;
        let count = 0, qty = 0, value = 0;

        rows.forEach(function (row) {
            const visible = row.dataset.grade === activeGrade &&
                (!q || row.dataset.search.includes(q)) &&
                (!d || row.dataset.division === d) &&
                (!l || row.dataset.location === l);
            row.hidden = !visible;
            if (visible) {
                count += 1;
                qty += parseFloat(row.dataset.qty || '0');
                value += parseFloat(row.dataset.value || '0');
            }
        });

        empty.hidden = count !== 0;
        countEl.textContent = count.toLocaleString();
        qtyEl.textContent = number(qty, 3);
        valueEl.textContent = 'Rs/ ' + number(value, 2);
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
