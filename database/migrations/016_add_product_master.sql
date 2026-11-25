-- Super Admin Product Master for manual Add Batch entry.
-- IMPORTANT: This table is independent of CSV import logic and templates.

CREATE TABLE IF NOT EXISTS product_master (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    product_code VARCHAR(60) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    uom VARCHAR(20) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_master_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_product_master_division_code (division_id, product_code),
    INDEX idx_product_master_name (product_name),
    INDEX idx_product_master_active (division_id, is_active)
) ENGINE=InnoDB;

-- Prefill the new Product Master from products already present in Finish Goods.
-- UoM is taken from the latest available batch for the material.
INSERT IGNORE INTO product_master (division_id, product_code, product_name, uom, is_active)
SELECT
    m.division_id,
    m.material_code,
    m.description,
    COALESCE(
        NULLIF((
            SELECT sb.uom
            FROM stock_batches sb
            WHERE sb.material_id=m.id AND TRIM(COALESCE(sb.uom,''))<>''
            ORDER BY sb.updated_at DESC, sb.id DESC
            LIMIT 1
        ), ''),
        'PCS'
    ) AS uom,
    m.is_active
FROM materials m;
