<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use App\Models\MarketReturnDivisionMapping;

class MarketReturnDivisionResolver
{
    private array $divisions = [];
    private array $divisionAliases = [];
    private array $exactMappings = [];
    private array $containsMappings = [];
    private array $history = [];

    public function __construct()
    {
        $pdo = Database::connection();

        foreach ($pdo->query('SELECT id,code,name FROM divisions WHERE is_active=1 ORDER BY code')->fetchAll() as $division) {
            $id = (int)$division['id'];
            $division['id'] = $id;
            $this->divisions[$id] = $division;
            $this->divisionAliases[$this->normalizeDivision((string)$division['code'])] = $id;
            $this->divisionAliases[$this->normalizeDivision((string)$division['name'])] = $id;
        }

        // Dedicated Market Return mapping. This module intentionally does not use
        // the Stock Aging material master because the Market Return product universe differs.
        try {
            $sql = 'SELECT m.match_type,m.match_text,m.match_key,m.division_id,d.code division_code,d.name division_name
                    FROM market_return_division_mappings m
                    JOIN divisions d ON d.id=m.division_id
                    WHERE m.is_active=1 AND d.is_active=1
                    ORDER BY CHAR_LENGTH(m.match_key) DESC, m.id';
            foreach ($pdo->query($sql)->fetchAll() as $row) {
                $entry = [
                    'division_id' => (int)$row['division_id'],
                    'division_code' => (string)$row['division_code'],
                    'division_name' => (string)$row['division_name'],
                    'match_text' => (string)$row['match_text'],
                    'match_key' => (string)$row['match_key'],
                ];
                if ($row['match_type'] === 'contains') {
                    $this->containsMappings[] = $entry;
                } else {
                    $this->exactMappings[(string)$row['match_key']][(int)$row['division_id']] = $entry;
                }
            }
        } catch (\Throwable $e) {
            // Migration 008 may not be installed yet. Prior Market Return history can still resolve known products.
        }

        // Reuse prior Market Return assignments only when a product historically belongs
        // to one unique division. This remains fully independent from Stock Aging data.
        try {
            $sql = 'SELECT mri.product_name,mri.division_id,d.code division_code,d.name division_name,COUNT(*) cnt
                    FROM market_return_items mri
                    JOIN divisions d ON d.id=mri.division_id
                    WHERE d.is_active=1
                    GROUP BY mri.product_name,mri.division_id,d.code,d.name';
            $byProduct = [];
            foreach ($pdo->query($sql)->fetchAll() as $row) {
                $key = MarketReturnDivisionMapping::normalize((string)$row['product_name']);
                if ($key === '') continue;
                $byProduct[$key][(int)$row['division_id']] = [
                    'division_id' => (int)$row['division_id'],
                    'division_code' => (string)$row['division_code'],
                    'division_name' => (string)$row['division_name'],
                    'count' => (int)$row['cnt'],
                ];
            }
            foreach ($byProduct as $key => $choices) {
                if (count($choices) === 1) $this->history[$key] = array_values($choices)[0];
            }
        } catch (\Throwable $e) {
            // Market Return tables may not yet exist on a fresh install.
        }
    }

    public function resolveItems(array $items, array $user): array
    {
        $resolved = [];
        $unmatched = [];
        $stats = [];

        $isGlobalOperator = Auth::isGlobalOperator($user);
        $lockedDivisionId = !$isGlobalOperator ? (int)($user['division_id'] ?? 0) : 0;

        foreach ($items as $index => $item) {
            $resolution = null;

            if (!$isGlobalOperator) {
                $division = $this->divisions[$lockedDivisionId] ?? null;
                if (!$division) {
                    $unmatched[] = $this->unmatchedRow($item, $index, 'Your login division is inactive or unavailable.');
                    continue;
                }
                $resolution = [
                    'division_id' => $lockedDivisionId,
                    'division_code' => $division['code'],
                    'division_name' => $division['name'],
                    'method' => 'login_division',
                    'confidence' => 100,
                ];
            } else {
                $resolution = $this->resolveSuperAdminItem($item);
            }

            if (!$resolution) {
                $unmatched[] = $this->unmatchedRow(
                    $item,
                    $index,
                    'No division could be identified from the file, Market Return Division Mapping, or prior Market Return history.'
                );
                continue;
            }

            $item['division_id'] = (int)$resolution['division_id'];
            $item['division_code'] = (string)$resolution['division_code'];
            $item['division_name'] = (string)$resolution['division_name'];
            $item['division_resolution'] = (string)$resolution['method'];
            $item['division_confidence'] = (int)$resolution['confidence'];
            $resolved[] = $item;

            $id = (int)$resolution['division_id'];
            if (!isset($stats[$id])) {
                $stats[$id] = [
                    'division_id' => $id,
                    'division_code' => (string)$resolution['division_code'],
                    'division_name' => (string)$resolution['division_name'],
                    'rows' => 0,
                    'methods' => [],
                ];
            }
            $stats[$id]['rows']++;
            $method = (string)$resolution['method'];
            $stats[$id]['methods'][$method] = ($stats[$id]['methods'][$method] ?? 0) + 1;
        }

        uasort($stats, static fn(array $a, array $b): int => strcmp($a['division_code'], $b['division_code']));

        return [
            'items' => $resolved,
            'unmatched' => $unmatched,
            'stats' => array_values($stats),
        ];
    }

