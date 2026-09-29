<?php
namespace App\Models;

use App\Core\Database;

class Division
{
    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM divisions';
        if ($activeOnly) $sql .= ' WHERE is_active = 1';
        $sql .= ' ORDER BY code';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function findByCodeOrName(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') return null;
        $stmt = Database::connection()->prepare(
            'SELECT * FROM divisions WHERE is_active=1 AND (UPPER(code)=UPPER(?) OR UPPER(name)=UPPER(?)) LIMIT 1'
        );
        $stmt->execute([$value, $value]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM divisions WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $code, string $name): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO divisions (code, name, is_active) VALUES (?, ?, 1)');
        $stmt->execute([strtoupper(trim($code)), trim($name)]);
        return (int)Database::connection()->lastInsertId();
    }

    public function toggle(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE divisions SET is_active = IF(is_active=1,0,1) WHERE id=?');
        $stmt->execute([$id]);
    }
}
