<div class="market-return-header">
    <div>
        <div class="market-return-kicker">Independent Market Return Setup</div>
        <h2>Market Return Division Mapping</h2>
        <p>This mapping belongs only to the Market Return Store module. It does not use or change the Stock Aging product/material master.</p>
    </div>
    <div class="market-return-header-actions">
        <a class="btn" href="<?= e(url('/market-return/import')) ?>">Import Market Return File</a>
        <a class="btn ghost" href="<?= e(url('/market-return')) ?>">Back to Module Dashboard</a>
    </div>
</div>

<?php if (!$databaseReady): ?>
    <div class="market-return-install-note">
        <strong>Market Return Division Mapping setup required</strong>
        <p>Import <code>database/migrations/008_add_market_return_division_mapping.sql</code> once in phpMyAdmin.</p>
    </div>
<?php else: ?>
<div class="market-mapping-layout">
    <div class="card market-mapping-form-card">
        <div class="card-head"><div><h2>Add Mapping Rule</h2><p>Use Exact Product for one product, or Contains Keyword to cover a product family.</p></div></div>
        <form method="post" action="<?= e(url('/market-return/mapping/store')) ?>" class="form-stack">
            <?= csrf_field() ?>
            <label>Product Name / Keyword
                <input type="text" name="match_text" placeholder="e.g. QJJ or Sharbat-e-Faulad 240ml" required>
            </label>
            <label>Match Type
                <select name="match_type" required>
                    <option value="exact">Exact Product</option>
                    <option value="contains">Contains Keyword</option>
                </select>
            </label>
            <label>Division
                <select name="division_id" required>
                    <option value="">Choose division</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?= (int)$division['id'] ?>"><?= e($division['code']) ?> — <?= e($division['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn primary" type="submit">Save Mapping</button>
        </form>
        <div class="market-mapping-note">
            <b>How automatic import works</b>
            <span>Division in file → Exact Market Return mapping → Keyword mapping → Previous Market Return history. Stock Aging master is not used.</span>
        </div>
    </div>

    <div class="card market-mapping-list-card">
        <div class="card-head"><div><h2>Saved Mapping Rules</h2><p><?= number_format(count($rows)) ?> rule(s) shown.</p></div></div>
        <form method="get" action="<?= e(url('/market-return/mapping')) ?>" class="market-mapping-filters">
            <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search product, keyword or division...">
            <select name="division_id">
                <option value="">All divisions</option>
                <?php foreach ($divisions as $division): ?>
                    <option value="<?= (int)$division['id'] ?>" <?= (int)($filters['division_id'] ?? 0) === (int)$division['id'] ? 'selected' : '' ?>><?= e($division['code']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="match_type">
                <option value="">All rule types</option>
                <option value="exact" <?= ($filters['match_type'] ?? '') === 'exact' ? 'selected' : '' ?>>Exact Product</option>
                <option value="contains" <?= ($filters['match_type'] ?? '') === 'contains' ? 'selected' : '' ?>>Contains Keyword</option>
            </select>
            <button class="btn" type="submit">Filter</button>
            <a class="btn ghost" href="<?= e(url('/market-return/mapping')) ?>">Reset</a>
        </form>
        <div class="table-wrap">
            <table class="market-mapping-table">
                <thead><tr><th>Rule</th><th>Match</th><th>Division</th><th>Source</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="5" class="empty">No Market Return mapping rules found.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><span class="mapping-type <?= e($row['match_type']) ?>"><?= e($row['match_type'] === 'contains' ? 'Keyword' : 'Exact') ?></span></td>
                        <td><b><?= e($row['match_text']) ?></b></td>
                        <td><span class="division-tag"><?= e($row['division_code']) ?></span> <?= e($row['division_name']) ?></td>
                        <td><?= e(ucfirst((string)$row['source_type'])) ?></td>
                        <td>
                            <form method="post" action="<?= e(url('/market-return/mapping/delete')) ?>" onsubmit="return confirm('Delete this mapping rule?')">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button class="danger-link" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
