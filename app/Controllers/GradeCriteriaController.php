<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\GradeSetting;
use App\Services\AuditLogger;

class GradeCriteriaController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('grades/index', [
            'title' => 'Grading Criteria',
            'rows' => (new GradeSetting())->allWithDivisions(),
        ]);
    }

    public function update(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $model = new GradeSetting();
        $before = $model->allWithDivisions();

        $aPlusValues = (array)($_POST['a_plus_used_pct'] ?? []);
        $aValues = (array)($_POST['a_used_pct'] ?? []);
        $bValues = (array)($_POST['b_used_pct'] ?? []);
        $cValues = (array)($_POST['c_used_pct'] ?? []);

        try {
            foreach ($before as $row) {
                $divisionId = (int)$row['division_id'];
                $aPlus = isset($aPlusValues[$divisionId]) ? (float)$aPlusValues[$divisionId] : (float)$row['a_plus_used_pct'];
                $a = isset($aValues[$divisionId]) ? (float)$aValues[$divisionId] : (float)$row['a_used_pct'];
                $b = isset($bValues[$divisionId]) ? (float)$bValues[$divisionId] : (float)$row['b_used_pct'];
                $c = isset($cValues[$divisionId]) ? (float)$cValues[$divisionId] : (float)$row['c_used_pct'];

                if ($aPlus < 0 || $aPlus > 100 || $a < 0 || $a > 100 || $b < 0 || $b > 100 || $c < 0 || $c > 100) {
                    throw new \RuntimeException($row['code'] . ': every percentage must be between 0 and 100.');
                }
                if ($aPlus >= $a) {
                    throw new \RuntimeException($row['code'] . ': A+ percentage must be less than A.');
                }
                if ($a >= $b) {
                    throw new \RuntimeException($row['code'] . ': A percentage must be less than B.');
                }
                if ($b >= $c) {
                    throw new \RuntimeException($row['code'] . ': B percentage must be less than C.');
                }

                $model->upsertPercent($divisionId, $aPlus, $a, $b, $c);
            }

            $after = $model->allWithDivisions();
            AuditLogger::log(
                'GRADE_CRITERIA_UPDATE',
                'Updated division shelf-life-used percentage grading criteria.',
                'grading_criteria',
                null,
                ['before' => $this->snapshot($before), 'after' => $this->snapshot($after)],
                $current
            );
            flash('success', 'Grading criteria updated. Each batch is graded against its own Manufacture Date and Expiry Date using the saved division percentages.');
        } catch (\Throwable $e) {
            flash('error', $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Unable to save grading criteria. If this is an existing database, import migration 010_add_shelf_life_percent_grading.sql.');
        }

        redirect('/grade-criteria');
    }

    private function snapshot(array $rows): array
    {
        return array_map(static fn(array $row): array => [
            'division' => $row['code'],
            'a_plus_max_used_pct' => (float)$row['a_plus_used_pct'],
            'a_max_used_pct' => (float)$row['a_used_pct'],
            'b_max_used_pct' => (float)$row['b_used_pct'],
            'c_max_used_pct' => (float)$row['c_used_pct'],
        ], $rows);
    }
}
