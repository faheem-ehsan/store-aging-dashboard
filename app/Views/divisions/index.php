<div class="two-col narrow-left">
<div class="card form-card">
<h2>Add Division</h2>
<p>The system is data-driven, so you can add the unnamed fourth division here without changing code.</p>
<form method="post" action="<?= e(url('/divisions/store')) ?>" class="form-stack">
<?= csrf_field() ?>
<label>Short Code<input name="code" maxlength="20" placeholder="e.g. ABC" required></label>
<label>Division Name<input name="name" maxlength="120" required></label>
<button class="btn primary" type="submit">Add Division</button>
</form>
</div>
<div class="card flush">
<div class="card-head padded"><div><h2>Configured Divisions</h2><p>JSD, SNW and RTD are preloaded.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Code</th><th>Name</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($divisions as $d): ?><tr><td><span class="division-tag"><?= e($d['code']) ?></span></td><td><?= e($d['name']) ?></td><td><?= $d['is_active'] ? '<span class="status on">Active</span>' : '<span class="status off">Disabled</span>' ?></td><td><form method="post" action="<?= e(url('/divisions/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="link-button" type="submit"><?= $d['is_active'] ? 'Disable' : 'Enable' ?></button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
</div>
</div>