<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private ?bool $avatarColumnAvailable = null;
    private ?bool $permissionColumnsAvailable = null;

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
        return $this->withPermissionDefaults($user);
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
        return $this->withPermissionDefaults($user);
    }

    public function authenticate(string $email, string $password, ?int $divisionId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) return null;

        if ($user['role'] === 'super_admin') {
            // Super Admin must use the dedicated Super Admin login selection.
            if ($divisionId !== null) return null;
        } elseif ($user['role'] === 'store_manager') {
            // Store Manager is a global operational role and must use its own login selection.
            if ($divisionId !== -1) return null;
        } elseif ($user['role'] === 'user' && empty($user['division_id'])) {
            // A read-only user with no division assignment has read access to all divisions.
            // It must use the dedicated "All Divisions / Read-only User" login selection.
            if ($divisionId !== 0) return null;
        } else {
            if (!$divisionId || (int)$user['division_id'] !== $divisionId) return null;
        }
        return $user;
    }

    public function all(): array
    {
        $avatarSelect = $this->hasAvatarColumn() ? ',u.avatar_path' : ',NULL avatar_path';
        $permissionSelect = $this->hasOperationalPermissionColumns()
            ? ',u.can_add,u.can_edit,u.can_delete'
            : ',IF(u.role IN (\'super_admin\',\'store_manager\',\'division_admin\'),1,0) can_add'
                . ',IF(u.role IN (\'super_admin\',\'store_manager\',\'division_admin\'),1,0) can_edit'
                . ',IF(u.role IN (\'super_admin\',\'store_manager\',\'division_admin\'),1,0) can_delete';

        return Database::connection()->query(
            'SELECT u.id,u.division_id,u.name,u.email,u.role,u.is_active,u.created_at'
                . $avatarSelect . $permissionSelect
                . ',d.code division_code,d.name division_name
             FROM users u LEFT JOIN divisions d ON d.id=u.division_id ORDER BY u.created_at DESC'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $role = $data['role'];
        $divisionId = (in_array($role, ['super_admin','store_manager'], true) || ($role === 'user' && empty($data['division_id'])))
            ? null
            : (int)$data['division_id'];

        if ($role === 'division_admin' && !$this->hasOperationalPermissionColumns()) {
            throw new \RuntimeException(
                'Division Admin permissions require database/migrations/015_add_division_admin_permissions.sql.'
            );
        }

        $hasPermissions = $this->hasOperationalPermissionColumns();
        $hasAvatar = $this->hasAvatarColumn();

        $columns = ['division_id','name','email','password_hash','role'];
        $values = [
            $divisionId,
            trim($data['name']),
            strtolower(trim($data['email'])),
            password_hash($data['password'], PASSWORD_DEFAULT),
            $role,
        ];

        if ($hasPermissions) {
            $columns = array_merge($columns, ['can_add','can_edit','can_delete']);
            $values[] = (int)($data['can_add'] ?? 0);
            $values[] = (int)($data['can_edit'] ?? 0);
            $values[] = (int)($data['can_delete'] ?? 0);
        }

        if ($hasAvatar) {
            $columns[] = 'avatar_path';
            $values[] = $data['avatar_path'] ?: null;
        }

        $columns[] = 'is_active';
        $values[] = 1;

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')'
        );
        $stmt->execute($values);

        return (int)Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $role = $data['role'];
        $divisionId = (in_array($role, ['super_admin','store_manager'], true) || ($role === 'user' && empty($data['division_id'])))
            ? null
            : (int)$data['division_id'];

        if ($role === 'division_admin' && !$this->hasOperationalPermissionColumns()) {
            throw new \RuntimeException(
                'Division Admin permissions require database/migrations/015_add_division_admin_permissions.sql.'
            );
        }

        $params = [$divisionId, trim($data['name']), strtolower(trim($data['email'])), $role];
        $sets = ['division_id=?', 'name=?', 'email=?', 'role=?'];

        if ($this->hasOperationalPermissionColumns()) {
            $sets[] = 'can_add=?';
            $sets[] = 'can_edit=?';
            $sets[] = 'can_delete=?';
            $params[] = (int)($data['can_add'] ?? 0);
            $params[] = (int)($data['can_edit'] ?? 0);
            $params[] = (int)($data['can_delete'] ?? 0);
        }

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

    public function delete(int $id, int $currentUserId): bool
    {
        if ($id <= 0 || $id === $currentUserId) return false;

        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function countActiveSuperAdmins(): int
    {
        $stmt = Database::connection()->query(
            "SELECT COUNT(*) FROM users WHERE role='super_admin' AND is_active=1"
        );
        return (int)$stmt->fetchColumn();
    }

    public function hasOperationalPermissionColumns(): bool
    {
        if ($this->permissionColumnsAvailable !== null) return $this->permissionColumnsAvailable;

        try {
            $pdo = Database::connection();
            foreach (['can_add','can_edit','can_delete'] as $column) {
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($column));
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    return $this->permissionColumnsAvailable = false;
                }
            }
            return $this->permissionColumnsAvailable = true;
        } catch (\Throwable $e) {
            return $this->permissionColumnsAvailable = false;
        }
    }

    private function withPermissionDefaults(?array $user): ?array
    {
        if (!$user) return null;

        $writeDefault = in_array(
            $user['role'] ?? '',
            ['super_admin','store_manager','division_admin'],
            true
        ) ? 1 : 0;

        foreach (['can_add','can_edit','can_delete'] as $key) {
            if (!array_key_exists($key, $user)) $user[$key] = $writeDefault;
        }

        return $user;
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
