<?php
$fmtPct = static function ($value): string {
    $number = (float)$value;
    if (abs($number - round($number)) < 0.00001) return (string)(int)round($number);
    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
};
?>

<div class="card grade-criteria-intro">
    <div class="card-head">
        <div>
            <h2>Division Grading Rules</h2>
            <p>Grade names and colors remain common for every division. Grading is calculated from the percentage of each batch's own shelf life already used, based on Manufacturing Date and Expiry Date.</p>
        </div>
    </div>
    <div class="grade-rule-flow">
        <div><span class="grade-bar blue"></span><strong>A+</strong><small>Dark Blue · Freshest shelf-life range</small></div>
        <div><span class="grade-bar green"></span><strong>A</strong><small>Green · Good shelf-life range</small></div>
        <div><span class="grade-bar yellow"></span><strong>B</strong><small>Yellow · Aging shelf-life range</small></div>
        <div><span class="grade-bar red"></span><strong>C</strong><small>Red · Critical shelf-life range until Expiry</small></div>
        <div><span class="grade-bar black"></span><strong>Expired</strong><small>Black · On the Expiry Date and afterwards</small></div>
    </div>
</div>

<form method="post" action="<?= e(url('/grade-criteria/update')) ?>" class="card grade-criteria-card">
    <?= csrf_field() ?>
    <div class="card-head">
        <div>
            <h2>Criteria by Division</h2>
            <p>Enter the maximum shelf-life-used percentage for A+, A, B and C. Recommended starting criteria are 25%, 50%, 75% and 100%.</p>
        </div>
        <button class="btn primary" type="submit">Save Criteria</button>
    </div>

    <div class="table-wrap">
        <table class="grade-criteria-table shelf-percent-criteria-table">
            <thead>
                <tr>
                    <th>Division</th>
                    <th>A+ (Max Used %)</th>
                    <th>A (Max Used %)</th>
                    <th>B (Max Used %)</th>
                    <th>C (Max Used %)</th>
                    <th>Expired</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <span class="division-tag"><?= e($row['code']) ?></span>
                        <small class="criteria-division-name"><?= e($row['name']) ?></small>
                    </td>
                    <td>
                        <label class="criteria-month-input criteria-percent-input">
                            <input type="number" min="0" max="100" step="0.01" name="a_plus_used_pct[<?= (int)$row['division_id'] ?>]" value="<?= e($fmtPct($row['a_plus_used_pct'])) ?>" required>
                            <span>% shelf life used</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input criteria-percent-input">
                            <input type="number" min="0" max="100" step="0.01" name="a_used_pct[<?= (int)$row['division_id'] ?>]" value="<?= e($fmtPct($row['a_used_pct'])) ?>" required>
                            <span>% shelf life used</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input criteria-percent-input">
                            <input type="number" min="0" max="100" step="0.01" name="b_used_pct[<?= (int)$row['division_id'] ?>]" value="<?= e($fmtPct($row['b_used_pct'])) ?>" required>
                            <span>% shelf life used</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input criteria-percent-input">
                            <input type="number" min="0" max="100" step="0.01" name="c_used_pct[<?= (int)$row['division_id'] ?>]" value="<?= e($fmtPct($row['c_used_pct'])) ?>" required>
                            <span>% shelf life used</span>
                        </label>
                    </td>
                    <td>
                        <strong>Expiry Date reached</strong>
                        <small class="criteria-division-name">Expiry Date and after</small>
                    </td>
                    <td><span class="status <?= $row['is_active'] ? 'on' : 'off' ?>"><?= $row['is_active'] ? 'Active' : 'Disabled' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="criteria-note">
        <strong>Rule:</strong> Thresholds must be in ascending order: A+ &lt; A &lt; B &lt; C. Example: A+ = 25%, A = 50%, B = 75%, C = 100%. The percentage is calculated independently for every batch from its Manufacturing Date and Expiry Date, so products in the same division can have different shelf lives. Expired always remains tied to the actual Expiry Date.
    </div>
</form>
