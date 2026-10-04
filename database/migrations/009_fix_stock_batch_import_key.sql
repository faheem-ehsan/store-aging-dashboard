USE store_aging;

-- Fix legacy installations where stock_batches may still be unique at material level.
-- Required behavior: one Product/Material can have many Batch Nos.
-- Only Division + Material + Batch No. must be unique.

DROP PROCEDURE IF EXISTS fix_stock_batch_import_key;
DELIMITER $$
CREATE PROCEDURE fix_stock_batch_import_key()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE idx_name VARCHAR(128);
    DECLARE idx_cols TEXT;
    DECLARE exact_key_count INT DEFAULT 0;

    DECLARE cur CURSOR FOR
        SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS cols
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'stock_batches'
          AND NON_UNIQUE = 0
          AND INDEX_NAME <> 'PRIMARY'
        GROUP BY INDEX_NAME;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO idx_name, idx_cols;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        -- These legacy keys incorrectly allow only one batch per material.
        IF idx_cols IN ('material_id', 'division_id,material_id') THEN
            SET @drop_sql = CONCAT('ALTER TABLE stock_batches DROP INDEX `', REPLACE(idx_name, '`', '``'), '`');
            PREPARE stmt FROM @drop_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END LOOP;
    CLOSE cur;

    SELECT COUNT(*) INTO exact_key_count
    FROM (
        SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS cols
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'stock_batches'
          AND NON_UNIQUE = 0
          AND INDEX_NAME <> 'PRIMARY'
        GROUP BY INDEX_NAME
    ) AS unique_indexes
    WHERE cols = 'division_id,material_id,batch_no';

    IF exact_key_count = 0 THEN
        ALTER TABLE stock_batches
            ADD UNIQUE KEY uq_batch_per_material (division_id, material_id, batch_no);
    END IF;
END$$
DELIMITER ;

CALL fix_stock_batch_import_key();
DROP PROCEDURE fix_stock_batch_import_key;
