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
        $aPlusEnd = $manufactureDate->modify('+' . $settings['a_plus_days'] . ' days');
        $aEnd = $manufactureDate->modify('+' . $settings['a_days'] . ' days');
        $bEnd = $manufactureDate->modify('+' . $settings['b_days'] . ' days');
        $cEnd = $manufactureDate->modify('+' . $settings['c_days'] . ' days');

        // Expired is always determined by the actual expiry date, never by an aging threshold.
        if ($asOf >= $expiryDate) {
            return [
                'grade_code' => 'EX',
                'grade_name' => 'Expired',
                'color_class' => 'black',
                'criteria_text' => 'Expiry Date and after',
                'period_end' => $expiryDate->format('Y-m-d'),
            ];
        }

        if ($asOf < $aPlusEnd) {
            return [
                'grade_code' => 'A+',
                'grade_name' => 'A+ Grade',
                'color_class' => 'blue',
                'criteria_text' => 'First ' . $settings['a_plus_days'] . ' day(s) from manufacture',
                'period_end' => $aPlusEnd->modify('-1 day')->format('Y-m-d'),
            ];
        }

        if ($asOf < $aEnd) {
            return [
                'grade_code' => 'A',
                'grade_name' => 'A Grade',
                'color_class' => 'green',
                'criteria_text' => 'After A+ through day ' . $settings['a_days'] . ' from manufacture',
                'period_end' => $aEnd->modify('-1 day')->format('Y-m-d'),
            ];
        }

        if ($asOf < $bEnd) {
            return [
                'grade_code' => 'B',
                'grade_name' => 'B Grade',
                'color_class' => 'yellow',
                'criteria_text' => 'After A through day ' . $settings['b_days'] . ' from manufacture',
                'period_end' => $bEnd->modify('-1 day')->format('Y-m-d'),
            ];
        }

        // C is the final non-expired grade. The configured C day limit is retained as
        // the division's final aging threshold; if expiry is later, the item remains C.
        $configuredCEnd = $cEnd < $expiryDate ? $cEnd : $expiryDate;
        return [
            'grade_code' => 'C',
            'grade_name' => 'C Grade',
            'color_class' => 'red',
            'criteria_text' => $asOf < $cEnd
                ? 'After B through day ' . $settings['c_days'] . ' from manufacture'
                : 'Above day ' . $settings['c_days'] . ' from manufacture until Expiry Date',
            'period_end' => ($configuredCEnd > $asOf ? $configuredCEnd : $expiryDate)->modify('-1 day')->format('Y-m-d'),
        ];
    }

    private function normalizeSettings(array $row): array
    {
        $ap = max(1, (int)($row['a_plus_days'] ?? 30));
        $a = max($ap + 1, (int)($row['a_days'] ?? 60));
        $b = max($a + 1, (int)($row['b_days'] ?? 90));
        $c = max($b + 1, (int)($row['c_days'] ?? 120));

        return [
            'a_plus_days' => $ap,
            'a_days' => $a,
            'b_days' => $b,
            'c_days' => $c,
        ];
    }
}
