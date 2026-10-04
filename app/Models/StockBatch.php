<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Auth;

class StockBatch
{
    private function baseSelect(): string
    {
        return 'SELECT sb.*, m.material_code, m.description material_description,
                       d.code division_code, d.name division_name
                FROM stock_batches sb
                JOIN materials m ON m.id=sb.material_id
                JOIN divisions d ON d.id=sb.division_id';
    }

    public function allAccessible(array $user, array $filters = []): array
    {
        $sql = $this->baseSelect() . ' WHERE 1=1';
        $params = [];

        $canReadAllDivisions = Auth::canReadAllDivisions($user);

        if (!$canReadAllDivisions) {
            $sql .= ' AND sb.division_id=?';
            $params[] = (int)$user['division_id'];
        } elseif (!empty($filters['division_id'])) {
            $sql .= ' AND sb.division_id=?';
            $params[] = (int)$filters['division_id'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (m.material_code LIKE ? OR m.description LIKE ? OR sb.batch_no LIKE ?)';
            $q = '%' . trim($filters['q']) . '%';
            array_push($params, $q, $q, $q);
        }

        $sql .= ' ORDER BY sb.expiry_date ASC, d.code, m.material_code';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function latestUpdateInfo(array $user, ?int $divisionId = null): ?array
    {
        $sql = 'SELECT sb.updated_at,
                       sb.division_id,
                       d.code division_code,
                       d.name division_name
                FROM stock_batches sb
                JOIN divisions d ON d.id=sb.division_id
                WHERE sb.updated_at IS NOT NULL';
        $params = [];

        $canReadAllDivisions = Auth::canReadAllDivisions($user);

        if (!$canReadAllDivisions) {
            $sql .= ' AND sb.division_id=?';
            $params[] = (int)$user['division_id'];
        } elseif ($divisionId) {
            $sql .= ' AND sb.division_id=?';
            $params[] = $divisionId;
        }

        $sql .= ' ORDER BY sb.updated_at DESC, sb.id DESC LIMIT 1';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function latestUpdatedAt(array $user, ?int $divisionId = null): ?string
    {
        $sql = 'SELECT MAX(sb.updated_at) FROM stock_batches sb WHERE 1=1';
        $params = [];

        $canReadAllDivisions = Auth::canReadAllDivisions($user);

        if (!$canReadAllDivisions) {
            $sql .= ' AND sb.division_id=?';
            $params[] = (int)$user['division_id'];
        } elseif ($divisionId) {
            $sql .= ' AND sb.division_id=?';
            $params[] = $divisionId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();

        return $value ? (string)$value : null;
    }

    public function findAccessible(int $id, array $user): ?array
    {
        $sql = $this->baseSelect() . ' WHERE sb.id=?';
        $params = [$id];
        $canReadAllDivisions = Auth::canReadAllDivisions($user);
        if (!$canReadAllDivisions) {
            $sql .= ' AND sb.division_id=?';
            $params[] = (int)$user['division_id'];
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function findByNaturalKey(int $divisionId, string $materialCode, string $batchNo): ?array
    {
        // A stock row is unique by Division + Product/Material + Batch No.
        // Material code alone must never be treated as an existing batch during CSV import.
        // BINARY keeps the batch comparison exact so a genuinely different batch number
        // cannot accidentally select an older record for update.
        $stmt = Database::connection()->prepare(
            $this->baseSelect() . ' WHERE sb.division_id=? AND m.material_code=? AND BINARY sb.batch_no=BINARY ? LIMIT 1'
        );
        $stmt->execute([$divisionId, trim($materialCode), trim($batchNo)]);
        return $stmt->fetch() ?: null;
    }

    public function save(array $data, array $user, ?int $id = null): int
    {
        $pdo = Database::connection();
        $divisionId = Auth::isGlobalOperator($user) ? (int)$data['division_id'] : (int)$user['division_id'];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id FROM materials WHERE division_id=? AND material_code=? LIMIT 1');
            $stmt->execute([$divisionId, trim($data['material_code'])]);
            $materialId = $stmt->fetchColumn();
            if ($materialId) {
                $u = $pdo->prepare('UPDATE materials SET description=?, is_active=1 WHERE id=?');
                $u->execute([trim($data['material_description']), $materialId]);
            } else {
                $i = $pdo->prepare('INSERT INTO materials (division_id,material_code,description,is_active) VALUES (?,?,?,1)');
                $i->execute([$divisionId, trim($data['material_code']), trim($data['material_description'])]);
                $materialId = (int)$pdo->lastInsertId();
            }

            $lastReceipt = !empty($data['last_receipt_date']) ? $data['last_receipt_date'] : null;
            $uom = trim($data['uom'] ?? 'PCS') ?: 'PCS';
            $stockValue = (float)($data['stock_value'] ?? 0);
            $receivingStore = trim($data['receiving_store'] ?? '');

            if ($id) {
                $existing = $this->findAccessible($id, $user);
                if (!$existing) throw new \RuntimeException('Batch not found or forbidden.');
                $stmt = $pdo->prepare(
                    'UPDATE stock_batches SET division_id=?,material_id=?,batch_no=?,manufacture_date=?,expiry_date=?,last_receipt_date=?,current_qty=?,stock_value=?,uom=?,receiving_store=?,updated_at=NOW() WHERE id=?'
                );
                $stmt->execute([
                    $divisionId,$materialId,trim($data['batch_no']),$data['manufacture_date'],$data['expiry_date'],
                    $lastReceipt,(float)$data['current_qty'],$stockValue,$uom,$receivingStore,$id
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO stock_batches (division_id,material_id,batch_no,manufacture_date,expiry_date,last_receipt_date,current_qty,stock_value,uom,receiving_store)
                     VALUES (?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $divisionId,$materialId,trim($data['batch_no']),$data['manufacture_date'],$data['expiry_date'],
                    $lastReceipt,(float)$data['current_qty'],$stockValue,$uom,$receivingStore
                ]);
            }
            $savedId = $id ?: (int)$pdo->lastInsertId();
            $pdo->commit();
            return $savedId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function upsertImport(array $data, array $user): string
    {
        $divisionId = Auth::isGlobalOperator($user) ? (int)$data['division_id'] : (int)$user['division_id'];
        $materialCode = trim((string)$data['material_code']);
        $batchNo = trim((string)$data['batch_no']);

        // Import rule:
        //   same Division + Product Code + Batch No. => update that exact batch
        //   different Batch No.                      => always insert a new stock batch
        $existing = $this->findByNaturalKey($divisionId, $materialCode, $batchNo);
        if ($existing) {
            $this->save($data, $user, (int)$existing['id']);
            return 'updated';
        }

        try {
            $this->save($data, $user);
        } catch (\PDOException $e) {
            // Older installations may still have a legacy UNIQUE key on
            // (division_id, material_id), which wrongly prevents multiple batches
            // for one product. Give an actionable message instead of allowing the
            // import behavior to be mistaken for a material-level replacement.
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'duplicate') || str_contains($message, 'unique')) {
                throw new \RuntimeException(
                    'A different Batch No. exists for this Product Code, but the database still has a legacy material-level unique key. Run database/migrations/009_fix_stock_batch_import_key.sql once, then import again.'
                );
            }
            throw $e;
        }
        return 'inserted';
    }

    public function findManyByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));

        if (!$ids) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            $this->baseSelect() . " WHERE sb.id IN ($placeholders) ORDER BY d.code, sb.id"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function deleteSelectedByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));

        if (!$ids) {
            return [
                'deleted_batches' => 0,
                'deleted_movements' => 0,
                'rows' => [],
            ];
        }

        $rows = $this->findManyByIds($ids);
        if (!$rows) {
            return [
                'deleted_batches' => 0,
                'deleted_movements' => 0,
                'rows' => [],
            ];
        }

        // Delete only IDs that actually exist in stock_batches.
        $existingIds = array_map(
            static fn(array $row): int => (int)$row['id'],
            $rows
        );
        $placeholders = implode(',', array_fill(0, count($existingIds), '?'));

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $deletedMovements = 0;

            // Older databases may have a restrictive FK from stock_movements.
            // Remove only movement rows belonging to the selected batch IDs.
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'stock_movements'");
                if ((bool)$tableCheck->fetchColumn()) {
                    $movementDelete = $pdo->prepare(
                        "DELETE FROM stock_movements WHERE batch_id IN ($placeholders)"
                    );
                    $movementDelete->execute($existingIds);
                    $deletedMovements = $movementDelete->rowCount();
                }
            } catch (\Throwable $e) {
                // If the optional movement table is unavailable, continue with
                // the normal stock_batches delete.
            }

            $batchDelete = $pdo->prepare(
                "DELETE FROM stock_batches WHERE id IN ($placeholders)"
            );
            $batchDelete->execute($existingIds);
            $deletedBatches = $batchDelete->rowCount();

            $pdo->commit();

            return [
                'deleted_batches' => $deletedBatches,
                'deleted_movements' => $deletedMovements,
                'rows' => $rows,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function bulkDeleteDivisionStats(): array
    {
        $sql = 'SELECT d.id division_id,
                       d.code division_code,
                       d.name division_name,
                       d.is_active,
                       COUNT(sb.id) batch_count,
                       COALESCE(SUM(sb.current_qty),0) total_qty,
                       COALESCE(SUM(sb.stock_value),0) stock_value
                FROM divisions d
                LEFT JOIN stock_batches sb ON sb.division_id=d.id
                GROUP BY d.id,d.code,d.name,d.is_active
                ORDER BY d.code';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function bulkDeleteByDivisions(array $divisionIds): array
    {
        $divisionIds = array_values(array_unique(array_filter(
            array_map('intval', $divisionIds),
            static fn(int $id): bool => $id > 0
        )));

        if (!$divisionIds) {
            return [
                'deleted_batches' => 0,
                'deleted_movements' => 0,
                'divisions' => [],
            ];
        }

        $pdo = Database::connection();
        $placeholders = implode(',', array_fill(0, count($divisionIds), '?'));

        // Snapshot totals first for audit logs and confirmation messages.
        $statsStmt = $pdo->prepare(
            "SELECT d.id division_id,
                    d.code division_code,
                    d.name division_name,
                    COUNT(sb.id) batch_count,
                    COALESCE(SUM(sb.current_qty),0) total_qty,
                    COALESCE(SUM(sb.stock_value),0) stock_value
             FROM divisions d
             LEFT JOIN stock_batches sb ON sb.division_id=d.id
             WHERE d.id IN ($placeholders)
             GROUP BY d.id,d.code,d.name
             ORDER BY d.code"
        );
        $statsStmt->execute($divisionIds);
        $stats = $statsStmt->fetchAll();

        $pdo->beginTransaction();
        try {
            $deletedMovements = 0;

            // Some older installations have a restrictive FK from stock_movements.
            // Delete child rows explicitly so bulk deletion works safely regardless
            // of whether ON DELETE CASCADE is present.
            $movementTableExists = false;
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'stock_movements'");
                $movementTableExists = (bool)$tableCheck->fetchColumn();
            } catch (\Throwable $e) {
                $movementTableExists = false;
            }

            if ($movementTableExists) {
                $movementDelete = $pdo->prepare(
                    "DELETE sm
                     FROM stock_movements sm
                     INNER JOIN stock_batches sb ON sb.id=sm.batch_id
                     WHERE sb.division_id IN ($placeholders)"
                );
                $movementDelete->execute($divisionIds);
                $deletedMovements = $movementDelete->rowCount();
            }

            $batchDelete = $pdo->prepare(
                "DELETE FROM stock_batches WHERE division_id IN ($placeholders)"
            );
            $batchDelete->execute($divisionIds);
            $deletedBatches = $batchDelete->rowCount();

            $pdo->commit();

            return [
                'deleted_batches' => $deletedBatches,
                'deleted_movements' => $deletedMovements,
                'divisions' => $stats,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id, array $user): void
    {
        $batch = $this->findAccessible($id, $user);
        if (!$batch) return;
        $stmt = Database::connection()->prepare('DELETE FROM stock_batches WHERE id=?');
        $stmt->execute([$id]);
    }
}
