USE store_aging;

-- Change the configurable manufacture-age criteria from months to days.
-- Existing month settings are kept for backward compatibility, but the application
-- uses the four day thresholds after this migration.

SET @has_a_plus_days := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'a_plus_days'
);
SET @has_a_days := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'a_days'
);
SET @has_b_days := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'b_days'
);
SET @has_c_days := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'c_days'
);
SET @day_upgrade_needed := IF((@has_a_plus_days + @has_a_days + @has_b_days + @has_c_days) < 4, 1, 0);

SET @sql := IF(@has_a_plus_days = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN a_plus_days INT UNSIGNED NOT NULL DEFAULT 30 AFTER b_months',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@has_a_days = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN a_days INT UNSIGNED NOT NULL DEFAULT 60 AFTER a_plus_days',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@has_b_days = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN b_days INT UNSIGNED NOT NULL DEFAULT 90 AFTER a_days',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@has_c_days = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN c_days INT UNSIGNED NOT NULL DEFAULT 120 AFTER b_days',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- On the first upgrade only, convert the existing month thresholds to initial day
-- thresholds. Re-running this migration later will not overwrite day values edited
-- by Super Admin.
SET @sql := IF(@day_upgrade_needed = 1,
    'UPDATE division_grade_settings SET a_plus_days = GREATEST(1, COALESCE(a_plus_months, 1) * 30), a_days = GREATEST(COALESCE(a_plus_months, 1) * 30 + 1, COALESCE(a_months, 2) * 30), b_days = GREATEST(COALESCE(a_months, 2) * 30 + 1, COALESCE(b_months, 3) * 30), c_days = GREATEST(COALESCE(b_months, 3) * 30 + 30, 120)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure every division has a row, including divisions created around older upgrades.
INSERT INTO division_grade_settings (division_id, a_plus_days, a_days, b_days, c_days)
SELECT d.id, 30, 60, 90, 120
FROM divisions d
LEFT JOIN division_grade_settings gs ON gs.division_id = d.id
WHERE gs.division_id IS NULL;
