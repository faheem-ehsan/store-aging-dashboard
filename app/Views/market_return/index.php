<?php
$activeCategory = $filters['category'] ?? '';
$queryBase = [];
if (!empty($filters['division_id'])) $queryBase['division_id'] = (int)$filters['division_id'];
if (!empty($filters['q'])) $queryBase['q'] = $filters['q'];
if (!empty($filters['value_status'])) $queryBase['value_status'] = $filters['value_status'];
?>
<div class="market-return-header">
    <div>
        <div class="market-return-kicker">Separate Store Module</div>
        <h2>Market Return Store Dashboard</h2>
        <p>Cabinets, export, promotion, RawalKot, loose items, fresh saleable, utilizable and expired stock are managed independently from Stock Aging.</p>
    </div>
    <?php if ($canManage && $databaseReady): ?>
        <div class="market-return-header-actions">
            <a class="btn" href="<?= e(url('/market-return/import' . (!empty($filters['division_id']) ? '?division_id=' . (int)$filters['division_id'] : ''))) ?>">Import Market Return File</a>
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
<div class="market-return-toolbar card">
    <form method="get" action="<?= e(url('/market-return')) ?>" class="market-return-filters">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Product, remarks, section, expiry...">
        <?php if ($user['role'] === 'super_admin'): ?>
            <select name="division_id">
                <option value="">All divisions</option>
                <?php foreach ($divisions as $division): ?>
                    <option value="<?= (int)$division['id'] ?>" <?= (int)($filters['division_id'] ?? 0) === (int)$division['id'] ? 'selected' : '' ?>><?= e($division['code']) ?> — <?= e($division['name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <select name="category">
            <option value="">All Market Return categories</option>
            <?php foreach ($categories as $code => $meta): ?>
                <option value="<?= e($code) ?>" <?= $activeCategory === $code ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="value_status">
            <option value="">All valuation statuses</option>
            <option value="valued" <?= ($filters['value_status'] ?? '') === 'valued' ? 'selected' : '' ?>>Value available</option>
            <option value="unvalued" <?= ($filters['value_status'] ?? '') === 'unvalued' ? 'selected' : '' ?>>Value not available</option>
        </select>
        <button class="btn primary" type="submit">Apply Filters</button>
        <a class="btn ghost" href="<?= e(url('/market-return')) ?>">Reset</a>
    </form>
    <div class="market-return-filter-context">
        <span>Division <b><?= e($selectedDivisionLabel) ?></b></span>
        <span>Lines <b><?= number_format($metrics['lines']) ?></b></span>
    </div>
</div>

<div class="market-return-summary-grid">
    <div class="market-return-summary primary-summary">
        <span>Total Item Lines</span>
        <strong><?= number_format($metrics['lines']) ?></strong>
        <small><?= number_format($metrics['qty'], 3) ?> total quantity</small>
    </div>
    <div class="market-return-summary value-summary">
        <span>Known Stock Value</span>
        <strong>Rs/ <?= number_format($metrics['value'], 2) ?></strong>
        <small><?= number_format($metrics['unvalued']) ?> line(s) without known value</small>
    </div>
</div>

<div class="market-return-category-grid">
    <?php foreach ($categories as $code => $meta):
        $m = $metrics['categories'][$code];
        $params = $queryBase;
        $params['category'] = $code;
        $href = url('/market-return?' . http_build_query($params));
    ?>
        <a class="market-return-category-card <?= e($meta['class']) ?> <?= $activeCategory === $code ? 'active' : '' ?>" href="<?= e($href) ?>">
            <div class="category-card-top"><span><?= e($meta['label']) ?></span><b><?= number_format($m['lines']) ?> lines</b></div>
            <strong><?= number_format($m['qty'], 3) ?></strong>
            <small>Qty · Rs/ <?= number_format($m['value'], 2) ?> known value<?= $m['unvalued'] ? ' · ' . number_format($m['unvalued']) . ' unvalued' : '' ?></small>
        </a>
    <?php endforeach; ?>
</div>

<div class="card market-return-table-card">
    <div class="card-head">
        <div>
            <h2><?= $activeCategory !== '' && isset($categories[$activeCategory]) ? e($categories[$activeCategory]['label']) : 'Market Return Store Items' ?></h2>
            <p>Data shown here belongs only to the Market Return Store module and follows your uploaded workbook structure.</p>
        </div>
        <div class="market-return-table-count"><?= number_format(count($rows)) ?> row(s)</div>
    </div>
    <div class="table-wrap">
        <table class="market-return-table">
            <thead>
                <tr>
                    <th>Division</th>
                    <th>Category</th>
                    <th>S. No.</th>
                    <th>Product / Description</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Expiry</th>
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
                <tr><td colspan="13" class="empty">No Market Return Store records found for the selected filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row):
                $meta = $categories[$row['category_code']] ?? ['label' => $row['category_code'], 'class' => 'default'];
            ?>
                <tr>
                    <td><span class="division-tag"><?= e($row['division_code']) ?></span></td>
                    <td><span class="market-return-category-tag <?= e($meta['class']) ?>"><?= e($meta['label']) ?></span></td>
                    <td><?= e($row['serial_no'] ?? '') ?></td>
                    <td class="market-return-product"><?= e($row['product_name']) ?></td>
                    <td class="num"><?= number_format((float)$row['quantity'], 3) ?></td>
                    <td><?= e($row['unit']) ?></td>
                    <td><?= e($row['expiry_raw'] ?: ($row['expiry_date'] ? date('d.m.Y', strtotime($row['expiry_date'])) : '—')) ?></td>
                    <td class="num"><?= $row['retail_price'] !== null ? 'Rs/ ' . number_format((float)$row['retail_price'], 2) : '—' ?></td>
                    <td class="num"><?php if ($row['total_amount'] !== null): ?>Rs/ <?= number_format((float)$row['total_amount'], 2) ?><?php elseif (!empty($row['amount_text'])): ?><?= e($row['amount_text']) ?><?php else: ?>—<?php endif; ?></td>
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
<?php endif; ?>
