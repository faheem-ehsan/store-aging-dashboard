<div class="market-return-import-layout">
    <div class="card market-return-import-card">
        <div class="card-head">
            <div>
                <h2>Import Market Return Store Data</h2>
                <p>This importer is completely separate from the Stock Aging CSV importer.</p>
            </div>
            <div class="market-return-template-actions">
                <?php if ($user['role'] === 'super_admin'): ?><a class="btn ghost" href="<?= e(url('/market-return/mapping')) ?>">Division Mapping</a><?php endif; ?>
                <a class="btn ghost" href="<?= e(url('/market-return/import-template')) ?>">Download XLSX Workbook Template</a>
                <a class="btn ghost" href="<?= e(url('/market-return/csv-template')) ?>">Download CSV Template</a>
            </div>
        </div>

        <div class="market-return-import-explainer">
            <strong>Workbook mode</strong>
            <p>Upload the same multi-sheet format you supplied. Recognized sheets are Cabinets, Export Stock, Promotion Items, RawalKot stock, Tablet Strips, Fresh Saleable Stock final, Utilizable Stock and Expired.</p>
            <strong>Automatic division and category assignment</strong>
            <?php if ($user['role'] === 'super_admin'): ?>
                <p>No Target Division selection is required. The importer assigns every row automatically. It first uses a Division column when present, then the independent Market Return Division Mapping, then prior Market Return assignments. The Stock Aging product/material master is not used by this module.</p>
            <?php else: ?>
                <p>Your login is locked to <b><?= e(($user['division_code'] ?? '') . ' — ' . ($user['division_name'] ?? '')) ?></b>. All imported rows are automatically assigned to this division.</p>
            <?php endif; ?>
            <strong>Safety</strong>
            <p>If Super Admin uploads a row that cannot be matched to one unique division, that row is not guessed. It is shown in the import result as an unmatched item so it cannot be stored under the wrong division.</p>
            <p><b>CSV date format:</b> DD.MM.YYYY only for Expiry and Report Date, for example 31.08.2027 and 24.09.2026. XLSX imports keep their existing flexible date handling.</p>
            <p><b>CSV Category and Section are automatic.</b> S. No. is not required. Any new Category or Section value in the CSV is accepted automatically and stored as a new group. If Category is written once at the start of a block, following blank category cells can inherit it.</p>
        </div>

        <form method="post" action="<?= e(url('/market-return/import')) ?>" enctype="multipart/form-data" class="market-return-import-form">
            <?= csrf_field() ?>
            <div class="auto-import-assignment-grid">
                <div class="auto-division-status">
                    <span class="auto-division-icon">AUTO</span>
                    <span>
                        <b>Division Assignment</b>
                        <small><?= $user['role'] === 'super_admin' ? 'Detected from file / Market Return mapping' : e(($user['division_code'] ?? '') . ' — ' . ($user['division_name'] ?? '')) ?></small>
                    </span>
                </div>
                <div class="auto-category-status">
                    <span class="auto-category-icon">AUTO</span>
                    <span>
                        <b>Category Assignment</b>
                        <small>XLSX: detected from sheet name · CSV: detected from Category/Source Sheet column or filename</small>
                    </span>
                </div>
            </div>

            <label class="market-return-dropzone">
                <span class="market-return-file-icon">XLSX</span>
                <span><b>Select Market Return Store file</b><small>.xlsx multi-sheet workbook or .csv · maximum 15 MB</small></span>
                <input type="file" name="market_return_file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
            </label>

            <label class="market-return-replace-option">
                <input type="checkbox" name="replace_existing" value="1" <?= !isset($_POST['replace_existing']) || $_POST['replace_existing'] === '1' ? 'checked' : '' ?>>
                <span><b>Replace existing records for the identified Division + Category + Section groups</b><small>A new Category or Section is added as a new group. Existing data in other Sections is not removed.</small></span>
            </label>

            <div class="form-actions">
                <a class="btn ghost" href="<?= e(url('/market-return')) ?>">Back to Module Dashboard</a>
                <button class="btn primary" type="submit">Import Market Return Data</button>
            </div>
        </form>
    </div>

    <?php if ($result): ?>
    <div class="card market-return-import-result">
        <div class="card-head"><div><h2>Import Result</h2><p>Market Return Store file processing has completed.</p></div></div>
        <?php if (!empty($result['errors'])): ?>
            <div class="import-errors">
                <strong>Import could not be completed</strong>
                <ul><?php foreach ($result['errors'] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php else: ?>
            <div class="market-return-import-stats market-return-import-stats-auto">
                <div><span>Imported Rows</span><strong><?= number_format((int)$result['inserted']) ?></strong></div>
                <div><span>Divisions Detected</span><strong><?= number_format(count($result['division_stats'] ?? [])) ?></strong></div>
                <div><span>Unmatched Rows</span><strong><?= number_format(count($result['unmatched'] ?? [])) ?></strong></div>
                <div><span>Replaced Previous Rows</span><strong><?= number_format((int)$result['deleted_previous']) ?></strong></div>
            </div>

            <?php if (!empty($result['division_stats'])): ?>
                <div class="auto-division-result-block">
                    <h3>Automatic Division Assignment</h3>
                    <div class="table-wrap">
                        <table class="auto-division-result-table">
                            <thead><tr><th>Division</th><th>Assigned Rows</th><th>Imported</th><th>Categories</th><th>Detection Method</th></tr></thead>
                            <tbody>
                            <?php foreach ($result['division_stats'] as $stat): ?>
                                <?php
                                $methodLabels = [];
                                foreach (($stat['methods'] ?? []) as $method => $count) {
                                    $label = match ($method) {
                                        'file_division' => 'File Division',
                                        'previous_market_return' => 'Previous Mapping',
                                        'market_return_mapping' => 'Market Return Exact Mapping',
                                        'market_return_keyword' => 'Market Return Keyword Rule',
                                        'login_division' => 'Login Division',
                                        default => ucwords(str_replace('_', ' ', $method)),
                                    };
                                    $methodLabels[] = $label . ': ' . number_format((int)$count);
                                }
                                ?>
                                <tr>
                                    <td><span class="division-tag"><?= e($stat['division_code']) ?></span> <?= e($stat['division_name']) ?></td>
                                    <td class="num"><?= number_format((int)$stat['rows']) ?></td>
                                    <td class="num"><?= number_format((int)($stat['inserted'] ?? 0)) ?></td>
                                    <td><?= e(implode(', ', $stat['categories'] ?? [])) ?></td>
                                    <td><?= e(implode(' · ', $methodLabels)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($result['warnings'])): ?>
                <div class="market-return-import-warnings"><strong>Import notes</strong><ul><?php foreach ($result['warnings'] as $warning): ?><li><?= e($warning) ?></li><?php endforeach; ?></ul></div>
            <?php else: ?>
                <div class="flash success">The Market Return Store data was imported successfully and assigned to divisions automatically.</div>
            <?php endif; ?>

            <?php if (!empty($result['unmatched'])): ?>
                <div class="auto-division-unmatched">
                    <h3>Rows Not Imported — Division Could Not Be Identified</h3>
                    <p>These rows were left out rather than being assigned to the wrong division. Add a Division column to the source file or configure the product/keyword under Market Return Division Mapping, then import again.</p>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Source Sheet</th><th>Product</th><th>Division in File</th><th>Reason</th></tr></thead>
                            <tbody>
                            <?php foreach (array_slice($result['unmatched'], 0, 100) as $row): ?>
                                <tr>
                                    <td><?= e($row['source_sheet'] ?? '') ?></td>
                                    <td><?= e($row['product_name'] ?? '') ?></td>
                                    <td><?= e($row['division_hint'] ?? '') ?: '—' ?></td>
                                    <td><?= e($row['reason'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($result['unmatched']) > 100): ?><small>Showing the first 100 unmatched rows.</small><?php endif; ?>
                </div>
            <?php endif; ?>

            <a class="btn primary" href="<?= e(url('/market-return' . (!empty($result['division']['id']) ? '?division_id=' . (int)$result['division']['id'] : ''))) ?>">View Imported Market Return Data</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
