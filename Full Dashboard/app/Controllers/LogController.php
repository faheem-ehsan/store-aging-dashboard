<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\User;

class LogController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        $filters = $this->filters();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 100;
        $model = new AuditLog();
        $total = $model->count($filters);
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) $page = $pages;

        View::render('logs/index', [
            'title' => 'System Logs',
            'logs' => $model->search($filters, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'actions' => $model->actions(),
            'users' => (new User())->all(),
            'divisions' => (new Division())->all(false),
            'stats' => $model->stats(),
            'tableAvailable' => $model->tableExists(),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    public function export(): void
    {
        Auth::requireSuperAdmin();
        $model = new AuditLog();
        if (!$model->tableExists()) {
            flash('error', 'Log database table is not installed. Run database/migrations/003_add_audit_logs.sql.');
            redirect('/logs');
        }

        $filters = $this->filters();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="qarshi_system_logs_' . date('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['Date/Time','User','Email','Role','Division','Action','Entity','Entity ID','Description','IP Address','Details']);

        $offset = 0;
        do {
            $rows = $model->search($filters, 500, $offset);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['created_at'], $row['actor_name'], $row['actor_email'], $row['actor_role'],
                    $row['division_code'], $row['action'], $row['entity_type'], $row['entity_id'],
                    $row['description'], $row['ip_address'], $row['details_json'],
                ]);
            }
            $offset += count($rows);
        } while (count($rows) === 500);

        fclose($out);
        exit;
    }

    private function filters(): array
    {
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));
        return [
            'q' => trim((string)($_GET['q'] ?? '')),
            'action' => trim((string)($_GET['action'] ?? '')),
            'user_id' => (int)($_GET['user_id'] ?? 0),
            'division_id' => (int)($_GET['division_id'] ?? 0),
            'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '',
            'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : '',
        ];
    }
}
