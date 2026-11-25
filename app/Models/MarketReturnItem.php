<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Auth;

class MarketReturnItem
{
    private function baseSelect(): string
    {
        return 'SELECT mri.*, d.code division_code, d.name division_name,
                       mi.file_name import_file_name, mi.imported_at
                FROM market_return_items mri
                JOIN divisions d ON d.id=mri.division_id
                LEFT JOIN market_return_imports mi ON mi.id=mri.import_id';
    }

    public function allAccessible(array $user, array $filters = []): array
    {
        $sql = $this->baseSelect() . ' WHERE 1=1';
        $params = [];

        $canReadAllDivisions = Auth::canReadAllDivisions($user);

        if (!$canReadAllDivisions) {
            $sql .= ' AND mri.division_id=?';
            $params[] = (int)$user['division_id'];
        } elseif (!empty($filters['division_id'])) {
            $sql .= ' AND mri.division_id=?';
            $params[] = (int)$filters['division_id'];
        }

        if (!empty($filters['category'])) {
            $sql .= ' AND mri.category_code=?';
            $params[] = (string)$filters['category'];
        }

        if (!empty($filters['q'])) {
            $q = '%' . trim((string)$filters['q']) . '%';
            $sql .= ' AND (mri.product_name LIKE ? OR mri.remarks LIKE ? OR mri.section_name LIKE ? OR mri.unit LIKE ? OR mri.expiry_raw LIKE ?)';
            array_push($params, $q, $q, $q, $q, $q);
        }

        if (($filters['value_status'] ?? '') === 'valued') {
            $sql .= ' AND mri.total_amount IS NOT NULL';
        } elseif (($filters['value_status'] ?? '') === 'unvalued') {
            $sql .= ' AND mri.total_amount IS NULL';
        }

        $sql .= " ORDER BY
                    CASE
                        WHEN FIELD(mri.category_code,'fresh_saleable','utilizable','cabinets','rawalkot','export','promotion','tablet_strips','expired') = 0 THEN 999
                        ELSE FIELD(mri.category_code,'fresh_saleable','utilizable','cabinets','rawalkot','export','promotion','tablet_strips','expired')
                    END,
                    mri.source_sheet, mri.section_name, mri.product_name";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function categoryOptions(array $user, ?int $divisionId = null): array
    {
        $sql = 'SELECT mri.category_code,
                       MAX(CASE WHEN TRIM(COALESCE(mri.source_sheet, \'\')) <> \'\' THEN mri.source_sheet ELSE mri.category_code END) category_label
                FROM market_return_items mri
                WHERE 1=1';
        $params = [];

        $canReadAllDivisions = Auth::canReadAllDivisions($user);

        if (!$canReadAllDivisions) {
            $sql .= ' AND mri.division_id=?';
            $params[] = (int)$user['division_id'];
        } elseif ($divisionId) {
            $sql .= ' AND mri.division_id=?';
            $params[] = $divisionId;
        }

        $sql .= ' GROUP BY mri.category_code ORDER BY category_label';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function bulkDeleteDivisionStats(): array
    {
        $sql = 'SELECT d.id division_id,
                       d.code division_code,
                       d.name division_name,
                       d.is_active,
                       COUNT(mri.id) item_count,
                       COALESCE(SUM(mri.quantity),0) total_qty,
                       COALESCE(SUM(mri.total_amount),0) stock_value
                FROM divisions d
                LEFT JOIN market_return_items mri ON mri.division_id=d.id
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
                'deleted_items' => 0,
                'divisions' => [],
            ];
        }

        $pdo = Database::connection();
        $placeholders = implode(',', array_fill(0, count($divisionIds), '?'));

        // Snapshot division totals before deletion for Activity Logs and UI feedback.
        $statsStmt = $pdo->prepare(
            "SELECT d.id division_id,
                    d.code division_code,
                    d.name division_name,
                    COUNT(mri.id) item_count,
                    COALESCE(SUM(mri.quantity),0) total_qty,
                    COALESCE(SUM(mri.total_amount),0) stock_value
             FROM divisions d
             LEFT JOIN market_return_items mri ON mri.division_id=d.id
             WHERE d.id IN ($placeholders)
             GROUP BY d.id,d.code,d.name
             ORDER BY d.code"
        );
        $statsStmt->execute($divisionIds);
        $stats = $statsStmt->fetchAll();

        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare(
                "DELETE FROM market_return_items WHERE division_id IN ($placeholders)"
            );
            $delete->execute($divisionIds);
            $deletedItems = $delete->rowCount();

            // Intentionally preserve:
            // - divisions
            // - users
            // - market_return_imports (import history)
            // - market_return_division_mappings
            // - Finish Goods stock
            $pdo->commit();

            return [
                'deleted_items' => $deletedItems,
                'divisions' => $stats,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function findAccessible(int $id, array $user): ?array
    {
        $sql = $this->baseSelect() . ' WHERE mri.id=?';
        $params = [$id];
        $canReadAllDivisions = Auth::canReadAllDivisions($user);
        if (!$canReadAllDivisions) {
            $sql .= ' AND mri.division_id=?';
            $params[] = (int)$user['division_id'];
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function save(array $data, array $user, ?int $id = null): int
    {
        $divisionId = Auth::isGlobalOperator($user)
            ? (int)$data['division_id']
            : (int)$user['division_id'];

        $payload = [
            $divisionId,
            trim((string)$data['category_code']),
            trim((string)($data['source_sheet'] ?? 'Manual Entry')) ?: 'Manual Entry',
            trim((string)($data['section_name'] ?? '')) ?: null,
            trim((string)($data['serial_no'] ?? '')) ?: null,
            trim((string)$data['product_name']),
            (float)$data['quantity'],
            trim((string)$data['unit']),
            trim((string)($data['expiry_raw'] ?? '')) ?: null,
            !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            $data['retail_price'] === '' || $data['retail_price'] === null ? null : (float)$data['retail_price'],
            $data['total_amount'] === '' || $data['total_amount'] === null ? null : (float)$data['total_amount'],
            trim((string)($data['amount_text'] ?? '')) ?: null,
            trim((string)($data['remarks'] ?? '')) ?: null,
            trim((string)($data['aux_label'] ?? '')) ?: null,
            trim((string)($data['aux_value'] ?? '')) ?: null,
            !empty($data['report_date']) ? $data['report_date'] : null,
        ];

        $pdo = Database::connection();
        if ($id) {
            $existing = $this->findAccessible($id, $user);
            if (!$existing) throw new \RuntimeException('Market return item not found or forbidden.');
            $stmt = $pdo->prepare(
                'UPDATE market_return_items SET division_id=?,category_code=?,source_sheet=?,section_name=?,serial_no=?,product_name=?,quantity=?,unit=?,expiry_raw=?,expiry_date=?,retail_price=?,total_amount=?,amount_text=?,remarks=?,aux_label=?,aux_value=?,report_date=?,updated_at=NOW() WHERE id=?'
            );
            $payload[] = $id;
            $stmt->execute($payload);
            return $id;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO market_return_items (division_id,category_code,source_sheet,section_name,serial_no,product_name,quantity,unit,expiry_raw,expiry_date,retail_price,total_amount,amount_text,remarks,aux_label,aux_value,report_date,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $payload[] = isset($user['id']) ? (int)$user['id'] : null;
        $stmt->execute($payload);
        return (int)$pdo->lastInsertId();
    }

    public function delete(int $id, array $user): void
    {
        $row = $this->findAccessible($id, $user);
        if (!$row) return;
        $stmt = Database::connection()->prepare('DELETE FROM market_return_items WHERE id=?');
        $stmt->execute([$id]);
    }

    public function importSnapshot(
        int $divisionId,
        array $items,
        array $categories,
        array $user,
        string $fileName,
        string $fileType,
        bool $replaceExisting = true
    ): array {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $importStmt = $pdo->prepare(
                'INSERT INTO market_return_imports (division_id,file_name,file_type,imported_by,row_count,category_count,replace_existing,status)
                 VALUES (?,?,?,?,0,?,?,\'processing\')'
            );
            $importStmt->execute([
                $divisionId,
                substr($fileName, 0, 255),
                substr($fileType, 0, 20),
                isset($user['id']) ? (int)$user['id'] : null,
                count($categories),
                $replaceExisting ? 1 : 0,
            ]);
            $importId = (int)$pdo->lastInsertId();

            $deleted = 0;
            if ($replaceExisting && $items) {
                // Replacement is scoped to Division + Category + Section.
                // A newly introduced Section under an existing Category is therefore
                // added as a new group instead of deleting the other Sections.
                $replaceGroups = [];
                foreach ($items as $item) {
                    $categoryCode = trim((string)($item['category_code'] ?? ''));
                    $sectionName = trim((string)($item['section_name'] ?? ''));
                    if ($categoryCode === '') continue;
                    $replaceGroups[$categoryCode . "\x1F" . $sectionName] = [$categoryCode, $sectionName];
                }

                $delete = $pdo->prepare(
                    "DELETE FROM market_return_items
                     WHERE division_id=? AND category_code=? AND COALESCE(section_name,'')=?"
                );

                foreach ($replaceGroups as [$categoryCode, $sectionName]) {
                    $delete->execute([$divisionId, $categoryCode, $sectionName]);
                    $deleted += $delete->rowCount();
                }
            }

            $insert = $pdo->prepare(
                'INSERT INTO market_return_items
                 (division_id,category_code,source_sheet,section_name,serial_no,product_name,quantity,unit,expiry_raw,expiry_date,retail_price,total_amount,amount_text,remarks,aux_label,aux_value,report_date,import_id,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );

            $inserted = 0;
            foreach ($items as $item) {
                $insert->execute([
                    $divisionId,
                    $item['category_code'],
                    $item['source_sheet'],
                    $item['section_name'] ?: null,
                    $item['serial_no'] ?: null,
                    $item['product_name'],
                    (float)$item['quantity'],
                    $item['unit'] ?: 'Nos',
                    $item['expiry_raw'] ?: null,
                    $item['expiry_date'] ?: null,
                    $item['retail_price'],
                    $item['total_amount'],
                    $item['amount_text'] ?: null,
                    $item['remarks'] ?: null,
                    $item['aux_label'] ?: null,
                    $item['aux_value'] ?: null,
                    $item['report_date'] ?: null,
                    $importId,
                    isset($user['id']) ? (int)$user['id'] : null,
                ]);
                $inserted++;
            }

            $summary = json_encode([
                'inserted' => $inserted,
                'deleted_previous' => $deleted,
                'categories' => array_values($categories),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $update = $pdo->prepare('UPDATE market_return_imports SET row_count=?, status=\'completed\', summary_json=? WHERE id=?');
            $update->execute([$inserted, $summary, $importId]);
            $pdo->commit();

            return [
                'import_id' => $importId,
                'inserted' => $inserted,
                'deleted_previous' => $deleted,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
