<?php
$isEdit = !empty($editUser);
$formUser = $editUser ?? [
    'id' => null,
    'name' => '',
    'email' => '',
    'role' => 'division_admin',
    'division_id' => '',
    'can_add' => 1,
    'can_edit' => 1,
    'can_delete' => 1,
    'avatar_path' => null,
];
$formRoleValue = (($formUser['role'] ?? '') === 'user' && empty($formUser['division_id']))
    ? 'read_only_all'
    : ($formUser['role'] ?? 'division_admin');
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
                <p><?= $isEdit ? 'Update account access, division and profile picture.' : 'Create an account and assign its access level.' ?></p>
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
                        <option value="store_manager" <?= $formRoleValue === 'store_manager' ? 'selected' : '' ?>>Store Manager — All Divisions</option>
                        <option value="division_admin" <?= $formRoleValue === 'division_admin' ? 'selected' : '' ?>>Division Admin</option>
                        <option value="user" <?= $formRoleValue === 'user' ? 'selected' : '' ?>>Read-only User — Division</option>
                        <option value="read_only_all" <?= $formRoleValue === 'read_only_all' ? 'selected' : '' ?>>Read-only User — All Divisions</option>
                        <option value="super_admin" <?= $formRoleValue === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </label>
                <label class="span-2" id="divisionField">Division
                    <select name="division_id" id="divisionSelect">
                        <option value="" id="divisionEmptyOption">Select Division</option>
                        <?php foreach ($divisions as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (int)($formUser['division_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['code']) ?> — <?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="span-2 division-admin-permissions" id="divisionAdminPermissions">
                    <div class="division-admin-permissions-head">
                        <div>
                            <strong>Division Admin Operational Rights</strong>
                            <small>These permissions apply only inside the assigned Division. Viewing access remains available.</small>
                        </div>
                        <span>Granular Access</span>
                    </div>

                    <div class="division-admin-permission-grid">
                        <label class="permission-toggle-card">
                            <input type="checkbox" name="can_add" value="1" <?= !empty($formUser['can_add']) ? 'checked' : '' ?>>
                            <span>
                                <b>Add</b>
                                <small>Add batches/items and import new records.</small>
                            </span>
                        </label>

                        <label class="permission-toggle-card">
                            <input type="checkbox" name="can_edit" value="1" <?= !empty($formUser['can_edit']) ? 'checked' : '' ?>>
                            <span>
                                <b>Edit</b>
                                <small>Edit existing Finish Goods and Market Return records.</small>
                            </span>
                        </label>

                        <label class="permission-toggle-card permission-danger">
                            <input type="checkbox" name="can_delete" value="1" <?= !empty($formUser['can_delete']) ? 'checked' : '' ?>>
                            <span>
                                <b>Delete</b>
                                <small>Delete records and use selected-record deletion in the assigned Division.</small>
                            </span>
                        </label>
                    </div>
                </div>
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
                <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Division</th><th>Access Rights</th><th>Status</th><th>Action</th></tr></thead>
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
                        <td>
                            <?php
                            $userRoleLabel = ($u['role'] ?? '') === 'user'
                                ? (empty($u['division_code']) ? 'Read-only User — All Divisions' : 'Read-only User')
                                : ucwords(str_replace('_',' ', (string)$u['role']));
                            ?>
                            <?= e($userRoleLabel) ?>
                        </td>
                        <td><?= e($u['division_code'] ?? (in_array(($u['role'] ?? ''), ['super_admin','store_manager'], true) || (($u['role'] ?? '') === 'user' && empty($u['division_id'])) ? 'All Divisions' : '—')) ?></td>
                        <td>
                            <?php if (($u['role'] ?? '') === 'division_admin'): ?>
                                <div class="user-permission-pills">
                                    <span class="<?= !empty($u['can_add']) ? 'on' : 'off' ?>">Add</span>
                                    <span class="<?= !empty($u['can_edit']) ? 'on' : 'off' ?>">Edit</span>
                                    <span class="<?= !empty($u['can_delete']) ? 'on danger' : 'off' ?>">Delete</span>
                                </div>
                            <?php elseif (in_array(($u['role'] ?? ''), ['super_admin','store_manager'], true)): ?>
                                <span class="user-access-label full">Full Operational</span>
                            <?php else: ?>
                                <span class="user-access-label readonly">Read Only</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['is_active'] ? '<span class="status on">Active</span>' : '<span class="status off">Disabled</span>' ?></td>
                        <td class="user-actions">
                            <a href="<?= e(url('/users/edit?id=' . (int)$u['id'])) ?>">Edit</a>
                            <?php if ((int)$u['id'] !== (int)$current['id']): ?>
                                <form method="post" action="<?= e(url('/users/toggle')) ?>" onsubmit="return confirm('<?= $u['is_active'] ? 'Disable' : 'Enable' ?> this user account?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button class="link-button" type="submit"><?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
                                </form>

                                <form method="post" action="<?= e(url('/users/delete')) ?>" onsubmit="return confirm('Permanently delete this user account? This cannot be undone.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button class="danger-link" type="submit">Delete</button>
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
    const emptyOption = document.getElementById('divisionEmptyOption');
    const permissions = document.getElementById('divisionAdminPermissions');
    const permissionInputs = permissions ? Array.from(permissions.querySelectorAll('input[type="checkbox"]')) : [];

    function syncDivision() {
        const isSuper = role.value === 'super_admin';
        const isStoreManager = role.value === 'store_manager';
        const isAllDivisionReadOnly = role.value === 'read_only_all';
        const needsDivision = role.value === 'division_admin' || role.value === 'user';
        const disableDivision = isSuper || isStoreManager || isAllDivisionReadOnly;

        field.classList.toggle('muted-field', disableDivision);
        division.disabled = disableDivision;
        division.required = needsDivision;

        if (isSuper) {
            division.value = '';
            if (emptyOption) emptyOption.textContent = 'None / Super Admin';
        } else if (isStoreManager) {
            division.value = '';
            if (emptyOption) emptyOption.textContent = 'All Divisions / Store Manager';
        } else if (isAllDivisionReadOnly) {
            division.value = '';
            if (emptyOption) emptyOption.textContent = 'All Divisions';
        } else {
            if (emptyOption) emptyOption.textContent = 'Select Division';
        }

        const isDivisionAdmin = role.value === 'division_admin';
        if (permissions) permissions.hidden = !isDivisionAdmin;
        permissionInputs.forEach(function (input) {
            input.disabled = !isDivisionAdmin;
        });
    }
    role.addEventListener('change', syncDivision);
    syncDivision();
})();
</script>
