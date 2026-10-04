<?php
namespace App\Models;

use App\Core\Database;

class DailyStockReport
{
    public function ensureTables(): void
    {
        $pdo = Database::connection();

        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_stock_reports (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_date DATE NOT NULL,
            division_id INT UNSIGNED NOT NULL,
            total_batches INT UNSIGNED NOT NULL DEFAULT 0,
            total_quantity DECIMAL(20,3) NOT NULL DEFAULT 0,
            total_stock_value DECIMAL(20,2) NOT NULL DEFAULT 0,
            grade_summary_json JSON NULL,
            generation_type ENUM('manual','automatic') NOT NULL DEFAULT 'manual',
            generated_by INT UNSIGNED NULL,
            generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            verified_by INT UNSIGNED NULL,
            verified_at DATETIME NULL,
            verification_note VARCHAR(500) NULL,
            CONSTRAINT fk_daily_stock_report_division
                FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT,
            CONSTRAINT fk_daily_stock_report_generated_by
                FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_daily_stock_report_verified_by
                FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY uq_daily_stock_report_division_date (division_id, report_date),
            INDEX idx_daily_stock_report_date (report_date),
            INDEX idx_daily_stock_report_verified (verified_at)
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_stock_report_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id BIGINT UNSIGNED NOT NULL,
            source_batch_id BIGINT UNSIGNED NULL,
            product_code VARCHAR(80) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            batch_no VARCHAR(80) NOT NULL,
            current_qty DECIMAL(20,3) NOT NULL DEFAULT 0,
            uom VARCHAR(40) NOT NULL DEFAULT '',
            stock_value DECIMAL(20,2) NOT NULL DEFAULT 0,
            manufacture_date DATE NOT NULL,
            expiry_date DATE NOT NULL,
            grade_code VARCHAR(10) NOT NULL,
            grade_name VARCHAR(50) NOT NULL,
            stock_location VARCHAR(150) NULL,
            source_updated_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_daily_stock_item_report
                FOREIGN KEY (report_id) REFERENCES daily_stock_reports(id) ON DELETE CASCADE,
            INDEX idx_daily_stock_item_report (report_id),
            INDEX idx_daily_stock_item_product (report_id, product_code),
            INDEX idx_daily_stock_item_batch (batch_no),
            INDEX idx_daily_stock_item_grade (report_id, grade_code)
        ) ENGINE=InnoDB");
    }

