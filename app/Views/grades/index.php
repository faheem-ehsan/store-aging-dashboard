<div class="card grade-criteria-intro">
    <div class="card-head">
        <div>
            <h2>Division Grading Rules</h2>
            <p>Grade names and colors are common for every division. Grade thresholds are based on days remaining to the Expiry Date and can be configured separately for each division.</p>
        </div>
    </div>
    <div class="grade-rule-flow">
        <div><span class="grade-bar blue"></span><strong>A+</strong><small>Dark Blue · More than the A maximum days remaining</small></div>
        <div><span class="grade-bar green"></span><strong>A</strong><small>Green · Above B maximum through A maximum days remaining</small></div>
        <div><span class="grade-bar yellow"></span><strong>B</strong><small>Yellow · Above C maximum through B maximum days remaining</small></div>
        <div><span class="grade-bar red"></span><strong>C</strong><small>Red · 1 day through C maximum days remaining</small></div>
        <div><span class="grade-bar black"></span><strong>Expired</strong><small>Black · On the Expiry Date and afterwards</small></div>
    </div>
</div>

<form method="post" action="<?= e(url('/grade-criteria/update')) ?>" class="card grade-criteria-card">
    <?= csrf_field() ?>
    <div class="card-head">
        <div>
            <h2>Criteria by Division</h2>
            <p>Enter the maximum remaining shelf-life days for A, B and C. A+ is automatically anything above the A limit.</p>
        </div>
        <button class="btn primary" type="submit">Save Criteria</button>
    </div>
    <div class="table-wrap">
        <table class="grade-criteria-table">
            <thead>
                <tr>
                    <th>Division</th>
                    <th>A+ Criteria</th>
                    <th>A Max (Days Remaining)</th>
                    <th>B Max (Days Remaining)</th>
                    <th>C Max (Days Remaining)</th>
                    <th>Expired</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><span class="division-tag"><?= e($row['code']) ?></span><small class="criteria-division-name"><?= e($row['name']) ?></small></td>
                    <td><strong>Above <?= number_format((int)$row['a_days']) ?> days</strong><small class="criteria-division-name">remaining to Expiry</small></td>
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
    <div class="criteria-note"><strong>Example:</strong> A = 365, B = 180, C = 90 means A+ = more than 365 days remaining, A = 181–365 days, B = 91–180 days, C = 1–90 days, and Expired = 0 days remaining or after the Expiry Date. Values must follow A &gt; B &gt; C.</div>
</form>
