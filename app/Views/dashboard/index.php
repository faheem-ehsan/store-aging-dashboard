<?php
if (!empty($gradeCriteria)) {
    $legendItems = [
        ['class' => 'blue', 'label' => 'A+', 'range' => $gradeCriteria['A+']],
        ['class' => 'green', 'label' => 'A', 'range' => $gradeCriteria['A']],
        ['class' => 'yellow', 'label' => 'B', 'range' => $gradeCriteria['B']],
        ['class' => 'red', 'label' => 'C', 'range' => $gradeCriteria['C']],
        ['class' => 'black', 'label' => 'Expired', 'range' => $gradeCriteria['EX']],
    ];
} else {
    $legendItems = [
        ['class' => 'blue', 'label' => 'A+', 'range' => 'Division-specific shelf-life-used range'],
        ['class' => 'green', 'label' => 'A', 'range' => 'Division-specific shelf-life-used range'],
        ['class' => 'yellow', 'label' => 'B', 'range' => 'Division-specific shelf-life-used range'],
        ['class' => 'red', 'label' => 'C', 'range' => 'Division-specific shelf-life-used range until Expiry'],
        ['class' => 'black', 'label' => 'Expired', 'range' => 'Expiry Date and after'],
    ];
}

$dashboardNumber = static function ($value): string {
    $number = (float)$value;
    if (abs($number) >= 1000000) {
        return number_format((int)round($number / 1000000)) . ' Million';
    }
    return number_format((int)round($number));
};

$dashboardStockValue = static function ($value): string {
    $number = (float)$value;

    if (abs($number) >= 1000000) {
        // Preserve the actual stock value while displaying it in Millions.
        // Up to 8 decimal places are available so paisa precision is not lost.
        // Only unnecessary trailing zeroes are removed.
        $millions = $number / 1000000;
        $formatted = number_format($millions, 8, '.', ',');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted . ' Million';
    }

    // Keep exact rupee/paisa value below one million and remove only
    // unnecessary trailing zeroes.
    $formatted = number_format($number, 2, '.', ',');
    return rtrim(rtrim($formatted, '0'), '.');
};

$dashboardPlainNumber = static function ($value): string {
    return number_format((int)round((float)$value));
};
$dashboardActualMoney = static function ($value): string {
    $number = (float)$value;
    $formatted = rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    return 'Rs/ ' . $formatted;
};
$dashboardCalendarAgingPeriod = static function ($startDate, $endDate, bool $inclusiveEnd = false): string {
    try {
        $start = new \DateTimeImmutable((string)$startDate);
        $end = new \DateTimeImmutable((string)$endDate);

        if ($inclusiveEnd && $end > $start) {
            $end = $end->modify('+1 day');
        }
    } catch (\Throwable $e) {
        return '—';
    }

    $interval = $start->diff($end);
    $sign = $interval->invert ? '-' : '';
    $years = (int)$interval->y;
    $months = (int)$interval->m;
    $days = (int)$interval->d;

    if ($years > 0) {
        $label = $sign . number_format($years) . ' Year' . ($years === 1 ? '' : 's');
        if ($months > 0) {
            $label .= ' ' . number_format($months) . ' Month' . ($months === 1 ? '' : 's');
        }
        return $label;
    }

    if ($months > 0) {
        $label = $sign . number_format($months) . ' Month' . ($months === 1 ? '' : 's');
        if ($days > 0) {
            $label .= ' ' . number_format($days) . ' Day' . ($days === 1 ? '' : 's');
        }
        return $label;
    }

    return $sign . number_format($days) . ' Day' . ($days === 1 ? '' : 's');
};
$dashboardAgingReferenceDate = (new \DateTimeImmutable('today'))->format('Y-m-d');
$uomTotals = $metrics['uoms'] ?? [];

/*
 * Unit of Measure display names used in the Dashboard Grade Details popup.
 * Add or adjust codes here if another stock/SAP unit is introduced later.
 */
