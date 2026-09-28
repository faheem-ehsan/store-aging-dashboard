<?php
namespace App\Services;

use App\Models\GradeSetting;

class GradeService
{
    private array $settingsByDivision = [];

    public function __construct()
    {
        foreach ((new GradeSetting())->all() as $row) {
            $this->settingsByDivision[(int)$row['division_id']] = $this->normalizeSettings($row);
        }
    }

    public function enrich(array $batch): array
    {
        $today = new \DateTimeImmutable('today');
        $mfg = new \DateTimeImmutable($batch['manufacture_date']);
        $expiry = new \DateTimeImmutable($batch['expiry_date']);
        $batch['aging_days'] = (int)$mfg->diff($today)->format('%r%a');
        $batch['remaining_days'] = (int)$today->diff($expiry)->format('%r%a');
        $batch['grade'] = $this->gradeForBatch($mfg, $expiry, (int)$batch['division_id'], $today);
        return $batch;
    }

    public function settingsForDivision(int $divisionId): array
    {
        if (isset($this->settingsByDivision[$divisionId])) {
            return $this->settingsByDivision[$divisionId];
        }

        return $this->normalizeSettings((new GradeSetting())->forDivision($divisionId));
    }

    public function gradeForBatch(
        \DateTimeImmutable $manufactureDate,
        \DateTimeImmutable $expiryDate,
        int $divisionId,
        ?\DateTimeImmutable $asOf = null
    ): array {
        $asOf = $asOf ?? new \DateTimeImmutable('today');
        $settings = $this->settingsForDivision($divisionId);
        $remainingDays = (int)$asOf->diff($expiryDate)->format('%r%a');

        // Expired is always based on the actual Expiry Date.
        if ($remainingDays <= 0) {
            return [
                'grade_code' => 'EX',
                'grade_name' => 'Expired',
                'color_class' => 'black',
                'criteria_text' => 'Expiry Date and after',
                'period_end' => $expiryDate->format('Y-m-d'),
            ];
        }

        // Expiry-based grading uses remaining shelf-life days.
        // A+ = above A limit, A = above B through A limit,
        // B = above C through B limit, C = 1 through C limit.
        if ($remainingDays > $settings['a_days']) {
            return [
                'grade_code' => 'A+',
                'grade_name' => 'A+ Grade',
                'color_class' => 'blue',
                'criteria_text' => 'More than ' . $settings['a_days'] . ' day(s) remaining to Expiry Date',
                'period_end' => $expiryDate->modify('-' . $settings['a_days'] . ' days')->format('Y-m-d'),
            ];
        }

        if ($remainingDays > $settings['b_days']) {
            return [
                'grade_code' => 'A',
                'grade_name' => 'A Grade',
                'color_class' => 'green',
                'criteria_text' => ($settings['b_days'] + 1) . '–' . $settings['a_days'] . ' day(s) remaining to Expiry Date',
                'period_end' => $expiryDate->modify('-' . $settings['b_days'] . ' days')->format('Y-m-d'),
            ];
        }

        if ($remainingDays > $settings['c_days']) {
            return [
                'grade_code' => 'B',
                'grade_name' => 'B Grade',
                'color_class' => 'yellow',
                'criteria_text' => ($settings['c_days'] + 1) . '–' . $settings['b_days'] . ' day(s) remaining to Expiry Date',
                'period_end' => $expiryDate->modify('-' . $settings['c_days'] . ' days')->format('Y-m-d'),
            ];
        }

        return [
            'grade_code' => 'C',
            'grade_name' => 'C Grade',
            'color_class' => 'red',
            'criteria_text' => '1–' . $settings['c_days'] . ' day(s) remaining to Expiry Date',
            'period_end' => $expiryDate->modify('-1 day')->format('Y-m-d'),
        ];
    }

    private function normalizeSettings(array $row): array
    {
        $a = (int)($row['a_days'] ?? 365);
        $b = (int)($row['b_days'] ?? 180);
        $c = (int)($row['c_days'] ?? 90);

        // Old manufacture-age settings used ascending values. If found, use the
        // new safe expiry-based defaults until Super Admin saves division criteria.
        if ($c < 1 || $b <= $c || $a <= $b) {
            $a = 365;
            $b = 180;
            $c = 90;
        }

        return [
            'a_plus_days' => $a + 1,
            'a_days' => $a,
            'b_days' => $b,
            'c_days' => $c,
        ];
    }
}
