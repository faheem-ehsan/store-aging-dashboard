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
    <div class="metric">
        <span>Total Batches</span>
        <strong><?= number_format($metrics['batches']) ?></strong>
        <small>active stock lots</small>
    </div>
    <div class="metric value-total">
        <span>Current Stock Value</span>
        <strong><?= number_format($metrics['stock_value'], 2) ?></strong>
        <small>all selected grades</small>
    </div>
    <div class="metric grade-aplus">
        <span>A+ Stock Qty.</span>
        <strong><?= number_format($metrics['grades']['A+']['qty'], 3) ?></strong>
        <small>Stock Value: <?= number_format($metrics['grades']['A+']['value'], 2) ?> · <?= number_format($metrics['grades']['A+']['batches']) ?> batch(es)</small>
    </div>
    <div class="metric grade-a">
        <span>A Stock Qty.</span>
        <strong><?= number_format($metrics['grades']['A']['qty'], 3) ?></strong>
        <small>Stock Value: <?= number_format($metrics['grades']['A']['value'], 2) ?> · <?= number_format($metrics['grades']['A']['batches']) ?> batch(es)</small>
    </div>
    <div class="metric grade-b">
        <span>B Stock Qty.</span>
        <strong><?= number_format($metrics['grades']['B']['qty'], 3) ?></strong>
        <small>Stock Value: <?= number_format($metrics['grades']['B']['value'], 2) ?> · <?= number_format($metrics['grades']['B']['batches']) ?> batch(es)</small>
    </div>
    <div class="metric grade-c">
        <span>C Stock Qty.</span>
        <strong><?= number_format($metrics['grades']['C']['qty'], 3) ?></strong>
        <small>Stock Value: <?= number_format($metrics['grades']['C']['value'], 2) ?> · <?= number_format($metrics['grades']['C']['batches']) ?> batch(es)</small>
    </div>
    <div class="metric grade-ex">
        <span>Expired Stock Qty.</span>
        <strong><?= number_format($metrics['grades']['EX']['qty'], 3) ?></strong>
        <small>Stock Value: <?= number_format($metrics['grades']['EX']['value'], 2) ?> · <?= number_format($metrics['grades']['EX']['batches']) ?> batch(es)</small>
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
