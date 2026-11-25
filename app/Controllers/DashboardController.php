<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\StockBatch;
use App\Models\SapIntegration;
use App\Models\UserPreference;
use App\Services\AgingModuleRegistry;
use App\Services\GradeService;

class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        $divisionId = isset($_GET['division_id']) && $_GET['division_id'] !== '' ? (int)$_GET['division_id'] : null;
        $canReadAllDivisions = Auth::canReadAllDivisions($user);
        if (!$canReadAllDivisions) $divisionId = (int)$user['division_id'];

        $stockBatchModel = new StockBatch();
        $rows = $stockBatchModel->allAccessible($user, ['division_id' => $divisionId]);
        $lastStockUpdateInfo = $stockBatchModel->latestUpdateInfo($user, $divisionId);
        $lastStockUpdatedAt = $lastStockUpdateInfo['updated_at'] ?? null;
        $sapLastStockUpdatedAt = null;
        try {
            $sapIntegration = new SapIntegration();
            if ($sapIntegration->isPublishingEnabled()) {
                $sapLastStockUpdatedAt = $sapIntegration->latestSuccessfulStockSync($divisionId);
            }
        } catch (\Throwable $e) {
            // Keep the dashboard available even if the optional SAP integration
            // has not been installed/configured yet.
            $sapLastStockUpdatedAt = null;
        }
        $grader = new GradeService();
        $rows = array_map(fn($r) => $grader->enrich($r), $rows);

        $metrics = [
            'batches' => count($rows),
            'stock_qty' => 0.0,
            'stock_value' => 0.0,
            'uoms' => [],
            'grades' => [
                'A+' => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0, 'uoms' => []],
                'A'  => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0, 'uoms' => []],
                'B'  => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0, 'uoms' => []],
                'C'  => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0, 'uoms' => []],
                'EX' => ['batches' => 0, 'qty' => 0.0, 'value' => 0.0, 'uoms' => []],
            ],
        ];
        foreach ($rows as $row) {
            $qty = (float)$row['current_qty'];
            $value = (float)($row['stock_value'] ?? 0);
            $metrics['stock_qty'] += $qty;
            $metrics['stock_value'] += $value;

            $uom = strtoupper(trim((string)($row['uom'] ?? '')));
            if ($uom === '') $uom = 'UNSPECIFIED';
            if (!isset($metrics['uoms'][$uom])) {
                $metrics['uoms'][$uom] = ['qty' => 0.0, 'batches' => 0, 'value' => 0.0];
            }
            $metrics['uoms'][$uom]['qty'] += $qty;
            $metrics['uoms'][$uom]['batches']++;
            $metrics['uoms'][$uom]['value'] += $value;

            $code = (string)$row['grade']['grade_code'];
            if (isset($metrics['grades'][$code])) {
                $metrics['grades'][$code]['batches']++;
                $metrics['grades'][$code]['qty'] += $qty;
                $metrics['grades'][$code]['value'] += $value;
                if (!isset($metrics['grades'][$code]['uoms'][$uom])) {
                    $metrics['grades'][$code]['uoms'][$uom] = ['qty' => 0.0, 'batches' => 0, 'value' => 0.0];
                }
                $metrics['grades'][$code]['uoms'][$uom]['qty'] += $qty;
                $metrics['grades'][$code]['uoms'][$uom]['batches']++;
                $metrics['grades'][$code]['uoms'][$uom]['value'] += $value;
            }
        }

        ksort($metrics['uoms'], SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($metrics['grades'] as &$gradeMetric) {
            if (!empty($gradeMetric['uoms'])) {
                ksort($gradeMetric['uoms'], SORT_NATURAL | SORT_FLAG_CASE);
            }
        }
        unset($gradeMetric);

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

        $gradeCriteria = $divisionId ? $grader->criteriaForDivision((int)$divisionId) : null;

        // Extra aging modules (Raw Material, Export Goods, any added later) shown
        // below the Finish Goods grade summary. Discovered automatically; each
        // user chooses which ones to display. Same division scope and grading.
        $availableModules = [];
        foreach (AgingModuleRegistry::modules() as $key => $moduleController) {
            $availableModules[$key] = $moduleController::MODULE['index_title'];
        }
        $shownModuleKeys = $this->shownModuleKeys((int)$user['id'], array_keys($availableModules));

        $moduleSummaries = [];
        foreach (AgingModuleRegistry::modules() as $key => $moduleController) {
            if (!in_array($key, $shownModuleKeys, true)) continue;
            try {
                $moduleSummaries[] = $this->moduleSummary(
                    $moduleController::MODULE,
                    AgingModuleRegistry::batchModel($moduleController),
                    $user,
                    $divisionId,
                    $grader
                );
            } catch (\Throwable $e) {
                // An optional module must never take the main dashboard down.
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
            'gradeCriteria' => $gradeCriteria,
            'lastStockUpdatedAt' => $lastStockUpdatedAt,
            'lastStockUpdateInfo' => $lastStockUpdateInfo,
            'sapLastStockUpdatedAt' => $sapLastStockUpdatedAt,
            'moduleSummaries' => $moduleSummaries,
            'availableModules' => $availableModules,
            'shownModuleKeys' => $shownModuleKeys,
        ]);
    }

    private const SHOWN_MODULES_PREF = 'dashboard.aging_modules';

    /** POST /dashboard/modules: save which aging modules this user shows on the Dashboard. */
    public function saveModules(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();

        $available = array_keys(AgingModuleRegistry::modules());
        $requested = $_POST['modules'] ?? [];
        if (!is_array($requested)) $requested = [];
        $selected = array_values(array_intersect($available, array_map('strval', $requested)));

        (new UserPreference())->set((int)$user['id'], self::SHOWN_MODULES_PREF, json_encode($selected));

        $divisionId = (int)($_POST['return_division_id'] ?? 0);
        redirect('/' . ($divisionId > 0 ? '?division_id=' . $divisionId : ''));
    }

    /**
     * Module keys this user shows. No saved choice yet = all modules, so new
     * modules appear automatically until the user picks their own set.
     */
    private function shownModuleKeys(int $userId, array $availableKeys): array
    {
        try {
            $saved = (new UserPreference())->get($userId, self::SHOWN_MODULES_PREF);
        } catch (\Throwable $e) {
            $saved = null;
        }
        if ($saved === null) return $availableKeys;

        $keys = json_decode($saved, true);
        return is_array($keys) ? array_values(array_intersect($availableKeys, $keys)) : $availableKeys;
    }

    /** Totals and per-grade breakdown for one aging module (Raw Material / Export Goods). */
    private function moduleSummary(array $module, StockBatch $model, array $user, ?int $divisionId, GradeService $grader): array
    {
        $summary = [
            'name' => $module['index_title'],
            'base' => $module['base'],
            'icon' => $module['icon'] ?? '',
            'batches' => 0,
            'value' => 0.0,
            'attention_batches' => 0,
            'attention_value' => 0.0,
            'grades' => array_fill_keys(['A+', 'A', 'B', 'C', 'EX'], ['batches' => 0, 'value' => 0.0]),
        ];

        foreach ($model->allAccessible($user, ['division_id' => $divisionId]) as $row) {
            $row = $grader->enrich($row);
            $value = (float)($row['stock_value'] ?? 0);
            $code = (string)$row['grade']['grade_code'];

            $summary['batches']++;
            $summary['value'] += $value;
            if (isset($summary['grades'][$code])) {
                $summary['grades'][$code]['batches']++;
                $summary['grades'][$code]['value'] += $value;
            }
            if ($code === 'C' || $code === 'EX') {
                $summary['attention_batches']++;
                $summary['attention_value'] += $value;
            }
        }

        return $summary;
    }
}
