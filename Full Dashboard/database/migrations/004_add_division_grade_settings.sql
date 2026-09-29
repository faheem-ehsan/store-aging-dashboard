USE store_aging;

CREATE TABLE IF NOT EXISTS division_grade_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    a_plus_months INT UNSIGNED NOT NULL DEFAULT 1,
    a_months INT UNSIGNED NOT NULL DEFAULT 2,
    b_months INT UNSIGNED NOT NULL DEFAULT 3,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grade_settings_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_grade_settings_division (division_id)
) ENGINE=InnoDB;

-- Start every existing division with the requested default rule:
-- A+ = first 1 month from manufacture
-- A  = after A+ through 2 months from manufacture
-- B  = after A through 3 months from manufacture
-- C  = above 3 months until expiry
-- Expired = on the expiry date and afterwards
INSERT IGNORE INTO division_grade_settings (division_id, a_plus_months, a_months, b_months)
SELECT id, 1, 2, 3 FROM divisions;
