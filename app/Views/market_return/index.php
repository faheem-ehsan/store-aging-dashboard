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
        $millions = $number / 1000000;
        $formatted = rtrim(rtrim(number_format($millions, 8, '.', ','), '0'), '.');
        return 'Rs/ ' . $formatted . ' Million';
    }
    $formatted = rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    return 'Rs/ ' . $formatted;
};
$formatStockValue = static function ($value): string {
    $number = (float)$value;
    if (abs($number) >= 1000000) {
        $millions = $number / 1000000;
        return rtrim(rtrim(number_format($millions, 8, '.', ','), '0'), '.') . ' Million';
    }
    return rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
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
    if (!isset($expiryUomTotals[$bucketCode][$unitCode])) {
        $expiryUomTotals[$bucketCode][$unitCode] = [
            'qty' => 0.0,
            'value' => 0.0,
        ];
    }

    $expiryUomTotals[$bucketCode][$unitCode]['qty'] += (float)($uomRow['quantity'] ?? 0);
    if ($uomRow['total_amount'] !== null) {
        $expiryUomTotals[$bucketCode][$unitCode]['value'] += (float)$uomRow['total_amount'];
    }
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
$marketReturnDivisionBars = array_map(static function (array $division): array {
    return [
        'code' => (string)($division['code'] ?? ''),
        'name' => (string)($division['name'] ?? ''),
    ];
}, $divisions ?? []);

// Market Return table pagination: 5 rows per page.
$rowsPerPage = 5;
$totalTableRows = count($rows ?? []);
$totalPages = max(1, (int)ceil($totalTableRows / $rowsPerPage));
$currentPage = max(1, (int)($_GET['page'] ?? 1));
if ($currentPage > $totalPages) $currentPage = $totalPages;
$pageOffset = ($currentPage - 1) * $rowsPerPage;
$tableRows = array_slice($rows ?? [], $pageOffset, $rowsPerPage);
$showingFrom = $totalTableRows > 0 ? $pageOffset + 1 : 0;
$showingTo = min($pageOffset + $rowsPerPage, $totalTableRows);
$paginationParams = [
    'q' => $filters['q'] ?? '',
    'division_id' => $filters['division_id'] ?? '',
    'category' => $filters['category'] ?? '',
    'expiry_bucket' => $filters['expiry_bucket'] ?? '',
    'value_status' => $filters['value_status'] ?? '',
];
$paginationParams = array_filter($paginationParams, static fn($value): bool => $value !== '' && $value !== null);
$paginationUrl = static function (int $page) use ($paginationParams): string {
    $params = $paginationParams;
    $params['page'] = $page;
    return url('/market-return') . '?' . http_build_query($params);
};

$marketReturnReturnFields = [
    'return_q' => $filters['q'] ?? '',
    'return_division_id' => $filters['division_id'] ?? '',
    'return_category' => $filters['category'] ?? '',
    'return_expiry_bucket' => $filters['expiry_bucket'] ?? '',
    'return_value_status' => $filters['value_status'] ?? '',
    'return_page' => $currentPage,
];
$marketReturnReturnFields = array_filter(
    $marketReturnReturnFields,
    static fn($value): bool => $value !== '' && $value !== null
);
$marketReturnEditUrl = static function (int $id) use ($marketReturnReturnFields): string {
    return url('/market-return/edit?' . http_build_query(array_merge(['id' => $id], $marketReturnReturnFields)));
};
?>

<section class="stock-control-panel market-return-control-panel market-return-dashboard-filter" aria-label="Market Return filters and actions">
    <div class="stock-control-head">
        <div class="stock-control-heading">
            <strong>Market Return Goods</strong>
        </div>
        <?php if ($canManage && $databaseReady): ?>
            <div class="stock-actions stock-control-actions">
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

    <form method="get" action="<?= e(url('/market-return')) ?>" class="market-return-filter-grid-refined" id="marketReturnAutoFilterForm">
        <div class="stock-filter-field stock-filter-search-wrap">
            <label for="marketReturnSearch">Search</label>
            <input id="marketReturnSearch" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Product, remarks, section, expiry...">
        </div>

        <?php if (in_array(($user['role'] ?? ''), ['super_admin','store_manager'], true) || (($user['role'] ?? '') === 'user' && empty($user['division_id']))): ?>
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
            <a class="btn ghost stock-filter-reset-btn" href="<?= e(url('/market-return')) ?>">Reset</a>
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
<div class="dashboard-summary-grid market-return-summary-cards market-return-dashboard-kpis">
    <div class="dashboard-summary-card market-return-total-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 7.5 12 4l8 3.5-8 3.5-8-3.5ZM4 7.5V17l8 3 8-3V7.5M8 13H4m0 0 2-2m-2 2 2 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div>
            <small>Total Quantity Goods</small>
            <strong><?= $formatQty($metrics['qty']) ?></strong>
        </div>
    </div>
    <div class="dashboard-summary-card market-return-value-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 3 5 6v5c0 4.8 2.8 8.1 7 10 4.2-1.9 7-5.2 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 9h4a2 2 0 1 1 0 4H9m3-6v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </span>
        <div>
            <small>Return Retail Stock Value</small>
            <strong><?= $formatMoney($metrics['value']) ?></strong>
            
        </div>
    </div>
    <div class="dashboard-summary-card market-return-expired-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 7v5l3 2M20 12a8 8 0 1 1-8-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M16.5 4.5h3v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div>
            <small>Total Expired Quantity</small>
            <strong><?= $formatQty($expiryMetrics['expired']['qty'] ?? 0) ?></strong>
        </div>
    </div>
</div>

<section class="market-return-dashboard-grade-shell">
<div class="dashboard-grade-section-head market-return-grade-head">
    <div>
        <h2>Grading Summary</h2>
    </div>
</div>

<div class="metric-grid dashboard-grade-metrics dashboard-grade-metrics-refreshed market-return-grade-cards">
    <?php foreach ($distributionStops as $stop): ?>
        <div class="metric <?= e($stop['class']) ?> grade-click-card market-return-click-card" role="button" tabindex="0" data-expiry-group="<?= e($stop['code']) ?>" data-expiry-label="<?= e($stop['label']) ?>" aria-label="Open <?= e($stop['label']) ?> details">
            <div class="grade-card-topline">
                <span class="grade-card-title"><?= e($stop['label']) ?></span>
                <span class="grade-uom-count"><?= $formatInt($stop['lines']) ?> item(s)</span>
            </div>
            <div class="market-return-unit-grid">
                <div class="market-return-unit-head" aria-hidden="true">
                    <span>Unit</span>
                    <span>Quantity</span>
                    <span>Stock Retail Value</span>
                </div>

                <div class="grade-uom-list market-return-uom-values">
                    <?php if (empty($stop['uoms'])): ?>
                        <div class="grade-uom-empty market-return-unit-empty">0</div>
                    <?php else: ?>
                        <?php foreach ($stop['uoms'] as $uomCode => $uomMetric): ?>
                            <div class="grade-uom-row market-return-unit-row">
                                <span class="market-return-unit-code"><?= e($uomCode) ?></span>
                                <strong class="market-return-unit-qty"><?= $formatQty($uomMetric['qty'] ?? 0) ?></strong>
                                <span class="market-return-unit-value"><?= e($formatMoney($uomMetric['value'] ?? 0)) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="grade-value-line"><span class="grade-currency-label">Stock Retail Value</span><span class="grade-stock-value"><?= e($formatMoney($stop['value'])) ?></span></div>
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
</section>

<div class="card market-return-table-card market-return-dashboard-table">
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
        <div class="market-return-table-count"><?= $formatInt($totalTableRows) ?> row(s)</div>
    </div>
    <div class="table-wrap">
        <table class="market-return-table">
            <thead>
                <tr>
                    <th>Division</th>
                    <th>Source Category</th>
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
            <?php if (!$tableRows): ?>
                <tr><td colspan="13" class="empty">No Market Return Store records found for the selected filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($tableRows as $row):
                $meta = $categories[$row['category_code']] ?? ['label' => $row['category_code'], 'class' => 'default'];
                $expiryCode = $row['expiry_bucket'] ?? 'no_expiry';
                $expiryMeta = $expiryBuckets[$expiryCode] ?? $expiryBuckets['no_expiry'];
            ?>
                <tr>
                    <td><span class="division-tag"><?= e($row['division_code']) ?></span></td>
                    <td><span class="market-return-category-tag <?= e($meta['class']) ?>"><?= e($meta['label']) ?></span></td>
                    <td class="market-return-product"><?= e($row['product_name']) ?></td>
                    <td class="num"><?= $formatQty($row['quantity']) ?></td>
                    <td><?= e($row['unit']) ?></td>
                    <td><?= e($row['expiry_display'] ?? (!empty($row['expiry_date']) ? format_app_date($row['expiry_date']) : ($row['expiry_raw'] ?: '—'))) ?></td>
                    <td><span class="market-return-expiry-tag <?= e($expiryMeta['class']) ?>"><?= e($expiryMeta['short']) ?></span></td>
                    <td class="num"><?= $row['retail_price'] !== null ? 'Rs/ ' . number_format((int)round((float)$row['retail_price'])) : '—' ?></td>
                    <td class="num"><?php if ($row['total_amount'] !== null): ?><?= e($formatMoney($row['total_amount'])) ?><?php elseif (!empty($row['amount_text'])): ?><?= e($row['amount_text']) ?><?php else: ?>—<?php endif; ?></td>
                    <td class="market-return-remarks"><?= e($row['remarks'] ?? '') ?><?php if (!empty($row['aux_label']) || !empty($row['aux_value'])): ?><small><?= e(trim(($row['aux_label'] ?? '') . ': ' . ($row['aux_value'] ?? ''), ': ')) ?></small><?php endif; ?></td>
                    <td><?= e($row['section_name'] ?? '') ?></td>
                    <td><?= !empty($row['report_date']) ? e(format_app_date($row['report_date'])) : '—' ?></td>
                    <?php if ($canManage): ?>
                    <td class="actions">
                        <a href="<?= e($marketReturnEditUrl((int)$row['id'])) ?>">Edit</a>
                        <form method="post" action="<?= e(url('/market-return/delete')) ?>" onsubmit="return confirm('Delete this Market Return item?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <?php foreach ($marketReturnReturnFields as $returnName => $returnValue): ?>
                                <input type="hidden" name="<?= e($returnName) ?>" value="<?= e((string)$returnValue) ?>">
                            <?php endforeach; ?>
                            <button class="danger-link" type="submit">Delete</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalTableRows > 0): ?>
    <div class="market-return-pagination" aria-label="Market Return Store Items pagination">
        <div class="market-return-pagination-info">
            Showing <strong><?= number_format($showingFrom) ?>–<?= number_format($showingTo) ?></strong> of <strong><?= number_format($totalTableRows) ?></strong> items
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="pagination-controls" aria-label="Pagination">
            <a class="pagination-btn<?= $currentPage <= 1 ? ' is-disabled' : '' ?>" href="<?= $currentPage > 1 ? e($paginationUrl($currentPage - 1)) : '#' ?>" aria-label="Previous page"<?= $currentPage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Previous</a>

            <?php
            $pageStart = max(1, $currentPage - 2);
            $pageEnd = min($totalPages, $currentPage + 2);
            if ($pageStart > 1): ?>
                <a class="pagination-page" href="<?= e($paginationUrl(1)) ?>">1</a>
                <?php if ($pageStart > 2): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($page = $pageStart; $page <= $pageEnd; $page++): ?>
                <a class="pagination-page<?= $page === $currentPage ? ' is-active' : '' ?>" href="<?= e($paginationUrl($page)) ?>"<?= $page === $currentPage ? ' aria-current="page"' : '' ?>><?= $page ?></a>
            <?php endfor; ?>

            <?php if ($pageEnd < $totalPages): ?>
                <?php if ($pageEnd < $totalPages - 1): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                <a class="pagination-page" href="<?= e($paginationUrl($totalPages)) ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <a class="pagination-btn<?= $currentPage >= $totalPages ? ' is-disabled' : '' ?>" href="<?= $currentPage < $totalPages ? e($paginationUrl($currentPage + 1)) : '#' ?>" aria-label="Next page"<?= $currentPage >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Next</a>
        </nav>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="grade-modal" id="marketReturnModal" aria-hidden="true">
    <div class="grade-modal-backdrop" data-market-return-close></div>
    <section class="grade-modal-dialog <?= empty($filters['division_id']) ? 'grade-modal-all-divisions' : 'grade-modal-single-division' ?>" role="dialog" aria-modal="true" aria-labelledby="marketReturnModalTitle">
        <div class="grade-modal-head">
            <div>
                <span class="grade-modal-kicker">Market Return Details</span>
                <h2 id="marketReturnModalTitle"><span id="marketReturnModalName">Expiry Group</span></h2>
            </div>
            <button type="button" class="grade-modal-close" data-market-return-close aria-label="Close">×</button>
        </div>

        <div class="grade-modal-summary">
            <div><span>No. of Items</span><strong id="marketReturnModalLineCount">0</strong></div>
            <div><span>Total Quantity</span><strong id="marketReturnModalQty">0</strong></div>
            <div><span>Retail Stock Value</span><strong id="marketReturnModalValue">Rs/ 0</strong></div>
        </div>

        <div class="grade-modal-breakdown-grid market-return-breakdown-grid" id="marketReturnBreakdownGrid" <?= !empty($filters['division_id']) ? 'hidden' : '' ?>>
            <div class="grade-division-bars" id="marketReturnDivisionBars">
                <div class="grade-division-bars-head">
                    <strong>Quantity by Division</strong>
                    <span>Selected grading group</span>
                </div>
                <div class="grade-division-summary-head">
                    <span>Division Name</span>
                    <span>No of Items</span>
                    <span>QTY</span>
                    <span>Value</span>
                </div>
                <div class="grade-division-bars-list grade-division-summary-list" id="marketReturnDivisionBarsList"></div>
            </div>

            <div class="grade-unit-split-panel market-return-unit-split-panel">
                <div class="grade-unit-split-head">
                    <strong>Unit-wise Quantity</strong>
                    <span>Selected grading group</span>
                </div>
                <div class="grade-modal-uoms grade-modal-uoms-panel" id="marketReturnUnitSummary"></div>
            </div>
        </div>

        <div class="grade-modal-filters market-return-modal-filters grade-modal-detail-only">
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

        <div class="grade-modal-table-wrap grade-modal-detail-only">
            <table class="market-return-table grade-modal-table market-return-modal-table">
                <thead>
                    <tr>
                        <th class="popup-sortable-th" data-sort-index="0" data-sort-type="text"><button type="button" class="popup-sort-btn">Division <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="1" data-sort-type="text"><button type="button" class="popup-sort-btn">Source Category <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="2" data-sort-type="text"><button type="button" class="popup-sort-btn">Product / Description <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="3" data-sort-type="number"><button type="button" class="popup-sort-btn">Quantity <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="4" data-sort-type="text"><button type="button" class="popup-sort-btn">Unit <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="5" data-sort-type="date"><button type="button" class="popup-sort-btn">Expiry <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="6" data-sort-type="number"><button type="button" class="popup-sort-btn">Retail Price <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="7" data-sort-type="number"><button type="button" class="popup-sort-btn">Total Amount <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="8" data-sort-type="text"><button type="button" class="popup-sort-btn">Section <i>↕</i></button></th>
                        <th class="popup-sortable-th" data-sort-index="9" data-sort-type="text"><button type="button" class="popup-sort-btn">Remarks <i>↕</i></button></th>
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
                        <td><?= e($row['expiry_display'] ?? (!empty($row['expiry_date']) ? format_app_date($row['expiry_date']) : ($row['expiry_raw'] ?: '—'))) ?></td>
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
            <span id="marketReturnModalResultText" class="grade-modal-detail-only">Showing 0 items</span>
            <button type="button" class="btn" data-market-return-close>Close</button>
        </div>
    </section>
