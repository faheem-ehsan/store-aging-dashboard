<div class="card grade-criteria-intro">
    <div class="card-head">
        <div>
            <h2>Division Grading Rules</h2>
            <p>Grade names and colors are common for every division. Every grade day value can be entered separately for each division and is based on days remaining to the Expiry Date.</p>
        </div>
    </div>
    <div class="grade-rule-flow">
        <div><span class="grade-bar blue"></span><strong>A+</strong><small>Dark Blue · User-defined A+ remaining-day threshold</small></div>
        <div><span class="grade-bar green"></span><strong>A</strong><small>Green · User-defined A remaining-day threshold</small></div>
        <div><span class="grade-bar yellow"></span><strong>B</strong><small>Yellow · User-defined B remaining-day threshold</small></div>
        <div><span class="grade-bar red"></span><strong>C</strong><small>Red · User-defined C remaining-day threshold; remains C until Expiry</small></div>
        <div><span class="grade-bar black"></span><strong>Expired</strong><small>Black · On the Expiry Date and afterwards</small></div>
    </div>
</div>

<form method="post" action="<?= e(url('/grade-criteria/update')) ?>" class="card grade-criteria-card">
    <?= csrf_field() ?>
    <div class="card-head">
        <div>
            <h2>Criteria by Division</h2>
            <p>Enter the required remaining-expiry days for A+, A, B and C. A+ is no longer fixed or calculated automatically.</p>
        </div>
        <button class="btn primary" type="submit">Save Criteria</button>
    </div>
    <div class="table-wrap">
        <table class="grade-criteria-table">
            <thead>
                <tr>
                    <th>Division</th>
                    <th>A+ (Days Remaining)</th>
                    <th>A (Days Remaining)</th>
                    <th>B (Days Remaining)</th>
                    <th>C (Days Remaining)</th>
                    <th>Expired</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><span class="division-tag"><?= e($row['code']) ?></span><small class="criteria-division-name"><?= e($row['name']) ?></small></td>
                    <td>
                        <label class="criteria-month-input">
                            <input type="number" min="4" step="1" name="a_plus_days[<?= (int)$row['division_id'] ?>]" value="<?= (int)$row['a_plus_days'] ?>" required>
                            <span>days remaining</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input">
                            <input type="number" min="3" step="1" name="a_days[<?= (int)$row['division_id'] ?>]" value="<?= (int)$row['a_days'] ?>" required>
                            <span>days remaining</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input">
                            <input type="number" min="2" step="1" name="b_days[<?= (int)$row['division_id'] ?>]" value="<?= (int)$row['b_days'] ?>" required>
                            <span>days remaining</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input">
                            <input type="number" min="1" step="1" name="c_days[<?= (int)$row['division_id'] ?>]" value="<?= (int)$row['c_days'] ?>" required>
                            <span>days remaining</span>
                        </label>
                    </td>
                    <td><strong>0 days or less</strong><small class="criteria-division-name">Expiry Date and after</small></td>
                    <td><span class="status <?= $row['is_active'] ? 'on' : 'off' ?>"><?= $row['is_active'] ? 'Active' : 'Disabled' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="criteria-note"><strong>Rule:</strong> Enter thresholds in descending order: A+ &gt; A &gt; B &gt; C. Example: A+ = 365, A = 270, B = 180, C = 90. Expired always remains tied to the actual Expiry Date (0 days or less). If a product has fewer positive days than the C threshold, it remains C until the Expiry Date.</div>
</form>
