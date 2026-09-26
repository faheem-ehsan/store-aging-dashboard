<div class="card grade-criteria-intro">
    <div class="card-head">
        <div>
            <h2>Division Grading Rules</h2>
            <p>Grade names and colors are common for every division. Manufacture-date thresholds can be configured separately for each division.</p>
        </div>
    </div>
    <div class="grade-rule-flow">
        <div><span class="grade-bar blue"></span><strong>A+</strong><small>Dark Blue · From manufacture date until the A+ day limit</small></div>
        <div><span class="grade-bar green"></span><strong>A</strong><small>Green · After A+ until the A day limit</small></div>
        <div><span class="grade-bar yellow"></span><strong>B</strong><small>Yellow · After A until the B day limit</small></div>
        <div><span class="grade-bar red"></span><strong>C</strong><small>Red · After B until expiry</small></div>
        <div><span class="grade-bar black"></span><strong>Expired</strong><small>Black · On the expiry date and afterwards</small></div>
    </div>
</div>

<form method="post" action="<?= e(url('/grade-criteria/update')) ?>" class="card grade-criteria-card">
    <?= csrf_field() ?>
    <div class="card-head">
        <div>
            <h2>Criteria by Division</h2>
            <p>Enter the required manufacture-age limits in days for each division. There is no fixed maximum day value.</p>
        </div>
        <button class="btn primary" type="submit">Save Criteria</button>
    </div>
    <div class="table-wrap">
        <table class="grade-criteria-table">
            <thead>
                <tr>
                    <th>Division</th>
                    <th>A+ (Days)</th>
                    <th>A (Days)</th>
                    <th>B (Days)</th>
                    <th>C (Days)</th>
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
                            <input type="number" min="1" step="1" name="a_plus_days[<?= (int)$row['division_id'] ?>]" value="<?= (int)$row['a_plus_days'] ?>" required>
                            <span>day(s) from MFG</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input">
                            <input type="number" min="1" step="1" name="a_days[<?= (int)$row['division_id'] ?>]" value="<?= (int)$row['a_days'] ?>" required>
                            <span>day(s) from MFG</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input">
                            <input type="number" min="1" step="1" name="b_days[<?= (int)$row['division_id'] ?>]" value="<?= (int)$row['b_days'] ?>" required>
                            <span>day(s) from MFG</span>
                        </label>
                    </td>
                    <td>
                        <label class="criteria-month-input">
                            <input type="number" min="1" step="1" name="c_days[<?= (int)$row['division_id'] ?>]" value="<?= (int)$row['c_days'] ?>" required>
                            <span>day(s) from MFG</span>
                        </label>
                    </td>
                    <td>Expiry Date and After</td>
                    <td><span class="status <?= $row['is_active'] ? 'on' : 'off' ?>"><?= $row['is_active'] ? 'Active' : 'Disabled' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="criteria-note"><strong>Example:</strong> A+ = 30, A = 60, B = 90, C = 120 means the grade thresholds are calculated by days from Date of Manufacture. Expired always starts on the actual Expiry Date. Values must progress A+ &lt; A &lt; B &lt; C.</div>
</form>
