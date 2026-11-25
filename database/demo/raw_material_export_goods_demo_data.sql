-- =============================================================================
-- DEMO DATA: Raw Material Aging + Export Goods Aging
-- =============================================================================
-- Loads realistic sample stock for every ACTIVE division so both aging pages
-- (and their Masters in Administration) have data to show.
--
-- * Dates are relative to the day you run this file, and each batch is placed
--   inside its grade band using that division's own grading criteria, so every
--   division shows A+, A, B, C and Expired stock.
-- * Only touches Raw Material / Export Goods tables. Finish Goods, Market
--   Return, users and divisions are not changed.
-- * Safe to run more than once (INSERT IGNORE): it will not create duplicates.
--   To refresh the dates, run the CLEANUP block at the bottom first.
--
-- Prerequisite: the module tables must exist. Run migrations 018 and 019, or
-- open Raw Material Aging and Export Goods Aging once in the app.
--
-- Run:  mysql -u <user> -p store_aging < database/demo/raw_material_export_goods_demo_data.sql
-- =============================================================================

USE store_aging;

-- -----------------------------------------------------------------------------
-- 1. Effective grade bands (% of shelf life used) per division.
--    Mirrors App\Models\GradeSetting: *_used_pct columns when present,
--    otherwise the legacy *_days columns, otherwise 25 / 50 / 75.
-- -----------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS demo_bands;
CREATE TEMPORARY TABLE demo_bands (
    division_id INT UNSIGNED PRIMARY KEY,
    ap DECIMAL(6,2) NOT NULL,
    a  DECIMAL(6,2) NOT NULL,
    b  DECIMAL(6,2) NOT NULL
);
INSERT INTO demo_bands SELECT id, 25, 50, 75 FROM divisions WHERE is_active = 1;

SET @has_pct := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'a_plus_used_pct');
SET @has_days := (SELECT COUNT(*) FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'division_grade_settings' AND COLUMN_NAME = 'a_plus_days');
SET @sql := IF(@has_pct > 0,
    'UPDATE demo_bands x JOIN division_grade_settings g ON g.division_id = x.division_id SET x.ap = g.a_plus_used_pct, x.a = g.a_used_pct, x.b = g.b_used_pct',
    IF(@has_days > 0,
        'UPDATE demo_bands x JOIN division_grade_settings g ON g.division_id = x.division_id SET x.ap = g.a_plus_days, x.a = g.a_days, x.b = g.b_days',
        'DO 0'));
PREPARE demo_stmt FROM @sql;
EXECUTE demo_stmt;
DEALLOCATE PREPARE demo_stmt;

