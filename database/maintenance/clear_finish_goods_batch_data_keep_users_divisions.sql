USE store_aging;

-- ============================================================
-- CLEAR FINISH GOODS BATCH DATA - FOREIGN KEY SAFE
-- ============================================================
-- Removes Finish Goods batch transactional data while preserving:
--   * divisions
--   * users
--   * materials / product master
--   * grading criteria / division grade settings
--   * Market Return module data
--   * Market Return division mappings
--   * audit logs
--
-- IMPORTANT:
-- stock_movements is a child of stock_batches through:
--   stock_movements.batch_id -> stock_batches.id
--
-- Therefore DELETE the child rows first, then the parent rows.
-- DELETE is used instead of TRUNCATE because MariaDB/MySQL can reject
-- TRUNCATE on a table referenced by a foreign-key constraint.
-- ============================================================

START TRANSACTION;

DELETE FROM stock_movements;
DELETE FROM stock_batches;

COMMIT;

-- Reset auto-increment counters after the data has been deleted.
ALTER TABLE stock_movements AUTO_INCREMENT = 1;
ALTER TABLE stock_batches AUTO_INCREMENT = 1;

-- Verification
SELECT COUNT(*) AS remaining_stock_movements FROM stock_movements;
SELECT COUNT(*) AS remaining_stock_batches FROM stock_batches;
SELECT COUNT(*) AS preserved_divisions FROM divisions;
SELECT COUNT(*) AS preserved_users FROM users;
