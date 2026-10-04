<?php
namespace App\Models;

use App\Core\Database;

class GradeSetting
{
    /**
     * Maximum percentage of a batch's own shelf life already used.
     * The batch shelf life itself is Manufacture Date -> Expiry Date.
     */
    private const DEFAULTS = [
        'a_plus_used_pct' => 25.0,
        'a_used_pct' => 50.0,
        'b_used_pct' => 75.0,
        'c_used_pct' => 100.0,
    ];

    public function all(): array
    {
        try {
            $rows = Database::connection()->query(
                'SELECT division_id, a_plus_used_pct, a_used_pct, b_used_pct, c_used_pct
                 FROM division_grade_settings'
            )->fetchAll();
            return array_map(fn(array $row): array => $this->normalizePercentRow($row), $rows);
        } catch (\Throwable $e) {
            return $this->allFromLegacyColumns();
        }
    }

    public function forDivision(int $divisionId): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT division_id, a_plus_used_pct, a_used_pct, b_used_pct, c_used_pct
                 FROM division_grade_settings WHERE division_id=? LIMIT 1'
            );
            $stmt->execute([$divisionId]);
            $row = $stmt->fetch();
            if ($row) return $this->normalizePercentRow($row);
        } catch (\Throwable $e) {
            try {
                $stmt = Database::connection()->prepare(
                    'SELECT division_id,
                            a_plus_days AS a_plus_used_pct,
                            a_days AS a_used_pct,
                            b_days AS b_used_pct,
                            c_days AS c_used_pct
                     FROM division_grade_settings WHERE division_id=? LIMIT 1'
                );
                $stmt->execute([$divisionId]);
                $row = $stmt->fetch();
                if ($row) return $this->normalizePercentRow($row);
            } catch (\Throwable $ignored) {
                // Fall through to safe defaults.
            }
        }

        return ['division_id' => $divisionId] + self::DEFAULTS;
    }

    public function allWithDivisions(): array
    {
        try {
            $rows = Database::connection()->query(
                'SELECT d.id division_id, d.code, d.name, d.is_active,
                        gs.a_plus_used_pct, gs.a_used_pct, gs.b_used_pct, gs.c_used_pct
                 FROM divisions d
                 LEFT JOIN division_grade_settings gs ON gs.division_id=d.id
                 ORDER BY d.code'
            )->fetchAll();
            return array_map(fn(array $row): array => $this->normalizePercentRow($row), $rows);
        } catch (\Throwable $e) {
            try {
                $rows = Database::connection()->query(
                    'SELECT d.id division_id, d.code, d.name, d.is_active,
                            gs.a_plus_days AS a_plus_used_pct,
                            gs.a_days AS a_used_pct,
                            gs.b_days AS b_used_pct,
                            gs.c_days AS c_used_pct
                     FROM divisions d
                     LEFT JOIN division_grade_settings gs ON gs.division_id=d.id
                     ORDER BY d.code'
                )->fetchAll();
                return array_map(fn(array $row): array => $this->normalizePercentRow($row), $rows);
            } catch (\Throwable $ignored) {
                $rows = (new Division())->all(false);
                return array_map(static fn(array $d): array => [
                    'division_id' => (int)$d['id'],
                    'code' => $d['code'],
                    'name' => $d['name'],
                    'is_active' => $d['is_active'],
                ] + self::DEFAULTS, $rows);
            }
        }
    }

    public function upsertPercent(
        int $divisionId,
        float $aPlusUsedPct,
        float $aUsedPct,
        float $bUsedPct,
        float $cUsedPct
    ): void {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO division_grade_settings
                    (division_id, a_plus_used_pct, a_used_pct, b_used_pct, c_used_pct)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    a_plus_used_pct=VALUES(a_plus_used_pct),
                    a_used_pct=VALUES(a_used_pct),
                    b_used_pct=VALUES(b_used_pct),
                    c_used_pct=VALUES(c_used_pct)'
            );
            $stmt->execute([$divisionId, $aPlusUsedPct, $aUsedPct, $bUsedPct, $cUsedPct]);
            return;
        } catch (\Throwable $e) {
            // Backward-compatible fallback for an installation where migration 010
            // has not yet been imported. The legacy integer day columns can safely
            // hold whole percentages until the migration is applied.
            $stmt = Database::connection()->prepare(
                'INSERT INTO division_grade_settings (division_id, a_plus_days, a_days, b_days, c_days)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    a_plus_days=VALUES(a_plus_days),
                    a_days=VALUES(a_days),
                    b_days=VALUES(b_days),
                    c_days=VALUES(c_days)'
            );
            $stmt->execute([
                $divisionId,
                (int)round($aPlusUsedPct),
                (int)round($aUsedPct),
                (int)round($bUsedPct),
                (int)round($cUsedPct),
            ]);
        }
    }

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    private function allFromLegacyColumns(): array
    {
        try {
            $rows = Database::connection()->query(
                'SELECT division_id,
                        a_plus_days AS a_plus_used_pct,
                        a_days AS a_used_pct,
                        b_days AS b_used_pct,
                        c_days AS c_used_pct
                 FROM division_grade_settings'
            )->fetchAll();
            return array_map(fn(array $row): array => $this->normalizePercentRow($row), $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function normalizePercentRow(array $row): array
    {
        $aPlus = (float)($row['a_plus_used_pct'] ?? 0);
        $a = (float)($row['a_used_pct'] ?? 0);
        $b = (float)($row['b_used_pct'] ?? 0);
        $c = (float)($row['c_used_pct'] ?? 0);

        // Old day-based values are not valid percentage bands. If detected,
        // use the safe percentage defaults until Super Admin saves the new criteria.
        if (
            $aPlus < 0 || $aPlus >= $a ||
            $a <= $aPlus || $a >= $b ||
            $b <= $a || $b >= $c ||
            $c <= $b || $c > 100
        ) {
            $aPlus = self::DEFAULTS['a_plus_used_pct'];
            $a = self::DEFAULTS['a_used_pct'];
            $b = self::DEFAULTS['b_used_pct'];
            $c = self::DEFAULTS['c_used_pct'];
        }

        $row['a_plus_used_pct'] = $aPlus;
        $row['a_used_pct'] = $a;
        $row['b_used_pct'] = $b;
        $row['c_used_pct'] = $c;
        return $row;
    }
}
