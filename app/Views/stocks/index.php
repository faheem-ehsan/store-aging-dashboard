<?php
$stockQtyNumber = static function ($value): string {
    return number_format((int)round((float)$value));
};
$stockValueNumber = static function ($value): string {
    $number = (float)$value;
    if (abs($number) >= 100000) {
        return 'Rs/ ' . number_format((int)round($number / 1000000)) . ' Million';
    }
    return 'Rs/ ' . number_format((int)round($number));
};
$stockValueActual = static function ($value): string {
    return 'Rs/ ' . number_format((int)round((float)$value));
};
$calendarAgingPeriod = static function ($startDate, $endDate): string {
    try {
        $start = new \DateTimeImmutable((string)$startDate);
        $end = new \DateTimeImmutable((string)$endDate);
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
?>
<section class="stock-control-panel" aria-label="Stock filters and actions">
    <div class="stock-control-head">
        <div class="stock-control-heading">
            <span class="stock-control-kicker">Stock View</span>
            <strong>Filter Finish Goods Aging</strong>
        </div>
        <?php if ($canManage): ?>
            <div class="stock-actions stock-control-actions">
                <a class="btn ghost" href="<?= e(url('/stocks/import' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">Import CSV</a>
                <a class="btn primary" href="<?= e(url('/stocks/create' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">+ Add Batch</a>
            </div>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= e(url('/stocks')) ?>" class="stock-filter-grid stock-filter-grid-refined">
        <div class="stock-filter-field stock-filter-search-wrap">
            <label for="stockSearch">Search</label>
            <input id="stockSearch" class="stock-filter-search" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Product code, product name or batch no...">
        </div>

        <?php if ($user['role'] === 'super_admin'): ?>
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
        <span class="criteria"><small>Criteria</small><strong><?php if (!empty($selectedCriteria)): ?>A+ <?= (int)$selectedCriteria['a_plus_days'] ?>d+ · A <?= (int)$selectedCriteria['a_days'] ?>–<?= ((int)$selectedCriteria['a_plus_days'] - 1) ?>d · B <?= (int)$selectedCriteria['b_days'] ?>–<?= ((int)$selectedCriteria['a_days'] - 1) ?>d · C 1–<?= ((int)$selectedCriteria['b_days'] - 1) ?>d<?php else: ?>Varies by division<?php endif; ?></strong></span>
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
    <th>Division</th>
    <th>Product Code</th>
    <th>Product Name</th>
    <th>Current Stock</th>
    <th>UoM</th>
    <th>Retail Stock Value</th>
    <th>Batch No</th>
    <th>Manufacturing Date</th>
    <th>Expiry Date</th>
    <th>Aging</th>
    <th>Expiry Remaining</th>
    <th>Stock Location</th>
    <th>Grade</th>
    <?php if ($canManage): ?><th>Action</th><?php endif; ?>
</tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="14" class="empty">No stock batches found.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<tr>
    <td><span class="division-tag"><?= e($r['division_code']) ?></span></td>
    <td><?= e($r['material_code']) ?></td>
    <td><?= e($r['material_description']) ?></td>
    <td class="num"><?= $stockQtyNumber($r['current_qty']) ?></td>
    <td><?= e($r['uom'] ?? '') ?></td>
    <td class="num"><?= $stockValueActual($r['stock_value'] ?? 0) ?></td>
    <td><?= e($r['batch_no']) ?></td>
    <td><?= e(date('d.m.Y', strtotime($r['manufacture_date']))) ?></td>
    <td><?= e(date('d.m.Y', strtotime($r['expiry_date']))) ?></td>
    <td class="num aging-period"><?= e($calendarAgingPeriod($r['manufacture_date'], $agingReferenceDate)) ?></td>
    <td class="num aging-period <?= $r['remaining_days'] <= 0 ? 'text-danger' : '' ?>"><?= e($calendarAgingPeriod($agingReferenceDate, $r['expiry_date'])) ?></td>
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
</div>
