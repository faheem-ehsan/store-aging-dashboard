<?php
$activeCategory = $filters['category'] ?? '';
$activeExpiryBucket = $filters['expiry_bucket'] ?? '';
$queryBase = [];
if (!empty($filters['division_id'])) $queryBase['division_id'] = (int)$filters['division_id'];
if (!empty($filters['q'])) $queryBase['q'] = $filters['q'];
if (!empty($filters['category'])) $queryBase['category'] = $filters['category'];
if (!empty($filters['value_status'])) $queryBase['value_status'] = $filters['value_status'];

$formatQty = static function ($value): string {
    return number_format((int)round((float)$value));
};
$formatMoney = static function ($value): string {
    $number = (float)$value;
    if (abs($number) >= 1000000) {
        return 'Rs/ ' . number_format((int)round($number / 1000000)) . ' Million';
    }
    return 'Rs/ ' . number_format((int)round($number));
};
$formatStockValue = static function ($value): string {
    $number = (float)$value;
    if (abs($number) >= 1000000) {
        return number_format((int)round($number / 1000000)) . ' Million';
    }
    return number_format((int)round($number));
};
$formatInt = static function ($value): string {
    return number_format((int)$value);
};
$visibleCategoryCount = 0;
$visibleCategoryMap = [];
foreach (($baseRows ?? []) as $row) {
    $code = trim((string)($row['category_code'] ?? ''));
    if ($code !== '') $visibleCategoryMap[$code] = true;
}
$visibleCategoryCount = count($visibleCategoryMap);
$activeExpiryGroups = 0;
$maxExpiryLines = 1;
foreach ($expiryMetrics as $metric) {
    $maxExpiryLines = max($maxExpiryLines, (int)($metric['lines'] ?? 0));
    if ((int)($metric['lines'] ?? 0) > 0) $activeExpiryGroups++;
}
$totalExpiryLines = 0;
$expiryUomTotals = [];
foreach (($baseRows ?? []) as $uomRow) {
    $bucketCode = $uomRow['expiry_bucket'] ?? 'no_expiry';
    if (!isset($expiryBuckets[$bucketCode])) $bucketCode = 'no_expiry';
    $unitCode = strtoupper(trim((string)($uomRow['unit'] ?? '')));
    if ($unitCode === '') $unitCode = 'UNSPECIFIED';
    if (!isset($expiryUomTotals[$bucketCode])) $expiryUomTotals[$bucketCode] = [];
    if (!isset($expiryUomTotals[$bucketCode][$unitCode])) $expiryUomTotals[$bucketCode][$unitCode] = 0.0;
    $expiryUomTotals[$bucketCode][$unitCode] += (float)($uomRow['quantity'] ?? 0);
}
foreach ($expiryUomTotals as &$uomGroup) {
    ksort($uomGroup);
}
unset($uomGroup);
$distributionStops = [];
$distributionColors = [
    'a' => '#92d050',
    'b' => '#fff200',
    'c' => '#ff1717',
    'expired' => '#000000',
    'no_expiry' => '#94a3b8',
];
$cumulativePct = 0;
foreach ($expiryBuckets as $code => $meta) {
    $lines = (int)($expiryMetrics[$code]['lines'] ?? 0);
    $pct = 0;
    $totalExpiryLines += $lines;
    $distributionStops[] = [
        'code' => $code,
        'label' => $meta['label'],
        'short' => $meta['short'],
        'class' => $meta['class'],
        'color' => $distributionColors[$code] ?? '#cbd5e1',
        'lines' => $lines,
        'qty' => (float)($expiryMetrics[$code]['qty'] ?? 0),
        'value' => (float)($expiryMetrics[$code]['value'] ?? 0),
        'unvalued' => (int)($expiryMetrics[$code]['unvalued'] ?? 0),
        'uoms' => $expiryUomTotals[$code] ?? [],
        'range' => $meta['range'],
        'pct' => $pct,
    ];
}
foreach ($distributionStops as &$stop) {
    $stop['pct'] = $totalExpiryLines > 0 ? round(($stop['lines'] / $totalExpiryLines) * 100, 2) : 0;
    $stop['from'] = $cumulativePct;
    $stop['to'] = $cumulativePct + $stop['pct'];
    $cumulativePct += $stop['pct'];
}
unset($stop);
$gradientParts = [];
foreach ($distributionStops as $stop) {
    $from = rtrim(rtrim(number_format($stop['from'], 2, '.', ''), '0'), '.');
    $to = rtrim(rtrim(number_format($stop['to'], 2, '.', ''), '0'), '.');
    $gradientParts[] = $stop['color'] . ' ' . ($from === '' ? '0' : $from) . '% ' . ($to === '' ? '0' : $to) . '%';
}
$donutGradient = !empty($gradientParts) ? 'conic-gradient(' . implode(', ', $gradientParts) . ')' : 'conic-gradient(#e5e7eb 0 100%)';

