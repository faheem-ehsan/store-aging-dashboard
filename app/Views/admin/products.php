<?php
$master = $master ?? \App\Controllers\ProductMasterController::MODULE;
$isEdit = !empty($editProduct);
$formDivisionId = $isEdit ? (int)$editProduct['division_id'] : (int)($selectedDivisionId ?? 0);
?>
<section class="product-master-page">
    <div class="product-master-head">
        <div>
            <span class="admin-hub-kicker">SUPER ADMIN</span>
            <h2><?= e($master['title']) ?></h2>
            <p>Maintain the approved <?= e($master['code_label']) ?>, <?= e($master['name_label']) ?> and Unit of Measure for each Division. This master is used only by <?= e($master['used_by']) ?>.</p>
        </div>
        <a class="btn ghost" href="<?= e(url('/admin')) ?>">Back to Administration</a>
    </div>

    <div class="product-master-layout">
        <div class="card product-master-form-card">
            <div class="card-head">
                <div>
                    <h2><?= e(($isEdit ? 'Edit ' : 'Add ') . $master['item']) ?></h2>
                    <p>Configure one approved <?= e($master['item_lower']) ?> for a Division.</p>
                </div>
                <?php if ($isEdit): ?><a class="btn tiny ghost" href="<?= e(url($master['base'] . '?division_id=' . $formDivisionId)) ?>">Cancel Edit</a><?php endif; ?>
            </div>

            <form method="post" action="<?= e(url($master['base'] . ($isEdit ? '/update' : '/store'))) ?>" class="form-stack">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$editProduct['id'] ?>"><?php endif; ?>

                <label>Division
                    <select name="division_id" required>
                        <option value="">Select Division...</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?= (int)$division['id'] ?>" <?= $formDivisionId === (int)$division['id'] ? 'selected' : '' ?>><?= e($division['code']) ?> — <?= e($division['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label><?= e($master['code_label']) ?>
                    <input name="product_code" maxlength="60" value="<?= e($editProduct['product_code'] ?? '') ?>" placeholder="<?= e($master['code_placeholder']) ?>" required>
                </label>

                <label><?= e($master['name_label']) ?>
                    <input name="product_name" maxlength="255" value="<?= e($editProduct['product_name'] ?? '') ?>" placeholder="<?= e($master['name_placeholder']) ?>" required>
                </label>

                <label>Unit of Measure (UoM)
                    <input name="uom" maxlength="20" value="<?= e($editProduct['uom'] ?? '') ?>" placeholder="<?= e($master['uom_placeholder']) ?>" required>
                </label>

                <button class="btn primary" type="submit"><?= e(($isEdit ? 'Save ' : 'Add ') . $master['item']) ?></button>
            </form>

            <div class="product-master-note">
                <strong>Import remains independent</strong>
                <span>CSV import rules, CSV template and existing import behavior are not changed by this <?= e($master['title']) ?>.</span>
            </div>
        </div>

        <div class="card flush product-master-list-card">
            <div class="card-head padded product-master-list-head">
                <div>
                    <h2>Configured <?= e($master['items']) ?></h2>
                    <p><?= number_format(count($products)) ?> <?= e($master['item_lower']) ?>(s) in the current view.</p>
                </div>
            </div>

            <form method="get" action="<?= e(url($master['base'])) ?>" class="product-master-filters">
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search <?= e($master['item_lower']) ?> code, name or UoM...">
                <select name="division_id">
                    <option value="">All Divisions</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?= (int)$division['id'] ?>" <?= (int)$selectedDivisionId === (int)$division['id'] ? 'selected' : '' ?>><?= e($division['code']) ?> — <?= e($division['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn ghost" type="submit">Filter</button>
                <a class="btn ghost" href="<?= e(url($master['base'])) ?>">Reset</a>
            </form>

            <div class="table-wrap">
                <table class="product-master-table">
                    <thead>
                    <tr>
                        <th>Division</th>
                        <th><?= e($master['code_label']) ?></th>
                        <th><?= e($master['name_label']) ?></th>
                        <th>UoM</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="6" class="empty">No <?= e($master['title']) ?> records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><span class="division-tag"><?= e($product['division_code']) ?></span><small class="product-master-division-name"><?= e($product['division_name']) ?></small></td>
                            <td><strong><?= e($product['product_code']) ?></strong></td>
                            <td><?= e($product['product_name']) ?></td>
                            <td><span class="product-master-uom"><?= e($product['uom']) ?></span></td>
                            <td><?= $product['is_active'] ? '<span class="status on">Active</span>' : '<span class="status off">Disabled</span>' ?></td>
                            <td class="actions">
                                <a href="<?= e(url($master['base'] . '?' . http_build_query(['division_id'=>(int)$product['division_id'],'q'=>$search,'edit'=>(int)$product['id']]))) ?>">Edit</a>
                                <form method="post" action="<?= e(url($master['base'] . '/toggle')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                    <button class="link-button" type="submit"><?= $product['is_active'] ? 'Disable' : 'Enable' ?></button>
                                </form>
                                <form method="post" action="<?= e(url($master['base'] . '/delete')) ?>" onsubmit="return confirm(<?= e(json_encode('Remove this ' . $master['item_lower'] . ' from the ' . $master['title'] . '? Existing stock batches will not be deleted.')) ?>)">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
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
</section>
