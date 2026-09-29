<div class="import-layout">
    <div class="card import-card">
        <div class="card-head">
            <div>
                <h2>Import Stock Batches from CSV</h2>
                <p>Use one CSV file to add new stock batches. By default, an already-imported Division + Product Code + Batch No. is kept unchanged and skipped. A different Batch No. is inserted as a new batch.</p>
            </div>
            <a class="btn ghost" href="<?= e(url('/stocks/import-template')) ?>">Download CSV Template</a>
        </div>

        <div class="import-notes">
            <strong>Import matching rule</strong>
            <p><b>Default: Add New Batches Only.</b> Same Division + Product Code + Batch No. = <b>skip existing record</b>. Different Batch No. = <b>insert a new batch</b>, even when the Product Code is the same.</p>
            <strong>Required CSV headings</strong>
            <div class="csv-columns">
                <span>Division</span><span>Product Code</span><span>Product Name</span><span>Current Stock</span><span>UoM</span><span>Retail Stock Value</span><span>Batch No</span><span>Manufacturing Date</span><span>Expiry Date</span><span>Aging (Days)</span><span>Aging Remaining (Days)</span><span>Stock Location</span>
            </div>
            <p>This matches the uploaded Stock Aging Report layout. Aging columns may be blank because the system calculates them automatically. Dates can be YYYY-MM-DD or DD.MM.YYYY, and Excel date serials are accepted. The source code SNMW is also accepted as SNW.</p>
            <?php if ($user['role'] !== 'super_admin'): ?>
                <p>Your login is restricted to <b><?= e($user['division_code'] ?? '') ?></b>. The importer automatically assigns every row to this division. A Division column is optional.</p>
            <?php else: ?>
                <p><b>Single-division file:</b> choose a Target Division below. The Division column may then be blank or omitted. <b>Mixed-division file:</b> leave Target Division as “Use Division from CSV” and put JSD, SNW, RTD, etc. in every row.</p>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('/stocks/import')) ?>" enctype="multipart/form-data" class="csv-upload-form">
            <?= csrf_field() ?>

            <label class="csv-target-division csv-import-mode">
                <span>Import Mode</span>
                <select name="import_mode">
                    <option value="new_only" <?= ($_POST['import_mode'] ?? 'new_only') === 'new_only' ? 'selected' : '' ?>>Add New Batches Only (Recommended)</option>
                    <option value="update_existing" <?= ($_POST['import_mode'] ?? '') === 'update_existing' ? 'selected' : '' ?>>Update Existing Batches + Add New</option>
                </select>
                <small>Use “Add New Batches Only” to preserve previously imported stock. Existing Batch Nos. are skipped; only new Batch Nos. are added.</small>
            </label>

            <?php if ($user['role'] === 'super_admin'): ?>
            <label class="csv-target-division">
                <span>Target Division</span>
                <select name="target_division_id">
                    <option value="">Use Division from CSV (mixed divisions)</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?= (int)$division['id'] ?>" <?= (int)($_POST['target_division_id'] ?? ($preselectedDivisionId ?? 0)) === (int)$division['id'] ? 'selected' : '' ?>>
                            <?= e($division['code']) ?> — <?= e($division['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>If your CSV starts with Product Code instead of JSD/SNW/RTD, select the correct division here before importing.</small>
            </label>
            <?php else: ?>
                <input type="hidden" name="target_division_id" value="<?= (int)$user['division_id'] ?>">
                <div class="csv-fixed-division"><span>Target Division</span><strong><?= e($user['division_code'] ?? '') ?></strong></div>
            <?php endif; ?>

            <label class="csv-dropzone">
                <span class="csv-icon">CSV</span>
                <span><b>Select CSV file</b><small>Maximum file size: 5 MB</small></span>
                <input type="file" name="csv_file" accept=".csv,text/csv" required>
            </label>
            <div class="form-actions">
                <a class="btn ghost" href="<?= e(url('/stocks')) ?>">Back to Stock</a>
                <button class="btn primary" type="submit">Import CSV</button>
            </div>
        </form>
    </div>

    <?php if ($result): ?>
    <div class="card import-result-card">
        <div class="card-head"><div><h2>Import Result</h2><p>CSV processing has completed.</p></div></div>
        <div class="import-stats import-stats-four">
            <div><span>New Batches Added</span><strong><?= number_format((int)$result['inserted']) ?></strong></div>
            <div><span>Updated</span><strong><?= number_format((int)$result['updated']) ?></strong></div>
            <div class="skipped"><span>Existing Batches Skipped</span><strong><?= number_format((int)($result['skipped'] ?? 0)) ?></strong></div>
            <div class="failed"><span>Failed</span><strong><?= number_format((int)$result['failed']) ?></strong></div>
        </div>
        <?php if (!empty($result['errors'])): ?>
            <div class="import-errors">
                <strong>Rows requiring attention</strong>
                <ul>
                    <?php foreach ($result['errors'] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="flash success">
                Import completed. New Batch Nos. were added and existing Batch Nos. were preserved according to the selected import mode.
            </div>
        <?php endif; ?>
        <a class="btn primary" href="<?= e(url('/stocks')) ?>">View Imported Stock</a>
    </div>
    <?php endif; ?>
</div>
