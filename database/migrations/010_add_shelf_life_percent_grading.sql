-- Product-aware shelf-life percentage grading by division.
-- Run once on an existing database.

ALTER TABLE division_grade_settings
    ADD COLUMN a_plus_used_pct DECIMAL(5,2) NOT NULL DEFAULT 25.00 AFTER c_days,
    ADD COLUMN a_used_pct DECIMAL(5,2) NOT NULL DEFAULT 50.00 AFTER a_plus_used_pct,
    ADD COLUMN b_used_pct DECIMAL(5,2) NOT NULL DEFAULT 75.00 AFTER a_used_pct,
    ADD COLUMN c_used_pct DECIMAL(5,2) NOT NULL DEFAULT 100.00 AFTER b_used_pct;

-- Existing divisions receive recommended starting bands.
UPDATE division_grade_settings
SET a_plus_used_pct = 25.00,
    a_used_pct = 50.00,
    b_used_pct = 75.00,
    c_used_pct = 100.00;

-- Ensure every active/legacy division has a settings row.
INSERT INTO division_grade_settings
    (division_id, a_plus_months, a_months, b_months, a_plus_days, a_days, b_days, c_days,
     a_plus_used_pct, a_used_pct, b_used_pct, c_used_pct)
SELECT d.id, 1, 2, 3, 366, 365, 180, 90, 25.00, 50.00, 75.00, 100.00
FROM divisions d
LEFT JOIN division_grade_settings gs ON gs.division_id = d.id
WHERE gs.division_id IS NULL;