$uomFullForms = [
    'CAR' => 'Carton',
    'CTN' => 'Carton',
    'CTN.' => 'Carton',
    'CD' => 'Card',
    'EA' => 'Each',
    'PAC' => 'Pack',
    'PACK' => 'Pack',
    'PK' => 'Pack',
    'CS' => 'Case',
    'CASE' => 'Case',
    'PCS' => 'Pieces',
    'PC' => 'Piece',
    'BTL' => 'Bottle',
    'BTLS' => 'Bottles',
    'BTLS.' => 'Bottles',
    'BOX' => 'Box',
    'BOXES' => 'Boxes',
    'JAR' => 'Jar',
    'JARS' => 'Jars',
    'NOS' => 'Numbers',
    'NO' => 'Number',
    'STRIP' => 'Strip',
    'STRIPS' => 'Strips',
    'TAB' => 'Tablet',
    'TABLET' => 'Tablet',
    'TABLETS' => 'Tablets',
];

$formatUomFull = static function ($code) use ($uomFullForms): string {
    $raw = trim((string)$code);
    if ($raw === '') return 'Unspecified';
    $key = strtoupper($raw);
    $full = $uomFullForms[$key] ?? null;
    return $full ? ($raw . ' (' . $full . ')') : $raw;
};
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
$dashboardDivisionTitle = !empty($pageDivision) && !empty($pageDivision['name'])
    ? (string)$pageDivision['name']
    : 'All Divisions';
$dashboardWelcomeText = 'Finish Goods Stock Aging Dashboard | ' . $dashboardDivisionTitle;
// TEST PHASE: Keep the Dashboard Latest Stock Update date fixed for every Division.
// Remove this override later to restore the live last-stock-update date.
$dashboardLastUpdatedLabel = '05/Oct/2026';

$lastUpdateDivisionCode = trim((string)($lastStockUpdateInfo['division_code'] ?? ''));
$lastUpdateDivisionName = trim((string)($lastStockUpdateInfo['division_name'] ?? ''));

if (!empty($pageDivision)) {
    $lastUpdateDivisionCode = trim((string)($pageDivision['code'] ?? $lastUpdateDivisionCode));
    $lastUpdateDivisionName = trim((string)($pageDivision['name'] ?? $lastUpdateDivisionName));
}

$dashboardLastUpdateDivision = trim(
    $lastUpdateDivisionCode .
    ($lastUpdateDivisionCode !== '' && $lastUpdateDivisionName !== '' ? ' — ' : '') .
    $lastUpdateDivisionName
);

if ($dashboardLastUpdateDivision === '') {
    $dashboardLastUpdateDivision = !empty($pageDivision) ? $selectedDivisionLabel : 'No division update available';
}

$dashboardLastUpdateHeading = empty($pageDivision)
    ? 'Latest Stock Update'
    : 'Last Stock Update';

$dashboardLastUpdateScopeLabel = empty($pageDivision)
    ? 'Source'
    : '';

$dashboardLastUpdateTooltip = empty($pageDivision)
    ? 'Latest stock update across all divisions.'
    : 'Latest stock update for the selected division.';
$dashboardDivisionBars = array_map(static function (array $division): array {
    return [
        'code' => (string)($division['code'] ?? ''),
        'name' => (string)($division['name'] ?? ''),
    ];
}, $divisions ?? []);
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

<section class="dashboard-variant2-overview">
<div class="dashboard-clean-head dashboard-title-only-head">
    <h1><?= e($dashboardWelcomeText) ?></h1>

    <div class="dashboard-division-selector">
        <span class="division-selector-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5M8 10h.01M12 10h.01M16 10h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div class="division-selector-content">
            <small>Division</small>
            <?php if (in_array(($user['role'] ?? ''), ['super_admin','store_manager'], true) || (($user['role'] ?? '') === 'user' && empty($user['division_id']))): ?>
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

    <div class="dashboard-last-update dashboard-last-update-compact" title="<?= e($dashboardLastUpdateTooltip) ?>">
        <span class="dashboard-update-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 8v4l2.5 1.5M20 12a8 8 0 1 1-2.34-5.66M17 3.5v4h-4"
                      stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>

        <div class="dashboard-update-copy">
            <div class="dashboard-update-line">
                <small><?= e($dashboardLastUpdateHeading) ?></small>
                <strong><?= e($dashboardLastUpdatedLabel) ?></strong>
            </div>

        </div>
    </div>
