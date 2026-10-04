<?php
namespace App\Services;

/**
 * Small dependency-free XLSX reader for this import workflow.
 * It reads workbook/sheet XML directly and supports shared strings,
 * inline strings, booleans and numeric/formula cached values.
 */
class SimpleXlsxReader
{
    public function read(string $filePath): array
    {
        $zip = null;
        $phar = null;

        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                throw new \RuntimeException('Unable to open the XLSX workbook.');
            }
            $readEntry = static fn(string $name) => $zip->getFromName($name);
        } elseif (class_exists('PharData')) {
            try {
                $phar = new \PharData($filePath);
            } catch (\Throwable $e) {
                throw new \RuntimeException('Unable to open the XLSX workbook. Enable php_zip or Phar support in PHP.');
            }
            $readEntry = static function (string $name) use ($phar) {
                try {
                    return isset($phar[$name]) ? $phar[$name]->getContent() : false;
                } catch (\Throwable $e) {
                    return false;
                }
            };
        } else {
            throw new \RuntimeException('XLSX import requires either PHP ZipArchive or PharData support.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($readEntry);
            $workbookXml = $readEntry('xl/workbook.xml');
            $relsXml = $readEntry('xl/_rels/workbook.xml.rels');
            if ($workbookXml === false || $relsXml === false) {
                throw new \RuntimeException('The XLSX workbook structure is incomplete.');
            }

            $relationships = $this->readRelationships((string)$relsXml);
            $sheets = $this->readWorkbookSheets((string)$workbookXml);
            $result = [];

            foreach ($sheets as $sheet) {
                $target = $relationships[$sheet['rel_id']] ?? null;
                if (!$target) continue;
                $xml = $readEntry($this->normalizeWorksheetPath($target));
                if ($xml === false) continue;
                $result[$sheet['name']] = $this->readWorksheetRows((string)$xml, $sharedStrings);
            }
            return $result;
        } finally {
            if ($zip instanceof \ZipArchive) $zip->close();
        }
    }

    private function readSharedStrings(callable $readEntry): array
    {
        $xml = $readEntry('xl/sharedStrings.xml');
        if ($xml === false) return [];
        $strings = [];
        if (!preg_match_all('/<(?:[A-Za-z0-9_]+:)?si\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?si>/si', (string)$xml, $matches)) return [];
        foreach ($matches[1] as $si) {
            $parts = [];
            if (preg_match_all('/<(?:[A-Za-z0-9_]+:)?t\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?t>/si', $si, $texts)) {
                foreach ($texts[1] as $text) $parts[] = $this->xmlText($text);
            }
            $strings[] = implode('', $parts);
        }
        return $strings;
    }

    private function readRelationships(string $xml): array
    {
        $rels = [];
        if (!preg_match_all('/<Relationship\b([^>]*)\/?\s*>/si', $xml, $matches)) return $rels;
        foreach ($matches[1] as $attrsText) {
            $attrs = $this->attributes($attrsText);
            if (!empty($attrs['Id']) && !empty($attrs['Target'])) $rels[$attrs['Id']] = $attrs['Target'];
        }
        return $rels;
    }

    private function readWorkbookSheets(string $xml): array
    {
        $sheets = [];
        if (!preg_match_all('/<(?:[A-Za-z0-9_]+:)?sheet\b([^>]*)\/?\s*>/si', $xml, $matches)) return $sheets;
        foreach ($matches[1] as $attrsText) {
            $attrs = $this->attributes($attrsText);
            $name = $attrs['name'] ?? null;
            $relId = $attrs['r:id'] ?? ($attrs['id'] ?? null);
            if ($name && $relId) $sheets[] = ['name' => $name, 'rel_id' => $relId];
        }
        return $sheets;
    }

    private function normalizeWorksheetPath(string $target): string
    {
        $target = str_replace('\\', '/', $target);
        if (str_starts_with($target, '/')) return ltrim($target, '/');
        if (str_starts_with($target, 'xl/')) return $target;
        while (str_starts_with($target, '../')) $target = substr($target, 3);
        return 'xl/' . ltrim($target, '/');
    }

    private function readWorksheetRows(string $xml, array $sharedStrings): array
    {
        $rows = [];
        if (!preg_match_all('/<(?:[A-Za-z0-9_]+:)?row\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?row>/si', $xml, $rowMatches)) return $rows;
        foreach ($rowMatches[1] as $rowXml) {
            $sparse = [];
            if (preg_match_all('/<(?:[A-Za-z0-9_]+:)?c\b([^>]*?)(?:\/>|>(.*?)<\/(?:[A-Za-z0-9_]+:)?c>)/si', $rowXml, $cellMatches, PREG_SET_ORDER)) {
                foreach ($cellMatches as $cellMatch) {
                    $attrs = $this->attributes($cellMatch[1]);
                    $cellXml = $cellMatch[2] ?? '';
                    $ref = $attrs['r'] ?? '';
                    $col = $this->columnIndexFromReference($ref);
                    $type = $attrs['t'] ?? '';
                    $value = null;

                    if ($type === 'inlineStr') {
                        $parts = [];
                        if (preg_match_all('/<(?:[A-Za-z0-9_]+:)?t\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?t>/si', $cellXml, $texts)) {
                            foreach ($texts[1] as $text) $parts[] = $this->xmlText($text);
                        }
                        $value = implode('', $parts);
                    } else {
                        $raw = '';
                        if (preg_match('/<(?:[A-Za-z0-9_]+:)?v\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?v>/si', $cellXml, $v)) $raw = $this->xmlText($v[1]);
                        if ($type === 's') {
                            $value = $sharedStrings[(int)$raw] ?? '';
                        } elseif ($type === 'b') {
                            $value = $raw === '1' ? 1 : 0;
                        } elseif ($type === 'str') {
                            $value = $raw;
                        } elseif ($raw !== '' && is_numeric($raw)) {
                            $number = (float)$raw;
                            $value = floor($number) == $number ? (int)$number : $number;
                        } else {
                            $value = $raw;
                        }
                    }
                    $sparse[$col] = $value;
                }
            }
            if (!$sparse) {
                $rows[] = [];
                continue;
            }
            ksort($sparse);
            $max = max(array_keys($sparse));
            $dense = [];
            for ($i = 0; $i <= $max; $i++) $dense[$i] = $sparse[$i] ?? null;
            $rows[] = $dense;
        }
        return $rows;
    }

    private function attributes(string $text): array
    {
        $attrs = [];
        if (preg_match_all('/([A-Za-z_:][A-Za-z0-9_.:-]*)\s*=\s*(["\'])(.*?)\2/s', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) $attrs[$m[1]] = $this->xmlText($m[3]);
        }
        return $attrs;
    }

    private function xmlText(string $text): string
    {
        $text = preg_replace('/<!\[CDATA\[(.*?)\]\]>/s', '$1', $text) ?? $text;
        return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function columnIndexFromReference(string $reference): int
    {
        if (!preg_match('/^([A-Z]+)/i', $reference, $m)) return 0;
        $letters = strtoupper($m[1]);
        $index = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) $index = $index * 26 + (ord($letters[$i]) - 64);
        return max(0, $index - 1);
    }
}
