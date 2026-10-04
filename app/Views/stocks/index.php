<?php
$stockQtyNumber = static function ($value): string {
    return number_format((int)round((float)$value));
};
$stockValueNumber = static function ($value): string {
    $number = (float)$value;
    if (abs($number) >= 100000) {
        $millions = $number / 1000000;
        $formatted = rtrim(rtrim(number_format($millions, 8, '.', ','), '0'), '.');
        return 'Rs/ ' . $formatted . ' Million';
    }
    $formatted = rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    return 'Rs/ ' . $formatted;
};
$stockValueActual = static function ($value): string {
    $number = (float)$value;
    $formatted = rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    return 'Rs/ ' . $formatted;
};
$calendarAgingPeriod = static function ($startDate, $endDate, bool $inclusiveEnd = false): string {
    try {
        $start = new \DateTimeImmutable((string)$startDate);
        $end = new \DateTimeImmutable((string)$endDate);

        // For live Expiry Remaining, include the expiry calendar date itself.
        // We do this by diffing to the day after expiry.
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
$agingReferenceDate = (new \DateTimeImmutable('today'))->format('Y-m-d');
$gradeOptions = [
    'A+' => 'A+ · Dark Blue',
    'A' => 'A · Green',
    'B' => 'B · Yellow',
    'C' => 'C · Red',
    'EX' => 'Expired · Black',
];

// Finish Goods Aging detail-table sorting + pagination.
// Default page size is 50, with 25 / 50 / 100 row presets.
$allowedPageSizes = [25, 50, 100];
$requestedPageSize = (int)($_GET['per_page'] ?? 50);
$rowsPerPage = in_array($requestedPageSize, $allowedPageSizes, true) ? $requestedPageSize : 50;

$sortKey = trim((string)($_GET['sort'] ?? ''));
$sortDir = strtolower(trim((string)($_GET['dir'] ?? 'asc')));
$sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

$sortDefinitions = [
    'division' => ['type' => 'string', 'value' => static fn(array $row) => (string)($row['division_code'] ?? '')],
    'product_code' => ['type' => 'string', 'value' => static fn(array $row) => (string)($row['material_code'] ?? '')],
    'product_name' => ['type' => 'string', 'value' => static fn(array $row) => (string)($row['material_description'] ?? '')],
    'current_stock' => ['type' => 'number', 'value' => static fn(array $row) => (float)($row['current_qty'] ?? 0)],
    'uom' => ['type' => 'string', 'value' => static fn(array $row) => (string)($row['uom'] ?? '')],
    'stock_value' => ['type' => 'number', 'value' => static fn(array $row) => (float)($row['stock_value'] ?? 0)],
    'batch_no' => ['type' => 'string', 'value' => static fn(array $row) => (string)($row['batch_no'] ?? '')],
    'manufacturing_date' => ['type' => 'date', 'value' => static fn(array $row) => (string)($row['manufacture_date'] ?? '')],
    'expiry_date' => ['type' => 'date', 'value' => static fn(array $row) => (string)($row['expiry_date'] ?? '')],
    'aging' => ['type' => 'number', 'value' => static function (array $row) use ($agingReferenceDate): float {
        $manufactureTs = strtotime((string)($row['manufacture_date'] ?? ''));
        $referenceTs = strtotime($agingReferenceDate);
        return ($manufactureTs !== false && $referenceTs !== false) ? (float)(($referenceTs - $manufactureTs) / 86400) : 0.0;
    }],
    'expiry_remaining' => ['type' => 'number', 'value' => static fn(array $row) => (float)($row['remaining_days'] ?? 0)],
    'stock_location' => ['type' => 'string', 'value' => static fn(array $row) => (string)($row['receiving_store'] ?? '')],
    'grade' => ['type' => 'grade', 'value' => static fn(array $row) => (string)($row['grade']['grade_code'] ?? '')],
];

$sortedRows = array_values($rows ?? []);
if ($sortKey !== '' && isset($sortDefinitions[$sortKey])) {
    $definition = $sortDefinitions[$sortKey];
    $direction = $sortDir === 'desc' ? -1 : 1;
    $gradeOrder = ['A+' => 1, 'A' => 2, 'B' => 3, 'C' => 4, 'EX' => 5];

    usort($sortedRows, static function (array $left, array $right) use ($definition, $direction, $gradeOrder): int {
        $leftValue = ($definition['value'])($left);
        $rightValue = ($definition['value'])($right);

        if ($definition['type'] === 'number') {
            $comparison = (float)$leftValue <=> (float)$rightValue;
        } elseif ($definition['type'] === 'date') {
            $leftTs = strtotime((string)$leftValue);
            $rightTs = strtotime((string)$rightValue);
            $comparison = (($leftTs === false ? 0 : $leftTs) <=> ($rightTs === false ? 0 : $rightTs));
        } elseif ($definition['type'] === 'grade') {
            $comparison = (($gradeOrder[(string)$leftValue] ?? 99) <=> ($gradeOrder[(string)$rightValue] ?? 99));
        } else {
            $comparison = strnatcasecmp((string)$leftValue, (string)$rightValue);
        }

        if ($comparison === 0) {
            $comparison = ((int)($left['id'] ?? 0)) <=> ((int)($right['id'] ?? 0));
        }

        return $comparison * $direction;
    });
}

$totalTableRows = count($sortedRows);
$totalPages = max(1, (int)ceil($totalTableRows / $rowsPerPage));
$currentPage = max(1, (int)($_GET['page'] ?? 1));
if ($currentPage > $totalPages) $currentPage = $totalPages;
$pageOffset = ($currentPage - 1) * $rowsPerPage;
$tableRows = array_slice($sortedRows, $pageOffset, $rowsPerPage);
$showingFrom = $totalTableRows > 0 ? $pageOffset + 1 : 0;
$showingTo = min($pageOffset + $rowsPerPage, $totalTableRows);

$baseTableParams = [
    'q' => $filters['q'] ?? '',
    'division_id' => $filters['division_id'] ?? '',
    'grade' => $filters['grade'] ?? '',
    'sort' => $sortKey,
    'dir' => $sortKey !== '' ? $sortDir : '',
    'per_page' => $rowsPerPage,
];
$baseTableParams = array_filter($baseTableParams, static fn($value): bool => $value !== '' && $value !== null);

$paginationUrl = static function (int $page) use ($baseTableParams): string {
    $params = $baseTableParams;
    $params['page'] = $page;
    return url('/stocks') . '?' . http_build_query($params);
};

$sortUrl = static function (string $column) use ($baseTableParams, $sortKey, $sortDir): string {
    $params = $baseTableParams;
    $params['sort'] = $column;
    $params['dir'] = ($sortKey === $column && $sortDir === 'asc') ? 'desc' : 'asc';
    unset($params['page']);
    return url('/stocks') . '?' . http_build_query($params);
};

$sortIndicator = static function (string $column) use ($sortKey, $sortDir): string {
    if ($sortKey !== $column) return '↕';
    return $sortDir === 'asc' ? '↑' : '↓';
};

$sortAria = static function (string $column) use ($sortKey, $sortDir): string {
    if ($sortKey !== $column) return 'none';
    return $sortDir === 'asc' ? 'ascending' : 'descending';
};
?>
<section class="stock-control-panel" aria-label="Stock filters and actions">
    <div class="stock-control-head">
        <div class="stock-control-heading">
            <strong>Search Finish Goods</strong>
        </div>
        <?php if ($canManage): ?>
            <div class="stock-actions stock-control-actions">
                <?php if (($user['role'] ?? '') === 'super_admin'): ?>
                    <button class="btn bulk-delete-trigger" type="button" id="openStockBulkDelete">Bulk Delete</button>
                <?php endif; ?>
                <a class="btn ghost" href="<?= e(url('/stocks/import' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">Import CSV</a>
                <a class="btn primary" href="<?= e(url('/stocks/create' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">+ Add Batch</a>
            </div>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= e(url('/stocks')) ?>" class="stock-filter-grid stock-filter-grid-refined">
        <?php if ($sortKey !== ''): ?><input type="hidden" name="sort" value="<?= e($sortKey) ?>"><?php endif; ?>
        <?php if ($sortKey !== ''): ?><input type="hidden" name="dir" value="<?= e($sortDir) ?>"><?php endif; ?>
        <input type="hidden" name="per_page" value="<?= (int)$rowsPerPage ?>">
        <div class="stock-filter-field stock-filter-search-wrap">
            <label for="stockSearch">Search</label>
            <input id="stockSearch" class="stock-filter-search" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Product code, product name or batch no...">
        </div>

        <?php if ($user['role'] === 'super_admin' || (($user['role'] ?? '') === 'user' && empty($user['division_id']))): ?>
            <div class="stock-filter-field">
                <label for="stockDivision">Division</label>
                <select id="stockDivision" name="division_id">
                    <option value="">All divisions</option>
                    <?php foreach ($divisions as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= (int)$filters['division_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['code']) ?> — <?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="stock-filter-field">
            <label for="stockGrade">Grade</label>
            <select id="stockGrade" name="grade">
                <option value="">All Grades</option>
                <?php foreach ($gradeOptions as $g => $label): ?>
                    <option value="<?= $g ?>" <?= $filters['grade'] === $g ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="stock-filter-actions stock-filter-actions-refined">
            <button class="btn primary" type="submit">Apply Filters</button>
            <a class="btn ghost" href="<?= e(url('/stocks')) ?>">Reset</a>
        </div>
    </form>

    <div class="stock-context-strip" aria-label="Current filter context">
        <span><small>Division</small><strong><?= e($selectedDivisionLabel ?? 'All Divisions') ?></strong></span>
        <span><small>Grade</small><strong><?= e($selectedGradeLabel ?? 'All Grades') ?></strong></span>
        <span class="criteria"><small>Criteria</small><strong><?php if (!empty($gradeCriteria)): ?>A+ <?= e($gradeCriteria['A+']) ?> · A <?= e($gradeCriteria['A']) ?> · B <?= e($gradeCriteria['B']) ?> · C <?= e($gradeCriteria['C']) ?><?php else: ?>Varies by division · based on each batch's own shelf life<?php endif; ?></strong></span>
    </div>
</section>

<section class="stock-overview-grid" aria-label="Filtered stock summary">
    <article class="stock-overview-card qty">
        <div class="stock-overview-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M5 7.5 12 4l7 3.5-7 3.5-7-3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M5 7.5V12l7 3.5 7-3.5V7.5M5 12v4.5L12 20l7-3.5V12" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="stock-overview-copy">
            <span>Stock Quantity</span>
            <strong><?= $stockQtyNumber($summary['current_qty'] ?? 0) ?></strong>
        </div>
    </article>

    <article class="stock-overview-card value">
        <div class="stock-overview-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <rect x="3.5" y="6" width="17" height="12" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                <path d="M7 9.5h.01M17 14.5h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                <circle cx="12" cy="12" r="2.4" stroke="currentColor" stroke-width="1.7"/>
            </svg>
        </div>
        <div class="stock-overview-copy">
            <span>Current Retail Stock Value</span>
            <strong><?= $stockValueNumber($summary['stock_value'] ?? 0) ?></strong>
        </div>
    </article>
</section>

<div class="card flush">
<div class="table-wrap">
<table class="aging-table dispatch-stock-table">
<thead><tr>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('division')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('division')) ?>"><span>Division</span><b><?= e($sortIndicator('division')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('product_code')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('product_code')) ?>"><span>Product Code</span><b><?= e($sortIndicator('product_code')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('product_name')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('product_name')) ?>"><span>Product Name</span><b><?= e($sortIndicator('product_name')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('current_stock')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('current_stock')) ?>"><span>Current Stock</span><b><?= e($sortIndicator('current_stock')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('uom')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('uom')) ?>"><span>UoM</span><b><?= e($sortIndicator('uom')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('stock_value')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('stock_value')) ?>"><span>Retail Stock Value</span><b><?= e($sortIndicator('stock_value')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('batch_no')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('batch_no')) ?>"><span>Batch No</span><b><?= e($sortIndicator('batch_no')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('manufacturing_date')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('manufacturing_date')) ?>"><span>Manufacturing Date</span><b><?= e($sortIndicator('manufacturing_date')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('expiry_date')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('expiry_date')) ?>"><span>Expiry Date</span><b><?= e($sortIndicator('expiry_date')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('aging')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('aging')) ?>"><span>Aging</span><b><?= e($sortIndicator('aging')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('expiry_remaining')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('expiry_remaining')) ?>"><span>Expiry Remaining</span><b><?= e($sortIndicator('expiry_remaining')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('stock_location')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('stock_location')) ?>"><span>Stock Location</span><b><?= e($sortIndicator('stock_location')) ?></b></a></th>
    <th class="stock-sortable-th" aria-sort="<?= e($sortAria('grade')) ?>"><a class="stock-sort-link" href="<?= e($sortUrl('grade')) ?>"><span>Grade</span><b><?= e($sortIndicator('grade')) ?></b></a></th>
    <?php if ($canManage): ?><th>Action</th><?php endif; ?>
</tr></thead>
<tbody>
<?php if (!$tableRows): ?><tr><td colspan="14" class="empty">No stock batches found.</td></tr><?php endif; ?>
<?php foreach ($tableRows as $r): ?>
<tr>
    <td><span class="division-tag"><?= e($r['division_code']) ?></span></td>
    <td><?= e($r['material_code']) ?></td>
    <td><?= e($r['material_description']) ?></td>
    <td class="num"><?= $stockQtyNumber($r['current_qty']) ?></td>
    <td><?= e($r['uom'] ?? '') ?></td>
    <td class="num"><?= $stockValueActual($r['stock_value'] ?? 0) ?></td>
    <td><?= e($r['batch_no']) ?></td>
    <td><?= e(format_app_date($r['manufacture_date'])) ?></td>
    <td><?= e(format_app_date($r['expiry_date'])) ?></td>
    <td class="num aging-period"><?= e($calendarAgingPeriod($r['manufacture_date'], $agingReferenceDate)) ?></td>
    <td class="num aging-period <?= $r['remaining_days'] <= 0 ? 'text-danger' : '' ?>"><?= e($calendarAgingPeriod($agingReferenceDate, $r['expiry_date'], (int)($r['remaining_days'] ?? 0) > 0)) ?></td>
    <td><?= e($r['receiving_store'] ?? '') ?></td>
    <td>
        <span class="grade-bar-wrap" title="<?= e($r['grade']['grade_code']) ?> · <?= e($r['grade']['criteria_text']) ?>">
            <span class="grade-bar <?= e($r['grade']['color_class']) ?>"></span>
            <span class="sr-only">Grade <?= e($r['grade']['grade_code']) ?> - <?= e($r['grade']['grade_name']) ?></span>
        </span>
    </td>
    <?php if ($canManage): ?><td class="actions">
        <a href="<?= e(url('/stocks/edit?id=' . (int)$r['id'])) ?>">Edit</a>
        <form method="post" action="<?= e(url('/stocks/delete')) ?>" onsubmit="return confirm('Delete this batch?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="danger-link" type="submit">Delete</button></form>
    </td><?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php if ($totalTableRows > 0): ?>
<div class="market-return-pagination stock-table-pagination" aria-label="Finish Goods Aging pagination">
    <div class="stock-pagination-summary">
        <div class="market-return-pagination-info">
            Showing <strong><?= number_format($showingFrom) ?>–<?= number_format($showingTo) ?></strong>
            of <strong><?= number_format($totalTableRows) ?></strong> batches
        </div>

        <form method="get" action="<?= e(url('/stocks')) ?>" class="stock-page-size-form" aria-label="Rows per page">
            <?php if (($filters['q'] ?? '') !== ''): ?><input type="hidden" name="q" value="<?= e($filters['q']) ?>"><?php endif; ?>
            <?php if (($filters['division_id'] ?? '') !== ''): ?><input type="hidden" name="division_id" value="<?= e((string)$filters['division_id']) ?>"><?php endif; ?>
            <?php if (($filters['grade'] ?? '') !== ''): ?><input type="hidden" name="grade" value="<?= e($filters['grade']) ?>"><?php endif; ?>
            <?php if ($sortKey !== ''): ?><input type="hidden" name="sort" value="<?= e($sortKey) ?>"><input type="hidden" name="dir" value="<?= e($sortDir) ?>"><?php endif; ?>
            <label for="stockRowsPerPage">Rows per page</label>
            <select id="stockRowsPerPage" name="per_page" onchange="this.form.submit()">
                <?php foreach ($allowedPageSizes as $pageSize): ?>
                    <option value="<?= (int)$pageSize ?>" <?= $rowsPerPage === $pageSize ? 'selected' : '' ?>><?= (int)$pageSize ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination-controls" aria-label="Detail table pages">
        <a class="pagination-btn <?= $currentPage <= 1 ? 'is-disabled' : '' ?>" href="<?= $currentPage > 1 ? e($paginationUrl($currentPage - 1)) : '#' ?>" aria-label="Previous page">Previous</a>

        <?php
        $pageNumbers = [1, $totalPages];
        for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++) {
            $pageNumbers[] = $p;
        }
        $pageNumbers = array_values(array_unique($pageNumbers));
        sort($pageNumbers);
        $previousPrinted = null;
        ?>
        <?php foreach ($pageNumbers as $pageNumber): ?>
            <?php if ($previousPrinted !== null && $pageNumber > $previousPrinted + 1): ?>
                <span class="pagination-ellipsis" aria-hidden="true">…</span>
            <?php endif; ?>
            <a class="pagination-page <?= $pageNumber === $currentPage ? 'is-active' : '' ?>" href="<?= e($paginationUrl($pageNumber)) ?>" <?= $pageNumber === $currentPage ? 'aria-current="page"' : '' ?>><?= number_format($pageNumber) ?></a>
            <?php $previousPrinted = $pageNumber; ?>
        <?php endforeach; ?>

        <a class="pagination-btn <?= $currentPage >= $totalPages ? 'is-disabled' : '' ?>" href="<?= $currentPage < $totalPages ? e($paginationUrl($currentPage + 1)) : '#' ?>" aria-label="Next page">Next</a>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>


<?php if (($user['role'] ?? '') === 'super_admin'): ?>
<div class="stock-bulk-modal" id="stockBulkDeleteModal" hidden>
    <section class="stock-bulk-dialog" role="dialog" aria-modal="true" aria-labelledby="stockBulkDeleteTitle">
        <div class="stock-bulk-head">
            <div>
                <span>SUPER ADMIN</span>
                <h2 id="stockBulkDeleteTitle">Bulk Delete Finish Goods</h2>
                <p>Delete stock batch records by Division.</p>
            </div>
            <button type="button" class="stock-bulk-close" id="closeStockBulkDelete" aria-label="Close">×</button>
        </div>

        <form method="post" action="<?= e(url('/stocks/bulk-delete')) ?>" id="stockBulkDeleteForm">
            <?= csrf_field() ?>

            <div class="stock-bulk-warning">
                <strong>Permanent action</strong>
                <span>This deletes Finish Goods batches for the selected Division(s). Divisions, users, grading criteria, materials and Market Return records remain unchanged.</span>
            </div>

            <div class="stock-bulk-toolbar">
                <div>
                    <strong>Select Division(s)</strong>
                    <span>Only divisions with Finish Goods batches can be selected.</span>
                </div>
                <button type="button" class="stock-bulk-select-all" id="stockBulkSelectAll">Select All With Stock</button>
            </div>

            <div class="stock-bulk-division-list">
                <?php foreach (($bulkDeleteStats ?? []) as $stat): ?>
                    <?php
                    $bulkBatchCount = (int)($stat['batch_count'] ?? 0);
                    $bulkHasStock = $bulkBatchCount > 0;
                    $bulkPreselected = $bulkHasStock
                        && !empty($filters['division_id'])
                        && (int)$filters['division_id'] === (int)$stat['division_id'];
                    ?>
                    <label class="stock-bulk-division-row <?= !$bulkHasStock ? 'is-empty' : '' ?>">
                        <input
                            type="checkbox"
                            name="division_ids[]"
                            value="<?= (int)$stat['division_id'] ?>"
                            <?= $bulkPreselected ? 'checked' : '' ?>
                            <?= !$bulkHasStock ? 'disabled' : '' ?>>
                        <span class="stock-bulk-division-name">
                            <b><?= e($stat['division_code']) ?></b>
                            <small><?= e($stat['division_name']) ?></small>
                        </span>
                        <span class="stock-bulk-stat">
                            <small>Batches</small>
                            <strong><?= number_format($bulkBatchCount) ?></strong>
                        </span>
                        <span class="stock-bulk-stat">
                            <small>Quantity</small>
                            <strong><?= number_format((float)($stat['total_qty'] ?? 0), 0) ?></strong>
                        </span>
                        <span class="stock-bulk-stat stock-bulk-value">
                            <small>Stock Value</small>
                            <strong><?= $stockValueActual($stat['stock_value'] ?? 0) ?></strong>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="stock-bulk-confirm">
                <label for="stockBulkConfirm">
                    <span>Type <b>DELETE</b> to confirm</span>
                    <input
                        id="stockBulkConfirm"
                        name="confirm_text"
                        type="text"
                        autocomplete="off"
                        placeholder="DELETE"
                        maxlength="6"
                        required>
                </label>
            </div>

            <div class="stock-bulk-foot">
                <span id="stockBulkSelectedText">No divisions selected</span>
                <div>
                    <button type="button" class="btn ghost" id="cancelStockBulkDelete">Cancel</button>
                    <button type="submit" class="btn stock-bulk-delete-btn" id="confirmStockBulkDelete" disabled>Delete Selected Records</button>
                </div>
            </div>
        </form>
    </section>
</div>

<script>
(function () {
    const modal = document.getElementById('stockBulkDeleteModal');
    const openBtn = document.getElementById('openStockBulkDelete');
    const closeBtn = document.getElementById('closeStockBulkDelete');
    const cancelBtn = document.getElementById('cancelStockBulkDelete');
    const selectAllBtn = document.getElementById('stockBulkSelectAll');
    const form = document.getElementById('stockBulkDeleteForm');
    const confirmInput = document.getElementById('stockBulkConfirm');
    const submitBtn = document.getElementById('confirmStockBulkDelete');
    const selectedText = document.getElementById('stockBulkSelectedText');

    if (!modal || !openBtn || !form) return;

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
        openBtn.focus();
    }

    openBtn.addEventListener('click', openModal);
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
        const allSelected = checkboxes.length > 0 && checkboxes.every(function (box) { return box.checked; });
        checkboxes.forEach(function (box) { box.checked = !allSelected; });
        selectAllBtn.textContent = allSelected ? 'Select All With Stock' : 'Clear Selection';
        updateState();
    });

    form.addEventListener('submit', function (event) {
        if (submitBtn.disabled) {
            event.preventDefault();
            return;
        }

        const count = selectedBoxes().length;
        if (!window.confirm(
            'Permanently delete all Finish Goods batch records from ' +
            count + ' selected Division' + (count === 1 ? '' : 's') + '?'
        )) {
            event.preventDefault();
        }
    });

    updateState();
})();
</script>
<?php endif; ?>

