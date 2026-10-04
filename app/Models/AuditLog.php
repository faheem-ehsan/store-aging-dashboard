<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class AuditLog
{
    private ?bool $tableAvailable = null;

    public function tableExists(): bool
    {
        if ($this->tableAvailable !== null) return $this->tableAvailable;
        try {
            $stmt = Database::connection()->query("SHOW TABLES LIKE 'audit_logs'");
            $this->tableAvailable = (bool)$stmt->fetch(PDO::FETCH_NUM);
        } catch (\Throwable $e) {
            $this->tableAvailable = false;
        }
        return $this->tableAvailable;
    }

    public function create(array $data): void
    {
        if (!$this->tableExists()) return;

        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_logs
             (actor_user_id,actor_name,actor_email,actor_role,division_id,division_code,action,entity_type,entity_id,description,details_json,ip_address,user_agent)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['actor_user_id'] ?? null,
            $data['actor_name'] ?? null,
            $data['actor_email'] ?? null,
            $data['actor_role'] ?? null,
            $data['division_id'] ?? null,
            $data['division_code'] ?? null,
            $data['action'] ?? 'UNKNOWN',
            $data['entity_type'] ?? null,
            $data['entity_id'] ?? null,
            $data['description'] ?? '',
            $data['details_json'] ?? null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
        ]);
    }

    public function search(array $filters, int $limit = 100, int $offset = 0): array
    {
        if (!$this->tableExists()) return [];

        [$where, $params] = $this->where($filters);
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $sql = 'SELECT * FROM audit_logs' . $where . ' ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $filters): int
    {
        if (!$this->tableExists()) return 0;
        [$where, $params] = $this->where($filters);
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM audit_logs' . $where);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function actions(): array
    {
        if (!$this->tableExists()) return [];
        $stmt = Database::connection()->query('SELECT DISTINCT action FROM audit_logs ORDER BY action');
        return array_map(static fn(array $r): string => (string)$r['action'], $stmt->fetchAll());
    }

    public function stats(): array
    {
        if (!$this->tableExists()) {
            return ['today' => 0, 'failed_logins' => 0, 'stock_changes' => 0, 'imports' => 0];
        }
        $sql = "SELECT
                    SUM(created_at >= CURDATE()) AS today,
                    SUM(action='LOGIN_FAILED') AS failed_logins,
                    SUM(action IN ('BATCH_CREATE','BATCH_UPDATE','BATCH_DELETE','BATCH_BULK_DELETE')) AS stock_changes,
                    SUM(action='CSV_IMPORT') AS imports
                FROM audit_logs";
        $row = Database::connection()->query($sql)->fetch() ?: [];
        return [
            'today' => (int)($row['today'] ?? 0),
            'failed_logins' => (int)($row['failed_logins'] ?? 0),
            'stock_changes' => (int)($row['stock_changes'] ?? 0),
            'imports' => (int)($row['imports'] ?? 0),
        ];
    }

    private function where(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['q'])) {
            $q = '%' . trim((string)$filters['q']) . '%';
            $clauses[] = '(actor_name LIKE ? OR actor_email LIKE ? OR description LIKE ? OR entity_id LIKE ? OR division_code LIKE ?)';
            array_push($params, $q, $q, $q, $q, $q);
        }
        if (!empty($filters['action'])) {
            $clauses[] = 'action=?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $clauses[] = 'actor_user_id=?';
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['division_id'])) {
            $clauses[] = 'division_id=?';
            $params[] = (int)$filters['division_id'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        return [$clauses ? ' WHERE ' . implode(' AND ', $clauses) : '', $params];
    }
}
