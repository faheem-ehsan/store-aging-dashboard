<?php
$isEdit = !empty($editUser);
$formUser = $editUser ?? [
    'id' => null,
    'name' => '',
    'email' => '',
    'role' => 'division_admin',
    'division_id' => '',
    'avatar_path' => null,
];
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') $letters .= strtoupper(substr($part, 0, 1));
    }
    return $letters ?: 'U';
};
?>
<div class="user-admin-layout">
    <section class="card user-form-card">
        <div class="card-head user-form-head">
            <div>
                <h2><?= $isEdit ? 'Edit User' : 'Create User' ?></h2>
                <p><?= $isEdit ? 'Update account access, division and profile picture.' : 'Create a division-based account and assign its access level.' ?></p>
            </div>
            <?php if ($isEdit): ?><a class="btn ghost" href="<?= e(url('/users')) ?>">Cancel</a><?php endif; ?>
        </div>

        <form method="post" enctype="multipart/form-data" action="<?= e(url($isEdit ? '/users/update' : '/users/store')) ?>" class="user-form-grid">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$formUser['id'] ?>"><?php endif; ?>

            <div class="user-photo-field">
                <div class="user-photo-preview">
                    <?php if (!empty($formUser['avatar_path'])): ?>
                        <img src="<?= e(url($formUser['avatar_path'])) ?>" alt="<?= e($formUser['name']) ?> profile picture">
                    <?php else: ?>
                        <span><?= e($initials($formUser['name'] ?: 'User')) ?></span>
                    <?php endif; ?>
                </div>
                <div class="user-photo-copy">
                    <strong>Profile Picture</strong>
                    <small>JPG, PNG or WEBP. Maximum 2 MB.</small>
                    <label class="file-picker">Choose Picture<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
                    <?php if ($isEdit && !empty($formUser['avatar_path'])): ?>
                        <label class="remove-photo"><input type="checkbox" name="remove_avatar" value="1"> Remove current picture</label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="user-fields-grid">
                <label>Full Name
                    <input name="name" value="<?= e($formUser['name']) ?>" required>
                </label>
                <label>Email Address
                    <input type="email" name="email" value="<?= e($formUser['email']) ?>" required>
                </label>
                <label><?= $isEdit ? 'New Password' : 'Password' ?>
                    <input type="password" name="password" minlength="8" <?= $isEdit ? '' : 'required' ?> placeholder="<?= $isEdit ? 'Leave blank to keep current password' : 'Minimum 8 characters' ?>">
                </label>
                <label>Role
                    <select name="role" id="roleSelect" required>
                        <option value="division_admin" <?= $formUser['role'] === 'division_admin' ? 'selected' : '' ?>>Division Admin</option>
                        <option value="user" <?= $formUser['role'] === 'user' ? 'selected' : '' ?>>Read-only User</option>
                        <option value="super_admin" <?= $formUser['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </label>
                <label class="span-2" id="divisionField">Division
                    <select name="division_id" id="divisionSelect">
                        <option value="">None / Super Admin</option>
                        <?php foreach ($divisions as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (int)($formUser['division_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['code']) ?> — <?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="user-form-actions">
                <?php if ($isEdit): ?><a class="btn ghost" href="<?= e(url('/users')) ?>">Cancel</a><?php endif; ?>
                <button class="btn primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create User' ?></button>
            </div>
        </form>
    </section>

    <section class="card flush user-list-card">
        <div class="card-head padded"><div><h2>Users</h2><p>Account access, profile pictures and division assignment.</p></div></div>
        <div class="table-wrap">
            <table class="users-table">
                <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Division</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-list-avatar">
                                    <?php if (!empty($u['avatar_path'])): ?>
                                        <img src="<?= e(url($u['avatar_path'])) ?>" alt="<?= e($u['name']) ?> profile picture">
                                    <?php else: ?>
                                        <span><?= e($initials($u['name'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <strong><?= e($u['name']) ?></strong>
                            </div>
                        </td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e(ucwords(str_replace('_',' ', $u['role']))) ?></td>
                        <td><?= e($u['division_code'] ?? 'All') ?></td>
                        <td><?= $u['is_active'] ? '<span class="status on">Active</span>' : '<span class="status off">Disabled</span>' ?></td>
                        <td class="user-actions">
                            <a href="<?= e(url('/users/edit?id=' . (int)$u['id'])) ?>">Edit</a>
                            <?php if ((int)$u['id'] !== (int)$current['id']): ?>
                                <form method="post" action="<?= e(url('/users/toggle')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button class="link-button" type="submit"><?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
(function () {
    const role = document.getElementById('roleSelect');
    const division = document.getElementById('divisionSelect');
    const field = document.getElementById('divisionField');
    if (!role || !division || !field) return;
    function syncDivision() {
        const isSuper = role.value === 'super_admin';
        field.classList.toggle('muted-field', isSuper);
        division.disabled = isSuper;
        if (isSuper) division.value = '';
    }
    role.addEventListener('change', syncDivision);
    syncDivision();
})();
</script>
