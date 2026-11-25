-- SAP S/4HANA read-only integration module
-- Phase 1: SAP -> local staging -> existing Finish Goods dashboard.
-- Existing CSV/Excel import settings and templates are not changed.

CREATE TABLE IF NOT EXISTS sap_integration_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    publish_to_dashboard TINYINT(1) NOT NULL DEFAULT 0,
    environment_label VARCHAR(80) NOT NULL DEFAULT 'SAP S/4HANA',
    base_url VARCHAR(500) NULL,
    auth_type ENUM('basic','oauth2') NOT NULL DEFAULT 'basic',
    username VARCHAR(190) NULL,
    password_enc TEXT NULL,
    oauth_token_url VARCHAR(500) NULL,
    client_id VARCHAR(190) NULL,
    client_secret_enc TEXT NULL,
    product_endpoint VARCHAR(500) NULL,
    batch_endpoint VARCHAR(500) NULL,
    stock_endpoint VARCHAR(500) NULL,
    request_timeout_sec INT UNSIGNED NOT NULL DEFAULT 30,
    page_size INT UNSIGNED NOT NULL DEFAULT 500,
    verify_ssl TINYINT(1) NOT NULL DEFAULT 1,
    stock_type_filter VARCHAR(500) NULL,

    product_code_field VARCHAR(190) NOT NULL DEFAULT 'Product',
    product_name_field VARCHAR(190) NOT NULL DEFAULT 'ProductDescription',
    product_uom_field VARCHAR(190) NOT NULL DEFAULT 'BaseUnit',

    batch_material_field VARCHAR(190) NOT NULL DEFAULT 'Material',
    batch_no_field VARCHAR(190) NOT NULL DEFAULT 'Batch',
    manufacture_date_field VARCHAR(190) NOT NULL DEFAULT 'ManufactureDate',
    expiry_date_field VARCHAR(190) NOT NULL DEFAULT 'ShelfLifeExpirationDate',
    last_receipt_date_field VARCHAR(190) NOT NULL DEFAULT 'LastGoodsReceiptDate',

    stock_material_field VARCHAR(190) NOT NULL DEFAULT 'Material',
    stock_batch_field VARCHAR(190) NOT NULL DEFAULT 'Batch',
    plant_field VARCHAR(190) NOT NULL DEFAULT 'Plant',
    storage_location_field VARCHAR(190) NOT NULL DEFAULT 'StorageLocation',
    quantity_field VARCHAR(190) NOT NULL DEFAULT 'MatlWrhsStkQtyInMatlBaseUnit',
    stock_type_field VARCHAR(190) NOT NULL DEFAULT 'InventoryStockType',
    stock_value_field VARCHAR(190) NULL,
    stock_uom_field VARCHAR(190) NOT NULL DEFAULT 'MaterialBaseUnit',

    last_test_at DATETIME NULL,
    last_test_status VARCHAR(30) NULL,
    last_test_summary VARCHAR(500) NULL,
    last_sync_at DATETIME NULL,
    last_sync_status VARCHAR(30) NULL,
    last_sync_summary VARCHAR(500) NULL,
    updated_by INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sap_settings_user
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO sap_integration_settings (id) VALUES (1);

CREATE TABLE IF NOT EXISTS sap_division_mapping (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sap_plant VARCHAR(40) NOT NULL,
    sap_storage_location VARCHAR(40) NOT NULL DEFAULT '',
    division_id INT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sap_mapping_division
        FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    CONSTRAINT fk_sap_mapping_user
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_sap_plant_storage (sap_plant, sap_storage_location),
    INDEX idx_sap_mapping_division (division_id, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sap_sync_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trigger_type ENUM('manual','scheduled','preview','test') NOT NULL DEFAULT 'manual',
    status VARCHAR(30) NOT NULL DEFAULT 'running',
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    initiated_by INT UNSIGNED NULL,
    product_rows INT UNSIGNED NOT NULL DEFAULT 0,
    batch_rows INT UNSIGNED NOT NULL DEFAULT 0,
    stock_rows INT UNSIGNED NOT NULL DEFAULT 0,
    mapped_stock_rows INT UNSIGNED NOT NULL DEFAULT 0,
    published_batches INT UNSIGNED NOT NULL DEFAULT 0,
    rejected_rows INT UNSIGNED NOT NULL DEFAULT 0,
    unmapped_rows INT UNSIGNED NOT NULL DEFAULT 0,
    message VARCHAR(500) NULL,
    summary_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sap_sync_user
        FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sap_sync_status (status, started_at),
    INDEX idx_sap_sync_finished (finished_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sap_products (
    product_code VARCHAR(80) NOT NULL PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    uom VARCHAR(40) NULL,
    raw_json LONGTEXT NULL,
    sync_log_id BIGINT UNSIGNED NULL,
    synced_at DATETIME NOT NULL,
    CONSTRAINT fk_sap_product_sync
        FOREIGN KEY (sync_log_id) REFERENCES sap_sync_log(id) ON DELETE SET NULL,
    INDEX idx_sap_product_sync (sync_log_id, synced_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sap_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(80) NOT NULL,
    batch_no VARCHAR(100) NOT NULL,
    manufacture_date DATE NULL,
    expiry_date DATE NULL,
    last_receipt_date DATE NULL,
    raw_json LONGTEXT NULL,
    sync_log_id BIGINT UNSIGNED NULL,
    synced_at DATETIME NOT NULL,
    CONSTRAINT fk_sap_batch_sync
        FOREIGN KEY (sync_log_id) REFERENCES sap_sync_log(id) ON DELETE SET NULL,
    UNIQUE KEY uq_sap_batch_product (product_code, batch_no),
    INDEX idx_sap_batch_expiry (expiry_date),
    INDEX idx_sap_batch_sync (sync_log_id, synced_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sap_stock (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_key CHAR(64) NOT NULL,
    product_code VARCHAR(80) NOT NULL,
    batch_no VARCHAR(100) NOT NULL,
    sap_plant VARCHAR(40) NOT NULL,
    sap_storage_location VARCHAR(40) NOT NULL DEFAULT '',
    stock_type VARCHAR(80) NULL,
    current_qty DECIMAL(20,3) NOT NULL DEFAULT 0,
    stock_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    uom VARCHAR(40) NULL,
    division_id INT UNSIGNED NULL,
    raw_json LONGTEXT NULL,
    sync_log_id BIGINT UNSIGNED NULL,
    synced_at DATETIME NOT NULL,
    CONSTRAINT fk_sap_stock_division
        FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE SET NULL,
    CONSTRAINT fk_sap_stock_sync
        FOREIGN KEY (sync_log_id) REFERENCES sap_sync_log(id) ON DELETE SET NULL,
    UNIQUE KEY uq_sap_stock_key (sync_key),
    INDEX idx_sap_stock_scope (division_id, product_code, batch_no),
    INDEX idx_sap_stock_location (sap_plant, sap_storage_location),
    INDEX idx_sap_stock_sync (sync_log_id, synced_at)
) ENGINE=InnoDB;

-- Mark dashboard batches that are maintained by the SAP synchronization.
ALTER TABLE stock_batches
    ADD COLUMN source_system ENUM('manual','sap') NOT NULL DEFAULT 'manual' AFTER receiving_store,
    ADD COLUMN sap_sync_key CHAR(64) NULL AFTER source_system,
    ADD COLUMN sap_last_sync_id BIGINT UNSIGNED NULL AFTER sap_sync_key,
    ADD UNIQUE KEY uq_stock_batch_sap_sync_key (sap_sync_key),
    ADD INDEX idx_stock_batch_source_system (source_system, division_id);
