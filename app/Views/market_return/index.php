<?php
$activeCategory = $filters['category'] ?? '';
$activeExpiryBucket = $filters['expiry_bucket'] ?? '';
$queryBase = [];
if (!empty($filters['division_id'])) $queryBase['division_id'] = (int)$filters['division_id'];
if (!empty($filters['q'])) $queryBase['q'] = $filters['q'];
if (!empty($filters['category'])) $queryBase['category'] = $filters['category'];
if (!empty($filters['value_status'])) $queryBase['value_status'] = $filters['value_status'];
?>
<div class="market-return-header">
    <div>
        <div class="market-return-kicker">Separate Store Module</div>
        <h2>Market Return Store Dashboard</h2>
        <p>Market Return stock is grouped into B, C, Expired and Other / No Expiry using Expiry Date as the reference. Source workbook categories remain available as a separate filter.</p>
    </div>
    <?php if ($canManage && $databaseReady): ?>
        <div class="market-return-header-actions">
            <?php if ($user['role'] === 'super_admin'): ?><a class="btn" href="<?= e(url('/market-return/mapping')) ?>">Division Mapping</a><?php endif; ?>
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
    <form method="get" action="<?= e(url('/market-return')) ?>" class="market-return-filters expiry-enabled">
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
            <option value="">All source categories</option>
            <?php foreach ($categories as $code => $meta): ?>
                <option value="<?= e($code) ?>" <?= $activeCategory === $code ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="expiry_bucket">
            <option value="">All expiry groups</option>
            <?php foreach ($expiryBuckets as $code => $meta): ?>
                <option value="<?= e($code) ?>" <?= $activeExpiryBucket === $code ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
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
        <?php if ($activeExpiryBucket !== '' && isset($expiryBuckets[$activeExpiryBucket])): ?>
            <span>Expiry Group <b><?= e($expiryBuckets[$activeExpiryBucket]['label']) ?></b></span>
        <?php endif; ?>
        <?php if ($activeCategory !== '' && isset($categories[$activeCategory])): ?>
            <span>Source Category <b><?= e($categories[$activeCategory]['label']) ?></b></span>
        <?php endif; ?>
        <span>Visible Lines <b><?= number_format($metrics['lines']) ?></b></span>
    </div>
</div>

<div class="market-return-summary-grid">
    <div class="market-return-summary primary-summary">
        <span>Visible Item Lines</span>
        <strong><?= number_format($metrics['lines']) ?></strong>
        <small><?= number_format($metrics['qty'], 3) ?> total quantity</small>
    </div>
    <div class="market-return-summary value-summary">
        <span>Visible Stock Value</span>
        <strong>Rs/ <?= number_format($metrics['value'], 2) ?></strong>
        <small><?= number_format($metrics['unvalued']) ?> line(s) without known value</small>
    </div>
</div>

<div class="market-return-expiry-intro">
    <strong>Expiry Status</strong>
    <span>B = 1+ year to expiry, C = less than 1 year, Expired = Expiry Date and after. Records without a valid expiry are grouped separately under Other / No Expiry.</span>
</div>

<div class="market-return-expiry-grid">
    <?php foreach ($expiryBuckets as $code => $meta):
        $m = $expiryMetrics[$code] ?? ['lines' => 0, 'qty' => 0.0, 'value' => 0.0, 'unvalued' => 0];
        $params = $queryBase;
        $params['expiry_bucket'] = $code;
        $href = url('/market-return?' . http_build_query($params));
    ?>
        <a class="market-return-expiry-card <?= e($meta['class']) ?> <?= $activeExpiryBucket === $code ? 'active' : '' ?>" href="<?= e($href) ?>">
            <div class="expiry-card-top">
                <span><?= e($meta['label']) ?></span>
                <b><?= number_format($m['lines']) ?> line(s)</b>
            </div>
            <strong><?= number_format($m['qty'], 3) ?></strong>
            <small>Qty</small>
            <em>Rs/ <?= number_format($m['value'], 2) ?> stock value<?= $m['unvalued'] ? ' · ' . number_format($m['unvalued']) . ' unvalued' : '' ?></em>
            <div class="expiry-card-range"><?= e($meta['range']) ?></div>
        </a>
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
            <p>Expiry status is calculated from Expiry Date using B / C / Expired rules; records without a valid expiry remain under Other / No Expiry.</p>
        </div>
        <div class="market-return-table-count"><?= number_format(count($rows)) ?> row(s)</div>
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
                    <td class="num"><?= number_format((float)$row['quantity'], 3) ?></td>
                    <td><?= e($row['unit']) ?></td>
                    <td><?= e($row['expiry_raw'] ?: ($row['expiry_date'] ? date('d.m.Y', strtotime($row['expiry_date'])) : '—')) ?></td>
                    <td><span class="market-return-expiry-tag <?= e($expiryMeta['class']) ?>"><?= e($expiryMeta['short']) ?></span></td>
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
