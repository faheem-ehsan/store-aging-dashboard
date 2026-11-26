<?php
namespace App\Models;

use App\Core\Database;

/**
 * Approved export products per Division (code, name, UoM). Used by the manual
 * Add Batch form on Export Goods Aging, exactly as Product Master is used by
 * Finish Goods Aging. CSV import does not depend on it.
 */
class ExportGoodsMaster extends ProductMaster
{
    protected string $table = 'export_goods_master';

    private static bool $tableReady = false;

    public function ensureTable(): void
    {
        if (self::$tableReady) return;

        Database::connection()->exec("CREATE TABLE IF NOT EXISTS export_goods_master (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::$tableReady = true;
    }
}