$modalRows = $baseRows ?? $rows ?? [];
$modalDivisions = [];
$modalCategories = [];
$modalUnits = [];
foreach ($modalRows as $modalRow) {
    $dCode = trim((string)($modalRow['division_code'] ?? ''));
    $cCode = trim((string)($modalRow['category_code'] ?? ''));
    $unit = trim((string)($modalRow['unit'] ?? ''));
    if ($dCode !== '') $modalDivisions[$dCode] = $dCode;
    if ($cCode !== '' && isset($categories[$cCode])) $modalCategories[$cCode] = $categories[$cCode]['label'];
    if ($unit !== '') $modalUnits[$unit] = $unit;
}
ksort($modalDivisions);
ksort($modalCategories);
ksort($modalUnits);
?>

<section class="stock-control-panel market-return-control-panel" aria-label="Market Return filters and actions">
    <div class="stock-control-head">
        <div class="stock-control-heading">
            <span class="stock-control-kicker">Market Return View</span>
            <strong>Filter Market Return Store</strong>
        </div>
        <?php if ($canManage && $databaseReady): ?>
            <div class="stock-actions stock-control-actions">
                <?php if ($user['role'] === 'super_admin'): ?><a class="btn ghost" href="<?= e(url('/market-return/mapping')) ?>">Division Mapping</a><?php endif; ?>
                <a class="btn ghost" href="<?= e(url('/market-return/import' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">Import Market Return File</a>
                <a class="btn primary" href="<?= e(url('/market-return/create' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">+ Add Item</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$databaseReady): ?>
        <div class="market-return-install-note">
            <strong>Market Return Store setup required</strong>
            <p><?= e($databaseError) ?></p>
        </div>
    <?php else: ?>

    <form method="get" action="<?= e(url('/market-return')) ?>" class="market-return-filter-grid-refined">
        <div class="stock-filter-field stock-filter-search-wrap">
            <label for="marketReturnSearch">Search</label>
            <input id="marketReturnSearch" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Product, remarks, section, expiry...">
        </div>

        <?php if ($user['role'] === 'super_admin'): ?>
            <div class="stock-filter-field">
                <label for="marketReturnDivision">Division</label>
                <select id="marketReturnDivision" name="division_id">
                    <option value="">All divisions</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?= (int)$division['id'] ?>" <?= (int)($filters['division_id'] ?? 0) === (int)$division['id'] ? 'selected' : '' ?>><?= e($division['code']) ?> — <?= e($division['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <input type="hidden" name="division_id" value="<?= (int)($filters['division_id'] ?? 0) ?>">
        <?php endif; ?>

        <div class="stock-filter-field">
            <label for="marketReturnCategory">Source Category</label>
            <select id="marketReturnCategory" name="category">
                <option value="">All source categories</option>
                <?php foreach ($categories as $code => $meta): ?>
                    <option value="<?= e($code) ?>" <?= $activeCategory === $code ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="stock-filter-field">
            <label for="marketReturnExpiry">Expiry Group</label>
            <select id="marketReturnExpiry" name="expiry_bucket">
                <option value="">All expiry groups</option>
                <?php foreach ($expiryBuckets as $code => $meta): ?>
                    <option value="<?= e($code) ?>" <?= $activeExpiryBucket === $code ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="stock-filter-field">
            <label for="marketReturnValueStatus">Valuation Status</label>
            <select id="marketReturnValueStatus" name="value_status">
                <option value="">All valuation statuses</option>
                <option value="valued" <?= ($filters['value_status'] ?? '') === 'valued' ? 'selected' : '' ?>>Value available</option>
                <option value="unvalued" <?= ($filters['value_status'] ?? '') === 'unvalued' ? 'selected' : '' ?>>Value not available</option>
            </select>
        </div>

        <div class="stock-filter-actions stock-filter-actions-refined market-return-filter-actions-refined">
            <button class="btn primary" type="submit">Apply Filters</button>
            <a class="btn ghost" href="<?= e(url('/market-return')) ?>">Reset</a>
        </div>
    </form>

    <div class="stock-context-strip market-return-context-strip" aria-label="Current filter context">
        <span><small>Division</small><strong><?= e($selectedDivisionLabel) ?></strong></span>
        <?php if ($activeCategory !== '' && isset($categories[$activeCategory])): ?>
            <span><small>Source Category</small><strong><?= e($categories[$activeCategory]['label']) ?></strong></span>
        <?php endif; ?>
        <?php if ($activeExpiryBucket !== '' && isset($expiryBuckets[$activeExpiryBucket])): ?>
            <span><small>Expiry Group</small><strong><?= e($expiryBuckets[$activeExpiryBucket]['label']) ?></strong></span>
        <?php endif; ?>
        <span><small>Visible Items</small><strong><?= $formatInt($metrics['lines']) ?></strong></span>
    </div>
    <?php endif; ?>
