<?php $isEdit = !empty($item); ?>
<div class="card market-return-form-card">
    <div class="card-head">
        <div>
            <h2><?= $isEdit ? 'Edit Market Return Item' : 'Add Market Return Item' ?></h2>
            <p>This entry is stored separately from Stock Aging data while using the same division permissions.</p>
        </div>
        <a class="btn ghost" href="<?= e(url('/market-return')) ?>">Back to Market Return Store</a>
    </div>
    <form method="post" action="<?= e(url($isEdit ? '/market-return/update' : '/market-return/store')) ?>" class="market-return-entry-form">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>
        <input type="hidden" name="source_sheet" value="<?= e($item['source_sheet'] ?? 'Manual Entry') ?>">

        <div class="market-return-form-grid">
            <label>Division
                <?php if ($user['role'] === 'super_admin'): ?>
                    <select name="division_id" required>
                        <option value="">Select division</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?= (int)$division['id'] ?>" <?= (int)($item['division_id'] ?? $selectedDivisionId ?? 0) === (int)$division['id'] ? 'selected' : '' ?>><?= e($division['code']) ?> — <?= e($division['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" value="<?= e(($user['division_code'] ?? '') . ' — ' . ($user['division_name'] ?? '')) ?>" disabled>
                    <input type="hidden" name="division_id" value="<?= (int)$user['division_id'] ?>">
                <?php endif; ?>
            </label>
            <label>Category
                <select name="category_code" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $code => $meta): ?>
                        <option value="<?= e($code) ?>" <?= ($item['category_code'] ?? $selectedCategory ?? '') === $code ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="span-2">Product Name / Description
                <input type="text" name="product_name" value="<?= e($item['product_name'] ?? '') ?>" required>
            </label>
            <label>S. No.
                <input type="text" name="serial_no" value="<?= e($item['serial_no'] ?? '') ?>">
            </label>
            <label>Section
                <input type="text" name="section_name" value="<?= e($item['section_name'] ?? '') ?>" placeholder="e.g. Loos Packing / Qarshi Foundation Stock">
            </label>
            <label>Quantity
                <input type="number" step="0.001" min="0" name="quantity" value="<?= e($item['quantity'] ?? '0') ?>" required>
            </label>
            <label>Unit
                <input type="text" name="unit" value="<?= e($item['unit'] ?? 'Nos') ?>" required>
            </label>
            <label>Expiry / Status (as reported)
                <input type="text" name="expiry_raw" value="<?= e($item['expiry_raw'] ?? '') ?>" placeholder="May.2028 / Expired / N.A">
            </label>
            <label>Normalized Expiry Date <small>Optional</small>
                <input type="date" name="expiry_date" value="<?= e($item['expiry_date'] ?? '') ?>">
            </label>
            <label>Retail Price
                <input type="number" step="0.01" min="0" name="retail_price" value="<?= e($item['retail_price'] ?? '') ?>">
            </label>
            <label>Total Amount
                <input type="number" step="0.01" min="0" name="total_amount" value="<?= e($item['total_amount'] ?? '') ?>">
            </label>
            <label>Amount Text <small>For values such as “Not Known”</small>
                <input type="text" name="amount_text" value="<?= e($item['amount_text'] ?? '') ?>">
            </label>
            <label>Report Date
                <input type="date" name="report_date" value="<?= e($item['report_date'] ?? '') ?>">
            </label>
            <label>Additional Quantity Label
                <input type="text" name="aux_label" value="<?= e($item['aux_label'] ?? '') ?>" placeholder="Cartons / Bags">
            </label>
            <label>Additional Quantity Value
                <input type="text" name="aux_value" value="<?= e($item['aux_value'] ?? '') ?>">
            </label>
            <label class="span-2">Remarks
                <textarea name="remarks" rows="4"><?= e($item['remarks'] ?? '') ?></textarea>
            </label>
        </div>
        <div class="form-actions">
            <a class="btn ghost" href="<?= e(url('/market-return')) ?>">Cancel</a>
            <button class="btn primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Add Item' ?></button>
        </div>
    </form>
</div>
