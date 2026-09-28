<?php
namespace App\Models;

use App\Core\Database;

class GradeSetting
{
    private const DEFAULTS = [
        'a_plus_days' => 366,
        'a_days' => 365,
        'b_days' => 180,
        'c_days' => 90,
    ];

    public function all(): array
    {
        try {
            $rows = Database::connection()->query(
                'SELECT division_id, a_plus_days, a_days, b_days, c_days FROM division_grade_settings'
            )->fetchAll();
            return array_map(fn(array $row): array => $this->normalizeExpiryRow($row), $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function forDivision(int $divisionId): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT division_id, a_plus_days, a_days, b_days, c_days FROM division_grade_settings WHERE division_id=? LIMIT 1'
            );
            $stmt->execute([$divisionId]);
            $row = $stmt->fetch();
            if ($row) return $this->normalizeExpiryRow($row);
        } catch (\Throwable $e) {
            // Keep the dashboard usable until the existing database is upgraded/saved.
        }

        return ['division_id' => $divisionId] + self::DEFAULTS;
    }

    public function allWithDivisions(): array
    {
        try {
            $rows = Database::connection()->query(
                'SELECT d.id division_id, d.code, d.name, d.is_active,
                        gs.a_plus_days, gs.a_days, gs.b_days, gs.c_days
                 FROM divisions d
                 LEFT JOIN division_grade_settings gs ON gs.division_id=d.id
                 ORDER BY d.code'
            )->fetchAll();
            return array_map(fn(array $row): array => $this->normalizeExpiryRow($row), $rows);
        } catch (\Throwable $e) {
            $rows = (new Division())->all(false);
            return array_map(static fn(array $d): array => [
                'division_id' => (int)$d['id'],
                'code' => $d['code'],
                'name' => $d['name'],
                'is_active' => $d['is_active'],
            ] + self::DEFAULTS, $rows);
        }
    }

    public function upsert(int $divisionId, int $aPlusDays, int $aDays, int $bDays, int $cDays): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO division_grade_settings (division_id, a_plus_days, a_days, b_days, c_days)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                a_plus_days=VALUES(a_plus_days),
                a_days=VALUES(a_days),
                b_days=VALUES(b_days),
                c_days=VALUES(c_days)'
        );
        $stmt->execute([$divisionId, $aPlusDays, $aDays, $bDays, $cDays]);
    }

    private function normalizeExpiryRow(array $row): array
    {
        $aPlus = (int)($row['a_plus_days'] ?? 0);
        $a = (int)($row['a_days'] ?? 0);
        $b = (int)($row['b_days'] ?? 0);
        $c = (int)($row['c_days'] ?? 0);

        // Expiry remaining-day thresholds must descend A+ > A > B > C.
        // Older manufacture-age settings were ascending, so fall back safely until saved.
        if ($c < 1 || $b <= $c || $a <= $b || $aPlus <= $a) {
            $aPlus = self::DEFAULTS['a_plus_days'];
            $a = self::DEFAULTS['a_days'];
            $b = self::DEFAULTS['b_days'];
            $c = self::DEFAULTS['c_days'];
        }

        $row['a_plus_days'] = $aPlus;
        $row['a_days'] = $a;
        $row['b_days'] = $b;
        $row['c_days'] = $c;
        return $row;
    }
}
