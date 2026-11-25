<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\User;
use App\Services\AuditLogger;

class UserController
{
    public function index(): void
    {
        $current = Auth::requireSuperAdmin();
        View::render('users/index', [
            'title' => 'User Management',
            'users' => (new User())->all(),
            'divisions' => (new Division())->all(true),
            'current' => $current,
            'editUser' => null,
        ]);
    }

    public function edit(): void
    {
        $current = Auth::requireSuperAdmin();
        $model = new User();
        $editUser = $model->findAny((int)($_GET['id'] ?? 0));
        if (!$editUser) {
            flash('error', 'User not found.');
            redirect('/users');
        }
        View::render('users/index', [
            'title' => 'User Management',
            'users' => $model->all(),
            'divisions' => (new Division())->all(true),
            'current' => $current,
            'editUser' => $editUser,
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $avatarPath = null;

        try {
            $data = $this->validatedUserData(true);
            $avatarPath = $this->handleAvatarUpload();
            $data['avatar_path'] = $avatarPath;
            $model = new User();
            if ($avatarPath && !$model->hasAvatarColumn()) {
                $this->deleteAvatarFile($avatarPath);
                flash('error', 'Profile picture support needs the database migration first. Import database/migrations/001_add_user_avatar.sql.');
                redirect('/users');
            }
            $newId = $model->create($data);
            $created = $model->findAny($newId);
            $division = !empty($created['division_id']) ? (new Division())->find((int)$created['division_id']) : null;
            AuditLogger::log(
                'USER_CREATE',
                'Created user ' . $data['name'] . '.',
                'user',
                $newId,
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'division' => $division['code'] ?? null,
                    'permissions' => [
                        'add' => (bool)$data['can_add'],
                        'edit' => (bool)$data['can_edit'],
                        'delete' => (bool)$data['can_delete'],
                    ],
                    'profile_picture' => $avatarPath ? 'uploaded' : 'not uploaded',
                ],
                $current,
                $division ? (int)$division['id'] : null,
                $division['code'] ?? null
            );
            flash('success', 'User created.');
        } catch (\Throwable $e) {
            if ($avatarPath) $this->deleteAvatarFile($avatarPath);
            flash('error', $e instanceof \RuntimeException ? $e->getMessage() : 'Unable to create user. Email may already exist.');
        }
        redirect('/users');
    }

    public function update(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $model = new User();
        $existing = $model->findAny($id);
        if (!$existing) {
            flash('error', 'User not found.');
            redirect('/users');
        }

        $newAvatar = null;
        try {
            $data = $this->validatedUserData(false);

            if (
                ($existing['role'] ?? '') === 'super_admin'
                && $data['role'] !== 'super_admin'
                && (int)($existing['is_active'] ?? 0) === 1
                && $model->countActiveSuperAdmins() <= 1
            ) {
                throw new \RuntimeException('The last active Super Admin cannot be changed to another role.');
            }

            if (!empty($_POST['remove_avatar'])) {
                $data['avatar_path'] = null;
            }

            if (!empty($_FILES['avatar']['name'])) {
                if (!$model->hasAvatarColumn()) {
                    throw new \RuntimeException('Profile picture support needs the database migration first. Import database/migrations/001_add_user_avatar.sql.');
                }
                $newAvatar = $this->handleAvatarUpload();
                $data['avatar_path'] = $newAvatar;
            }

            $model->update($id, $data);

            if (array_key_exists('avatar_path', $data) && !empty($existing['avatar_path']) && $existing['avatar_path'] !== ($data['avatar_path'] ?? null)) {
                $this->deleteAvatarFile($existing['avatar_path']);
            }
            $updated = $model->findAny($id) ?? $existing;
            $division = !empty($updated['division_id']) ? (new Division())->find((int)$updated['division_id']) : null;
            AuditLogger::log(
                'USER_UPDATE',
                'Updated user ' . $updated['name'] . '.',
                'user',
                $id,
                [
                    'before' => [
                        'name' => $existing['name'],
                        'email' => $existing['email'],
                        'role' => $existing['role'],
                        'division' => $existing['division_code'] ?? null,
                        'permissions' => [
                            'add' => (bool)($existing['can_add'] ?? 0),
                            'edit' => (bool)($existing['can_edit'] ?? 0),
                            'delete' => (bool)($existing['can_delete'] ?? 0),
                        ],
                    ],
                    'after' => [
                        'name' => $updated['name'],
                        'email' => $updated['email'],
                        'role' => $updated['role'],
                        'division' => $updated['division_code'] ?? null,
                        'permissions' => [
                            'add' => (bool)($updated['can_add'] ?? 0),
                            'edit' => (bool)($updated['can_edit'] ?? 0),
                            'delete' => (bool)($updated['can_delete'] ?? 0),
                        ],
                    ],
                    'password_changed' => !empty($data['password']),
                    'profile_picture_changed' => array_key_exists('avatar_path', $data),
                ],
                $current,
                $division ? (int)$division['id'] : null,
                $division['code'] ?? null
            );
            flash('success', 'User updated.');
        } catch (\Throwable $e) {
            if ($newAvatar) $this->deleteAvatarFile($newAvatar);
            flash('error', $e instanceof \RuntimeException ? $e->getMessage() : 'Unable to update user. Email may already exist.');
        }
        redirect('/users');
    }

    public function toggle(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = (int)($_POST['id'] ?? 0);

        if ($id === (int)$current['id']) {
            flash('error', 'You cannot disable your own Super Admin account.');
            redirect('/users');
        }

        $model = new User();
        $before = $model->findAny($id);
        if (!$before) {
            flash('error', 'User not found.');
            redirect('/users');
        }

        if (
            ($before['role'] ?? '') === 'super_admin'
            && (int)$before['is_active'] === 1
            && $model->countActiveSuperAdmins() <= 1
        ) {
            flash('error', 'The last active Super Admin cannot be disabled.');
            redirect('/users');
        }

        $model->toggle($id, (int)$current['id']);
        $after = $model->findAny($id);

        if ($after && (int)$before['is_active'] !== (int)$after['is_active']) {
            $division = !empty($after['division_id'])
                ? (new Division())->find((int)$after['division_id'])
                : null;

            AuditLogger::log(
                'USER_STATUS',
                ($after['is_active'] ? 'Enabled' : 'Disabled') . ' user ' . $after['name'] . '.',
                'user',
                $id,
                [
                    'old_status' => $before['is_active'] ? 'active' : 'disabled',
                    'new_status' => $after['is_active'] ? 'active' : 'disabled',
                ],
                $current,
                $division ? (int)$division['id'] : null,
                $division['code'] ?? null
            );
        }

        flash('success', 'User status updated.');
        redirect('/users');
    }

    public function delete(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            flash('error', 'Invalid user.');
            redirect('/users');
        }

        if ($id === (int)$current['id']) {
            flash('error', 'You cannot delete your own Super Admin account.');
            redirect('/users');
        }

        $model = new User();
        $target = $model->findAny($id);

        if (!$target) {
            flash('error', 'User not found.');
            redirect('/users');
        }

        if (
            ($target['role'] ?? '') === 'super_admin'
            && (int)($target['is_active'] ?? 0) === 1
            && $model->countActiveSuperAdmins() <= 1
        ) {
            flash('error', 'The last active Super Admin cannot be deleted.');
            redirect('/users');
        }

        $division = !empty($target['division_id'])
            ? (new Division())->find((int)$target['division_id'])
            : null;

        try {
            $deleted = $model->delete($id, (int)$current['id']);
            if (!$deleted) throw new \RuntimeException('User could not be deleted.');

            AuditLogger::log(
                'USER_DELETE',
                'Deleted user ' . $target['name'] . '.',
                'user',
                $id,
                [
                    'name' => $target['name'],
                    'email' => $target['email'],
                    'role' => $target['role'],
                    'division' => $target['division_code'] ?? null,
                    'status' => !empty($target['is_active']) ? 'active' : 'disabled',
                    'permissions' => [
                        'add' => (bool)($target['can_add'] ?? 0),
                        'edit' => (bool)($target['can_edit'] ?? 0),
                        'delete' => (bool)($target['can_delete'] ?? 0),
                    ],
                ],
                $current,
                $division ? (int)$division['id'] : null,
                $division['code'] ?? null
            );

            if (!empty($target['avatar_path'])) {
                $this->deleteAvatarFile($target['avatar_path']);
            }

            flash('success', 'User deleted permanently.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to delete this user. The account may still be referenced by protected system records.');
        }

        redirect('/users');
    }

