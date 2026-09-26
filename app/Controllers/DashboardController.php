<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\StockBatch;
use App\Services\GradeService;

class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        $divisionId = isset($_GET['division_id']) && $_GET['division_id'] !== '' ? (int)$_GET['division_id'] : null;
        if ($user['role'] !== 'super_admin') $divisionId = (int)$user['division_id'];

        $rows = (new StockBatch())->allAccessible($user, ['division_id' => $divisionId]);
        $grader = new GradeService();
        $rows = array_map(fn($r) => $grader->enrich($r), $rows);

        $metrics = [
            'batches' => count($rows),
            'stock_qty' => 0.0,
            'stock_value' => 0.0,
            'grades' => [
                'A+' => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0],
                'A'  => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0],
                'B'  => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0],
                'C'  => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0],
                'EX' => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0],
            ],
        ];
        foreach ($rows as $row) {
            $qty = (float)$row['current_qty'];
            $value = (float)($row['stock_value'] ?? 0);
            $metrics['stock_qty'] += $qty;
            $metrics['stock_value'] += $value;

            $code = (string)$row['grade']['grade_code'];
            if (isset($metrics['grades'][$code])) {
                $metrics['grades'][$code]['batches']++;
                $metrics['grades'][$code]['qty'] += $qty;
                $metrics['grades'][$code]['value'] += $value;
            }
        }

        // Keep the dashboard stock table aligned with the Stock Aging Dashboard.
        // The model already returns records in FEFO order (earliest expiry first).
        $tableRows = $rows;

        $alertRows = $rows;
        usort($alertRows, fn($a,$b) => $a['remaining_days'] <=> $b['remaining_days']);
        $alerts = array_slice(
            array_filter($alertRows, fn($r) => in_array($r['grade']['grade_code'], ['C','EX'], true)),
            0,
            12
        );

        $selectedCriteria = $divisionId ? $grader->settingsForDivision((int)$divisionId) : null;

        $divisionModel = new Division();
        $divisions = $divisionModel->all(true);
        $pageDivision = null;
        if ($divisionId) {
            foreach ($divisions as $division) {
                if ((int)$division['id'] === (int)$divisionId) {
                    $pageDivision = $division;
                    break;
                }
            }
            if ($pageDivision === null) {
                $pageDivision = $divisionModel->find((int)$divisionId);
            }
        }

        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'divisions' => $divisions,
            'selectedDivision' => $divisionId,
            'pageDivision' => $pageDivision,
            'metrics' => $metrics,
            'rows' => $tableRows,
            'alerts' => $alerts,
            'selectedCriteria' => $selectedCriteria,
        ]);
    }
}
