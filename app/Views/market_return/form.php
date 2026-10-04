<?php
$isEdit = !empty($item);
$expiryDateDisplay = format_app_date($item['expiry_date'] ?? '', '');
$reportDateDisplay = format_app_date($item['report_date'] ?? '', '');
?>
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
                <input type="text" name="expiry_raw" value="<?= e($item['expiry_raw'] ?? '') ?>" placeholder="01/Sep/2026 / Expired / N.A">
            </label>
            <label>Normalized Expiry Date <small>Optional</small>
                <input type="text" name="expiry_date" value="<?= e($expiryDateDisplay) ?>" placeholder="DD/MON/YYYY" maxlength="11" pattern="\d{2}/[A-Za-z]{3}/\d{4}" title="Enter date as DD/MON/YYYY, for example 01/Sep/2026">
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
                <input type="text" name="report_date" value="<?= e($reportDateDisplay) ?>" placeholder="DD/MON/YYYY" maxlength="11" pattern="\d{2}/[A-Za-z]{3}/\d{4}" title="Enter date as DD/MON/YYYY, for example 01/Sep/2026">
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

<script>
(function () {
    const fields = document.querySelectorAll('input[name="expiry_date"], input[name="report_date"]');
    const months = {jan:'Jan',feb:'Feb',mar:'Mar',apr:'Apr',may:'May',jun:'Jun',jul:'Jul',aug:'Aug',sep:'Sep',oct:'Oct',nov:'Nov',dec:'Dec'};

    function formatDateWhileTyping(value) {
        const raw = String(value || '')
            .replace(/[^0-9A-Za-z]/g, '')
            .slice(0, 9);

        let day = '';
        let month = '';
        let year = '';

        for (const ch of raw) {
            if (day.length < 2) {
                if (/\d/.test(ch)) day += ch;
                continue;
            }
            if (month.length < 3) {
                if (/[A-Za-z]/.test(ch)) month += ch;
                continue;
            }
            if (year.length < 4 && /\d/.test(ch)) {
                year += ch;
            }
        }

        if (month.length > 0) {
            month = month.charAt(0).toUpperCase() + month.slice(1).toLowerCase();
        }

        let formatted = day;
        if (month.length > 0) formatted += '/' + month;
        if (year.length > 0) formatted += '/' + year;

        return formatted;
    }

    function normalizeDateText(value) {
        const typed = formatDateWhileTyping(value);
        const m = typed.match(/^(\d{2})\/([A-Za-z]{3})\/(\d{4})$/);
        if (!m) return typed;
        const mon = months[m[2].toLowerCase()];
        return mon ? m[1] + '/' + mon + '/' + m[3] : typed;
    }

    fields.forEach(function (field) {
        field.addEventListener('input', function () {
            field.value = formatDateWhileTyping(field.value);
        });
        field.addEventListener('blur', function () {
            field.value = normalizeDateText(field.value);
        });
    });
})();
</script>
