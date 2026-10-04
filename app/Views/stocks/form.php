<?php
$isEdit = !empty($batch);
$selectedDivisionId = $isEdit
    ? (int)($batch['division_id'] ?? 0)
    : (int)($selectedDivisionId ?? 0);
$divisionLabel = $user['role'] === 'super_admin'
    ? ''
    : (($user['division_code'] ?? '') . ' — ' . ($user['division_name'] ?? ''));
$formatFormDate = static function (?string $value): string {
    return format_app_date($value, '');
};
$manufactureDate = $formatFormDate($batch['manufacture_date'] ?? '');
$expiryDate = $formatFormDate($batch['expiry_date'] ?? '');
$divisionCode = $user['role'] === 'super_admin' ? '' : ($user['division_code'] ?? '');
if ($user['role'] === 'super_admin' && $selectedDivisionId > 0) {
    foreach ($divisions as $d) {
        if ((int)$d['id'] === $selectedDivisionId) { $divisionCode = $d['code']; break; }
    }
}
$formHeading = $isEdit ? 'Edit Batch' : (($divisionCode !== '' ? $divisionCode . ' ' : 'Division ') . 'Add Batch');
?>
<div class="card form-card dispatch-form-card">
    <div class="card-head">
        <div>
            <h2><?= e($formHeading) ?></h2>
            <p>Entry layout follows the approved stock-aging import template. Aging values are calculated automatically.</p>
        </div>
        <?php if (!$isEdit): ?><a class="btn ghost" href="<?= e(url('/stocks/import' . ($selectedDivisionId > 0 ? '?division_id=' . $selectedDivisionId : ''))) ?>">Import CSV</a><?php endif; ?>
    </div>
    <form method="post" action="<?= e(url($isEdit ? '/stocks/update' : '/stocks/store')) ?>" class="form-grid dispatch-batch-grid" id="batchForm">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$batch['id'] ?>"><?php endif; ?>

        <?php if ($user['role'] === 'super_admin'): ?>
        <label>Division
            <select name="division_id" required>
                <option value="">Select division...</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= $selectedDivisionId === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['code']) ?> — <?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php else: ?>
        <label>Division<input value="<?= e($divisionLabel) ?>" readonly></label>
        <?php endif; ?>

        <label>Product Code<input name="material_code" value="<?= e($batch['material_code'] ?? '') ?>" required></label>
        <label class="span-2">Product Name<input name="material_description" value="<?= e($batch['material_description'] ?? '') ?>" required></label>
        <label>Current Stock<input type="number" step="0.001" min="0" name="current_qty" value="<?= e($batch['current_qty'] ?? '0') ?>" required></label>
        <label>UoM<input name="uom" value="<?= e($batch['uom'] ?? '') ?>" placeholder="e.g. CS, PCS, BT" required></label>
        <label>Retail Stock Value<input type="number" step="0.01" min="0" name="stock_value" value="<?= e($batch['stock_value'] ?? '0') ?>" required></label>
        <label>Batch No<input name="batch_no" value="<?= e($batch['batch_no'] ?? '') ?>" required></label>
        <label>Manufacturing Date
            <input
                type="text"
                id="manufacture_date"
                name="manufacture_date"
                value="<?= e($manufactureDate) ?>"
                placeholder="DD/MON/YYYY"
                inputmode="text"
                autocomplete="off"
                maxlength="11"
                pattern="\d{2}/[A-Za-z]{3}/\d{4}"
                title="Enter date as DD/MON/YYYY, for example 18/Sep/2026"
                required>
        </label>
        <label>Expiry Date
            <input
                type="text"
                id="expiry_date"
                name="expiry_date"
                value="<?= e($expiryDate) ?>"
                placeholder="DD/MON/YYYY"
                inputmode="text"
                autocomplete="off"
                maxlength="11"
                pattern="\d{2}/[A-Za-z]{3}/\d{4}"
                title="Enter date as DD/MON/YYYY, for example 18/Sep/2027"
                required>
        </label>
        <label>Aging (Days)<input id="aging_days_preview" value="" readonly tabindex="-1"></label>
        <label>Expiry Remaining (Days)<input id="remaining_days_preview" value="" readonly tabindex="-1"></label>
        <label class="span-2">Stock Location<input name="receiving_store" value="<?= e($batch['receiving_store'] ?? '') ?>" placeholder="e.g. FW02" required></label>

        <div class="form-actions span-2">
            <a class="btn ghost" href="<?= e(url('/stocks')) ?>">Cancel</a>
            <button class="btn primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Batch' ?></button>
        </div>
    </form>
</div>
<script>
(function () {
    const mfg = document.getElementById('manufacture_date');
    const exp = document.getElementById('expiry_date');
    const aging = document.getElementById('aging_days_preview');
    const remaining = document.getElementById('remaining_days_preview');
    const dayMs = 86400000;
    const monthMap = {jan:0,feb:1,mar:2,apr:3,may:4,jun:5,jul:6,aug:7,sep:8,oct:9,nov:10,dec:11};
    const monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

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
        const match = typed.match(/^(\d{2})\/([A-Za-z]{3})\/(\d{4})$/);
        if (!match) return typed;
        const monthIndex = monthMap[match[2].toLowerCase()];
        if (monthIndex === undefined) return typed;
        return match[1] + '/' + monthLabels[monthIndex] + '/' + match[3];
    }

    function dateOnly(value) {
        const normalized = normalizeDateText(value);
        const match = normalized.match(/^(\d{2})\/([A-Za-z]{3})\/(\d{4})$/);
        if (!match) return null;
        const day = Number(match[1]);
        const month = monthMap[match[2].toLowerCase()];
        const year = Number(match[3]);
        if (month === undefined) return null;
        const utc = Date.UTC(year, month, day);
        const check = new Date(utc);
        if (check.getUTCFullYear() !== year || check.getUTCMonth() !== month || check.getUTCDate() !== day) return null;
        return utc;
    }

    function update() {
        const today = new Date();
        const todayUtc = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
        const mfgUtc = dateOnly(mfg.value);
        const expUtc = dateOnly(exp.value);
        aging.value = mfgUtc === null ? '' : Math.floor((todayUtc - mfgUtc) / dayMs);

        if (expUtc === null) {
            remaining.value = '';
        } else {
            const rawRemaining = Math.floor((expUtc - todayUtc) / dayMs);

            // Count remaining live calendar days inclusively.
            // Example: 04/Oct through 31/Oct = 28 calendar days.
            // On Expiry Date itself, remaining is 0 because the batch is expired.
            remaining.value = rawRemaining > 0 ? rawRemaining + 1 : rawRemaining;
        }
    }

    [mfg, exp].forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = formatDateWhileTyping(input.value);
            update();
        });
        input.addEventListener('blur', function () {
            input.value = normalizeDateText(input.value);
            update();
        });
    });

    update();
})();
</script>
