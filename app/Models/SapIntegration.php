<?php
namespace App\Models;

use App\Core\Database;
use App\Services\SapCredentialCipher;
use PDO;

class SapIntegration
{
    public function ensureTables(): void
    {
        $pdo = Database::connection();

        $pdo->exec("CREATE TABLE IF NOT EXISTS sap_integration_settings (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
            publish_to_dashboard TINYINT(1) NOT NULL DEFAULT 0,
            environment_label VARCHAR(80) NOT NULL DEFAULT 'SAP S/4HANA',
            base_url VARCHAR(500) NULL,
            auth_type ENUM('basic','oauth2','api_key') NOT NULL DEFAULT 'basic',
            username VARCHAR(190) NULL,
            password_enc TEXT NULL,
            oauth_token_url VARCHAR(500) NULL,
            client_id VARCHAR(190) NULL,
            client_secret_enc TEXT NULL,
            api_key_enc TEXT NULL,
            api_key_header VARCHAR(80) NOT NULL DEFAULT 'APIKey',
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
            CONSTRAINT fk_sap_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB");

        // Backwards-compatible migration for databases created before API-key support.
        $authColumn = $pdo->query("SHOW COLUMNS FROM sap_integration_settings LIKE 'auth_type'")->fetch();
        if ($authColumn && strpos((string)$authColumn['Type'], "'api_key'") === false) {
            $pdo->exec("ALTER TABLE sap_integration_settings MODIFY auth_type ENUM('basic','oauth2','api_key') NOT NULL DEFAULT 'basic'");
        }
        $columns = array_column($pdo->query('SHOW COLUMNS FROM sap_integration_settings')->fetchAll(), 'Field');
        if (!in_array('api_key_enc', $columns, true)) {
            $pdo->exec('ALTER TABLE sap_integration_settings ADD COLUMN api_key_enc TEXT NULL');
        }
        if (!in_array('api_key_header', $columns, true)) {
            $pdo->exec("ALTER TABLE sap_integration_settings ADD COLUMN api_key_header VARCHAR(80) NOT NULL DEFAULT 'APIKey'");
        }
        $pdo->exec("INSERT IGNORE INTO sap_integration_settings (id) VALUES (1)");

        $pdo->exec("CREATE TABLE IF NOT EXISTS sap_division_mapping (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sap_plant VARCHAR(40) NOT NULL,
            sap_storage_location VARCHAR(40) NOT NULL DEFAULT '',
            division_id INT UNSIGNED NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_sap_mapping_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
            CONSTRAINT fk_sap_mapping_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY uq_sap_plant_storage (sap_plant, sap_storage_location),
            INDEX idx_sap_mapping_division (division_id, is_active)
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS sap_sync_log (
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
            CONSTRAINT fk_sap_sync_user FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_sap_sync_status (status, started_at),
            INDEX idx_sap_sync_finished (finished_at)
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS sap_products (
            product_code VARCHAR(80) NOT NULL PRIMARY KEY,
            product_name VARCHAR(255) NOT NULL,
            uom VARCHAR(40) NULL,
            raw_json LONGTEXT NULL,
            sync_log_id BIGINT UNSIGNED NULL,
            synced_at DATETIME NOT NULL,
            CONSTRAINT fk_sap_product_sync FOREIGN KEY (sync_log_id) REFERENCES sap_sync_log(id) ON DELETE SET NULL,
            INDEX idx_sap_product_sync (sync_log_id, synced_at)
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS sap_batches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_code VARCHAR(80) NOT NULL,
            batch_no VARCHAR(100) NOT NULL,
            manufacture_date DATE NULL,
            expiry_date DATE NULL,
            last_receipt_date DATE NULL,
            raw_json LONGTEXT NULL,
            sync_log_id BIGINT UNSIGNED NULL,
            synced_at DATETIME NOT NULL,
            CONSTRAINT fk_sap_batch_sync FOREIGN KEY (sync_log_id) REFERENCES sap_sync_log(id) ON DELETE SET NULL,
            UNIQUE KEY uq_sap_batch_product (product_code, batch_no),
            INDEX idx_sap_batch_expiry (expiry_date),
            INDEX idx_sap_batch_sync (sync_log_id, synced_at)
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS sap_stock (
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
            CONSTRAINT fk_sap_stock_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE SET NULL,
            CONSTRAINT fk_sap_stock_sync FOREIGN KEY (sync_log_id) REFERENCES sap_sync_log(id) ON DELETE SET NULL,
            UNIQUE KEY uq_sap_stock_key (sync_key),
            INDEX idx_sap_stock_scope (division_id, product_code, batch_no),
            INDEX idx_sap_stock_location (sap_plant, sap_storage_location),
            INDEX idx_sap_stock_sync (sync_log_id, synced_at)
        ) ENGINE=InnoDB");

        $this->ensureStockBatchColumns($pdo);
    }

    private function ensureStockBatchColumns(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query("SHOW COLUMNS FROM stock_batches")->fetchAll() as $column) {
            $columns[strtolower((string)($column['Field'] ?? ''))] = true;
        }

        if (empty($columns['source_system'])) {
            $pdo->exec("ALTER TABLE stock_batches ADD COLUMN source_system ENUM('manual','sap') NOT NULL DEFAULT 'manual' AFTER receiving_store");
        }
        if (empty($columns['sap_sync_key'])) {
            $pdo->exec("ALTER TABLE stock_batches ADD COLUMN sap_sync_key CHAR(64) NULL AFTER source_system");
        }
        if (empty($columns['sap_last_sync_id'])) {
            $pdo->exec("ALTER TABLE stock_batches ADD COLUMN sap_last_sync_id BIGINT UNSIGNED NULL AFTER sap_sync_key");
        }

        $indexes = [];
        foreach ($pdo->query("SHOW INDEX FROM stock_batches")->fetchAll() as $index) {
            $indexes[strtolower((string)($index['Key_name'] ?? ''))] = true;
        }
        if (empty($indexes['uq_stock_batch_sap_sync_key'])) {
            $pdo->exec("ALTER TABLE stock_batches ADD UNIQUE KEY uq_stock_batch_sap_sync_key (sap_sync_key)");
        }
        if (empty($indexes['idx_stock_batch_source_system'])) {
            $pdo->exec("ALTER TABLE stock_batches ADD INDEX idx_stock_batch_source_system (source_system, division_id)");
        }
    }

    public function settings(): array
    {
        $this->ensureTables();
        $row = Database::connection()->query('SELECT * FROM sap_integration_settings WHERE id=1')->fetch() ?: [];
        $row['password_set'] = !empty($row['password_enc']);
        $row['client_secret_set'] = !empty($row['client_secret_enc']);
        $row['api_key_set'] = !empty($row['api_key_enc']);
        unset($row['password_enc'], $row['client_secret_enc'], $row['api_key_enc']);
        return $row;
    }

    public function clientSettings(): array
    {
        $this->ensureTables();
        $row = Database::connection()->query('SELECT * FROM sap_integration_settings WHERE id=1')->fetch() ?: [];
        $cipher = new SapCredentialCipher();
        $row['password'] = !empty($row['password_enc']) ? $cipher->decrypt((string)$row['password_enc']) : '';
        $row['client_secret'] = !empty($row['client_secret_enc']) ? $cipher->decrypt((string)$row['client_secret_enc']) : '';
        $row['api_key'] = !empty($row['api_key_enc']) ? $cipher->decrypt((string)$row['api_key_enc']) : '';
        return $row;
    }

    public function saveSettings(array $data, int $userId): void
    {
        $this->ensureTables();
        $pdo = Database::connection();
        $existing = $pdo->query('SELECT password_enc,client_secret_enc,api_key_enc FROM sap_integration_settings WHERE id=1')->fetch() ?: [];

        $passwordEnc = (string)($existing['password_enc'] ?? '');
        $clientSecretEnc = (string)($existing['client_secret_enc'] ?? '');
        $apiKeyEnc = (string)($existing['api_key_enc'] ?? '');
        $cipher = new SapCredentialCipher();

        if (array_key_exists('password', $data) && (string)$data['password'] !== '') {
            $passwordEnc = $cipher->encrypt((string)$data['password']);
        }
        if (array_key_exists('client_secret', $data) && (string)$data['client_secret'] !== '') {
            $clientSecretEnc = $cipher->encrypt((string)$data['client_secret']);
        }

        if (array_key_exists('api_key', $data) && (string)$data['api_key'] !== '') {
            $apiKeyEnc = $cipher->encrypt((string)$data['api_key']);
        }

        $stmt = $pdo->prepare(
            "UPDATE sap_integration_settings SET
                is_enabled=?, publish_to_dashboard=?, environment_label=?, base_url=?, auth_type=?,
                username=?, password_enc=?, oauth_token_url=?, client_id=?, client_secret_enc=?, api_key_enc=?, api_key_header=?,
                product_endpoint=?, batch_endpoint=?, stock_endpoint=?,
                request_timeout_sec=?, page_size=?, verify_ssl=?, stock_type_filter=?,
                product_code_field=?, product_name_field=?, product_uom_field=?,
                batch_material_field=?, batch_no_field=?, manufacture_date_field=?, expiry_date_field=?, last_receipt_date_field=?,
                stock_material_field=?, stock_batch_field=?, plant_field=?, storage_location_field=?,
                quantity_field=?, stock_type_field=?, stock_value_field=?, stock_uom_field=?,
                updated_by=?
             WHERE id=1"
        );
        $stmt->execute([
            !empty($data['is_enabled']) ? 1 : 0,
            !empty($data['publish_to_dashboard']) ? 1 : 0,
            trim((string)($data['environment_label'] ?? 'SAP S/4HANA')) ?: 'SAP S/4HANA',
            $this->cleanUrl($data['base_url'] ?? ''),
            in_array(($data['auth_type'] ?? 'basic'), ['basic','oauth2','api_key'], true) ? $data['auth_type'] : 'basic',
            trim((string)($data['username'] ?? '')) ?: null,
            $passwordEnc ?: null,
            $this->cleanUrl($data['oauth_token_url'] ?? ''),
            trim((string)($data['client_id'] ?? '')) ?: null,
            $clientSecretEnc ?: null,
            $apiKeyEnc ?: null,
            preg_match('/^[A-Za-z0-9-]{1,80}$/', (string)($data['api_key_header'] ?? 'APIKey')) ? (string)$data['api_key_header'] : 'APIKey',
            trim((string)($data['product_endpoint'] ?? '')) ?: null,
            trim((string)($data['batch_endpoint'] ?? '')) ?: null,
            trim((string)($data['stock_endpoint'] ?? '')) ?: null,
            max(5, min(180, (int)($data['request_timeout_sec'] ?? 30))),
            max(1, min(5000, (int)($data['page_size'] ?? 500))),
            !empty($data['verify_ssl']) ? 1 : 0,
            trim((string)($data['stock_type_filter'] ?? '')) ?: null,
            $this->field($data, 'product_code_field', 'Product'),
            $this->field($data, 'product_name_field', 'ProductDescription'),
            $this->field($data, 'product_uom_field', 'BaseUnit'),
            $this->field($data, 'batch_material_field', 'Material'),
            $this->field($data, 'batch_no_field', 'Batch'),
            $this->field($data, 'manufacture_date_field', 'ManufactureDate'),
            $this->field($data, 'expiry_date_field', 'ShelfLifeExpirationDate'),
            $this->field($data, 'last_receipt_date_field', 'LastGoodsReceiptDate'),
            $this->field($data, 'stock_material_field', 'Material'),
            $this->field($data, 'stock_batch_field', 'Batch'),
            $this->field($data, 'plant_field', 'Plant'),
            $this->field($data, 'storage_location_field', 'StorageLocation'),
            $this->field($data, 'quantity_field', 'MatlWrhsStkQtyInMatlBaseUnit'),
            $this->field($data, 'stock_type_field', 'InventoryStockType'),
            trim((string)($data['stock_value_field'] ?? '')) ?: null,
            $this->field($data, 'stock_uom_field', 'MaterialBaseUnit'),
            $userId,
        ]);
    }

    private function field(array $data, string $key, string $default): string
    {
        return trim((string)($data[$key] ?? '')) ?: $default;
    }

    private function cleanUrl(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : rtrim($value, '/');
    }

    public function mappings(): array
    {
        $this->ensureTables();
        return Database::connection()->query(
            "SELECT m.*, d.code division_code, d.name division_name, d.is_active division_active
             FROM sap_division_mapping m
             JOIN divisions d ON d.id=m.division_id
             ORDER BY m.sap_plant,m.sap_storage_location,d.code"
        )->fetchAll();
    }

    public function activeMappingIndex(): array
    {
        $index = [];
        foreach ($this->mappings() as $row) {
            if (empty($row['is_active']) || empty($row['division_active'])) continue;
            $plant = strtoupper(trim((string)$row['sap_plant']));
            $storage = strtoupper(trim((string)$row['sap_storage_location']));
            $index[$plant . '|' . $storage] = (int)$row['division_id'];
            // Plant-only mapping acts as fallback when Storage Location is intentionally blank.
            if ($storage === '') {
                $index[$plant . '|*'] = (int)$row['division_id'];
            }
        }
        return $index;
    }

    public function saveMapping(string $plant, string $storage, int $divisionId, int $userId): void
    {
        $this->ensureTables();
        $plant = strtoupper(trim($plant));
        $storage = strtoupper(trim($storage));
        if ($plant === '' || $divisionId <= 0) {
            throw new \RuntimeException('SAP Plant and Dashboard Division are required.');
        }

        $stmt = Database::connection()->prepare(
            "INSERT INTO sap_division_mapping
                (sap_plant,sap_storage_location,division_id,is_active,created_by)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE division_id=VALUES(division_id),is_active=1,created_by=VALUES(created_by)"
        );
        $stmt->execute([$plant,$storage,$divisionId,1,$userId]);
    }

    public function deleteMapping(int $id): void
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare('DELETE FROM sap_division_mapping WHERE id=?');
        $stmt->execute([$id]);
    }

    public function hasRecentRunningSync(int $minutes = 120): bool
    {
        $this->ensureTables();
        $minutes = max(5, min(1440, $minutes));
        $value = Database::connection()->query(
            "SELECT COUNT(*) FROM sap_sync_log
             WHERE status='running'
               AND started_at >= DATE_SUB(NOW(), INTERVAL " . $minutes . " MINUTE)"
        )->fetchColumn();
        return (int)$value > 0;
    }

    public function createLog(string $triggerType, ?int $userId): int
    {
        $this->ensureTables();
        $triggerType = in_array($triggerType, ['manual','scheduled','preview','test'], true) ? $triggerType : 'manual';
        $stmt = Database::connection()->prepare(
            "INSERT INTO sap_sync_log (trigger_type,status,started_at,initiated_by)
             VALUES (?,'running',NOW(),?)"
        );
        $stmt->execute([$triggerType,$userId ?: null]);
        return (int)Database::connection()->lastInsertId();
    }

    public function finishLog(int $id, string $status, array $stats, string $message, array $summary = []): void
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare(
            "UPDATE sap_sync_log SET
                status=?,finished_at=NOW(),product_rows=?,batch_rows=?,stock_rows=?,
                mapped_stock_rows=?,published_batches=?,rejected_rows=?,unmapped_rows=?,
                message=?,summary_json=?
             WHERE id=?"
        );
        $stmt->execute([
            $status,
            (int)($stats['product_rows'] ?? 0),
            (int)($stats['batch_rows'] ?? 0),
            (int)($stats['stock_rows'] ?? 0),
            (int)($stats['mapped_stock_rows'] ?? 0),
            (int)($stats['published_batches'] ?? 0),
            (int)($stats['rejected_rows'] ?? 0),
            (int)($stats['unmapped_rows'] ?? 0),
            substr($message, 0, 500),
            $summary ? json_encode($summary, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
            $id,
        ]);
    }

    public function recordTest(string $status, string $summary): void
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare(
            'UPDATE sap_integration_settings
             SET last_test_at=NOW(),last_test_status=?,last_test_summary=? WHERE id=1'
        );
        $stmt->execute([$status, substr($summary,0,500)]);
    }

    public function recordSync(string $status, string $summary): void
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare(
            'UPDATE sap_integration_settings
             SET last_sync_at=NOW(),last_sync_status=?,last_sync_summary=? WHERE id=1'
        );
        $stmt->execute([$status, substr($summary,0,500)]);
    }

