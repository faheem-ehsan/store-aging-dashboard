USE store_aging;

-- Add a third manufacture-date threshold so the common grades become:
-- A+ (Dark Blue), A (Green), B (Yellow), C (Red), Expired (Black).
-- This block is safe if the column already exists.
SET @b_months_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'division_grade_settings'
      AND COLUMN_NAME = 'b_months'
);
SET @add_b_months_sql := IF(
    @b_months_exists = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN b_months INT UNSIGNED NOT NULL DEFAULT 3 AFTER a_months',
    'SELECT 1'
);
PREPARE add_b_months_stmt FROM @add_b_months_sql;
EXECUTE add_b_months_stmt;
DEALLOCATE PREPARE add_b_months_stmt;

-- Keep existing division settings valid by placing the B cut-off after the A cut-off.
UPDATE division_grade_settings
SET b_months = GREATEST(a_months + 1, 3)
WHERE b_months <= a_months OR b_months IS NULL;

-- Legacy grade_rules are retained for compatibility/display consistency.
UPDATE grade_rules SET color_class='blue', sort_order=1 WHERE grade_code='A+';
UPDATE grade_rules SET color_class='green', sort_order=2 WHERE grade_code='A';
UPDATE grade_rules SET color_class='yellow', sort_order=3 WHERE grade_code='B';
UPDATE grade_rules SET color_class='black', grade_name='Expired', sort_order=5 WHERE grade_code='EX';

INSERT INTO grade_rules (division_id, grade_code, grade_name, min_days, max_days, color_class, sort_order)
SELECT NULL, 'C', 'C Grade', NULL, NULL, 'red', 4
WHERE NOT EXISTS (
    SELECT 1 FROM grade_rules WHERE division_id IS NULL AND grade_code='C'
);