</div>

<script>
(function () {
    const modal = document.getElementById('marketReturnModal');
    if (!modal) return;

    const modalDialog = modal.querySelector('.grade-modal-dialog');
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
    const divisionBars = document.getElementById('marketReturnDivisionBars');
    const divisionBarsList = document.getElementById('marketReturnDivisionBarsList');
    const unitSummary = document.getElementById('marketReturnUnitSummary');
    const divisionDefinitions = <?= json_encode($marketReturnDivisionBars, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const showDivisionBars = <?= empty($filters['division_id']) ? 'true' : 'false' ?>;
    const popupBody = document.getElementById('marketReturnModalRows');
    const popupHeaders = Array.from(modal.querySelectorAll('.popup-sortable-th'));
    let activeGroup = '';
    let popupSortIndex = -1;
    let popupSortDirection = 'asc';

    function formatQty(value) {
        return Math.round(Number(value || 0)).toLocaleString();
    }
    function formatMoney(value) {
        const number = Number(value || 0);
        if (Math.abs(number) >= 1000000) {
            return 'Rs/ ' + (number / 1000000).toLocaleString(undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: 8
            }) + ' Million';
        }
        return 'Rs/ ' + number.toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function renderDivisionBars(divisionStats) {
        if (!divisionBars || !divisionBarsList) return;
        divisionBars.hidden = !showDivisionBars;
        if (!showDivisionBars) return;

        divisionBarsList.innerHTML = '';
        divisionDefinitions.forEach(function (divisionDef) {
            const key = divisionDef.code.toLowerCase();
            const stats = divisionStats[key] || {items: 0, qty: 0, value: 0};
            const item = document.createElement('div');
            item.className = 'grade-division-summary-row';
            item.innerHTML =
                '<span class="grade-division-name"><b>' + divisionDef.code + '</b> — ' + divisionDef.name + '</span>' +
                '<strong>' + Number(stats.items || 0).toLocaleString() + '</strong>' +
                '<strong>' + formatQty(stats.qty || 0) + '</strong>' +
                '<strong>' + formatMoney(stats.value || 0) + '</strong>';
            divisionBarsList.appendChild(item);
        });
    }

    function renderUnitSummary(unitStats) {
        if (!unitSummary || !showDivisionBars) return;
        unitSummary.innerHTML = '';

        const units = Object.keys(unitStats).sort(function (a, b) {
            return a.localeCompare(b, undefined, {numeric: true, sensitivity: 'base'});
        });

        if (!units.length) {
            const emptyUnit = document.createElement('div');
            emptyUnit.className = 'grade-modal-uom-chip';
            emptyUnit.innerHTML = '<span>No Unit</span><strong>0</strong>';
            unitSummary.appendChild(emptyUnit);
            return;
        }

        units.forEach(function (unitName) {
            const chip = document.createElement('div');
            chip.className = 'grade-modal-uom-chip';
            chip.innerHTML =
                '<span>' + unitName + '</span>' +
                '<strong>' + formatQty(unitStats[unitName] || 0) + '</strong>';
            unitSummary.appendChild(chip);
        });
    }

    function marketPopupSortNumber(value) {
        const text=String(value||'').toLowerCase();
        const multiplier=text.includes('million')?1000000:1;
        const number=parseFloat(text.replace(/[^0-9.\-]/g,''));
        return Number.isFinite(number)?number*multiplier:0;
    }
    function marketPopupSortDate(value) {
        const m = String(value || '').trim().match(/^(\d{1,2})\/([A-Za-z]{3})\/(\d{4})$/);
        if (!m) return 0;
        const months = {jan:0,feb:1,mar:2,apr:3,may:4,jun:5,jul:6,aug:7,sep:8,oct:9,nov:10,dec:11};
        const month = months[m[2].toLowerCase()];
        return month === undefined ? 0 : new Date(+m[3], month, +m[1]).getTime();
    }
    function sortPopupRows(index,type,direction){
        const factor=direction==='desc'?-1:1;
        rows.sort(function(a,b){
            let av=a.cells[index]?a.cells[index].textContent.trim():'', bv=b.cells[index]?b.cells[index].textContent.trim():'';
            if(type==='number'){ av=marketPopupSortNumber(av); bv=marketPopupSortNumber(bv); }
            else if(type==='date'){ av=marketPopupSortDate(av); bv=marketPopupSortDate(bv); }
            const result=(typeof av==='number'&&typeof bv==='number')?av-bv:String(av).localeCompare(String(bv),undefined,{numeric:true,sensitivity:'base'});
            return result*factor;
        });
        rows.forEach(function(row){ popupBody.insertBefore(row,empty); });
    }
    function resetPopupSort(){
        popupHeaders.forEach(function(h){ h.removeAttribute('aria-sort'); const i=h.querySelector('i'); if(i)i.textContent='↕'; });
    }
    popupHeaders.forEach(function(header){
        const button=header.querySelector('.popup-sort-btn'); if(!button)return;
        button.addEventListener('click',function(){
            const index=Number(header.dataset.sortIndex||0), type=header.dataset.sortType||'text';
            if(popupSortIndex===index) popupSortDirection=popupSortDirection==='asc'?'desc':'asc';
            else { popupSortIndex=index; popupSortDirection='asc'; }
            resetPopupSort(); header.setAttribute('aria-sort',popupSortDirection==='asc'?'ascending':'descending');
            const i=header.querySelector('i'); if(i)i.textContent=popupSortDirection==='asc'?'↑':'↓';
            sortPopupRows(index,type,popupSortDirection);
        });
    });

    function updateModal() {
        const q = (search.value || '').trim().toLowerCase();
        const d = (division.value || '').trim().toLowerCase();
        const c = (category.value || '').trim().toLowerCase();
        const u = (unit.value || '').trim().toLowerCase();
        let visibleCount = 0;
        let visibleQty = 0;
        let visibleValue = 0;
        const divisionStats = {};
        const unitStats = {};

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
                const rowQty = Number(row.dataset.qty || 0);
                const rowValue = Number(row.dataset.value || 0);
                visibleQty += rowQty;
                visibleValue += rowValue;

                const divisionCode = row.dataset.division || '';
                if (divisionCode) {
                    if (!divisionStats[divisionCode]) {
                        divisionStats[divisionCode] = {items: 0, qty: 0, value: 0};
                    }
                    divisionStats[divisionCode].items += 1;
                    divisionStats[divisionCode].qty += rowQty;
                    divisionStats[divisionCode].value += rowValue;
                }

                const unitName = (row.cells[4] ? row.cells[4].textContent.trim() : '') || 'No Unit';
                unitStats[unitName] = (unitStats[unitName] || 0) + rowQty;
            }
        });

        empty.hidden = visibleCount !== 0;
        countEl.textContent = visibleCount.toLocaleString();
        qtyEl.textContent = formatQty(visibleQty);
        valueEl.textContent = formatMoney(visibleValue);
        resultText.textContent = 'Showing ' + visibleCount.toLocaleString() + ' item(s)';
        renderDivisionBars(divisionStats);
        renderUnitSummary(unitStats);
    }

    function openModal(card) {
        activeGroup = card.dataset.expiryGroup || '';
        title.textContent = card.dataset.expiryLabel || 'Expiry Group';
        if (modalDialog) modalDialog.setAttribute('data-market-theme', activeGroup);
        popupSortIndex = -1; popupSortDirection = 'asc'; resetPopupSort();
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


<script>
(function () {
    const form = document.getElementById('marketReturnAutoFilterForm');
    if (!form) return;

    const search = document.getElementById('marketReturnSearch');
    const selects = [
        document.getElementById('marketReturnDivision'),
        document.getElementById('marketReturnCategory'),
        document.getElementById('marketReturnExpiry'),
        document.getElementById('marketReturnValueStatus')
    ].filter(Boolean);

    let searchTimer = null;
    let submitting = false;

    function submitFilters() {
        if (submitting) return;
        submitting = true;
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    selects.forEach(function (select) {
        select.addEventListener('change', function () {
            if (searchTimer) window.clearTimeout(searchTimer);
            submitFilters();
        });
    });

    if (search) {
        search.addEventListener('input', function () {
            if (searchTimer) window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(submitFilters, 450);
        });
    }
})();
</script>

<?php if (($user['role'] ?? '') === 'super_admin' && $databaseReady): ?>
<div class="stock-bulk-modal" id="marketReturnBulkDeleteModal" hidden>
    <section class="stock-bulk-dialog" role="dialog" aria-modal="true" aria-labelledby="marketReturnBulkDeleteTitle">
        <div class="stock-bulk-head">
            <div>
                <span>SUPER ADMIN</span>
                <h2 id="marketReturnBulkDeleteTitle">Bulk Delete Market Returns</h2>
                <p>Delete Market Return records by Division.</p>
            </div>
            <button type="button" class="stock-bulk-close" id="closeMarketReturnBulkDelete" aria-label="Close">×</button>
        </div>

        <form method="post" action="<?= e(url('/market-return/bulk-delete')) ?>" id="marketReturnBulkDeleteForm">
            <?= csrf_field() ?>

            <div class="stock-bulk-warning">
                <strong>Permanent action</strong>
                <span>
                    This deletes Market Return item records for the selected Division(s).
                    Finish Goods stock, Divisions, Users, Import History and Division Mapping remain unchanged.
                </span>
            </div>

            <div class="stock-bulk-toolbar">
                <div>
                    <strong>Select Division(s)</strong>
                    <span>Only divisions containing Market Return records can be selected.</span>
                </div>
                <button type="button" class="stock-bulk-select-all" id="marketReturnBulkSelectAll">Select All With Records</button>
            </div>

            <div class="stock-bulk-division-list">
                <?php foreach (($bulkDeleteStats ?? []) as $stat): ?>
                    <?php
                    $bulkItemCount = (int)($stat['item_count'] ?? 0);
                    $bulkHasRecords = $bulkItemCount > 0;
                    $bulkPreselected = $bulkHasRecords
                        && !empty($filters['division_id'])
                        && (int)$filters['division_id'] === (int)$stat['division_id'];
                    ?>
                    <label class="stock-bulk-division-row <?= !$bulkHasRecords ? 'is-empty' : '' ?>">
                        <input
                            type="checkbox"
                            name="division_ids[]"
                            value="<?= (int)$stat['division_id'] ?>"
                            <?= $bulkPreselected ? 'checked' : '' ?>
                            <?= !$bulkHasRecords ? 'disabled' : '' ?>>

                        <span class="stock-bulk-division-name">
                            <b><?= e($stat['division_code']) ?></b>
                            <small><?= e($stat['division_name']) ?></small>
                        </span>

                        <span class="stock-bulk-stat">
                            <small>Items</small>
                            <strong><?= number_format($bulkItemCount) ?></strong>
                        </span>

                        <span class="stock-bulk-stat">
                            <small>Quantity</small>
                            <strong><?= $formatQty($stat['total_qty'] ?? 0) ?></strong>
                        </span>

                        <span class="stock-bulk-stat stock-bulk-value">
                            <small>Stock Retail Value</small>
                            <strong><?= $formatMoney($stat['stock_value'] ?? 0) ?></strong>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="stock-bulk-confirm">
                <label for="marketReturnBulkConfirm">
                    <span>Type <b>DELETE</b> to confirm</span>
                    <input
                        id="marketReturnBulkConfirm"
                        name="confirm_text"
                        type="text"
                        autocomplete="off"
                        placeholder="DELETE"
                        maxlength="6"
                        required>
                </label>
            </div>

            <div class="stock-bulk-foot">
                <span id="marketReturnBulkSelectedText">No divisions selected</span>
                <div>
                    <button type="button" class="btn ghost" id="cancelMarketReturnBulkDelete">Cancel</button>
                    <button type="submit" class="btn stock-bulk-delete-btn" id="confirmMarketReturnBulkDelete" disabled>Delete Selected Records</button>
                </div>
            </div>
        </form>
    </section>
</div>

<script>
(function () {
    const modal = document.getElementById('marketReturnBulkDeleteModal');
    const openBtn = document.getElementById('openMarketReturnBulkDelete');
    const closeBtn = document.getElementById('closeMarketReturnBulkDelete');
    const cancelBtn = document.getElementById('cancelMarketReturnBulkDelete');
    const selectAllBtn = document.getElementById('marketReturnBulkSelectAll');
    const form = document.getElementById('marketReturnBulkDeleteForm');
    const confirmInput = document.getElementById('marketReturnBulkConfirm');
    const submitBtn = document.getElementById('confirmMarketReturnBulkDelete');
    const selectedText = document.getElementById('marketReturnBulkSelectedText');

    if (!modal || !form) return;

    const checkboxes = Array.from(
        form.querySelectorAll('input[name="division_ids[]"]:not(:disabled)')
    );

    function selectedBoxes() {
        return checkboxes.filter(function (box) { return box.checked; });
    }

    function updateState() {
        const selected = selectedBoxes();
        const confirmed = (confirmInput.value || '').trim().toUpperCase() === 'DELETE';

        selectedText.textContent = selected.length
            ? selected.length + ' Division' + (selected.length === 1 ? '' : 's') + ' selected'
            : 'No divisions selected';

        submitBtn.disabled = selected.length === 0 || !confirmed;

        const allSelected = checkboxes.length > 0
            && checkboxes.every(function (box) { return box.checked; });
        selectAllBtn.textContent = allSelected ? 'Clear Selection' : 'Select All With Records';
    }

    function openModal() {
        modal.hidden = false;
        document.body.classList.add('stock-bulk-modal-open');
        updateState();

        window.setTimeout(function () {
            const checked = selectedBoxes()[0];
            if (checked) checked.focus();
            else if (checkboxes[0]) checkboxes[0].focus();
        }, 30);
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('stock-bulk-modal-open');
        confirmInput.value = '';
        updateState();
        if (openBtn) openBtn.focus();
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    checkboxes.forEach(function (box) {
        box.addEventListener('change', updateState);
    });

    confirmInput.addEventListener('input', updateState);

    selectAllBtn.addEventListener('click', function () {
        const allSelected = checkboxes.length > 0
            && checkboxes.every(function (box) { return box.checked; });

        checkboxes.forEach(function (box) {
            box.checked = !allSelected;
        });

        updateState();
    });

    form.addEventListener('submit', function (event) {
        if (submitBtn.disabled) {
            event.preventDefault();
            return;
        }

        const count = selectedBoxes().length;
        const confirmed = window.confirm(
            'Permanently delete all Market Return item records from ' +
            count + ' selected Division' + (count === 1 ? '' : 's') + '?'
        );

        if (!confirmed) event.preventDefault();
    });

    updateState();

    const query = new URLSearchParams(window.location.search);
    if (query.get('bulk_delete') === '1') {
        openModal();
    }
})();
</script>
<?php endif; ?>

<?php endif; ?>