</section>

<?php if ($databaseReady): ?>
<div class="dashboard-summary-grid market-return-summary-cards">
    <div class="dashboard-summary-card">
        <span class="summary-icon">📦</span>
        <div>
            <small>Visible Items</small>
            <strong><?= $formatInt($metrics['lines']) ?></strong>
            <p><?= $formatQty($metrics['qty']) ?> total quantity</p>
        </div>
    </div>
    <div class="dashboard-summary-card">
        <span class="summary-icon">₨</span>
        <div>
            <small>Visible Retail Stock Value</small>
            <strong><?= $formatMoney($metrics['value']) ?></strong>
            <p><?= $formatInt($metrics['unvalued']) ?> item(s) without known value</p>
        </div>
    </div>
    <div class="dashboard-summary-card">
        <span class="summary-icon">◫</span>
        <div>
            <small>Source Categories</small>
            <strong><?= $formatInt($visibleCategoryCount) ?></strong>
            <p>Workbook categories in the current view</p>
        </div>
    </div>
    <div class="dashboard-summary-card">
        <span class="summary-icon">⚑</span>
        <div>
            <small>Active Expiry Groups</small>
            <strong><?= $formatInt($activeExpiryGroups) ?></strong>
            <p>A, B, C, Expired and Other / No Expiry</p>
        </div>
    </div>
</div>

