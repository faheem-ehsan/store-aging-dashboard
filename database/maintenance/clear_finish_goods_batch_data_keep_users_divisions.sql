USE store_aging;

-- ============================================================
-- CLEAR FINISH GOODS BATCH DATA
-- ============================================================
-- This script removes imported/entered Finish Goods batch data
-- while preserving:
--   * divisions
--   * users
--   * materials / product master
--   * grading criteria / division grade settings
--   * Market Return module data
--   * Market Return division mappings
--   * audit logs
--
-- Run manually in phpMyAdmin only when you intentionally want
-- to reset the Finish Goods Aging batch data.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE stock_movements;
TRUNCATE TABLE stock_batches;

SET FOREIGN_KEY_CHECKS = 1;

-- Verification
SELECT COUNT(*) AS remaining_stock_batches FROM stock_batches;
SELECT COUNT(*) AS remaining_stock_movements FROM stock_movements;
SELECT COUNT(*) AS preserved_divisions FROM divisions;
SELECT COUNT(*) AS preserved_users FROM users;
