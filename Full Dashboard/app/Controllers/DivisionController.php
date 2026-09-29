<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\GradeSetting;
use App\Services\AuditLogger;

class DivisionController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('divisions/index', [
            'title' => 'Divisions',
            'divisions' => (new Division())->all(false),
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if (!$code || !$name) { flash('error','Code and name are required.'); redirect('/divisions'); }
        try {
            $model = new Division();
            $id = $model->create($code, $name);
            try { (new GradeSetting())->upsert($id, 30, 60, 90, 120); } catch (\Throwable $e) { /* migration may not be installed yet */ }
            $division = $model->find($id);
            AuditLogger::log(
                'DIVISION_CREATE',
                'Created division ' . strtoupper($code) . ' — ' . $name . '.',
                'division',
                $id,
                ['code' => strtoupper($code), 'name' => $name],
                $current,
                $id,
                $division['code'] ?? strtoupper($code)
            );
            flash('success','Division added.');
        } catch (\Throwable $e) {
            flash('error','Unable to add division. Code may already exist.');
        }
        redirect('/divisions');
    }

    public function toggle(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $model = new Division();
        $before = $model->find($id);
        $model->toggle($id);
        $after = $model->find($id);
        if ($before && $after) {
            AuditLogger::log(
                'DIVISION_STATUS',
                ($after['is_active'] ? 'Enabled' : 'Disabled') . ' division ' . $after['code'] . '.',
                'division',
                $id,
                ['old_status' => $before['is_active'] ? 'active' : 'disabled', 'new_status' => $after['is_active'] ? 'active' : 'disabled'],
                $current,
                $id,
                $after['code']
            );
        }
        flash('success','Division status updated.');
        redirect('/divisions');
    }
}
