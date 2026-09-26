<?php
namespace App\Models;

use App\Core\Database;

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

        if ($user['role'] !== 'super_admin') {
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

    public function findAccessible(int $id, array $user): ?array
    {
        $sql = $this->baseSelect() . ' WHERE sb.id=?';
        $params = [$id];
        if ($user['role'] !== 'super_admin') {
            $sql .= ' AND sb.division_id=?';
            $params[] = (int)$user['division_id'];
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function findByNaturalKey(int $divisionId, string $materialCode, string $batchNo): ?array
    {
        $stmt = Database::connection()->prepare(
            $this->baseSelect() . ' WHERE sb.division_id=? AND m.material_code=? AND sb.batch_no=? LIMIT 1'
        );
        $stmt->execute([$divisionId, trim($materialCode), trim($batchNo)]);
        return $stmt->fetch() ?: null;
    }

    public function save(array $data, array $user, ?int $id = null): int
    {
        $pdo = Database::connection();
        $divisionId = $user['role'] === 'super_admin' ? (int)$data['division_id'] : (int)$user['division_id'];
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
        $divisionId = $user['role'] === 'super_admin' ? (int)$data['division_id'] : (int)$user['division_id'];
        $existing = $this->findByNaturalKey($divisionId, $data['material_code'], $data['batch_no']);
        if ($existing) {
            $this->save($data, $user, (int)$existing['id']);
            return 'updated';
        }
        $this->save($data, $user);
        return 'inserted';
    }

    public function delete(int $id, array $user): void
    {
        $batch = $this->findAccessible($id, $user);
        if (!$batch) return;
        $stmt = Database::connection()->prepare('DELETE FROM stock_batches WHERE id=?');
        $stmt->execute([$id]);
    }
}
