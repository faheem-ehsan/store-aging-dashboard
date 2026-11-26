-- Store Export Goods Aging module: separate tables so export stock never mixes
-- with Finish Goods (dashboard, daily stock reports, grade e-mail alerts, SAP).
-- The application also creates these tables automatically on first use
-- (App\Models\ExportGoodsBatch and App\Models\ExportGoodsMaster), so running
-- this file is optional.

USE store_aging;

CREATE TABLE IF NOT EXISTS export_goods_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    material_code VARCHAR(60) NOT NULL,
    description VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_export_item_division_code (division_id, material_code),
    INDEX idx_export_item_description (description),
    CONSTRAINT fk_export_item_division FOREIGN KEY (division_id) REFERENCES divisions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS export_goods_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NOT NULL,
    batch_no VARCHAR(80) NOT NULL,
    manufacture_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    last_receipt_date DATE NULL,
    current_qty DECIMAL(18,3) NOT NULL DEFAULT 0,
    stock_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    uom VARCHAR(20) NOT NULL DEFAULT 'CS',
    receiving_store VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_eg_batch_per_item (division_id, material_id, batch_no),
    INDEX idx_eg_batch_expiry (division_id, expiry_date),
    INDEX idx_eg_batch_number (batch_no),
    CONSTRAINT fk_eg_batch_division FOREIGN KEY (division_id) REFERENCES divisions(id),
    CONSTRAINT fk_eg_batch_item FOREIGN KEY (material_id) REFERENCES export_goods_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS export_goods_master (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    product_code VARCHAR(60) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    uom VARCHAR(20) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_export_goods_master_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_export_goods_master_division_code (division_id, product_code),
    INDEX idx_export_goods_master_name (product_name),
    INDEX idx_export_goods_master_active (division_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
