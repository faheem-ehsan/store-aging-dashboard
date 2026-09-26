<?php
$gradeOptions = [
    'A+' => 'A+ · Dark Blue',
    'A' => 'A · Green',
    'B' => 'B · Yellow',
    'C' => 'C · Red',
    'EX' => 'Expired · Black',
];
?>
<div class="toolbar wrap">
    <form method="get" action="<?= e(url('/stocks')) ?>" class="filter-grid">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Product code, name or batch...">
        <?php if ($user['role'] === 'super_admin'): ?>
            <select name="division_id">
                <option value="">All divisions</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= (int)$filters['division_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['code']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <select name="grade">
            <option value="">All Grades</option>
            <?php foreach ($gradeOptions as $g => $label): ?>
                <option value="<?= $g ?>" <?= $filters['grade'] === $g ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Filter</button>
        <a class="btn ghost" href="<?= e(url('/stocks')) ?>">Reset</a>
    </form>
    <?php if ($canManage): ?>
        <div class="stock-actions">
            <a class="btn ghost" href="<?= e(url('/stocks/import' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">Import CSV</a>
            <a class="btn primary" href="<?= e(url('/stocks/create' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">+ Add Batch</a>
        </div>
    <?php endif; ?>
</div>

<section class="stock-filter-summary" aria-label="Filtered stock summary">
    <div class="stock-summary-context">
        <div>
            <span>Selected Division</span>
            <strong><?= e($selectedDivisionLabel ?? 'All Divisions') ?></strong>
        </div>
        <div>
            <span>Grade Filter</span>
            <strong><?= e($selectedGradeLabel ?? 'All Grades') ?></strong>
        </div>
        <div>
            <span>Grading Criteria</span>
            <strong><?php if (!empty($selectedCriteria)): ?>A+ <?= (int)$selectedCriteria['a_plus_days'] ?>d · A <?= (int)$selectedCriteria['a_days'] ?>d · B <?= (int)$selectedCriteria['b_days'] ?>d · C <?= (int)$selectedCriteria['c_days'] ?>d<?php else: ?>Varies by division<?php endif; ?></strong>
        </div>
    </div>
    <div class="stock-summary-cards">
        <article class="stock-summary-card qty">
            <div class="stock-summary-icon" aria-hidden="true">QTY</div>
            <div>
                <span>Current Stock</span>
                <strong><?= number_format((float)($summary['current_qty'] ?? 0), 3) ?></strong>
                <small><?= number_format((int)($summary['batch_count'] ?? 0)) ?> filtered batch<?= (int)($summary['batch_count'] ?? 0) === 1 ? '' : 'es' ?></small>
            </div>
        </article>
        <article class="stock-summary-card value">
            <div class="stock-summary-icon" aria-hidden="true">VAL</div>
            <div>
                <span>Current Stock Value</span>
                <strong><?= number_format((float)($summary['stock_value'] ?? 0), 2) ?></strong>
                <small>For the selected division and grade filter</small>
            </div>
        </article>
    </div>
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
    <th>Stock Value</th>
    <th>Batch No</th>
    <th>Manufacturing Date</th>
    <th>Expiry Date</th>
    <th>Aging (Days)</th>
    <th>Aging Remaining (Days)</th>
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
    <td class="num"><?= number_format((float)$r['current_qty'], 3) ?></td>
    <td><?= e($r['uom'] ?? '') ?></td>
    <td class="num"><?= number_format((float)($r['stock_value'] ?? 0), 2) ?></td>
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