    private function validatedUserData(bool $passwordRequired): array
    {
        $requestedRole = $_POST['role'] ?? 'user';
        $isAllDivisionReadOnly = $requestedRole === 'read_only_all';

        $storedRole = $isAllDivisionReadOnly ? 'user' : $requestedRole;
        $isDivisionAdmin = $storedRole === 'division_admin';

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            // Keep global read-only stored as role=user with division_id=NULL.
            'role' => $storedRole,
            'division_id' => $isAllDivisionReadOnly ? '' : ($_POST['division_id'] ?? ''),
            // Granular write permissions apply only to Division Admin.
            // Global operational roles always have full operational access.
            'can_add' => $isDivisionAdmin
                ? (isset($_POST['can_add']) && $_POST['can_add'] === '1' ? 1 : 0)
                : (in_array($storedRole, ['super_admin','store_manager'], true) ? 1 : 0),
            'can_edit' => $isDivisionAdmin
                ? (isset($_POST['can_edit']) && $_POST['can_edit'] === '1' ? 1 : 0)
                : (in_array($storedRole, ['super_admin','store_manager'], true) ? 1 : 0),
            'can_delete' => $isDivisionAdmin
                ? (isset($_POST['can_delete']) && $_POST['can_delete'] === '1' ? 1 : 0)
                : (in_array($storedRole, ['super_admin','store_manager'], true) ? 1 : 0),
        ];

