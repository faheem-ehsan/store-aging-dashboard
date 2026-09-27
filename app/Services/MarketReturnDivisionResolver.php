<?php
namespace App\Services;

use App\Core\Database;

class MarketReturnDivisionResolver
{
    private array $divisions = [];
    private array $divisionAliases = [];
    private array $materials = [];
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

        try {
            $sql = 'SELECT m.description,d.id division_id,d.code division_code,d.name division_name
                    FROM materials m
                    JOIN divisions d ON d.id=m.division_id
                    WHERE m.is_active=1 AND d.is_active=1';
            foreach ($pdo->query($sql)->fetchAll() as $row) {
                $full = $this->normalizeProduct((string)$row['description'], false);
                $family = $this->normalizeProduct((string)$row['description'], true);
                if ($full === '' && $family === '') continue;
                $this->materials[] = [
                    'division_id' => (int)$row['division_id'],
                    'division_code' => (string)$row['division_code'],
                    'division_name' => (string)$row['division_name'],
                    'description' => (string)$row['description'],
                    'full' => $full,
                    'family' => $family,
                ];
            }
        } catch (\Throwable $e) {
            // Stock Aging material master may be empty on a new installation.
        }

        // Reuse prior, confirmed Market Return division assignments. If a product has
        // appeared under more than one division, leave it ambiguous rather than guessing.
        try {
            $sql = 'SELECT mri.product_name,mri.division_id,d.code division_code,d.name division_name,COUNT(*) cnt
                    FROM market_return_items mri
                    JOIN divisions d ON d.id=mri.division_id
                    WHERE d.is_active=1
                    GROUP BY mri.product_name,mri.division_id,d.code,d.name';
            $byProduct = [];
            foreach ($pdo->query($sql)->fetchAll() as $row) {
                $key = $this->normalizeProduct((string)$row['product_name'], false);
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

        $isSuper = ($user['role'] ?? '') === 'super_admin';
        $lockedDivisionId = !$isSuper ? (int)($user['division_id'] ?? 0) : 0;

        foreach ($items as $index => $item) {
            $resolution = null;

            if (!$isSuper) {
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
                    'No unique division match was found in the Division column, prior Market Return data, or Stock Aging material master.'
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
        $full = $this->normalizeProduct($product, false);
        $family = $this->normalizeProduct($product, true);
        if ($full === '') return null;

        if (isset($this->history[$full])) {
            $match = $this->history[$full];
            return [
                'division_id' => (int)$match['division_id'],
                'division_code' => (string)$match['division_code'],
                'division_name' => (string)$match['division_name'],
                'method' => 'previous_market_return',
                'confidence' => 99,
            ];
        }

        $scores = [];
        $examples = [];
        foreach ($this->materials as $material) {
            $score = $this->scoreProduct($full, $family, $material['full'], $material['family']);
            if ($score <= 0) continue;
            $id = (int)$material['division_id'];
            if (!isset($scores[$id]) || $score > $scores[$id]) {
                $scores[$id] = $score;
                $examples[$id] = $material;
            }
        }

        if (!$scores) return null;
        arsort($scores);
        $ids = array_keys($scores);
        $bestId = (int)$ids[0];
        $bestScore = (float)$scores[$bestId];
        $secondScore = isset($ids[1]) ? (float)$scores[(int)$ids[1]] : 0.0;

        // Require a strong, clearly better match. This intentionally avoids assigning
        // a division when two divisions carry similar product descriptions.
        if ($bestScore < 82.0) return null;
        if ($secondScore >= 82.0 && ($bestScore - $secondScore) < 7.0) return null;

        $division = $this->divisions[$bestId] ?? null;
        if (!$division) return null;

        return [
            'division_id' => $bestId,
            'division_code' => $division['code'],
            'division_name' => $division['name'],
            'method' => 'stock_aging_material',
            'confidence' => (int)round($bestScore),
            'matched_material' => $examples[$bestId]['description'] ?? null,
        ];
    }

    private function scoreProduct(string $leftFull, string $leftFamily, string $rightFull, string $rightFamily): float
    {
        if ($leftFull === '' || $rightFull === '') return 0.0;
        if ($leftFull === $rightFull) return 100.0;
        if ($leftFamily !== '' && $leftFamily === $rightFamily) return 96.0;

        $minFull = min(strlen($leftFull), strlen($rightFull));
        if ($minFull >= 6 && (str_contains($leftFull, $rightFull) || str_contains($rightFull, $leftFull))) return 92.0;

        if ($leftFamily !== '' && $rightFamily !== '') {
            $minFamily = min(strlen($leftFamily), strlen($rightFamily));
            if ($minFamily >= 5 && (str_contains($leftFamily, $rightFamily) || str_contains($rightFamily, $leftFamily))) return 90.0;
        }

        $similar = 0.0;
        similar_text($leftFamily !== '' ? $leftFamily : $leftFull, $rightFamily !== '' ? $rightFamily : $rightFull, $similar);
        $jaccard = $this->tokenJaccard($leftFamily !== '' ? $leftFamily : $leftFull, $rightFamily !== '' ? $rightFamily : $rightFull) * 100.0;

        return max($similar, $jaccard);
    }

    private function tokenJaccard(string $left, string $right): float
    {
        $a = array_values(array_unique(array_filter(explode(' ', $left), static fn(string $v): bool => strlen($v) > 1)));
        $b = array_values(array_unique(array_filter(explode(' ', $right), static fn(string $v): bool => strlen($v) > 1)));
        if (!$a || !$b) return 0.0;
        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));
        return $union > 0 ? $intersection / $union : 0.0;
    }

    private function normalizeProduct(string $text, bool $family): string
    {
        $text = strtolower(trim($text));
        $text = str_replace(['&', '+'], [' and ', ' '], $text);
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\bqarshi\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/\bexport\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/\bregular\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/\bsugar\s*free\b/u', ' sugarfree ', $text) ?? $text;
        $text = preg_replace('/\bchoc(?:olate)?\b/u', ' chocolate ', $text) ?? $text;
        $text = preg_replace('/\bzink\b/u', ' zinc ', $text) ?? $text;
        $text = preg_replace('/\b(safoof|sharbat|arq|majoon|habb)\s+e\b/u', ' $1 ', $text) ?? $text;

        if ($family) {
            $text = preg_replace('/\b\d+(?:\.\d+)?\s*(?:ml|ltr|liter|litre|gm|g|kg|mg|nos|pcs|box|boxes|bottle|bottles|jar|jars|dabbi|strip|strips|sachet|sachets)\b/u', ' ', $text) ?? $text;
            $text = preg_replace('/\b\d+(?:\.\d+)?\b/u', ' ', $text) ?? $text;
        }

        $tokens = array_values(array_filter(preg_split('/\s+/', trim($text)) ?: [], static fn(string $v): bool => $v !== ''));
        return implode(' ', $tokens);
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
