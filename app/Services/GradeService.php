<?php
namespace App\Services;

use App\Models\GradeSetting;

/**
 * Product-aware + division-configurable shelf-life grading.
 *
 * Every batch is measured against its own Manufacture Date -> Expiry Date shelf life,
 * while Super Admin controls the percentage bands separately for each division.
 * This means products in the same division may have 180-day, 1-year, 2-year,
 * 4-year or any other shelf life and still use one fair grading method.
 */
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

        $agingDays = (int)$mfg->diff($today)->format('%r%a');
        $rawRemainingDays = (int)$today->diff($expiry)->format('%r%a');
        $rawShelfLifeDays = (int)$mfg->diff($expiry)->format('%r%a');

        // Calendar-day convention used by the UI:
        // Aging = elapsed days since Manufacturing Date.
        // Remaining = current live day through Expiry Date, inclusive.
        //
        // Example:
        //   MFG 01/Oct/2026, as-of 04/Oct/2026, EXP 31/Oct/2026
        //   Aging = 3, Remaining = 28, Total calendar shelf-life = 31.
        //
        // On the actual Expiry Date the batch is already Expired, so remaining
        // stays 0 instead of becoming 1.
        $batch['aging_days'] = $agingDays;
        $batch['remaining_days'] = $rawRemainingDays > 0
            ? $rawRemainingDays + 1
            : $rawRemainingDays;
        $batch['shelf_life_days'] = $rawShelfLifeDays >= 0
            ? $rawShelfLifeDays + 1
            : 0;
        $batch['grade'] = $this->gradeForBatch($mfg, $expiry, (int)($batch['division_id'] ?? 0), $today);
        $batch['shelf_life_remaining_percent'] = $batch['grade']['remaining_percent'];
        $batch['shelf_life_used_percent'] = $batch['grade']['used_percent'];
        return $batch;
    }

    public function settingsForDivision(int $divisionId): array
    {
        if (isset($this->settingsByDivision[$divisionId])) {
            return $this->settingsByDivision[$divisionId];
        }

        $settings = $this->normalizeSettings((new GradeSetting())->forDivision($divisionId));
        $this->settingsByDivision[$divisionId] = $settings;
        return $settings;
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
        $totalShelfDays = (int)$manufactureDate->diff($expiryDate)->format('%r%a');

        // Expired always starts on the actual Expiry Date, independent of percentages.
        if ($remainingDays <= 0) {
            return $this->result(
                'EX', 'Expired', 'black', 'Expiry Date and after',
                $expiryDate->format('Y-m-d'), 0.0, 100.0, max(0, $totalShelfDays)
            );
        }

        if ($totalShelfDays <= 0) {
            // Keep malformed legacy rows usable rather than applying a division-day fallback.
            $totalShelfDays = max(1, $remainingDays);
        }

        $remainingPercent = ($remainingDays / $totalShelfDays) * 100;
        $remainingPercent = max(0.0, min(100.0, $remainingPercent));
        $usedPercent = 100.0 - $remainingPercent;

        $aPlus = $settings['a_plus_used_pct'];
        $a = $settings['a_used_pct'];
        $b = $settings['b_used_pct'];
        $c = $settings['c_used_pct'];

        if ($usedPercent <= $aPlus) {
            return $this->result(
                'A+', 'A+ Grade', 'blue',
                '0–' . $this->pct($aPlus) . '% shelf life used',
                $this->boundaryDate($manufactureDate, $totalShelfDays, $aPlus),
                $remainingPercent, $usedPercent, $totalShelfDays
            );
        }

        if ($usedPercent <= $a) {
            return $this->result(
                'A', 'A Grade', 'green',
                '>' . $this->pct($aPlus) . '–' . $this->pct($a) . '% shelf life used',
                $this->boundaryDate($manufactureDate, $totalShelfDays, $a),
                $remainingPercent, $usedPercent, $totalShelfDays
            );
        }

        if ($usedPercent <= $b) {
            return $this->result(
                'B', 'B Grade', 'yellow',
                '>' . $this->pct($a) . '–' . $this->pct($b) . '% shelf life used',
                $this->boundaryDate($manufactureDate, $totalShelfDays, $b),
                $remainingPercent, $usedPercent, $totalShelfDays
            );
        }

        // C is deliberately the final live grade and therefore continues until
        // the actual Expiry Date. c_used_pct remains a configurable reference/maximum
        // in the criteria screen, preserving the existing four-field configuration.
        $cText = '>' . $this->pct($b) . '–' . $this->pct($c) . '% shelf life used';
        if ($c < 100.0) {
            $cText .= ' · remains C until Expiry Date';
        }

        return $this->result(
            'C', 'C Grade', 'red', $cText,
            $expiryDate->modify('-1 day')->format('Y-m-d'),
            $remainingPercent, $usedPercent, $totalShelfDays
        );
    }

    /**
     * Returns the next grade transition for a live batch.
     * The notification system uses this to alert exactly one calendar day
     * before the configured grading threshold is crossed.
     */
    public function nextTransitionForBatch(array $batch, ?\DateTimeImmutable $asOf = null): ?array
    {
        $asOf = $asOf ?? new \DateTimeImmutable('today');
        $mfg = new \DateTimeImmutable((string)$batch['manufacture_date']);
        $expiry = new \DateTimeImmutable((string)$batch['expiry_date']);

        if ($asOf >= $expiry) return null;

        $divisionId = (int)($batch['division_id'] ?? 0);
        $grade = $this->gradeForBatch($mfg, $expiry, $divisionId, $asOf);
        $current = $grade['grade_code'];
        $settings = $this->settingsForDivision($divisionId);
        $totalShelfDays = (int)$mfg->diff($expiry)->format('%r%a');
        if ($totalShelfDays <= 0) return null;

        $next = null;
        $transitionDate = null;

        if ($current === 'A+') {
            $next = 'A';
            $transitionDate = $this->transitionDateAfterThreshold($mfg, $totalShelfDays, $settings['a_plus_used_pct']);
        } elseif ($current === 'A') {
            $next = 'B';
            $transitionDate = $this->transitionDateAfterThreshold($mfg, $totalShelfDays, $settings['a_used_pct']);
        } elseif ($current === 'B') {
            $next = 'C';
            $transitionDate = $this->transitionDateAfterThreshold($mfg, $totalShelfDays, $settings['b_used_pct']);
        } elseif ($current === 'C') {
            $next = 'EX';
            $transitionDate = $expiry;
        }

        if (!$next || !$transitionDate) return null;
        if ($transitionDate > $expiry) $transitionDate = $expiry;

        return [
            'current_grade' => $current,
            'next_grade' => $next,
            'next_grade_name' => $next === 'EX' ? 'Expired' : $next . ' Grade',
            'transition_date' => $transitionDate->format('Y-m-d'),
            'alert_date' => $transitionDate->modify('-1 day')->format('Y-m-d'),
        ];
    }

    public function criteriaForDivision(int $divisionId): array
    {
        return $this->criteriaFromSettings($this->settingsForDivision($divisionId));
    }

    public function defaultCriteria(): array
    {
        return $this->criteriaFromSettings($this->normalizeSettings(GradeSetting::defaults()));
    }

    private function criteriaFromSettings(array $settings): array
    {
        $ap = $settings['a_plus_used_pct'];
        $a = $settings['a_used_pct'];
        $b = $settings['b_used_pct'];
        $c = $settings['c_used_pct'];

        return [
            'A+' => '0–' . $this->pct($ap) . '% shelf life used',
            'A' => '>' . $this->pct($ap) . '–' . $this->pct($a) . '% shelf life used',
            'B' => '>' . $this->pct($a) . '–' . $this->pct($b) . '% shelf life used',
            'C' => '>' . $this->pct($b) . '–' . $this->pct($c) . '% shelf life used until Expiry Date',
            'EX' => 'Expiry Date and after',
        ];
    }

    private function normalizeSettings(array $row): array
    {
        $defaults = GradeSetting::defaults();
        $aPlus = (float)($row['a_plus_used_pct'] ?? $defaults['a_plus_used_pct']);
        $a = (float)($row['a_used_pct'] ?? $defaults['a_used_pct']);
        $b = (float)($row['b_used_pct'] ?? $defaults['b_used_pct']);
        $c = (float)($row['c_used_pct'] ?? $defaults['c_used_pct']);

        if ($aPlus < 0 || $aPlus >= $a || $a >= $b || $b >= $c || $c > 100) {
            return $defaults;
        }

        return [
            'a_plus_used_pct' => $aPlus,
            'a_used_pct' => $a,
            'b_used_pct' => $b,
            'c_used_pct' => $c,
        ];
    }

    private function result(
        string $code,
        string $name,
        string $color,
        string $criteriaText,
        string $periodEnd,
        float $remainingPercent,
        float $usedPercent,
        int $totalShelfDays
    ): array {
        return [
            'grade_code' => $code,
            'grade_name' => $name,
            'color_class' => $color,
            'criteria_text' => $criteriaText,
            'period_end' => $periodEnd,
            'remaining_percent' => round($remainingPercent, 2),
            'used_percent' => round($usedPercent, 2),
            'total_shelf_days' => $totalShelfDays,
        ];
    }

    private function boundaryDate(\DateTimeImmutable $manufactureDate, int $totalShelfDays, float $usedPercent): string
    {
        $days = (int)floor($totalShelfDays * ($usedPercent / 100));
        return $manufactureDate->modify('+' . max(0, $days) . ' days')->format('Y-m-d');
    }

    private function transitionDateAfterThreshold(\DateTimeImmutable $manufactureDate, int $totalShelfDays, float $usedPercent): \DateTimeImmutable
    {
        // The current grade includes the threshold itself (<=). The next grade
        // begins on the first whole day after that percentage is exceeded.
        $days = (int)floor($totalShelfDays * ($usedPercent / 100.0)) + 1;
        return $manufactureDate->modify('+' . max(1, $days) . ' days');
    }

    private function pct(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
