<?php
namespace App\Models;

use App\Core\Database;

/**
 * Raw Material Aging batches. Same behaviour as Finish Goods (StockBatch),
 * stored in separate tables so raw materials never appear in the Finish Goods
 * dashboard, daily stock reports, grade e-mail alerts or SAP publishing.
 */
class RawMaterialBatch extends StockBatch
{
    protected string $batchTable = 'raw_material_batches';
    protected string $materialTable = 'raw_materials';
    protected bool $usesStockMovements = false;

    private static bool $tablesReady = false;

    public function __construct()
    {
        $this->ensureTables();
    }

    /** Creates the tables on first use (same pattern as ProductMaster / SAP). See migration 018. */
    public function ensureTables(): void
    {
        if (self::$tablesReady) return;
        $pdo = Database::connection();

        $pdo->exec("CREATE TABLE IF NOT EXISTS raw_materials (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            division_id INT UNSIGNED NOT NULL,
            material_code VARCHAR(60) NOT NULL,
            description VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_raw_material_division_code (division_id, material_code),
            INDEX idx_raw_material_description (description),
            CONSTRAINT fk_raw_material_division FOREIGN KEY (division_id) REFERENCES divisions(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS raw_material_batches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            division_id INT UNSIGNED NOT NULL,
            material_id BIGINT UNSIGNED NOT NULL,
            batch_no VARCHAR(80) NOT NULL,
            manufacture_date DATE NOT NULL,
            expiry_date DATE NOT NULL,
            last_receipt_date DATE NULL,
            current_qty DECIMAL(18,3) NOT NULL DEFAULT 0,
            stock_value DECIMAL(18,2) NOT NULL DEFAULT 0,
            uom VARCHAR(20) NOT NULL DEFAULT 'KG',
            receiving_store VARCHAR(150) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rm_batch_per_material (division_id, material_id, batch_no),
            INDEX idx_rm_batch_expiry (division_id, expiry_date),
            INDEX idx_rm_batch_number (batch_no),
            CONSTRAINT fk_rm_batch_division FOREIGN KEY (division_id) REFERENCES divisions(id),
            CONSTRAINT fk_rm_batch_material FOREIGN KEY (material_id) REFERENCES raw_materials(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::$tablesReady = true;
    }
}
