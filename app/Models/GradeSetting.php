<?php
namespace App\Models;

use App\Core\Database;

class GradeSetting
{
    private const DEFAULTS = [
        'a_plus_days' => 30,
        'a_days' => 60,
        'b_days' => 90,
        'c_days' => 120,
    ];

    public function all(): array
    {
        try {
            return Database::connection()->query(
                'SELECT division_id, a_plus_days, a_days, b_days, c_days FROM division_grade_settings'
            )->fetchAll();
        } catch (\Throwable $e) {
            return $this->legacyAll();
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
            if ($row) return $row;
        } catch (\Throwable $e) {
            try {
                $stmt = Database::connection()->prepare(
                    'SELECT division_id, a_plus_months, a_months, b_months FROM division_grade_settings WHERE division_id=? LIMIT 1'
                );
                $stmt->execute([$divisionId]);
                $row = $stmt->fetch();
                if ($row) return $this->legacyRowToDays($row);
            } catch (\Throwable $ignored) {
                // Keep the application usable until the day-based migration is imported.
            }
        }

        return ['division_id' => $divisionId] + self::DEFAULTS;
    }

    public function allWithDivisions(): array
    {
        try {
            return Database::connection()->query(
                'SELECT d.id division_id, d.code, d.name, d.is_active,
                        COALESCE(gs.a_plus_days, 30) a_plus_days,
                        COALESCE(gs.a_days, 60) a_days,
                        COALESCE(gs.b_days, 90) b_days,
                        COALESCE(gs.c_days, 120) c_days
                 FROM divisions d
                 LEFT JOIN division_grade_settings gs ON gs.division_id=d.id
                 ORDER BY d.code'
            )->fetchAll();
        } catch (\Throwable $e) {
            try {
                $rows = Database::connection()->query(
                    'SELECT d.id division_id, d.code, d.name, d.is_active,
                            COALESCE(gs.a_plus_months, 1) a_plus_months,
                            COALESCE(gs.a_months, 2) a_months,
                            COALESCE(gs.b_months, 3) b_months
                     FROM divisions d
                     LEFT JOIN division_grade_settings gs ON gs.division_id=d.id
                     ORDER BY d.code'
                )->fetchAll();
                return array_map(fn(array $row): array => $this->legacyRowToDays($row), $rows);
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

    private function legacyAll(): array
    {
        try {
            $rows = Database::connection()->query(
                'SELECT division_id, a_plus_months, a_months, b_months FROM division_grade_settings'
            )->fetchAll();
            return array_map(fn(array $row): array => $this->legacyRowToDays($row), $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function legacyRowToDays(array $row): array
    {
        $aPlus = max(1, (int)($row['a_plus_months'] ?? 1)) * 30;
        $a = max((int)($row['a_months'] ?? 2) * 30, $aPlus + 1);
        $b = max((int)($row['b_months'] ?? 3) * 30, $a + 1);
        $c = max($b + 30, 120);

        unset($row['a_plus_months'], $row['a_months'], $row['b_months']);
        $row['a_plus_days'] = $aPlus;
        $row['a_days'] = $a;
        $row['b_days'] = $b;
        $row['c_days'] = $c;
        return $row;
    }
}
