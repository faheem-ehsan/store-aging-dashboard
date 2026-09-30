USE store_aging;

-- Grading is now based on the percentage of each batch's own shelf life used.
-- Existing day columns are retained for backward compatibility but are no longer used by the application.

SET @has_a_plus_percent := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'a_plus_percent'
);
SET @has_a_percent := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'a_percent'
);
SET @has_b_percent := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'b_percent'
);
SET @has_c_percent := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'c_percent'
);

SET @sql := IF(@has_a_plus_percent = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN a_plus_percent TINYINT UNSIGNED NOT NULL DEFAULT 25 AFTER c_days',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@has_a_percent = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN a_percent TINYINT UNSIGNED NOT NULL DEFAULT 50 AFTER a_plus_percent',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@has_b_percent = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN b_percent TINYINT UNSIGNED NOT NULL DEFAULT 75 AFTER a_percent',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@has_c_percent = 0,
    'ALTER TABLE division_grade_settings ADD COLUMN c_percent TINYINT UNSIGNED NOT NULL DEFAULT 100 AFTER b_percent',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Apply the recommended defaults to all existing divisions on first migration.
UPDATE division_grade_settings
SET a_plus_percent = 25,
    a_percent = 50,
    b_percent = 75,
    c_percent = 100
WHERE a_plus_percent = 0
   OR a_percent <= a_plus_percent
   OR b_percent <= a_percent
   OR c_percent <= b_percent
   OR c_percent > 100;

INSERT INTO division_grade_settings (division_id, a_plus_percent, a_percent, b_percent, c_percent)
SELECT d.id, 25, 50, 75, 100
FROM divisions d
LEFT JOIN division_grade_settings gs ON gs.division_id = d.id
WHERE gs.division_id IS NULL;
