<?php
namespace App\Models;

use App\Core\Database;

/**
 * Store Export Goods Aging batches. Same behaviour as Finish Goods (StockBatch),
 * stored in separate tables so export stock never appears in the Finish Goods
 * dashboard, daily stock reports, grade e-mail alerts or SAP publishing.
 */
class ExportGoodsBatch extends StockBatch
{
    protected string $batchTable = 'export_goods_batches';
    protected string $materialTable = 'export_goods_items';
    protected bool $usesStockMovements = false;

    private static bool $tablesReady = false;

    public function __construct()
    {
        $this->ensureTables();
    }

    /** Creates the tables on first use. See migration 019. */
    public function ensureTables(): void
    {
        if (self::$tablesReady) return;
        $pdo = Database::connection();

        $pdo->exec("CREATE TABLE IF NOT EXISTS export_goods_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            division_id INT UNSIGNED NOT NULL,
            material_code VARCHAR(60) NOT NULL,
            description VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_export_item_division_code (division_id, material_code),
            INDEX idx_export_item_description (description),
            CONSTRAINT fk_export_item_division FOREIGN KEY (division_id) REFERENCES divisions(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS export_goods_batches (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::$tablesReady = true;
    }
}