    private function resolveSuperAdminItem(array $item): ?array
    {
        $hint = trim((string)($item['division_hint'] ?? ''));
        if ($hint !== '') {
            $id = $this->divisionAliases[$this->normalizeDivision($hint)] ?? null;
            if ($id && isset($this->divisions[$id])) {
                $division = $this->divisions[$id];
                return [
                    'division_id' => $id,
                    'division_code' => $division['code'],
                    'division_name' => $division['name'],
                    'method' => 'file_division',
                    'confidence' => 100,
                ];
            }
            return null;
        }

        $product = trim((string)($item['product_name'] ?? ''));
        $key = MarketReturnDivisionMapping::normalize($product);
        if ($key === '') return null;

        if (!empty($this->exactMappings[$key])) {
            $choices = $this->exactMappings[$key];
            if (count($choices) === 1) {
                $match = array_values($choices)[0];
                return [
                    'division_id' => (int)$match['division_id'],
                    'division_code' => (string)$match['division_code'],
                    'division_name' => (string)$match['division_name'],
                    'method' => 'market_return_mapping',
                    'confidence' => 100,
                ];
            }
            return null;
        }

        // Keyword/contains rules are useful for Market Return families that never exist
        // in the Stock Aging material master. The longest unique matching rule wins.
        $containsMatches = [];
        foreach ($this->containsMappings as $mapping) {
            $pattern = (string)$mapping['match_key'];
            if ($pattern === '' || !str_contains($key, $pattern)) continue;
            $containsMatches[] = $mapping + ['length' => strlen($pattern)];
        }
        if ($containsMatches) {
            usort($containsMatches, static fn(array $a, array $b): int => $b['length'] <=> $a['length']);
            $bestLength = (int)$containsMatches[0]['length'];
            $best = array_values(array_filter($containsMatches, static fn(array $m): bool => (int)$m['length'] === $bestLength));
            $divisionIds = array_values(array_unique(array_map(static fn(array $m): int => (int)$m['division_id'], $best)));
            if (count($divisionIds) === 1) {
                $match = $best[0];
                return [
                    'division_id' => (int)$match['division_id'],
                    'division_code' => (string)$match['division_code'],
                    'division_name' => (string)$match['division_name'],
                    'method' => 'market_return_keyword',
                    'confidence' => 95,
                ];
            }
            return null;
        }

        if (isset($this->history[$key])) {
            $match = $this->history[$key];
            return [
                'division_id' => (int)$match['division_id'],
                'division_code' => (string)$match['division_code'],
                'division_name' => (string)$match['division_name'],
                'method' => 'previous_market_return',
                'confidence' => 99,
            ];
        }

        return null;
    }

    private function normalizeDivision(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', trim($value)) ?? '');
    }

    private function unmatchedRow(array $item, int $index, string $reason): array
    {
        return [
            'row' => $index + 1,
            'product_name' => (string)($item['product_name'] ?? ''),
            'source_sheet' => (string)($item['source_sheet'] ?? ''),
            'category_code' => (string)($item['category_code'] ?? ''),
            'division_hint' => (string)($item['division_hint'] ?? ''),
            'reason' => $reason,
        ];
    }
}