        if (!$data['name'] || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Name and a valid email address are required.');
        }
        if ($passwordRequired && strlen($data['password']) < 8) {
            throw new \RuntimeException('Password must be at least 8 characters.');
        }
        if (!$passwordRequired && $data['password'] !== '' && strlen($data['password']) < 8) {
            throw new \RuntimeException('New password must be at least 8 characters.');
        }
        if (!in_array($data['role'], ['super_admin','store_manager','division_admin','user'], true)) {
            throw new \RuntimeException('Invalid role.');
        }
        if ($data['role'] === 'division_admin' && !(int)$data['division_id']) {
            throw new \RuntimeException('Division is required for Division Admin.');
        }
        if ($data['role'] === 'user' && !$isAllDivisionReadOnly && !(int)$data['division_id']) {
            throw new \RuntimeException('Choose a Division, or select Read-only User — All Divisions.');
        }
        return $data;
    }

    private function handleAvatarUpload(): ?string
    {
        $file = $_FILES['avatar'] ?? null;
        if (!$file || empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \RuntimeException('Unable to upload the profile picture.');
        if ((int)$file['size'] > 2 * 1024 * 1024) throw new \RuntimeException('Profile picture must be 2 MB or smaller.');

        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
            if ($finfo) finfo_close($finfo);
        }
        if (!$mime && function_exists('mime_content_type')) $mime = mime_content_type($file['tmp_name']);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensions[$mime])) throw new \RuntimeException('Profile picture must be JPG, PNG, or WEBP.');

        $dir = ROOT_PATH . '/public/uploads/users';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create profile picture upload folder.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            throw new \RuntimeException('Unable to save the profile picture.');
        }
        return '/uploads/users/' . $filename;
    }

    private function deleteAvatarFile(?string $path): void
    {
        if (!$path || !str_starts_with($path, '/uploads/users/')) return;
        $file = ROOT_PATH . '/public' . $path;
        if (is_file($file)) @unlink($file);
    }
}
