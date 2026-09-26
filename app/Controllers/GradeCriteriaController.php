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
        $aPlusDays = (array)($_POST['a_plus_days'] ?? []);
        $aDays = (array)($_POST['a_days'] ?? []);
        $bDays = (array)($_POST['b_days'] ?? []);
        $cDays = (array)($_POST['c_days'] ?? []);

        try {
            foreach ($before as $row) {
                $divisionId = (int)$row['division_id'];
                $ap = isset($aPlusDays[$divisionId]) ? (int)$aPlusDays[$divisionId] : 30;
                $a = isset($aDays[$divisionId]) ? (int)$aDays[$divisionId] : 60;
                $b = isset($bDays[$divisionId]) ? (int)$bDays[$divisionId] : 90;
                $c = isset($cDays[$divisionId]) ? (int)$cDays[$divisionId] : 120;

                if ($ap < 1) {
                    throw new \RuntimeException($row['code'] . ': A+ days must be at least 1.');
                }
                if ($a <= $ap) {
                    throw new \RuntimeException($row['code'] . ': A days must be greater than A+ days.');
                }
                if ($b <= $a) {
                    throw new \RuntimeException($row['code'] . ': B days must be greater than A days.');
                }
                if ($c <= $b) {
                    throw new \RuntimeException($row['code'] . ': C days must be greater than B days.');
                }

                $model->upsert($divisionId, $ap, $a, $b, $c);
            }

            $after = $model->allWithDivisions();
            AuditLogger::log(
                'GRADE_CRITERIA_UPDATE',
                'Updated division day-based grading criteria.',
                'grading_criteria',
                null,
                ['before' => $this->snapshot($before), 'after' => $this->snapshot($after)],
                $current
            );
            flash('success', 'Grading criteria updated. Grades are calculated in days from each product manufacture date.');
        } catch (\Throwable $e) {
            flash('error', $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Unable to save grading criteria. Import database/migrations/006_change_grading_criteria_to_days.sql first.');
        }

        redirect('/grade-criteria');
    }

    private function snapshot(array $rows): array
    {
        return array_map(static fn(array $row): array => [
            'division' => $row['code'],
            'a_plus_days' => (int)$row['a_plus_days'],
            'a_days' => (int)$row['a_days'],
            'b_days' => (int)$row['b_days'],
            'c_days' => (int)$row['c_days'],
        ], $rows);
    }
}