    public function recentLogs(int $limit = 20): array
    {
        $this->ensureTables();
        $limit = max(1,min(100,$limit));
        return Database::connection()->query(
            "SELECT l.*,u.name initiated_by_name
             FROM sap_sync_log l
             LEFT JOIN users u ON u.id=l.initiated_by
             ORDER BY l.id DESC LIMIT " . $limit
        )->fetchAll();
    }

    public function stagingCounts(): array
    {
        $this->ensureTables();
        $pdo = Database::connection();
        return [
            'products'=>(int)$pdo->query('SELECT COUNT(*) FROM sap_products')->fetchColumn(),
            'batches'=>(int)$pdo->query('SELECT COUNT(*) FROM sap_batches')->fetchColumn(),
            'stock'=>(int)$pdo->query('SELECT COUNT(*) FROM sap_stock')->fetchColumn(),
            'unmapped'=>(int)$pdo->query('SELECT COUNT(*) FROM sap_stock WHERE division_id IS NULL')->fetchColumn(),
            'mapped'=>(int)$pdo->query('SELECT COUNT(*) FROM sap_stock WHERE division_id IS NOT NULL')->fetchColumn(),
        ];
    }

    public function latestSuccessfulStockSync(?int $divisionId = null): ?string
    {
        $this->ensureTables();
        $sql = "SELECT MAX(s.synced_at)
                FROM sap_stock s
                JOIN sap_sync_log l ON l.id=s.sync_log_id
                WHERE l.status='success' AND s.division_id IS NOT NULL";
        $params = [];
        if ($divisionId) {
            $sql .= ' AND s.division_id=?';
            $params[] = $divisionId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value ? (string)$value : null;
    }

    public function isPublishingEnabled(): bool
    {
        $settings = $this->settings();
        return !empty($settings['is_enabled']) && !empty($settings['publish_to_dashboard']);
    }
}
