<?php
$isEdit = !empty($editProduct);
$formDivisionId = $isEdit ? (int)$editProduct['division_id'] : (int)($selectedDivisionId ?? 0);
?>
<section class="product-master-page">
    <div class="product-master-head">
        <div>
            <span class="admin-hub-kicker">SUPER ADMIN</span>
            <h2>Product Master</h2>
            <p>Maintain the approved Product Code, Product Name and Unit of Measure for each Division. This master is used only by the manual Add Batch form.</p>
        </div>
        <a class="btn ghost" href="<?= e(url('/admin')) ?>">Back to Administration</a>
    </div>

    <div class="product-master-layout">
        <div class="card product-master-form-card">
            <div class="card-head">
                <div>
                    <h2><?= $isEdit ? 'Edit Product' : 'Add Product' ?></h2>
                    <p>Configure one approved product for a Division.</p>
                </div>
                <?php if ($isEdit): ?><a class="btn tiny ghost" href="<?= e(url('/admin/products?division_id=' . $formDivisionId)) ?>">Cancel Edit</a><?php endif; ?>
            </div>

            <form method="post" action="<?= e(url($isEdit ? '/admin/products/update' : '/admin/products/store')) ?>" class="form-stack">
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

                <label>Product Code
                    <input name="product_code" maxlength="60" value="<?= e($editProduct['product_code'] ?? '') ?>" placeholder="e.g. 100324" required>
                </label>

                <label>Product Name
                    <input name="product_name" maxlength="255" value="<?= e($editProduct['product_name'] ?? '') ?>" placeholder="e.g. JS Sparkling 500ml" required>
                </label>

                <label>Unit of Measure (UoM)
                    <input name="uom" maxlength="20" value="<?= e($editProduct['uom'] ?? '') ?>" placeholder="e.g. CS, CTN, BTLS, BOX, EA" required>
                </label>

                <button class="btn primary" type="submit"><?= $isEdit ? 'Save Product' : 'Add Product' ?></button>
            </form>

            <div class="product-master-note">
                <strong>Import remains independent</strong>
                <span>CSV import rules, CSV template and existing import behavior are not changed by this Product Master.</span>
            </div>
        </div>

        <div class="card flush product-master-list-card">
            <div class="card-head padded product-master-list-head">
                <div>
                    <h2>Configured Products</h2>
                    <p><?= number_format(count($products)) ?> product(s) in the current view.</p>
                </div>
            </div>

            <form method="get" action="<?= e(url('/admin/products')) ?>" class="product-master-filters">
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search product code, name or UoM...">
                <select name="division_id">
                    <option value="">All Divisions</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?= (int)$division['id'] ?>" <?= (int)$selectedDivisionId === (int)$division['id'] ? 'selected' : '' ?>><?= e($division['code']) ?> — <?= e($division['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn ghost" type="submit">Filter</button>
                <a class="btn ghost" href="<?= e(url('/admin/products')) ?>">Reset</a>
            </form>

            <div class="table-wrap">
                <table class="product-master-table">
                    <thead>
                    <tr>
                        <th>Division</th>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>UoM</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="6" class="empty">No Product Master records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><span class="division-tag"><?= e($product['division_code']) ?></span><small class="product-master-division-name"><?= e($product['division_name']) ?></small></td>
                            <td><strong><?= e($product['product_code']) ?></strong></td>
                            <td><?= e($product['product_name']) ?></td>
                            <td><span class="product-master-uom"><?= e($product['uom']) ?></span></td>
                            <td><?= $product['is_active'] ? '<span class="status on">Active</span>' : '<span class="status off">Disabled</span>' ?></td>
                            <td class="actions">
                                <a href="<?= e(url('/admin/products?' . http_build_query(['division_id'=>(int)$product['division_id'],'q'=>$search,'edit'=>(int)$product['id']]))) ?>">Edit</a>
                                <form method="post" action="<?= e(url('/admin/products/toggle')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                    <button class="link-button" type="submit"><?= $product['is_active'] ? 'Disable' : 'Enable' ?></button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/products/delete')) ?>" onsubmit="return confirm('Remove this product from the Product Master? Existing stock batches will not be deleted.')">
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