<div class="dashboard-analytics-grid market-return-analytics-grid">
    <div class="card dashboard-analytics-card">
        <div class="card-head analytics-head">
            <div><h2>Items by Expiry Group</h2></div>
            <span class="analytics-badge">Items</span>
        </div>
        <div class="aging-bars-panel">
            <?php foreach ($distributionStops as $stop):
                $height = max(14, (int)round(($stop['lines'] / $maxExpiryLines) * 100));
            ?>
                <div class="aging-bar-col">
                    <span class="bar-value"><?= $formatInt($stop['lines']) ?></span>
                    <div class="aging-bar-track">
                        <div class="aging-bar-fill <?= e($stop['class']) ?>" style="height: <?= $height ?>%;"></div>
                    </div>
                    <strong><?= e($stop['short']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card dashboard-analytics-card">
        <div class="card-head analytics-head">
            <div><h2>Expiry Group Distribution</h2></div>
        </div>
        <div class="distribution-panel">
            <div class="distribution-donut" style="background: <?= e($donutGradient) ?>;">
                <div class="distribution-donut-hole">
                    <strong><?= $formatInt($totalExpiryLines) ?></strong>
                    <span>Total Items</span>
                </div>
            </div>
            <div class="distribution-list">
                <?php foreach ($distributionStops as $stop): ?>
                    <div class="distribution-item">
                        <span class="distribution-swatch <?= e($stop['class']) ?>"></span>
                        <div class="distribution-copy">
                            <strong><?= e($stop['short']) ?></strong>
                            <small><?= $formatInt($stop['lines']) ?> item(s)</small>
                        </div>
                        <b><?= number_format($stop['pct'], 0) ?>%</b>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-grade-section-head market-return-grade-head">
    <div>
        <h2>Expiry Group Summary</h2>
    </div>
</div>

<div class="metric-grid dashboard-grade-metrics dashboard-grade-metrics-refreshed market-return-grade-cards">
    <?php foreach ($distributionStops as $stop): ?>
        <div class="metric <?= e($stop['class']) ?> grade-click-card market-return-click-card" role="button" tabindex="0" data-expiry-group="<?= e($stop['code']) ?>" data-expiry-label="<?= e($stop['label']) ?>" aria-label="Open <?= e($stop['label']) ?> details">
            <div class="grade-card-topline">
                <span class="grade-card-title"><?= e($stop['label']) ?></span>
                <span class="grade-uom-count"><?= $formatInt($stop['lines']) ?> item(s)</span>
            </div>
            <div class="grade-uom-list">
                <?php if (empty($stop['uoms'])): ?>
                    <div class="grade-uom-empty">No stock</div>
                <?php else: ?>
                    <?php foreach ($stop['uoms'] as $uomCode => $uomQty): ?>
                        <div class="grade-uom-row">
                            <span><?= e($uomCode) ?></span>
                            <strong><?= $formatQty($uomQty) ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="grade-value-line"><span class="grade-currency-label">Retail Value</span><span class="grade-stock-value"><?= e($formatMoney($stop['value'])) ?></span></div>
            <?php if ($stop['unvalued'] > 0): ?><em><?= $formatInt($stop['unvalued']) ?> unvalued item(s)</em><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="grade-legend market-return-legend">
    <?php $legendClassMap = ['a' => 'green', 'b' => 'yellow', 'c' => 'red', 'expired' => 'black', 'no_expiry' => 'gray']; ?>
    <?php foreach ($distributionStops as $stop): ?>
        <span>
            <span class="grade-bar <?= e($legendClassMap[$stop['code']] ?? 'gray') ?>"></span>
            <b><?= e($stop['short']) ?></b>
            <small><?= e($stop['range']) ?></small>
        </span>
    <?php endforeach; ?>
</div>

<div class="card market-return-table-card">
    <div class="card-head">
        <div>
            <h2>
                <?php if ($activeExpiryBucket !== '' && isset($expiryBuckets[$activeExpiryBucket])): ?>
                    <?= e($expiryBuckets[$activeExpiryBucket]['label']) ?> Stock
                <?php elseif ($activeCategory !== '' && isset($categories[$activeCategory])): ?>
                    <?= e($categories[$activeCategory]['label']) ?>
                <?php else: ?>
                    Market Return Store Items
                <?php endif; ?>
            </h2>
        </div>
        <div class="market-return-table-count"><?= $formatInt(count($rows)) ?> row(s)</div>
    </div>
    <div class="table-wrap">
        <table class="market-return-table">
            <thead>
                <tr>
                    <th>Division</th>
                    <th>Source Category</th>
                    <th>S. No.</th>
                    <th>Product / Description</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Expiry</th>
                    <th>Expiry Group</th>
                    <th>Retail Price</th>
                    <th>Total Amount</th>
                    <th>Remarks</th>
                    <th>Section</th>
                    <th>Report Date</th>
                    <?php if ($canManage): ?><th>Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="14" class="empty">No Market Return Store records found for the selected filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row):
                $meta = $categories[$row['category_code']] ?? ['label' => $row['category_code'], 'class' => 'default'];
                $expiryCode = $row['expiry_bucket'] ?? 'no_expiry';
                $expiryMeta = $expiryBuckets[$expiryCode] ?? $expiryBuckets['no_expiry'];
            ?>
                <tr>
                    <td><span class="division-tag"><?= e($row['division_code']) ?></span></td>
                    <td><span class="market-return-category-tag <?= e($meta['class']) ?>"><?= e($meta['label']) ?></span></td>
                    <td><?= e($row['serial_no'] ?? '') ?></td>
                    <td class="market-return-product"><?= e($row['product_name']) ?></td>
                    <td class="num"><?= $formatQty($row['quantity']) ?></td>
                    <td><?= e($row['unit']) ?></td>
                    <td><?= e($row['expiry_raw'] ?: ($row['expiry_date'] ? date('d.m.Y', strtotime($row['expiry_date'])) : '—')) ?></td>
                    <td><span class="market-return-expiry-tag <?= e($expiryMeta['class']) ?>"><?= e($expiryMeta['short']) ?></span></td>
                    <td class="num"><?= $row['retail_price'] !== null ? 'Rs/ ' . number_format((int)round((float)$row['retail_price'])) : '—' ?></td>
                    <td class="num"><?php if ($row['total_amount'] !== null): ?><?= e($formatMoney($row['total_amount'])) ?><?php elseif (!empty($row['amount_text'])): ?><?= e($row['amount_text']) ?><?php else: ?>—<?php endif; ?></td>
                    <td class="market-return-remarks"><?= e($row['remarks'] ?? '') ?><?php if (!empty($row['aux_label']) || !empty($row['aux_value'])): ?><small><?= e(trim(($row['aux_label'] ?? '') . ': ' . ($row['aux_value'] ?? ''), ': ')) ?></small><?php endif; ?></td>
                    <td><?= e($row['section_name'] ?? '') ?></td>
                    <td><?= !empty($row['report_date']) ? e(date('d.m.Y', strtotime($row['report_date']))) : '—' ?></td>
                    <?php if ($canManage): ?>
                    <td class="actions">
                        <a href="<?= e(url('/market-return/edit?id=' . (int)$row['id'])) ?>">Edit</a>
                        <form method="post" action="<?= e(url('/market-return/delete')) ?>" onsubmit="return confirm('Delete this Market Return item?')">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button class="danger-link" type="submit">Delete</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grade-modal" id="marketReturnModal" aria-hidden="true">
    <div class="grade-modal-backdrop" data-market-return-close></div>
    <section class="grade-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="marketReturnModalTitle">
        <div class="grade-modal-head">
            <div>
                <span class="grade-modal-kicker">Market Return Details</span>
                <h2 id="marketReturnModalTitle"><span id="marketReturnModalName">Expiry Group</span></h2>
                <p>Use the quick filters below to review item details for the selected expiry group.</p>
            </div>
            <button type="button" class="grade-modal-close" data-market-return-close aria-label="Close">×</button>
        </div>

        <div class="grade-modal-summary">
            <div><span>No. of Items</span><strong id="marketReturnModalLineCount">0</strong></div>
            <div><span>Total Quantity</span><strong id="marketReturnModalQty">0</strong></div>
            <div><span>Retail Stock Value</span><strong id="marketReturnModalValue">Rs/ 0</strong></div>
        </div>

        <div class="grade-modal-filters market-return-modal-filters">
            <label>
                Search
                <input type="search" id="marketReturnModalSearch" placeholder="Product, remarks, section...">
            </label>
            <label>
                Division
                <select id="marketReturnModalDivision">
                    <option value="">All divisions</option>
                    <?php foreach ($modalDivisions as $divisionCode): ?>
                        <option value="<?= e(strtolower($divisionCode)) ?>"><?= e($divisionCode) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Category
                <select id="marketReturnModalCategory">
                    <option value="">All categories</option>
                    <?php foreach ($modalCategories as $categoryCode => $categoryLabel): ?>
                        <option value="<?= e(strtolower($categoryCode)) ?>"><?= e($categoryLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Unit
                <select id="marketReturnModalUnit">
                    <option value="">All units</option>
                    <?php foreach ($modalUnits as $unit): ?>
                        <option value="<?= e(strtolower($unit)) ?>"><?= e($unit) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="button" class="btn ghost" id="marketReturnModalReset">Clear Filters</button>
        </div>

        <div class="grade-modal-table-wrap">
            <table class="market-return-table grade-modal-table market-return-modal-table">
                <thead>
                    <tr>
                        <th>Division</th>
                        <th>Source Category</th>
                        <th>Product / Description</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Expiry</th>
                        <th>Retail Price</th>
                        <th>Total Amount</th>
                        <th>Section</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody id="marketReturnModalRows">
                    <?php foreach ($modalRows as $row):
                        $meta = $categories[$row['category_code']] ?? ['label' => $row['category_code'], 'class' => 'default'];
                        $expiryCode = $row['expiry_bucket'] ?? 'no_expiry';
                        $searchBlob = strtolower(trim(implode(' ', array_filter([
                            $row['product_name'] ?? '',
                            $row['remarks'] ?? '',
                            $row['section_name'] ?? '',
                            $row['division_code'] ?? '',
                            $meta['label'] ?? '',
                            $row['unit'] ?? '',
                            $row['serial_no'] ?? '',
                        ]))));
                    ?>
                    <tr class="grade-modal-data-row market-return-modal-row"
                        data-expiry-group="<?= e($expiryCode) ?>"
                        data-division="<?= e(strtolower((string)($row['division_code'] ?? ''))) ?>"
                        data-category="<?= e(strtolower((string)($row['category_code'] ?? ''))) ?>"
                        data-unit="<?= e(strtolower((string)($row['unit'] ?? ''))) ?>"
                        data-search="<?= e($searchBlob) ?>"
                        data-qty="<?= e((string)((float)($row['quantity'] ?? 0))) ?>"
                        data-value="<?= e((string)($row['total_amount'] !== null ? (float)$row['total_amount'] : 0)) ?>"
                        data-valued="<?= $row['total_amount'] !== null ? '1' : '0' ?>">
                        <td><span class="division-tag"><?= e($row['division_code']) ?></span></td>
                        <td><span class="market-return-category-tag <?= e($meta['class']) ?>"><?= e($meta['label']) ?></span></td>
                        <td class="market-return-product"><?= e($row['product_name']) ?></td>
                        <td class="num"><?= $formatQty($row['quantity']) ?></td>
                        <td><?= e($row['unit']) ?></td>
                        <td><?= e($row['expiry_raw'] ?: ($row['expiry_date'] ? date('d.m.Y', strtotime($row['expiry_date'])) : '—')) ?></td>
                        <td class="num"><?= $row['retail_price'] !== null ? 'Rs/ ' . number_format((int)round((float)$row['retail_price'])) : '—' ?></td>
                        <td class="num"><?php if ($row['total_amount'] !== null): ?><?= e($formatMoney($row['total_amount'])) ?><?php elseif (!empty($row['amount_text'])): ?><?= e($row['amount_text']) ?><?php else: ?>—<?php endif; ?></td>
                        <td><?= e($row['section_name'] ?? '') ?></td>
                        <td class="market-return-remarks"><?= e($row['remarks'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="marketReturnModalEmpty" hidden><td colspan="10" class="empty">No items match the selected filters.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="grade-modal-foot">
            <span id="marketReturnModalResultText">Showing 0 items</span>
            <button type="button" class="btn" data-market-return-close>Close</button>
        </div>
    </section>
</div>

<script>
(function () {
    const modal = document.getElementById('marketReturnModal');
    if (!modal) return;

    const cards = document.querySelectorAll('.market-return-click-card');
    const rows = Array.from(modal.querySelectorAll('.market-return-modal-row'));
    const empty = document.getElementById('marketReturnModalEmpty');
    const title = document.getElementById('marketReturnModalName');
    const search = document.getElementById('marketReturnModalSearch');
    const division = document.getElementById('marketReturnModalDivision');
    const category = document.getElementById('marketReturnModalCategory');
    const unit = document.getElementById('marketReturnModalUnit');
    const reset = document.getElementById('marketReturnModalReset');
    const countEl = document.getElementById('marketReturnModalLineCount');
    const qtyEl = document.getElementById('marketReturnModalQty');
    const valueEl = document.getElementById('marketReturnModalValue');
    const resultText = document.getElementById('marketReturnModalResultText');
    let activeGroup = '';

    function formatQty(value) {
        return Math.round(Number(value || 0)).toLocaleString();
    }
    function formatMoney(value) {
        const number = Number(value || 0);
        if (Math.abs(number) >= 1000000) {
            return 'Rs/ ' + Math.round(number / 1000000).toLocaleString() + ' Million';
        }
        return 'Rs/ ' + Math.round(number).toLocaleString();
    }

    function updateModal() {
        const q = (search.value || '').trim().toLowerCase();
        const d = (division.value || '').trim().toLowerCase();
        const c = (category.value || '').trim().toLowerCase();
        const u = (unit.value || '').trim().toLowerCase();
        let visibleCount = 0;
        let visibleQty = 0;
        let visibleValue = 0;

        rows.forEach(function (row) {
            const matchGroup = row.dataset.expiryGroup === activeGroup;
            const matchSearch = !q || (row.dataset.search || '').includes(q);
            const matchDivision = !d || row.dataset.division === d;
            const matchCategory = !c || row.dataset.category === c;
            const matchUnit = !u || row.dataset.unit === u;
            const show = matchGroup && matchSearch && matchDivision && matchCategory && matchUnit;
            row.hidden = !show;
            if (show) {
                visibleCount++;
                visibleQty += Number(row.dataset.qty || 0);
                visibleValue += Number(row.dataset.value || 0);
            }
        });

        empty.hidden = visibleCount !== 0;
        countEl.textContent = visibleCount.toLocaleString();
        qtyEl.textContent = formatQty(visibleQty);
        valueEl.textContent = formatMoney(visibleValue);
        resultText.textContent = 'Showing ' + visibleCount.toLocaleString() + ' item(s)';
    }

    function openModal(card) {
        activeGroup = card.dataset.expiryGroup || '';
        title.textContent = card.dataset.expiryLabel || 'Expiry Group';
        search.value = '';
        division.value = '';
        category.value = '';
        unit.value = '';
        updateModal();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
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

    [search, division, category, unit].forEach(function (el) {
        el.addEventListener('input', updateModal);
        el.addEventListener('change', updateModal);
    });

    reset.addEventListener('click', function () {
        search.value = '';
        division.value = '';
        category.value = '';
        unit.value = '';
        updateModal();
    });

    modal.querySelectorAll('[data-market-return-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
})();
</script>

<?php endif; ?>
