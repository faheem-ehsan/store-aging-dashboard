<?php
namespace App\Models;

use App\Core\Database;

class ProductMaster
{
    public function ensureTable(): void
    {
        Database::connection()->exec("CREATE TABLE IF NOT EXISTS product_master (
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
        ) ENGINE=InnoDB");
    }

    public function all(?int $divisionId = null, string $search = ''): array
    {
        $this->ensureTable();

        $sql = "SELECT pm.*, d.code division_code, d.name division_name
                FROM product_master pm
                JOIN divisions d ON d.id=pm.division_id
                WHERE 1=1";
        $params = [];

        if ($divisionId) {
            $sql .= ' AND pm.division_id=?';
            $params[] = $divisionId;
        }

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' AND (pm.product_code LIKE ? OR pm.product_name LIKE ? OR pm.uom LIKE ?)';
            $q = '%' . $search . '%';
            array_push($params, $q, $q, $q);
        }

        $sql .= ' ORDER BY d.code, pm.product_code, pm.product_name';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function activeByDivision(int $divisionId): array
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            "SELECT pm.*, d.code division_code, d.name division_name
             FROM product_master pm
             JOIN divisions d ON d.id=pm.division_id
             WHERE pm.division_id=? AND pm.is_active=1 AND d.is_active=1
             ORDER BY pm.product_code, pm.product_name"
        );
        $stmt->execute([$divisionId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            "SELECT pm.*, d.code division_code, d.name division_name
             FROM product_master pm
             JOIN divisions d ON d.id=pm.division_id
             WHERE pm.id=? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findActiveByDivisionCode(int $divisionId, string $productCode): ?array
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            "SELECT pm.*, d.code division_code, d.name division_name
             FROM product_master pm
             JOIN divisions d ON d.id=pm.division_id
             WHERE pm.division_id=? AND pm.product_code=? AND pm.is_active=1 AND d.is_active=1
             LIMIT 1"
        );
        $stmt->execute([$divisionId, trim($productCode)]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $divisionId, string $productCode, string $productName, string $uom): int
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            'INSERT INTO product_master (division_id,product_code,product_name,uom,is_active) VALUES (?,?,?,?,1)'
        );
        $stmt->execute([
            $divisionId,
            trim($productCode),
            trim($productName),
            strtoupper(trim($uom)),
        ]);
        return (int)Database::connection()->lastInsertId();
    }

    public function update(int $id, int $divisionId, string $productCode, string $productName, string $uom): void
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            'UPDATE product_master SET division_id=?,product_code=?,product_name=?,uom=? WHERE id=?'
        );
        $stmt->execute([
            $divisionId,
            trim($productCode),
            trim($productName),
            strtoupper(trim($uom)),
            $id,
        ]);
    }

    public function toggle(int $id): void
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            'UPDATE product_master SET is_active=IF(is_active=1,0,1) WHERE id=?'
        );
        $stmt->execute([$id]);
    }

    public function delete(int $id): void
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare('DELETE FROM product_master WHERE id=?');
        $stmt->execute([$id]);
    }
}
