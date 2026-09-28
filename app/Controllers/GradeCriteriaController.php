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
                $aPlus = isset($aPlusDays[$divisionId]) ? (int)$aPlusDays[$divisionId] : (int)($row['a_plus_days'] ?? 366);
                $a = isset($aDays[$divisionId]) ? (int)$aDays[$divisionId] : 365;
                $b = isset($bDays[$divisionId]) ? (int)$bDays[$divisionId] : 180;
                $c = isset($cDays[$divisionId]) ? (int)$cDays[$divisionId] : 90;

                if ($c < 1) {
                    throw new \RuntimeException($row['code'] . ': C days must be at least 1.');
                }
                if ($b <= $c) {
                    throw new \RuntimeException($row['code'] . ': B days must be greater than C days.');
                }
                if ($a <= $b) {
                    throw new \RuntimeException($row['code'] . ': A days must be greater than B days.');
                }
                if ($aPlus <= $a) {
                    throw new \RuntimeException($row['code'] . ': A+ days must be greater than A days.');
                }

                $model->upsert($divisionId, $aPlus, $a, $b, $c);
            }

            $after = $model->allWithDivisions();
            AuditLogger::log(
                'GRADE_CRITERIA_UPDATE',
                'Updated division expiry remaining-day grading criteria including user-defined A+ threshold.',
                'grading_criteria',
                null,
                ['before' => $this->snapshot($before), 'after' => $this->snapshot($after)],
                $current
            );
            flash('success', 'Grading criteria updated. A+, A, B and C days are now user-defined for each division.');
        } catch (\Throwable $e) {
            flash('error', $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Unable to save grading criteria.');
        }

        redirect('/grade-criteria');
    }

    private function snapshot(array $rows): array
    {
        return array_map(static fn(array $row): array => [
            'division' => $row['code'],
            'a_plus_remaining_days' => (int)$row['a_plus_days'],
            'a_remaining_days' => (int)$row['a_days'],
            'b_max_remaining_days' => (int)$row['b_days'],
            'c_max_remaining_days' => (int)$row['c_days'],
        ], $rows);
    }
}
