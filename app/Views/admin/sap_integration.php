<?php
$enabled = !empty($settings['is_enabled']);
$publishEnabled = !empty($settings['publish_to_dashboard']);
$authType = (string)($settings['auth_type'] ?? 'basic');
$lastSyncStatus = strtolower((string)($settings['last_sync_status'] ?? ''));
$lastTestStatus = strtolower((string)($settings['last_test_status'] ?? ''));
$syncBadgeClass = $lastSyncStatus === 'success' ? 'ok' : ($lastSyncStatus === 'failed' ? 'bad' : 'neutral');
$testBadgeClass = $lastTestStatus === 'success' ? 'ok' : ($lastTestStatus === 'failed' ? 'bad' : 'neutral');
?>
<section class="sap-integration-page">
    <div class="sap-hero">
        <div class="sap-hero-copy">
            <span class="admin-hub-kicker">SUPER ADMIN · READ-ONLY SAP INTEGRATION</span>
            <h2>SAP S/4HANA Integration</h2>
            <p>Synchronize Product Master, Batch dates and Material Stock into local staging tables, then publish validated Finished Goods data to the existing Stock Aging Dashboard.</p>
        </div>
        <div class="sap-hero-statuses">
            <span class="sap-status-chip <?= $enabled ? 'ok' : 'neutral' ?>">
                <b><?= $enabled ? 'Enabled' : 'Disabled' ?></b>
                <small>Integration</small>
            </span>
            <span class="sap-status-chip <?= $publishEnabled ? 'ok' : 'neutral' ?>">
                <b><?= $publishEnabled ? 'Publish On' : 'Staging Only' ?></b>
                <small>Dashboard Mode</small>
            </span>
        </div>
    </div>

    <div class="sap-summary-grid">
        <div class="sap-summary-card">
            <small>Last Connection Test</small>
            <strong><?= !empty($settings['last_test_at']) ? e(format_app_datetime($settings['last_test_at'])) : 'Not tested' ?></strong>
            <span class="sap-mini-status <?= e($testBadgeClass) ?>"><?= e($settings['last_test_status'] ?: 'Not run') ?></span>
        </div>
        <div class="sap-summary-card">
            <small>Last SAP Sync</small>
            <strong><?= !empty($settings['last_sync_at']) ? e(format_app_datetime($settings['last_sync_at'])) : 'No successful run yet' ?></strong>
            <span class="sap-mini-status <?= e($syncBadgeClass) ?>"><?= e($settings['last_sync_status'] ?: 'Not run') ?></span>
        </div>
        <div class="sap-summary-card">
            <small>Mapped Stock Rows</small>
            <strong><?= number_format((int)($stagingCounts['mapped'] ?? 0)) ?></strong>
            <span><?= number_format((int)($stagingCounts['unmapped'] ?? 0)) ?> unmapped</span>
        </div>
        <div class="sap-summary-card">
            <small>Current Staging Snapshot</small>
            <strong><?= number_format((int)($stagingCounts['stock'] ?? 0)) ?> Stock</strong>
            <span><?= number_format((int)($stagingCounts['products'] ?? 0)) ?> products · <?= number_format((int)($stagingCounts['batches'] ?? 0)) ?> batches</span>
        </div>
    </div>

    <div class="sap-control-strip">
        <div>
            <strong>Controlled synchronization</strong>
            <span>Test Connection → Preview → Sync Now. Dashboard data is changed only after a complete successful extract and validation.</span>
        </div>
        <div class="sap-control-actions">
            <form method="post" action="<?= e(url('/admin/sap-integration/test')) ?>">
                <?= csrf_field() ?>
                <button class="btn ghost" type="submit">Test Connection</button>
            </form>
            <form method="post" action="<?= e(url('/admin/sap-integration/preview')) ?>">
                <?= csrf_field() ?>
                <button class="btn ghost" type="submit">Preview Sync</button>
            </form>
            <form method="post" action="<?= e(url('/admin/sap-integration/sync')) ?>" onsubmit="return confirm('Run SAP synchronization now? The Dashboard is updated only when Publish to Dashboard is enabled and the full synchronization succeeds.')">
                <?= csrf_field() ?>
                <button class="btn primary" type="submit" <?= !$enabled ? 'disabled' : '' ?>>Sync Now</button>
            </form>
        </div>
    </div>

    <?php if (empty($settings['stock_value_field'])): ?>
    <div class="sap-warning">
        <strong>Total Stock Retail Value is not mapped.</strong>
        <span>SAP stock value will remain Rs/ 0 until Finance / SAP confirms the approved valuation or custom field and you enter it under Field Mapping.</span>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/sap-integration/save')) ?>" class="sap-settings-form">
        <?= csrf_field() ?>

        <section class="sap-panel">
            <div class="sap-panel-head">
                <div>
                    <h3>1. Connection & Security</h3>
                    <p>Use a dedicated read-only SAP technical account or communication arrangement. Credentials are encrypted before being stored.</p>
                </div>
                <label class="sap-switch">
                    <input type="checkbox" name="is_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                    <span>Enable Integration</span>
                </label>
            </div>

            <div class="sap-form-grid">
                <label>Environment Label
                    <input name="environment_label" value="<?= e($settings['environment_label'] ?? 'SAP S/4HANA') ?>" placeholder="e.g. S/4HANA Production">
                </label>

                <label>SAP Base URL
                    <input name="base_url" value="<?= e($settings['base_url'] ?? '') ?>" placeholder="https://sap.example.com">
                </label>

                <label>Authentication
                    <select name="auth_type" id="sapAuthType">
                        <option value="basic" <?= $authType === 'basic' ? 'selected' : '' ?>>Basic Authentication</option>
                        <option value="oauth2" <?= $authType === 'oauth2' ? 'selected' : '' ?>>OAuth 2.0 Client Credentials</option>
                        <option value="api_key" <?= $authType === 'api_key' ? 'selected' : '' ?>>SAP Sandbox API Key</option>
                    </select>
                </label>

                <label>Request Timeout (seconds)
                    <input type="number" min="5" max="180" name="request_timeout_sec" value="<?= (int)($settings['request_timeout_sec'] ?? 30) ?>">
                </label>

                <label>OData Page Size
                    <input type="number" min="1" max="5000" name="page_size" value="<?= (int)($settings['page_size'] ?? 500) ?>">
                </label>

                <label class="sap-checkbox-row">
                    <input type="checkbox" name="verify_ssl" value="1" <?= !empty($settings['verify_ssl']) ? 'checked' : '' ?>>
                    <span><b>Verify HTTPS certificate</b><small>Keep enabled in production.</small></span>
                </label>
            </div>

            <div class="sap-auth-block" data-auth="api_key">
                <div class="sap-form-grid two">
                    <label>API Key Header
                        <input name="api_key_header" value="<?= e($settings['api_key_header'] ?? 'APIKey') ?>" placeholder="APIKey">
                        <small>Use the header name specified by SAP API documentation (e.g. APIKey or x-api-key).</small>
                    </label>
                    <label>Sandbox API Key
                        <input type="password" name="api_key" value="" autocomplete="new-password" placeholder="<?= !empty($settings['api_key_set']) ? 'Saved securely · leave blank to keep' : 'Enter API key privately in deployed dashboard' ?>">
                    </label>
                </div>
            </div>

            <div class="sap-auth-block" data-auth="basic">
                <div class="sap-form-grid two">
                    <label>SAP Username
                        <input name="username" value="<?= e($settings['username'] ?? '') ?>" autocomplete="off">
                    </label>
                    <label>SAP Password
                        <input type="password" name="password" value="" autocomplete="new-password" placeholder="<?= !empty($settings['password_set']) ? 'Saved securely · leave blank to keep' : 'Enter password' ?>">
                    </label>
                </div>
            </div>

            <div class="sap-auth-block" data-auth="oauth2">
                <div class="sap-form-grid">
                    <label>OAuth Token URL
                        <input name="oauth_token_url" value="<?= e($settings['oauth_token_url'] ?? '') ?>" placeholder="https://.../oauth/token">
                    </label>
                    <label>Client ID
                        <input name="client_id" value="<?= e($settings['client_id'] ?? '') ?>" autocomplete="off">
                    </label>
                    <label>Client Secret
                        <input type="password" name="client_secret" value="" autocomplete="new-password" placeholder="<?= !empty($settings['client_secret_set']) ? 'Saved securely · leave blank to keep' : 'Enter client secret' ?>">
                    </label>
                </div>
            </div>
        </section>

        <section class="sap-panel">
            <div class="sap-panel-head">
                <div>
                    <h3>2. SAP API Collection Endpoints</h3>
                    <p>Enter the OData collection/entity-set paths approved by the SAP functional / Basis team. Relative paths are joined to the Base URL.</p>
                </div>
            </div>

            <div class="sap-form-grid">
                <label>Product Master Collection
                    <input name="product_endpoint" value="<?= e($settings['product_endpoint'] ?? '') ?>" placeholder="/sap/opu/odata/sap/API_PRODUCT_SRV/&lt;collection&gt;">
                    <small>Service to evaluate: API_PRODUCT_SRV / approved Product API.</small>
                </label>
                <label>Batch Collection
                    <input name="batch_endpoint" value="<?= e($settings['batch_endpoint'] ?? '') ?>" placeholder="/sap/opu/odata/sap/API_BATCH_SRV/&lt;collection&gt;">
                    <small>Service to evaluate: API_BATCH_SRV.</small>
                </label>
                <label>Material Stock Collection
                    <input name="stock_endpoint" value="<?= e($settings['stock_endpoint'] ?? '') ?>" placeholder="/sap/opu/odata/sap/API_MATERIAL_STOCK_SRV/&lt;collection&gt;">
                    <small>Service to evaluate: API_MATERIAL_STOCK_SRV.</small>
                </label>
                <label>Included Inventory Stock Types
                    <input name="stock_type_filter" value="<?= e($settings['stock_type_filter'] ?? '') ?>" placeholder="e.g. UNRESTRICTED, QUALITY">
                    <small>Optional comma-separated filter. Blank means include all rows returned by the configured endpoint.</small>
                </label>
            </div>
        </section>

        <section class="sap-panel">
            <div class="sap-panel-head">
                <div>
                    <h3>3. SAP Field Mapping</h3>
                    <p>Field names are configurable because exact S/4HANA payloads depend on deployment, release and activated services. Dot paths are supported for nested JSON values.</p>
                </div>
            </div>

            <div class="sap-mapping-columns">
                <div class="sap-field-group">
                    <h4>Product Master</h4>
                    <label>Product Code<input name="product_code_field" value="<?= e($settings['product_code_field'] ?? 'Product') ?>"></label>
                    <label>Product Name<input name="product_name_field" value="<?= e($settings['product_name_field'] ?? 'ProductDescription') ?>"></label>
                    <label>Unit of Measure<input name="product_uom_field" value="<?= e($settings['product_uom_field'] ?? 'BaseUnit') ?>"></label>
                </div>

                <div class="sap-field-group">
                    <h4>Batch Master</h4>
                    <label>Material / Product<input name="batch_material_field" value="<?= e($settings['batch_material_field'] ?? 'Material') ?>"></label>
                    <label>Batch No.<input name="batch_no_field" value="<?= e($settings['batch_no_field'] ?? 'Batch') ?>"></label>
                    <label>Manufacturing Date<input name="manufacture_date_field" value="<?= e($settings['manufacture_date_field'] ?? 'ManufactureDate') ?>"></label>
                    <label>Expiry Date<input name="expiry_date_field" value="<?= e($settings['expiry_date_field'] ?? 'ShelfLifeExpirationDate') ?>"></label>
                    <label>Last Goods Receipt Date<input name="last_receipt_date_field" value="<?= e($settings['last_receipt_date_field'] ?? 'LastGoodsReceiptDate') ?>"></label>
                </div>

                <div class="sap-field-group">
                    <h4>Material Stock</h4>
                    <label>Material / Product<input name="stock_material_field" value="<?= e($settings['stock_material_field'] ?? 'Material') ?>"></label>
                    <label>Batch No.<input name="stock_batch_field" value="<?= e($settings['stock_batch_field'] ?? 'Batch') ?>"></label>
                    <label>Plant<input name="plant_field" value="<?= e($settings['plant_field'] ?? 'Plant') ?>"></label>
                    <label>Storage Location<input name="storage_location_field" value="<?= e($settings['storage_location_field'] ?? 'StorageLocation') ?>"></label>
                    <label>Stock Quantity<input name="quantity_field" value="<?= e($settings['quantity_field'] ?? 'MatlWrhsStkQtyInMatlBaseUnit') ?>"></label>
                    <label>Inventory Stock Type<input name="stock_type_field" value="<?= e($settings['stock_type_field'] ?? 'InventoryStockType') ?>"></label>
                    <label>Stock UoM<input name="stock_uom_field" value="<?= e($settings['stock_uom_field'] ?? 'MaterialBaseUnit') ?>"></label>
                    <label>Retail / Stock Value
                        <input name="stock_value_field" value="<?= e($settings['stock_value_field'] ?? '') ?>" placeholder="Leave blank until Finance/SAP confirms">
                    </label>
                </div>
            </div>
        </section>

        <section class="sap-panel">
            <div class="sap-panel-head">
                <div>
                    <h3>4. Publish Controls</h3>
                    <p>SAP staging is kept separate from the operational dashboard. Enable publishing only after field mapping and division reconciliation are signed off.</p>
                </div>
            </div>

            <label class="sap-publish-option">
                <input type="checkbox" name="publish_to_dashboard" value="1" <?= $publishEnabled ? 'checked' : '' ?>>
                <span>
                    <b>Publish validated SAP stock to Finish Goods Aging</b>
                    <small>Uses the existing grading engine, roles, email alerts and dashboard UI. CSV/Excel import remains available as a fallback channel.</small>
                </span>
            </label>

            <div class="sap-cutover-note">
                <strong>Production cutover control</strong>
                <span>Before enabling Publish, reconcile and remove/resolve any unmatched legacy manual batches for the mapped divisions to avoid double-counting. SAP-managed rows are tagged separately and refreshed as a complete snapshot.</span>
            </div>
        </section>

        <div class="sap-save-bar">
            <span>Saving configuration does not call SAP. Use Test Connection or Preview Sync after saving.</span>
            <button class="btn primary" type="submit">Save SAP Configuration</button>
        </div>
    </form>

    <section class="sap-panel" id="division-mapping">
        <div class="sap-panel-head">
            <div>
                <h3>5. SAP Plant / Storage Location → Dashboard Division</h3>
                <p>Division security remains local. Each SAP stock row is mapped before it can be published to the dashboard.</p>
            </div>
        </div>

        <form method="post" action="<?= e(url('/admin/sap-integration/mapping')) ?>" class="sap-inline-form">
            <?= csrf_field() ?>
            <label>SAP Plant
                <input name="sap_plant" maxlength="40" placeholder="e.g. 1000" required>
            </label>
            <label>Storage Location
                <input name="sap_storage_location" maxlength="40" placeholder="e.g. FG01">
                <small>Leave blank for a plant-level fallback mapping.</small>
            </label>
            <label>Dashboard Division
                <select name="division_id" required>
                    <option value="">Select Division...</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?= (int)$division['id'] ?>"><?= e($division['code']) ?> — <?= e($division['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn primary" type="submit">Save Mapping</button>
        </form>

        <div class="table-wrap sap-table-wrap">
            <table>
                <thead>
                <tr>
                    <th>SAP Plant</th>
                    <th>Storage Location</th>
                    <th>Dashboard Division</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$mappings): ?>
                    <tr><td colspan="5" class="empty">No SAP division mappings configured.</td></tr>
                <?php endif; ?>
                <?php foreach ($mappings as $mapping): ?>
                    <tr>
                        <td><strong><?= e($mapping['sap_plant']) ?></strong></td>
                        <td><?= e($mapping['sap_storage_location'] !== '' ? $mapping['sap_storage_location'] : 'Plant fallback') ?></td>
                        <td><span class="division-tag"><?= e($mapping['division_code']) ?></span> <?= e($mapping['division_name']) ?></td>
                        <td><?= !empty($mapping['is_active']) && !empty($mapping['division_active']) ? '<span class="status on">Active</span>' : '<span class="status off">Inactive</span>' ?></td>
                        <td>
                            <form method="post" action="<?= e(url('/admin/sap-integration/mapping/delete')) ?>" onsubmit="return confirm('Delete this SAP division mapping?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$mapping['id'] ?>">
                                <button class="danger-link" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="sap-panel">
        <div class="sap-panel-head">
            <div>
                <h3>6. Synchronization History</h3>
                <p>Connection tests, previews, manual runs and scheduled runs are recorded for traceability.</p>
            </div>
            <span class="sap-log-count"><?= number_format(count($logs)) ?> recent run(s)</span>
        </div>

        <div class="table-wrap sap-table-wrap">
            <table class="sap-log-table">
                <thead>
                <tr>
                    <th>Started</th>
                    <th>Trigger</th>
                    <th>Status</th>
                    <th>Products</th>
                    <th>Batches</th>
                    <th>Stock Rows</th>
                    <th>Mapped</th>
                    <th>Published</th>
                    <th>Unmapped</th>
                    <th>Initiated By</th>
                    <th>Result</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$logs): ?><tr><td colspan="11" class="empty">No SAP synchronization history yet.</td></tr><?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $status = strtolower((string)$log['status']);
                    $statusClass = in_array($status,['success','test_success','preview'],true)
                        ? 'on'
                        : (str_contains($status,'failed') ? 'off' : '');
                    ?>
                    <tr>
                        <td><?= e(format_app_datetime($log['started_at'])) ?></td>
                        <td><?= e(ucfirst((string)$log['trigger_type'])) ?></td>
                        <td><span class="status <?= e($statusClass) ?>"><?= e($log['status']) ?></span></td>
                        <td><?= number_format((int)$log['product_rows']) ?></td>
                        <td><?= number_format((int)$log['batch_rows']) ?></td>
                        <td><?= number_format((int)$log['stock_rows']) ?></td>
                        <td><?= number_format((int)$log['mapped_stock_rows']) ?></td>
                        <td><?= number_format((int)$log['published_batches']) ?></td>
                        <td><?= number_format((int)$log['unmapped_rows']) ?></td>
                        <td><?= e($log['initiated_by_name'] ?: ($log['trigger_type'] === 'scheduled' ? 'Scheduled Task' : 'System')) ?></td>
                        <td class="sap-log-message" title="<?= e($log['message'] ?? '') ?>"><?= e($log['message'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<script>
(function () {
    const auth = document.getElementById('sapAuthType');
    const blocks = Array.from(document.querySelectorAll('.sap-auth-block'));
    function syncAuth() {
        const value = auth ? auth.value : 'basic';
        blocks.forEach(function (block) {
            block.hidden = block.getAttribute('data-auth') !== value;
        });
    }
    if (auth) auth.addEventListener('change', syncAuth);
    syncAuth();
})();
</script>
