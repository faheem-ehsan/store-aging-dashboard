<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private ?bool $avatarColumnAvailable = null;

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, d.code division_code, d.name division_name
             FROM users u LEFT JOIN divisions d ON d.id=u.division_id
             WHERE u.id=? AND u.is_active=1 LIMIT 1'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch() ?: null;
        if ($user && !array_key_exists('avatar_path', $user)) $user['avatar_path'] = null;
        return $user;
    }

    public function findAny(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, d.code division_code, d.name division_name
             FROM users u LEFT JOIN divisions d ON d.id=u.division_id
             WHERE u.id=? LIMIT 1'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch() ?: null;
        if ($user && !array_key_exists('avatar_path', $user)) $user['avatar_path'] = null;
        return $user;
    }

    public function authenticate(string $email, string $password, ?int $divisionId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) return null;

        if ($user['role'] === 'super_admin') {
            if ($divisionId !== null) return null;
        } else {
            if (!$divisionId || (int)$user['division_id'] !== $divisionId) return null;
        }
        return $user;
    }

    public function all(): array
    {
        $avatarSelect = $this->hasAvatarColumn() ? ',u.avatar_path' : ',NULL avatar_path';
        return Database::connection()->query(
            'SELECT u.id,u.name,u.email,u.role,u.is_active,u.created_at' . $avatarSelect . ',d.code division_code,d.name division_name
             FROM users u LEFT JOIN divisions d ON d.id=u.division_id ORDER BY u.created_at DESC'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $role = $data['role'];
        $divisionId = $role === 'super_admin' ? null : (int)$data['division_id'];

        if ($this->hasAvatarColumn()) {
            $stmt = Database::connection()->prepare(
                'INSERT INTO users (division_id,name,email,password_hash,role,avatar_path,is_active) VALUES (?,?,?,?,?,?,1)'
            );
            $stmt->execute([
                $divisionId,
                trim($data['name']),
                strtolower(trim($data['email'])),
                password_hash($data['password'], PASSWORD_DEFAULT),
                $role,
                $data['avatar_path'] ?: null,
            ]);
        } else {
            $stmt = Database::connection()->prepare(
                'INSERT INTO users (division_id,name,email,password_hash,role,is_active) VALUES (?,?,?,?,?,1)'
            );
            $stmt->execute([
                $divisionId,
                trim($data['name']),
                strtolower(trim($data['email'])),
                password_hash($data['password'], PASSWORD_DEFAULT),
                $role,
            ]);
        }

        return (int)Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $role = $data['role'];
        $divisionId = $role === 'super_admin' ? null : (int)$data['division_id'];
        $params = [$divisionId, trim($data['name']), strtolower(trim($data['email'])), $role];
        $sets = ['division_id=?', 'name=?', 'email=?', 'role=?'];

        if (!empty($data['password'])) {
            $sets[] = 'password_hash=?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if ($this->hasAvatarColumn() && array_key_exists('avatar_path', $data)) {
            $sets[] = 'avatar_path=?';
            $params[] = $data['avatar_path'] ?: null;
        }

        $params[] = $id;
        $stmt = Database::connection()->prepare('UPDATE users SET ' . implode(',', $sets) . ' WHERE id=?');
        $stmt->execute($params);
    }

    public function toggle(int $id, int $currentUserId): void
    {
        if ($id === $currentUserId) return;
        $stmt = Database::connection()->prepare('UPDATE users SET is_active=IF(is_active=1,0,1) WHERE id=?');
        $stmt->execute([$id]);
    }

    public function hasAvatarColumn(): bool
    {
        if ($this->avatarColumnAvailable !== null) return $this->avatarColumnAvailable;
        try {
            $stmt = Database::connection()->query("SHOW COLUMNS FROM users LIKE 'avatar_path'");
            $this->avatarColumnAvailable = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->avatarColumnAvailable = false;
        }
        return $this->avatarColumnAvailable;
    }
}
