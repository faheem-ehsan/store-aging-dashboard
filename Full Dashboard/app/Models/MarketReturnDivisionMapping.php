<?php
namespace App\Models;

use App\Core\Database;

class MarketReturnDivisionMapping
{
    public function all(array $filters = []): array
    {
        $sql = 'SELECT m.*, d.code division_code, d.name division_name, u.name created_by_name
                FROM market_return_division_mappings m
                JOIN divisions d ON d.id=m.division_id
                LEFT JOIN users u ON u.id=m.created_by
                WHERE 1=1';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= ' AND (m.match_text LIKE ? OR d.code LIKE ? OR d.name LIKE ?)';
            $q = '%' . trim((string)$filters['q']) . '%';
            array_push($params, $q, $q, $q);
        }
        if (!empty($filters['division_id'])) {
            $sql .= ' AND m.division_id=?';
            $params[] = (int)$filters['division_id'];
        }
        if (!empty($filters['match_type']) && in_array($filters['match_type'], ['exact','contains'], true)) {
            $sql .= ' AND m.match_type=?';
            $params[] = $filters['match_type'];
        }

        $sql .= ' ORDER BY d.code, FIELD(m.match_type,\'contains\',\'exact\'), CHAR_LENGTH(m.match_key) DESC, m.match_text';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function add(string $matchText, string $matchType, int $divisionId, ?int $createdBy = null, string $sourceType = 'manual'): void
    {
        $matchText = trim($matchText);
        $matchType = in_array($matchType, ['exact','contains'], true) ? $matchType : 'exact';
        $sourceType = in_array($sourceType, ['manual','file','login'], true) ? $sourceType : 'manual';
        $key = self::normalize($matchText);
        if ($key === '') throw new \RuntimeException('Enter a product name or keyword.');

        $stmt = Database::connection()->prepare(
            'INSERT INTO market_return_division_mappings (match_type,match_text,match_key,division_id,source_type,created_by,is_active)
             VALUES (?,?,?,?,?,?,1)
             ON DUPLICATE KEY UPDATE match_text=VALUES(match_text),source_type=VALUES(source_type),created_by=COALESCE(VALUES(created_by),created_by),is_active=1,updated_at=NOW()'
        );
        $stmt->execute([$matchType, $matchText, $key, $divisionId, $sourceType, $createdBy]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM market_return_division_mappings WHERE id=?');
        $stmt->execute([$id]);
    }

    public function learnFromResolvedItems(array $items, array $user): int
    {
        $learned = 0;
        foreach ($items as $item) {
            $method = (string)($item['division_resolution'] ?? '');
            if (!in_array($method, ['file_division','login_division'], true)) continue;
            $name = trim((string)($item['product_name'] ?? ''));
            $divisionId = (int)($item['division_id'] ?? 0);
            if ($name === '' || $divisionId <= 0) continue;
            $source = $method === 'file_division' ? 'file' : 'login';
            $this->add($name, 'exact', $divisionId, isset($user['id']) ? (int)$user['id'] : null, $source);
            $learned++;
        }
        return $learned;
    }

    public static function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        $text = str_replace(['&', '+'], [' and ', ' '], $text);
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\bqarshi\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/\bexport\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/\bsugar\s*free\b/u', ' sugarfree ', $text) ?? $text;
        $text = preg_replace('/\bchoc(?:olate)?\b/u', ' chocolate ', $text) ?? $text;
        $text = preg_replace('/\bzink\b/u', ' zinc ', $text) ?? $text;
        $tokens = array_values(array_filter(preg_split('/\s+/', trim($text)) ?: [], static fn(string $v): bool => $v !== ''));
        return implode(' ', $tokens);
    }
}
