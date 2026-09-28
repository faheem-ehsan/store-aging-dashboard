<?php
$isEdit = !empty($batch);
$selectedDivisionId = $isEdit
    ? (int)($batch['division_id'] ?? 0)
    : (int)($selectedDivisionId ?? 0);
$divisionLabel = $user['role'] === 'super_admin'
    ? ''
    : (($user['division_code'] ?? '') . ' — ' . ($user['division_name'] ?? ''));
$manufactureDate = $batch['manufacture_date'] ?? '';
$expiryDate = $batch['expiry_date'] ?? '';
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
        <label>Manufacturing Date<input type="date" id="manufacture_date" name="manufacture_date" value="<?= e($manufactureDate) ?>" required></label>
        <label>Expiry Date<input type="date" id="expiry_date" name="expiry_date" value="<?= e($expiryDate) ?>" required></label>
        <label>Aging (Days)<input id="aging_days_preview" value="" readonly tabindex="-1"></label>
        <label>Aging Remaining (Days)<input id="remaining_days_preview" value="" readonly tabindex="-1"></label>
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
    function dateOnly(value) {
        if (!value) return null;
        const parts = value.split('-').map(Number);
        if (parts.length !== 3) return null;
        return Date.UTC(parts[0], parts[1] - 1, parts[2]);
    }
    function update() {
        const today = new Date();
        const todayUtc = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
        const mfgUtc = dateOnly(mfg.value);
        const expUtc = dateOnly(exp.value);
        aging.value = mfgUtc === null ? '' : Math.floor((todayUtc - mfgUtc) / dayMs);
        remaining.value = expUtc === null ? '' : Math.floor((expUtc - todayUtc) / dayMs);
    }
    mfg.addEventListener('change', update);
    exp.addEventListener('change', update);
    update();
})();
</script>