-- -----------------------------------------------------------------------------
-- 2. Item catalogues (code, name, UoM, unit value Rs, shelf life days)
-- -----------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS demo_items;
CREATE TEMPORARY TABLE demo_items (
    module CHAR(2) NOT NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(255) NOT NULL,
    uom VARCHAR(20) NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    shelf INT NOT NULL,
    PRIMARY KEY (module, code)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO demo_items VALUES
    ('RM', 'RM-1001', 'Sugar Refined',                     'KG',   150.00,  730),
    ('RM', 'RM-1002', 'Citric Acid Anhydrous',             'KG',   420.00, 1095),
    ('RM', 'RM-1003', 'Sodium Benzoate',                   'KG',   650.00,  730),
    ('RM', 'RM-1004', 'Natural Rose Flavour',              'LTR', 3800.00,  540),
    ('RM', 'RM-1005', 'PET Preform 28g',                   'PCS',   18.00, 1095),
    ('RM', 'RM-1006', 'HDPE Closure 28mm',                 'PCS',    4.50, 1095),
    ('RM', 'RM-1007', 'Liquid Glucose',                    'KG',   210.00,  365),
    ('RM', 'RM-1008', 'Herbal Extract Blend',              'KG',  2600.00,  540),
    ('EG', 'EXP-2001', 'Jam-e-Shirin 800ml Export (1*12)', 'CS',  9600.00,  730),
    ('EG', 'EXP-2002', 'Springley 500ml Export (1*24)',    'CS',  1200.00,  365),
    ('EG', 'EXP-2003', 'Mango Nectar 1L Export (1*12)',    'CS',  4200.00,  365),
    ('EG', 'EXP-2004', 'Herbal Syrup 120ml Export (1*48)', 'CS',  7400.00,  730),
    ('EG', 'EXP-2005', 'Lemon Squash 800ml Export (1*12)', 'CS',  5100.00,  540),
    ('EG', 'EXP-2006', 'Mineral Water 1.5L Export (1*6)',  'CS',   540.00,  540);

-- -----------------------------------------------------------------------------
-- 3. Batch plan: item, grade band, base quantity, stock location.
--    Quantities are scaled per division so totals differ between divisions.
-- -----------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS demo_batches;
CREATE TEMPORARY TABLE demo_batches (
    module CHAR(2) NOT NULL,
    code VARCHAR(60) NOT NULL,
    band VARCHAR(2) NOT NULL,      -- AP = A+, A, B, C, EX = expired
    qty DECIMAL(18,3) NOT NULL,
    location VARCHAR(20) NOT NULL
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO demo_batches VALUES
    ('RM', 'RM-1001', 'AP', 2500, 'RM01'), ('RM', 'RM-1001', 'C',  800, 'RM01'),
    ('RM', 'RM-1002', 'A',   600, 'RM02'),
    ('RM', 'RM-1003', 'B',   150, 'RM02'), ('RM', 'RM-1003', 'AP', 220, 'RM02'),
    ('RM', 'RM-1004', 'C',    40, 'RM03'),
    ('RM', 'RM-1005', 'EX', 18000, 'RM04'),
    ('RM', 'RM-1006', 'AP', 45000, 'RM04'),
    ('RM', 'RM-1007', 'A',   900, 'RM01'),
    ('RM', 'RM-1008', 'B',    75, 'RM03'), ('RM', 'RM-1008', 'EX', 12, 'RM03'),
    ('EG', 'EXP-2001', 'AP',  320, 'EXP01'), ('EG', 'EXP-2001', 'B', 140, 'EXP01'),
    ('EG', 'EXP-2002', 'A',   900, 'EXP02'),
    ('EG', 'EXP-2003', 'C',   260, 'EXP02'),
    ('EG', 'EXP-2004', 'AP',  180, 'EXP01'), ('EG', 'EXP-2004', 'EX', 35, 'EXP03'),
    ('EG', 'EXP-2005', 'B',   210, 'EXP03'),
    ('EG', 'EXP-2006', 'A',  1500, 'EXP02');

-- -----------------------------------------------------------------------------
-- 4. Masters (Administration → Raw Material Master / Export Goods Master)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO raw_material_master (division_id, product_code, product_name, uom, is_active)
SELECT x.division_id, i.code, i.name, i.uom, 1
FROM demo_bands x CROSS JOIN demo_items i WHERE i.module = 'RM';

INSERT IGNORE INTO export_goods_master (division_id, product_code, product_name, uom, is_active)
SELECT x.division_id, i.code, i.name, i.uom, 1
FROM demo_bands x CROSS JOIN demo_items i WHERE i.module = 'EG';

-- -----------------------------------------------------------------------------
-- 5. Item rows used by the batch tables
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO raw_materials (division_id, material_code, description, is_active)
SELECT x.division_id, i.code, i.name, 1
FROM demo_bands x CROSS JOIN demo_items i WHERE i.module = 'RM';

INSERT IGNORE INTO export_goods_items (division_id, material_code, description, is_active)
SELECT x.division_id, i.code, i.name, 1
FROM demo_bands x CROSS JOIN demo_items i WHERE i.module = 'EG';

-- -----------------------------------------------------------------------------
-- 6. Batches. Age (days since manufacture) puts each batch in the middle of
--    its band: A+ = half of A+ limit, A/B = midpoint, C = between B and 100%,
--    EX = expired 20 days ago.
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO raw_material_batches
    (division_id, material_id, batch_no, manufacture_date, expiry_date, last_receipt_date,
     current_qty, stock_value, uom, receiving_store)
SELECT t.division_id, t.material_id, t.batch_no, t.mfg, t.mfg + INTERVAL t.shelf DAY,
       LEAST(CURDATE(), t.mfg + INTERVAL 7 DAY), t.qty, ROUND(t.qty * t.rate, 2), t.uom, t.location
FROM (
    SELECT x.division_id, m.id AS material_id, i.shelf, i.rate, i.uom, b.location,
           CONCAT('DEMO-', REPLACE(i.code, 'RM-', ''), '-', b.band) AS batch_no,
           ROUND(b.qty * (1 + MOD(x.division_id, 4) * 0.35), 0) AS qty,
           CURDATE() - INTERVAL (CASE b.band
               WHEN 'AP' THEN ROUND(i.shelf * x.ap / 200)
               WHEN 'A'  THEN ROUND(i.shelf * (x.ap + x.a) / 200)
               WHEN 'B'  THEN ROUND(i.shelf * (x.a + x.b) / 200)
               WHEN 'C'  THEN ROUND(i.shelf * (x.b + 100) / 200)
               ELSE i.shelf + 20 END) DAY AS mfg
    FROM demo_batches b
    JOIN demo_items i ON i.module = b.module AND i.code = b.code
    JOIN demo_bands x
    JOIN raw_materials m ON m.division_id = x.division_id AND m.material_code = i.code
    WHERE b.module = 'RM'
) t;

INSERT IGNORE INTO export_goods_batches
    (division_id, material_id, batch_no, manufacture_date, expiry_date, last_receipt_date,
     current_qty, stock_value, uom, receiving_store)
SELECT t.division_id, t.material_id, t.batch_no, t.mfg, t.mfg + INTERVAL t.shelf DAY,
       LEAST(CURDATE(), t.mfg + INTERVAL 7 DAY), t.qty, ROUND(t.qty * t.rate, 2), t.uom, t.location
FROM (
    SELECT x.division_id, m.id AS material_id, i.shelf, i.rate, i.uom, b.location,
           CONCAT('DEMO-', REPLACE(i.code, 'EXP-', ''), '-', b.band) AS batch_no,
           ROUND(b.qty * (1 + MOD(x.division_id, 4) * 0.35), 0) AS qty,
           CURDATE() - INTERVAL (CASE b.band
               WHEN 'AP' THEN ROUND(i.shelf * x.ap / 200)
               WHEN 'A'  THEN ROUND(i.shelf * (x.ap + x.a) / 200)
               WHEN 'B'  THEN ROUND(i.shelf * (x.a + x.b) / 200)
               WHEN 'C'  THEN ROUND(i.shelf * (x.b + 100) / 200)
               ELSE i.shelf + 20 END) DAY AS mfg
    FROM demo_batches b
    JOIN demo_items i ON i.module = b.module AND i.code = b.code
    JOIN demo_bands x
    JOIN export_goods_items m ON m.division_id = x.division_id AND m.material_code = i.code
    WHERE b.module = 'EG'
) t;

DROP TEMPORARY TABLE IF EXISTS demo_batches;
DROP TEMPORARY TABLE IF EXISTS demo_items;
DROP TEMPORARY TABLE IF EXISTS demo_bands;

SELECT
    (SELECT COUNT(*) FROM raw_material_batches WHERE batch_no LIKE 'DEMO-%') AS raw_material_demo_batches,
    (SELECT COUNT(*) FROM export_goods_batches WHERE batch_no LIKE 'DEMO-%') AS export_goods_demo_batches;

-- =============================================================================
-- CLEANUP: remove all demo data (uncomment and run).
-- Removes DEMO-% batches, then the demo items/master rows that no longer have
-- any batches. Records you entered yourself are not touched.
-- =============================================================================
-- DELETE FROM raw_material_batches WHERE batch_no LIKE 'DEMO-%';
-- DELETE FROM export_goods_batches WHERE batch_no LIKE 'DEMO-%';
-- DELETE m FROM raw_materials m LEFT JOIN raw_material_batches b ON b.material_id = m.id
--     WHERE b.id IS NULL AND m.material_code IN ('RM-1001','RM-1002','RM-1003','RM-1004','RM-1005','RM-1006','RM-1007','RM-1008');
-- DELETE m FROM export_goods_items m LEFT JOIN export_goods_batches b ON b.material_id = m.id
--     WHERE b.id IS NULL AND m.material_code IN ('EXP-2001','EXP-2002','EXP-2003','EXP-2004','EXP-2005','EXP-2006');
-- DELETE FROM raw_material_master WHERE product_code IN ('RM-1001','RM-1002','RM-1003','RM-1004','RM-1005','RM-1006','RM-1007','RM-1008');
-- DELETE FROM export_goods_master WHERE product_code IN ('EXP-2001','EXP-2002','EXP-2003','EXP-2004','EXP-2005','EXP-2006');