</div>

<div class="dashboard-summary-grid">
    <div class="dashboard-summary-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="m4.5 7 7.5-4 7.5 4-7.5 4-7.5-4Zm0 0v10l7.5 4 7.5-4V7M4.5 12l7.5 4 7.5-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div>
            <small>Total Batches</small>
            <strong><?= $dashboardPlainNumber($metrics['batches']) ?></strong>
            
        </div>
    </div>
    <div class="dashboard-summary-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 7.5h16v9H4v-9Zm3 4.5h.01M17 12h.01M12 9.5c1.38 0 2.5 1.12 2.5 2.5s-1.12 2.5-2.5 2.5-2.5-1.12-2.5-2.5 1.12-2.5 2.5-2.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div>
            <small>Stock Retail Value</small>
            <strong>Rs/ <?= $dashboardStockValue($metrics['stock_value']) ?></strong>
            
        </div>
    </div>
</div>
</section>

<?php $gradeHoverFocusEnabled = empty($selectedDivision); ?>
<section class="dashboard-variant2-grade-shell<?= $gradeHoverFocusEnabled ? ' grade-hover-focus-enabled' : '' ?>">
<div class="dashboard-grade-section-head">
    <div>
        <h2>Grade Summary</h2>
    </div>
</div>

<div class="metric-grid dashboard-grade-metrics dashboard-grade-metrics-refreshed grade-summary-table-cards">
    <?php foreach ($gradeSeries as $gs): ?>
        <div class="metric <?= e($gs['class']) ?> grade-click-card grade-detail-card" role="button" tabindex="0" data-grade="<?= e($gs['key']) ?>" data-grade-label="<?= e($gs['label']) ?>" aria-label="Open <?= e($gs['label']) ?> grade stock details"<?= $gradeHoverFocusEnabled ? ' aria-describedby="gradeHover-' . e($gs['class']) . '"' : '' ?>>
            <div class="grade-card-topline">
                <span class="grade-card-title"><?= e($gs['label']) ?><?= $gs['key'] !== 'EX' ? ' Grade' : '' ?></span>
                <span class="grade-uom-count"><?= number_format(count($gs['uoms'])) ?> Unit<?= count($gs['uoms']) === 1 ? '' : 's' ?></span>
            </div>

            <div class="grade-hover-cloud" id="gradeHover-<?= e($gs['class']) ?>" role="tooltip" aria-hidden="true">
                <span class="grade-hover-cloud-pointer" aria-hidden="true"></span>
                <div class="grade-hover-cloud-head">
                    <span class="grade-hover-cloud-badge"><?= e($gs['label']) ?></span>
                    <div>
                        <strong><?= e($gs['label']) ?><?= $gs['key'] !== 'EX' ? ' Grade' : '' ?></strong>
                        
                    </div>
                </div>

                <div class="grade-hover-cloud-metrics">
                    <div class="grade-hover-cloud-metric">
                        <span class="grade-hover-cloud-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M5 7.5 12 4l7 3.5-7 3.5-7-3.5Zm0 0v4L12 15l7-3.5v-4M5 11.5v4L12 19l7-3.5v-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <small>Total Quantity</small>
                            <strong><?= $dashboardPlainNumber($gs['qty']) ?></strong>
                        </span>
                    </div>

                    <div class="grade-hover-cloud-metric">
                        <span class="grade-hover-cloud-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M6 5.5h12v13H6v-13ZM9 3.5h6v4H9v-4Zm0 8h6M9 15h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <small>Total No. of Batches</small>
                            <strong><?= number_format((int)$gs['batches']) ?></strong>
                        </span>
                    </div>

                    <div class="grade-hover-cloud-metric">
                        <span class="grade-hover-cloud-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M4 7.5h16v9H4v-9Zm3 4.5h.01M17 12h.01M12 9.5c1.38 0 2.5 1.12 2.5 2.5s-1.12 2.5-2.5 2.5-2.5-1.12-2.5-2.5 1.12-2.5 2.5-2.5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <small>Total Stock Retail Value</small>
                            <strong>Rs/ <?= $dashboardStockValue($gs['value']) ?></strong>
                        </span>
                    </div>
                </div>

                <div class="grade-hover-cloud-foot">
                    <span>Click to open detailed batches</span>
                    <span aria-hidden="true">↗</span>
                </div>
            </div>

            <div class="grade-unit-table" role="table" aria-label="<?= e($gs['label']) ?> unit summary">
                <div class="grade-unit-table-head" role="row">
                    <span role="columnheader">Units</span>
                    <span role="columnheader">Quantity</span>
                    <span role="columnheader">Stock Retail Value</span>
                </div>

                <div class="grade-unit-table-body">
                    <?php if (empty($gs['uoms'])): ?>
                        <div class="grade-unit-empty">0</div>
                    <?php else: ?>
                        <?php foreach ($gs['uoms'] as $uomCode => $uomMetric): ?>
                            <div class="grade-unit-table-row" role="row">
                                <span class="grade-unit-code" role="cell"><?= e($uomCode) ?></span>
                                <strong role="cell"><?= $dashboardPlainNumber($uomMetric['qty']) ?></strong>
                                <span class="grade-unit-value" role="cell">Rs/ <?= $dashboardStockValue($uomMetric['value']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grade-card-totals">
                <div>
                    <span>Total Qty</span>
                    <strong><?= $dashboardPlainNumber($gs['qty']) ?></strong>
                </div>
                <div>
                    <span>Total Stock Retail Value</span>
                    <strong>Rs/ <?= $dashboardStockValue($gs['value']) ?></strong>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($gradeHoverFocusEnabled): ?>
<div class="grade-hover-focus-backdrop" id="gradeHoverFocusBackdrop" aria-hidden="true"></div>
<div class="grade-hover-focus-portal" id="gradeHoverFocusPortal" aria-hidden="true">
    <div class="grade-hover-focus-cloud" id="gradeHoverFocusCloud" role="dialog" aria-modal="false" tabindex="0"></div>
</div>
<?php endif; ?>

<div class="grade-legend">
    <?php foreach ($legendItems as $item): ?>
        <span>
            <span class="grade-bar <?= e($item['class']) ?>"></span>
            <b><?= e($item['label']) ?></b>
            <small><?= e($item['range']) ?></small>
        </span>
    <?php endforeach; ?>
    <?php if ($user['role'] === 'super_admin'): ?>
        <a class="btn ghost grade-criteria-link" href="<?= e(url('/grade-criteria')) ?>">View Grading Criteria</a>
    <?php endif; ?>
</div>
</section>

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
    <section class="grade-modal-dialog <?= empty($selectedDivision) ? 'grade-modal-all-divisions' : 'grade-modal-single-division' ?>" role="dialog" aria-modal="true" aria-labelledby="gradeModalTitle">
        <div class="grade-modal-head">
            <div>
                <span class="grade-modal-kicker">Grade Stock Details</span>
                <h2 id="gradeModalTitle"><span id="gradeModalName">A+</span> Grade</h2>
            </div>
            <button type="button" class="grade-modal-close" data-grade-modal-close aria-label="Close">×</button>
        </div>

        <div class="grade-modal-summary">
            <div><span>No of Batches</span><strong id="gradeModalBatchCount">0</strong></div>
            <div><span>Total Quantity</span><strong id="gradeModalQty">0</strong></div>
            <div><span>Stock Retail Value</span><strong id="gradeModalValue">Rs/ 0</strong></div>
        </div>
        <div class="grade-modal-breakdown-grid <?= !empty($selectedDivision) ? 'division-selected' : '' ?>">
            <div class="grade-division-bars" id="gradeDivisionBars" <?= !empty($selectedDivision) ? 'hidden' : '' ?>>
                <div class="grade-division-bars-head">
                    <strong>Quantity by Division</strong>
                    <span>Selected grade</span>
                </div>
                <div class="grade-division-summary-head">
                    <span>Division Name</span>
                    <span>No of Batches</span>
                    <span>QTY</span>
                    <span>Value</span>
                </div>
                <div class="grade-division-bars-list grade-division-summary-list" id="gradeDivisionBarsList"></div>
            </div>

            <div class="grade-unit-split-panel">
                <div class="grade-unit-split-head">
                    <strong>Unit-wise Quantity</strong>
                    <span>Selected grade</span>
                </div>
                <div class="grade-modal-uoms grade-modal-uoms-panel" id="gradeModalUoms"></div>
            </div>
        </div>

        <div class="grade-modal-filters grade-modal-detail-only">
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

        <div class="grade-modal-table-wrap grade-modal-detail-only">
            <table class="aging-table grade-modal-table">
                <thead><tr>
                    <th class="popup-sortable-th" data-sort-index="0" data-sort-type="text"><button type="button" class="popup-sort-btn">Division <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="1" data-sort-type="text"><button type="button" class="popup-sort-btn">Product Code <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="2" data-sort-type="text"><button type="button" class="popup-sort-btn">Product Name <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="3" data-sort-type="qty"><button type="button" class="popup-sort-btn">Current Stock <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="4" data-sort-type="text"><button type="button" class="popup-sort-btn">UoM <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="5" data-sort-type="value"><button type="button" class="popup-sort-btn">Retail Stock Value <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="6" data-sort-type="text"><button type="button" class="popup-sort-btn">Batch No <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="7" data-sort-type="date"><button type="button" class="popup-sort-btn">Manufacturing Date <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="8" data-sort-type="date"><button type="button" class="popup-sort-btn">Expiry Date <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="9" data-sort-type="aging"><button type="button" class="popup-sort-btn">Aging <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="10" data-sort-type="remaining"><button type="button" class="popup-sort-btn">Aging Remaining <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="11" data-sort-type="text"><button type="button" class="popup-sort-btn">Stock Location <i>↕</i></button></th>
                    <th class="popup-sortable-th" data-sort-index="12" data-sort-type="text"><button type="button" class="popup-sort-btn">Grade <i>↕</i></button></th>
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
                        data-uom-label="<?= e($formatUomFull($r['uom'] ?? '')) ?>"
                        data-value="<?= e((string)(float)($r['stock_value'] ?? 0)) ?>"
                        data-aging-days="<?= e((string)max(0, (int)floor((strtotime($dashboardAgingReferenceDate) - strtotime($r['manufacture_date'])) / 86400))) ?>"
                        data-remaining-days="<?= e((string)(int)($r['remaining_days'] ?? 0)) ?>">
                        <td><span class="division-tag"><?= e($r['division_code']) ?></span></td>
                        <td><?= e($r['material_code']) ?></td>
                        <td><?= e($r['material_description']) ?></td>
                        <td class="num"><?= $dashboardNumber((float)$r['current_qty']) ?></td>
                        <td class="grade-modal-unit-cell"><?= e($formatUomFull($r['uom'] ?? '')) ?></td>
                        <td class="num"><?= $dashboardActualMoney($r['stock_value'] ?? 0) ?></td>
                        <td><?= e($r['batch_no']) ?></td>
                        <td><?= e(format_app_date($r['manufacture_date'])) ?></td>
                        <td><?= e(format_app_date($r['expiry_date'])) ?></td>
                        <td class="num aging-period"><?= e($dashboardCalendarAgingPeriod($r['manufacture_date'], $dashboardAgingReferenceDate)) ?></td>
                        <td class="num aging-period <?= $r['remaining_days'] <= 0 ? 'text-danger' : '' ?>"><?= e($dashboardCalendarAgingPeriod($dashboardAgingReferenceDate, $r['expiry_date'], (int)($r['remaining_days'] ?? 0) > 0)) ?></td>
                        <td><?= e($r['receiving_store'] ?? '') ?></td>
                        <td><span class="grade-bar <?= e($r['grade']['color_class']) ?>"></span></td>
                    </tr>
                <?php endforeach; ?>
                <tr id="gradeModalEmpty" hidden><td colspan="13" class="empty">No batches match the selected filters.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="grade-modal-foot">
            <span id="gradeModalResultText" class="grade-modal-detail-only">Showing 0 batches</span>
            <button type="button" class="btn" data-grade-modal-close>Close</button>
        </div>
    </section>
</div>

<script>
(function () {
    const modal = document.getElementById('gradeStockModal');
    if (!modal) return;

    const modalDialog = modal.querySelector('.grade-modal-dialog');
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
    const divisionBars = document.getElementById('gradeDivisionBars');
    const divisionBarsList = document.getElementById('gradeDivisionBarsList');
    const divisionDefinitions = <?= json_encode($dashboardDivisionBars, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const showDivisionBars = <?= empty($selectedDivision) ? 'true' : 'false' ?>;
    const popupBody = document.getElementById('gradeModalRows');
    const popupHeaders = Array.from(modal.querySelectorAll('.popup-sortable-th'));
    let activeGrade = '';
    let lastFocused = null;
    let popupSortIndex = -1;
    let popupSortDirection = 'asc';

    function dashboardNumber(value) {
        const number = Number(value || 0);
        if (Math.abs(number) >= 1000000) {
            return Math.round(number / 1000000).toLocaleString() + ' Million';
        }
        return Math.round(number).toLocaleString();
    }

    function dashboardStockValue(value) {
        const number = Number(value || 0);

        if (Math.abs(number) >= 1000000) {
            // Keep the real value in Millions instead of rounding to 2 decimals.
            // JavaScript automatically suppresses trailing zeroes.
            return (number / 1000000).toLocaleString(undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: 8
            }) + ' Million';
        }

        return number.toLocaleString(undefined, {
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
            const stats = divisionStats[divisionDef.code] || {batches: 0, qty: 0, value: 0};
            const item = document.createElement('div');
            item.className = 'grade-division-summary-row';
            item.innerHTML =
                '<span class="grade-division-name"><b>' + divisionDef.code + '</b> — ' + divisionDef.name + '</span>' +
                '<strong>' + Number(stats.batches || 0).toLocaleString() + '</strong>' +
                '<strong>' + Math.round(Number(stats.qty || 0)).toLocaleString() + '</strong>' +
                '<strong>Rs/ ' + dashboardStockValue(Number(stats.value || 0)) + '</strong>';
            divisionBarsList.appendChild(item);
        });
    }

    function popupSortDate(value) {
        const m = String(value || '').trim().match(/^(\d{1,2})\/([A-Za-z]{3})\/(\d{4})$/);
        if (!m) return 0;
        const months = {jan:0,feb:1,mar:2,apr:3,may:4,jun:5,jul:6,aug:7,sep:8,oct:9,nov:10,dec:11};
        const month = months[m[2].toLowerCase()];
        return month === undefined ? 0 : new Date(+m[3], month, +m[1]).getTime();
    }
    function dashboardPopupSortValue(row,index,type) {
        if (type === 'qty') return Number(row.dataset.qty || 0);
        if (type === 'value') return Number(row.dataset.value || 0);
        if (type === 'aging') return Number(row.dataset.agingDays || 0);
        if (type === 'remaining') return Number(row.dataset.remainingDays || 0);
        const cell = row.cells[index];
        const value = cell ? cell.textContent.trim() : '';
        if (type === 'date') return popupSortDate(value);
        return value.toLowerCase();
    }
    function sortPopupRows(index,type,direction) {
        const factor = direction === 'desc' ? -1 : 1;
        rows.sort(function(a,b){
            const av=dashboardPopupSortValue(a,index,type), bv=dashboardPopupSortValue(b,index,type);
            const result=(typeof av==='number' && typeof bv==='number') ? av-bv : String(av).localeCompare(String(bv),undefined,{numeric:true,sensitivity:'base'});
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
            resetPopupSort();
            header.setAttribute('aria-sort',popupSortDirection==='asc'?'ascending':'descending');
            const i=header.querySelector('i'); if(i)i.textContent=popupSortDirection==='asc'?'↑':'↓';
            sortPopupRows(index,type,popupSortDirection);
        });
    });

    function applyFilters() {
        const q = search.value.trim().toLowerCase();
        const d = division.value;
        const l = location.value;
        let count = 0, value = 0, totalQty = 0;
        const uomTotals = {};
        const divisionStats = {};

        rows.forEach(function (row) {
            const visible = row.dataset.grade === activeGrade &&
                (!q || row.dataset.search.includes(q)) &&
                (!d || row.dataset.division === d) &&
                (!l || row.dataset.location === l);
            row.hidden = !visible;
            if (visible) {
                count += 1;
                const uom = row.dataset.uom || 'UNSPECIFIED';
                if (!uomTotals[uom]) {
                    uomTotals[uom] = {
                        qty: 0,
                        label: row.dataset.uomLabel || uom
                    };
                }
                const rowQty = parseFloat(row.dataset.qty || '0');
                uomTotals[uom].qty += rowQty;
                totalQty += rowQty;
                value += parseFloat(row.dataset.value || '0');
                const divisionCode = row.dataset.division || '';
                if (divisionCode) {
                    if (!divisionStats[divisionCode]) {
                        divisionStats[divisionCode] = {batches: 0, qty: 0, value: 0};
                    }
                    divisionStats[divisionCode].batches += 1;
                    divisionStats[divisionCode].qty += rowQty;
                    divisionStats[divisionCode].value += parseFloat(row.dataset.value || '0');
                }
            }
        });

        empty.hidden = count !== 0;
        countEl.textContent = count.toLocaleString();
        qtyEl.textContent = Math.round(totalQty).toLocaleString();
        valueEl.textContent = 'Rs/ ' + dashboardStockValue(value);
        uomsEl.innerHTML = '';
        Object.keys(uomTotals).sort().forEach(function (uom) {
            const chip = document.createElement('div');
            chip.className = 'grade-modal-uom-chip grade-modal-uom-chip-full';
            chip.innerHTML = '<span>' + uomTotals[uom].label + '</span><strong>' + dashboardNumber(uomTotals[uom].qty) + '</strong>';
            uomsEl.appendChild(chip);
        });
        resultText.textContent = 'Showing ' + count.toLocaleString() + ' batch' + (count === 1 ? '' : 'es');
        renderDivisionBars(divisionStats);
    }

    function openModal(card) {
        closeHoverFocus();
        activeGrade = card.dataset.grade || '';
        title.textContent = card.dataset.gradeLabel || activeGrade;
        if (modalDialog) modalDialog.setAttribute('data-grade-theme', activeGrade);
        popupSortIndex = -1; popupSortDirection = 'asc'; resetPopupSort();
        search.value = '';
        division.value = '';
        location.value = '';
        applyFilters();
        lastFocused = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        if (!showDivisionBars) window.setTimeout(function () { search.focus(); }, 20);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    const hoverFocusEnabled = <?= $gradeHoverFocusEnabled ? 'true' : 'false' ?>;
    const hoverBackdrop = document.getElementById('gradeHoverFocusBackdrop');
    const hoverPortal = document.getElementById('gradeHoverFocusPortal');
    const hoverPortalCloud = document.getElementById('gradeHoverFocusCloud');
    let hoverFocusCard = null;
    let hoverFocusCloseTimer = null;

    function clearHoverFocusTimer() {
        if (hoverFocusCloseTimer) {
            window.clearTimeout(hoverFocusCloseTimer);
            hoverFocusCloseTimer = null;
        }
    }

    function openHoverFocus(card) {
        if (
            !hoverFocusEnabled ||
            !card ||
            !hoverPortal ||
            !hoverPortalCloud ||
            modal.classList.contains('is-open')
        ) return;

        clearHoverFocusTimer();

        const sourceCloud = card.querySelector('.grade-hover-cloud');
        if (!sourceCloud) return;

        hoverFocusCard = card;

        const cardStyle = window.getComputedStyle(card);
        hoverPortalCloud.style.setProperty(
            '--grade-hover-accent',
            cardStyle.getPropertyValue('--grade-hover-accent').trim() || '#153b7c'
        );
        hoverPortalCloud.style.setProperty(
            '--grade-hover-accent-text',
            cardStyle.getPropertyValue('--grade-hover-accent-text').trim() || '#fff'
        );

        hoverPortalCloud.innerHTML = sourceCloud.innerHTML;
        hoverPortalCloud.setAttribute(
            'aria-label',
            (card.dataset.label || card.dataset.grade || 'Grade') + ' summary'
        );

        hoverFocusCard.classList.add('is-hover-focus-source');

        if (hoverBackdrop) {
            hoverBackdrop.classList.add('is-visible');
            hoverBackdrop.setAttribute('aria-hidden', 'false');
        }

        hoverPortal.classList.add('is-visible');
        hoverPortal.setAttribute('aria-hidden', 'false');

        document.body.classList.add('grade-hover-focus-mode');

        // Browsers intentionally block web pages from moving the user's
        // physical mouse pointer. Give the centered popup immediate focus
        // instead, so keyboard/action focus is moved to the popup.
        window.requestAnimationFrame(function () {
            clearHoverFocusTimer();
            try {
                hoverPortalCloud.focus({preventScroll: true});
            } catch (e) {
                hoverPortalCloud.focus();
            }
        });
    }

    function closeHoverFocus() {
        clearHoverFocusTimer();

        if (hoverFocusCard) {
            hoverFocusCard.classList.remove('is-hover-focus-source');
        }

        hoverFocusCard = null;

        if (hoverBackdrop) {
            hoverBackdrop.classList.remove('is-visible');
            hoverBackdrop.setAttribute('aria-hidden', 'true');
        }

        if (hoverPortal) {
            hoverPortal.classList.remove('is-visible');
            hoverPortal.setAttribute('aria-hidden', 'true');
        }

        if (hoverPortalCloud) {
            hoverPortalCloud.innerHTML = '';
            hoverPortalCloud.removeAttribute('aria-label');
        }

        document.body.classList.remove('grade-hover-focus-mode');
    }

    function scheduleHoverFocusClose(delay) {
        clearHoverFocusTimer();
        hoverFocusCloseTimer = window.setTimeout(closeHoverFocus, delay || 650);
    }

    if (hoverFocusEnabled) {
        cards.forEach(function (card) {
            card.addEventListener('mouseenter', function () {
                openHoverFocus(card);
            });

            card.addEventListener('mouseleave', function () {
                // Allow time for the pointer to move from the card to the
                // centered popup without the popup disappearing immediately.
                scheduleHoverFocusClose(750);
            });

            card.addEventListener('focusin', function () {
                openHoverFocus(card);
            });

            card.addEventListener('focusout', function (event) {
                if (!card.contains(event.relatedTarget)) {
                    scheduleHoverFocusClose(260);
                }
            });
        });

        if (hoverPortalCloud) {
            hoverPortalCloud.addEventListener('mouseenter', clearHoverFocusTimer);
            hoverPortalCloud.addEventListener('focusin', clearHoverFocusTimer);

            hoverPortalCloud.addEventListener('mouseleave', function () {
                scheduleHoverFocusClose(300);
            });

            hoverPortalCloud.addEventListener('click', function (event) {
                event.stopPropagation();

                const card = hoverFocusCard;
                closeHoverFocus();

                if (card) {
                    openModal(card);
                }
            });
        }

        if (hoverBackdrop) {
            hoverBackdrop.addEventListener('click', closeHoverFocus);
        }

        if (hoverPortal) {
            hoverPortal.addEventListener('click', function (event) {
                if (event.target === hoverPortal) closeHoverFocus();
            });
        }
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
        if (event.key !== 'Escape') return;

        if (modal.classList.contains('is-open')) {
            closeModal();
            return;
        }

        if (hoverFocusCard) {
            closeHoverFocus();
        }
    });
})();
</script>