    public function findByDivisionDate(int $divisionId, string $reportDate): ?array
    {
        $this->ensureTables();

        $stmt = Database::connection()->prepare(
            "SELECT r.*,
                    d.code division_code,
                    d.name division_name,
                    gu.name generated_by_name,
                    vu.name verified_by_name
             FROM daily_stock_reports r
             JOIN divisions d ON d.id=r.division_id
             LEFT JOIN users gu ON gu.id=r.generated_by
             LEFT JOIN users vu ON vu.id=r.verified_by
             WHERE r.division_id=? AND r.report_date=?
             LIMIT 1"
        );
        $stmt->execute([$divisionId, $reportDate]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $this->ensureTables();

        $stmt = Database::connection()->prepare(
            "SELECT r.*,
                    d.code division_code,
                    d.name division_name,
                    gu.name generated_by_name,
                    vu.name verified_by_name
             FROM daily_stock_reports r
             JOIN divisions d ON d.id=r.division_id
             LEFT JOIN users gu ON gu.id=r.generated_by
             LEFT JOIN users vu ON vu.id=r.verified_by
             WHERE r.id=?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function reportsForDate(string $reportDate): array
    {
        $this->ensureTables();

        $stmt = Database::connection()->prepare(
            "SELECT r.*,
                    d.code division_code,
                    d.name division_name,
                    gu.name generated_by_name,
                    vu.name verified_by_name
             FROM daily_stock_reports r
             JOIN divisions d ON d.id=r.division_id
             LEFT JOIN users gu ON gu.id=r.generated_by
             LEFT JOIN users vu ON vu.id=r.verified_by
             WHERE r.report_date=?
             ORDER BY d.code"
        );
        $stmt->execute([$reportDate]);
        return $stmt->fetchAll();
    }

    public function previousReport(int $divisionId, string $beforeDate): ?array
    {
        $this->ensureTables();

        $stmt = Database::connection()->prepare(
            "SELECT r.*,
                    d.code division_code,
                    d.name division_name
             FROM daily_stock_reports r
             JOIN divisions d ON d.id=r.division_id
             WHERE r.division_id=? AND r.report_date<?
             ORDER BY r.report_date DESC
             LIMIT 1"
        );
        $stmt->execute([$divisionId, $beforeDate]);
        return $stmt->fetch() ?: null;
    }

    public function replaceSnapshot(
        int $divisionId,
        string $reportDate,
        array $summary,
        array $items,
        string $generationType,
        ?int $generatedBy,
        bool $force = false
    ): array {
        $this->ensureTables();

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $existingStmt = $pdo->prepare(
                'SELECT * FROM daily_stock_reports WHERE division_id=? AND report_date=? FOR UPDATE'
            );
            $existingStmt->execute([$divisionId, $reportDate]);
            $existing = $existingStmt->fetch() ?: null;

            if ($existing && !empty($existing['verified_at']) && !$force) {
                throw new \RuntimeException('This daily stock report is already verified and locked.');
            }

            $gradeSummaryJson = json_encode(
                $summary['grade_summary'] ?? [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            );

            if ($existing) {
                $reportId = (int)$existing['id'];

                $deleteItems = $pdo->prepare('DELETE FROM daily_stock_report_items WHERE report_id=?');
                $deleteItems->execute([$reportId]);

                $update = $pdo->prepare(
                    "UPDATE daily_stock_reports
                     SET total_batches=?,
                         total_quantity=?,
                         total_stock_value=?,
                         grade_summary_json=?,
                         generation_type=?,
                         generated_by=?,
                         generated_at=NOW(),
                         verified_by=NULL,
                         verified_at=NULL,
                         verification_note=NULL
                     WHERE id=?"
                );
                $update->execute([
                    (int)($summary['total_batches'] ?? 0),
                    $this->decimal3($summary['total_quantity'] ?? 0),
                    $this->decimal2($summary['total_stock_value'] ?? 0),
                    $gradeSummaryJson,
                    $generationType === 'automatic' ? 'automatic' : 'manual',
                    $generatedBy,
                    $reportId,
                ]);
                $created = false;
            } else {
                $insert = $pdo->prepare(
                    "INSERT INTO daily_stock_reports
                        (report_date,division_id,total_batches,total_quantity,total_stock_value,
                         grade_summary_json,generation_type,generated_by,generated_at)
                     VALUES (?,?,?,?,?,?,?,?,NOW())"
                );
                $insert->execute([
                    $reportDate,
                    $divisionId,
                    (int)($summary['total_batches'] ?? 0),
                    $this->decimal3($summary['total_quantity'] ?? 0),
                    $this->decimal2($summary['total_stock_value'] ?? 0),
                    $gradeSummaryJson,
                    $generationType === 'automatic' ? 'automatic' : 'manual',
                    $generatedBy,
                ]);
                $reportId = (int)$pdo->lastInsertId();
                $created = true;
            }

            $itemInsert = $pdo->prepare(
                "INSERT INTO daily_stock_report_items
                    (report_id,source_batch_id,product_code,product_name,batch_no,current_qty,uom,
                     stock_value,manufacture_date,expiry_date,grade_code,grade_name,stock_location,
                     source_updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );

            foreach ($items as $item) {
                $itemInsert->execute([
                    $reportId,
                    isset($item['source_batch_id']) ? (int)$item['source_batch_id'] : null,
                    (string)$item['product_code'],
                    (string)$item['product_name'],
                    (string)$item['batch_no'],
                    $this->decimal3($item['current_qty'] ?? 0),
                    (string)($item['uom'] ?? ''),
                    $this->decimal2($item['stock_value'] ?? 0),
                    (string)$item['manufacture_date'],
                    (string)$item['expiry_date'],
                    (string)$item['grade_code'],
                    (string)$item['grade_name'],
                    trim((string)($item['stock_location'] ?? '')) ?: null,
                    trim((string)($item['source_updated_at'] ?? '')) ?: null,
                ]);
            }

            $pdo->commit();

            return [
                'report_id' => $reportId,
                'created' => $created,
                'item_count' => count($items),
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function verify(int $reportId, int $userId, string $note = ''): void
    {
        $this->ensureTables();

        $stmt = Database::connection()->prepare(
            "UPDATE daily_stock_reports
             SET verified_by=?, verified_at=NOW(), verification_note=?
             WHERE id=?"
        );
        $stmt->execute([
            $userId,
            trim($note) !== '' ? substr(trim($note), 0, 500) : null,
            $reportId,
        ]);
    }

    public function itemCount(int $reportId, string $search = ''): int
    {
        $this->ensureTables();

        $sql = 'SELECT COUNT(*) FROM daily_stock_report_items WHERE report_id=?';
        $params = [$reportId];

        if ($search !== '') {
            $sql .= ' AND (product_code LIKE ? OR product_name LIKE ? OR batch_no LIKE ? OR stock_location LIKE ? OR uom LIKE ?)';
            $q = '%' . $search . '%';
            array_push($params, $q, $q, $q, $q, $q);
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function items(int $reportId, string $search = '', int $limit = 50, int $offset = 0): array
    {
        $this->ensureTables();

        $sql = 'SELECT * FROM daily_stock_report_items WHERE report_id=?';
        $params = [$reportId];

        if ($search !== '') {
            $sql .= ' AND (product_code LIKE ? OR product_name LIKE ? OR batch_no LIKE ? OR stock_location LIKE ? OR uom LIKE ?)';
            $q = '%' . $search . '%';
            array_push($params, $q, $q, $q, $q, $q);
        }

        $sql .= ' ORDER BY product_code, batch_no, id LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allItems(int $reportId): array
    {
        $this->ensureTables();

        $stmt = Database::connection()->prepare(
            'SELECT * FROM daily_stock_report_items WHERE report_id=? ORDER BY product_code,batch_no,id'
        );
        $stmt->execute([$reportId]);
        return $stmt->fetchAll();
    }

    public function productTotals(int $reportId): array
    {
        $this->ensureTables();

        $stmt = Database::connection()->prepare(
            "SELECT product_code,
                    MAX(product_name) product_name,
                    SUM(current_qty) total_quantity,
                    SUM(stock_value) total_stock_value,
                    COUNT(*) batch_count
             FROM daily_stock_report_items
             WHERE report_id=?
             GROUP BY product_code
             ORDER BY product_code"
        );
        $stmt->execute([$reportId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(string)$row['product_code']] = $row;
        }
        return $result;
    }

    public function exportRows(string $reportDate, ?int $divisionId = null): array
    {
        $this->ensureTables();

        $sql = "SELECT r.report_date,
                       r.generated_at,
                       r.generation_type,
                       r.verified_at,
                       d.code division_code,
                       d.name division_name,
                       i.product_code,
                       i.product_name,
                       i.batch_no,
                       i.current_qty,
                       i.uom,
                       i.stock_value,
                       i.manufacture_date,
                       i.expiry_date,
                       i.grade_code,
                       i.grade_name,
                       i.stock_location
                FROM daily_stock_reports r
                JOIN divisions d ON d.id=r.division_id
                JOIN daily_stock_report_items i ON i.report_id=r.id
                WHERE r.report_date=?";
        $params = [$reportDate];

        if ($divisionId) {
            $sql .= ' AND r.division_id=?';
            $params[] = $divisionId;
        }

        $sql .= ' ORDER BY d.code,i.product_code,i.batch_no,i.id';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function decimal2(mixed $value): string
    {
        return number_format((float)$value, 2, '.', '');
    }

    private function decimal3(mixed $value): string
    {
        return number_format((float)$value, 3, '.', '');
    }
}
