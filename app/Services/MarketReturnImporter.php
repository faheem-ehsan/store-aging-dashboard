<?php
namespace App\Services;

class MarketReturnImporter
{
    public const CATEGORIES = [
        'fresh_saleable' => ['label' => 'Fresh Saleable Stock', 'sheet' => 'Fresh Saleable Stock final', 'class' => 'fresh'],
        'utilizable' => ['label' => 'Utilizable Stock', 'sheet' => 'Utilizable Stock', 'class' => 'utilizable'],
        'cabinets' => ['label' => 'Cabinets', 'sheet' => 'Cabinets', 'class' => 'cabinets'],
        'rawalkot' => ['label' => 'RawalKot Stock', 'sheet' => 'RawalKot stock', 'class' => 'rawalkot'],
        'export' => ['label' => 'Export Stock', 'sheet' => 'Export Stock', 'class' => 'export'],
        'promotion' => ['label' => 'Promotion Items', 'sheet' => 'Promotion Items', 'class' => 'promotion'],
        'tablet_strips' => ['label' => 'Tablet / Loose Items', 'sheet' => 'Tablet  Strips', 'class' => 'tablet'],
        'expired' => ['label' => 'Expired Stock', 'sheet' => 'Expired', 'class' => 'expired'],
    ];

    public function parseXlsx(string $filePath): array
    {
        $workbook = (new SimpleXlsxReader())->read($filePath);
        $items = [];
        $warnings = [];
        $foundCategories = [];

        foreach ($workbook as $sheetName => $rows) {
            $categoryCode = $this->categoryForSheet($sheetName);
            if (!$categoryCode) {
                $warnings[] = 'Skipped unrecognized sheet: ' . $sheetName;
                continue;
            }
            $parsed = $this->parseSheet($sheetName, $rows, $categoryCode);
            if (!$parsed) {
                $warnings[] = 'No item rows were found in sheet: ' . $sheetName;
                continue;
            }
            $items = array_merge($items, $parsed);
            $foundCategories[$categoryCode] = true;
        }

        if (!$items) {
            throw new \RuntimeException('No importable market-return item rows were found in the workbook. Use the supplied workbook template and keep the sheet headings intact.');
        }

        return [
            'items' => $items,
            'categories' => array_keys($foundCategories),
            'warnings' => $warnings,
        ];
    }

    public function parseCsv(string $filePath, string $originalFileName = ''): array
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) throw new \RuntimeException('Unable to read the CSV file.');

        $fileCategory = $this->categoryFromFileName($originalFileName);
        $warnings = [];

        try {
            $first = fgets($handle);
            if ($first === false) throw new \RuntimeException('CSV file is empty.');
            $delimiter = $this->detectDelimiter($first);
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!$headers) throw new \RuntimeException('CSV headings could not be read.');
            $canonical = array_map(fn($h) => $this->canonicalHeader((string)$h), $headers);
            if (!in_array('product_name', $canonical, true) || !in_array('quantity', $canonical, true)) {
                throw new \RuntimeException('CSV requires Product Name/Description and Quantity columns.');
            }

            $hasCategoryColumn = in_array('category_code', $canonical, true);
            $hasSourceSheetColumn = in_array('source_sheet', $canonical, true);
            if (!$hasCategoryColumn && !$hasSourceSheetColumn && !$fileCategory) {
                throw new \RuntimeException(
                    'CSV category could not be detected automatically. Include a Category column (recommended), a Source Sheet column, or use a recognized category name in the CSV filename such as "Utilizable Stock.csv".'
                );
            }

            $items = [];
            $categories = [];
            $rowNo = 1;
            while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNo++;
                if ($this->rowIsEmpty($values)) continue;
                $row = [];
                foreach ($canonical as $i => $key) {
                    if ($key !== '') $row[$key] = trim((string)($values[$i] ?? ''));
                }

                $category = null;
                $categoryRaw = trim((string)($row['category_code'] ?? ''));
                if ($categoryRaw !== '') $category = $this->normalizeCategoryCode($categoryRaw);

                if (!$category) {
                    $sourceRaw = trim((string)($row['source_sheet'] ?? ''));
                    if ($sourceRaw !== '') $category = $this->normalizeCategoryCode($sourceRaw) ?: $this->categoryForSheet($sourceRaw);
                }

                if (!$category) $category = $fileCategory;
                if (!$category || !isset(self::CATEGORIES[$category])) {
                    throw new \RuntimeException(
                        'Row ' . $rowNo . ': category could not be detected automatically. Put a recognized value in the Category column, for example Fresh Saleable Stock, Utilizable Stock, Cabinets, RawalKot Stock, Export Stock, Promotion Items, Tablet / Loose Items, or Expired Stock.'
                    );
                }

                $product = trim((string)($row['product_name'] ?? ''));
                if ($product === '') throw new \RuntimeException('Row ' . $rowNo . ': product name is missing.');
                $qty = $this->numberOrNull($row['quantity'] ?? '');
                if ($qty === null) throw new \RuntimeException('Row ' . $rowNo . ': quantity is invalid.');
                [$expiryRaw, $expiryDate] = $this->normalizeExpiry($row['expiry_raw'] ?? '');
                $retail = $this->numberOrNull($row['retail_price'] ?? '');
                $amount = $this->numberOrNull($row['total_amount'] ?? '');
                $amountText = $amount === null ? trim((string)($row['total_amount'] ?? '')) : '';
                $reportDate = $this->normalizeDate($row['report_date'] ?? '');
                $sourceSheet = trim((string)($row['source_sheet'] ?? ''));
                if ($sourceSheet === '') $sourceSheet = self::CATEGORIES[$category]['sheet'];

                $items[] = [
                    'division_hint' => trim((string)($row['division_hint'] ?? '')),
                    'category_code' => $category,
                    'source_sheet' => $sourceSheet,
                    'section_name' => trim((string)($row['section_name'] ?? '')),
                    'serial_no' => trim((string)($row['serial_no'] ?? '')),
                    'product_name' => $product,
                    'quantity' => $qty,
                    'unit' => trim((string)($row['unit'] ?? 'Nos')) ?: 'Nos',
                    'expiry_raw' => $expiryRaw,
                    'expiry_date' => $expiryDate,
                    'retail_price' => $retail,
                    'total_amount' => $amount,
                    'amount_text' => $amountText,
                    'remarks' => trim((string)($row['remarks'] ?? '')),
                    'aux_label' => trim((string)($row['aux_label'] ?? '')),
                    'aux_value' => trim((string)($row['aux_value'] ?? '')),
                    'report_date' => $reportDate,
                ];
                $categories[$category] = true;
            }
        } finally {
            fclose($handle);
        }

        if (!$items) throw new \RuntimeException('No item rows were found in the CSV file.');
        if ($fileCategory && !$hasCategoryColumn) {
            $warnings[] = 'CSV category was detected automatically from the filename: ' . self::CATEGORIES[$fileCategory]['label'] . '.';
        }
        return ['items' => $items, 'categories' => array_keys($categories), 'warnings' => $warnings];
    }

    private function parseSheet(string $sheetName, array $rows, string $categoryCode): array
    {
        $reportDate = $this->extractReportDate($rows);
        $items = [];
        $rowCount = count($rows);

        for ($i = 0; $i < $rowCount; $i++) {
            $row = $rows[$i];
            if (!$this->isHeaderRow($row)) continue;
            $headers = [];
            $auxIndex = null;
            foreach ($row as $col => $value) {
                $key = $this->canonicalHeader((string)$value);
                if ($key !== '') {
                    $headers[$col] = $key;
                    if ($key === 'aux_value') $auxIndex = $col;
                }
            }
            // Some source sections (for example the rejected Khatooni batch)
            // leave the Quantity heading blank even though column C contains quantity.
            if (!in_array('quantity', $headers, true)) {
                $productIndex = array_search('product_name', $headers, true);
                $unitIndex = array_search('unit', $headers, true);
                if ($productIndex === 1 && $unitIndex === 3) $headers[2] = 'quantity';
            }
            $sectionName = $this->findSectionName($rows, $i, $sheetName);

            for ($r = $i + 1; $r < $rowCount; $r++) {
                $dataRow = $rows[$r] ?? [];
                if ($this->isHeaderRow($dataRow)) break;
                $serial = trim((string)($dataRow[0] ?? ''));
                $productIndex = array_search('product_name', $headers, true);
                $product = $productIndex === false ? '' : trim((string)($dataRow[$productIndex] ?? ''));
                if ($serial === '' && $product === '') continue;
                if ($product === '' || !preg_match('/^\d+(?:\.\d+)?$/', str_replace(',', '', $serial))) continue;

                $mapped = [];
                foreach ($headers as $col => $key) $mapped[$key] = $dataRow[$col] ?? null;
                $qty = $this->numberOrNull($mapped['quantity'] ?? null);
                if ($qty === null) continue;
                [$expiryRaw, $expiryDate] = $this->normalizeExpiry($mapped['expiry_raw'] ?? null);
                $retail = $this->numberOrNull($mapped['retail_price'] ?? null);
                $amountRaw = $mapped['total_amount'] ?? null;
                $amount = $this->numberOrNull($amountRaw);
                $amountText = $amount === null ? trim((string)$amountRaw) : '';

                $items[] = [
                    'division_hint' => trim((string)($mapped['division_hint'] ?? '')),
                    'category_code' => $categoryCode,
                    'source_sheet' => $sheetName,
                    'section_name' => $sectionName,
                    'serial_no' => $serial,
                    'product_name' => preg_replace('/\s+/u', ' ', trim($product)),
                    'quantity' => $qty,
                    'unit' => trim((string)($mapped['unit'] ?? 'Nos')) ?: 'Nos',
                    'expiry_raw' => $expiryRaw,
                    'expiry_date' => $expiryDate,
                    'retail_price' => $retail,
                    'total_amount' => $amount,
                    'amount_text' => $amountText,
                    'remarks' => trim((string)($mapped['remarks'] ?? '')),
                    'aux_label' => $auxIndex !== null ? trim((string)($row[$auxIndex] ?? '')) : '',
                    'aux_value' => trim((string)($mapped['aux_value'] ?? '')),
                    'report_date' => $reportDate,
                ];
            }
        }
        return $items;
    }

    private function categoryForSheet(string $sheetName): ?string
    {
        $normalized = $this->normalizeText($sheetName);
        foreach (self::CATEGORIES as $code => $meta) {
            if ($this->normalizeText($meta['sheet']) === $normalized) return $code;
        }
        return null;
    }

    private function categoryFromFileName(string $fileName): ?string
    {
        $base = pathinfo(trim($fileName), PATHINFO_FILENAME);
        if ($base === '') return null;
        $normalized = $this->normalizeText($base);
        foreach (self::CATEGORIES as $code => $meta) {
            $candidates = [
                $this->normalizeText($code),
                $this->normalizeText((string)$meta['label']),
                $this->normalizeText((string)$meta['sheet']),
            ];
            foreach ($candidates as $candidate) {
                if ($candidate !== '' && (str_contains($normalized, $candidate) || str_contains($candidate, $normalized))) {
                    return $code;
                }
            }
        }
        return null;
    }

    private function normalizeCategoryCode(string $value): ?string
    {
        $normalized = $this->normalizeText($value);
        foreach (self::CATEGORIES as $code => $meta) {
            if ($this->normalizeText($code) === $normalized || $this->normalizeText($meta['label']) === $normalized || $this->normalizeText($meta['sheet']) === $normalized) {
                return $code;
            }
        }
        return null;
    }

    private function isHeaderRow(array $row): bool
    {
        $first = $this->normalizeText((string)($row[0] ?? ''));
        if (!in_array($first, ['sno','srno','serialno'], true)) return false;
        foreach ($row as $value) {
            if ($this->canonicalHeader((string)$value) === 'product_name') return true;
        }
        return false;
    }

    private function canonicalHeader(string $header): string
    {
        $h = $this->normalizeText($header);
        $map = [
            'sno' => 'serial_no', 'srno' => 'serial_no', 'serialno' => 'serial_no',
            'productname' => 'product_name', 'description' => 'product_name', 'productdescription' => 'product_name',
            'quantity' => 'quantity', 'qty' => 'quantity',
            'unit' => 'unit', 'uom' => 'unit',
            'expiry' => 'expiry_raw', 'expirydate' => 'expiry_raw',
            'retailprice' => 'retail_price', 'retailpricers' => 'retail_price', 'retailprice$' => 'retail_price', 'perpiecerate' => 'retail_price',
            'totalamount' => 'total_amount', 'totalamountrs' => 'total_amount',
            'remarks' => 'remarks', 'remark' => 'remarks',
            'cartons' => 'aux_value', 'carton' => 'aux_value', 'bags' => 'aux_value', 'bag' => 'aux_value',
            'section' => 'section_name', 'sectionname' => 'section_name',
            'division' => 'division_hint', 'divisioncode' => 'division_hint', 'divisionname' => 'division_hint', 'businessdivision' => 'division_hint',
            'category' => 'category_code', 'stockcategory' => 'category_code', 'categorycode' => 'category_code',
            'sourcesheet' => 'source_sheet', 'sheet' => 'source_sheet', 'sheetname' => 'source_sheet', 'sourcecategory' => 'source_sheet',
            'reportdate' => 'report_date', 'date' => 'report_date',
            'auxlabel' => 'aux_label', 'auxvalue' => 'aux_value',
        ];
        return $map[$h] ?? '';
    }

    private function findSectionName(array $rows, int $headerIndex, string $sheetName): string
    {
        for ($i = $headerIndex - 1, $min = max(0, $headerIndex - 8); $i >= $min; $i--) {
            $candidateRow = $rows[$i] ?? [];
            $firstCell = trim((string)($candidateRow[0] ?? ''));
            if ($firstCell !== '' && is_numeric(str_replace(',', '', $firstCell))) continue;
            foreach ($candidateRow as $value) {
                $text = trim((string)$value);
                if ($text === '' || is_numeric(str_replace(',', '', $text))) continue;
                $norm = $this->normalizeText($text);
                if (str_contains($norm, 'qarshiindustries')) continue;
                if (str_starts_with($norm, 'date')) continue;
                if (str_starts_with($norm, 'totalcost')) continue;
                if (str_starts_with($norm, 'preparedby')) continue;
                if ($norm === 'executiveadministration') continue;
                if (in_array($norm, ['sno','srno','serialno','productname','quantity','qty','unit','expiry','expirydate','remarks'], true)) continue;
                if ($this->canonicalHeader($text) !== '') continue;
                if (preg_match('/^\(?\d+\s*year/i', $text)) continue;
                if (str_contains($norm, 'lessthanoneyear')) continue;
                return preg_replace('/\s+/u', ' ', $text);
            }
        }
        return self::CATEGORIES[$this->categoryForSheet($sheetName)]['label'] ?? $sheetName;
    }

    private function extractReportDate(array $rows): ?string
    {
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $text = trim((string)$value);
                if (preg_match('/Date\s*:\s*(.+)$/i', $text, $m)) {
                    $date = $this->normalizeDate(trim($m[1]));
                    if ($date) return $date;
                }
            }
        }
        return null;
    }

    private function normalizeExpiry(mixed $value): array
    {
        if ($value === null || trim((string)$value) === '') return ['', null];
        if (is_numeric($value) && (float)$value > 20000 && (float)$value < 100000) {
            $date = $this->excelSerialToDate((float)$value);
            return [$date ? (new \DateTimeImmutable($date))->format('d.m.Y') : (string)$value, $date];
        }
        $raw = preg_replace('/\s+/u', ' ', trim((string)$value));
        if (preg_match('/^(expired|n\.?a\.?|not found)$/i', $raw)) return [$raw, null];
        $date = $this->normalizeDate($raw, true);
        return [$raw, $date];
    }

    private function normalizeDate(mixed $value, bool $monthYearAllowed = false): ?string
    {
        $value = trim((string)$value);
        if ($value === '') return null;
        if (is_numeric($value) && (float)$value > 20000 && (float)$value < 100000) return $this->excelSerialToDate((float)$value);

        // IMPORTANT: Market Return files frequently use month/year only (for example
        // "Apr 2029" or "Sept.26"). Parse those BEFORE the permissive full-date
        // formats below. PHP can otherwise interpret "Apr 2029" as 20-Apr-0029.
        if ($monthYearAllowed) {
            $clean = preg_replace('/[.,\-\/]+/', ' ', $value);
            $clean = preg_replace('/\s+/', ' ', trim((string)$clean));
            if (preg_match('/^([A-Za-z]+)\s+(\d{2,4})$/', $clean, $m)) {
                $monthNames = [
                    'jan'=>1,'january'=>1,'feb'=>2,'february'=>2,'mar'=>3,'march'=>3,'apr'=>4,'april'=>4,'may'=>5,'jun'=>6,'june'=>6,
                    'jul'=>7,'july'=>7,'aug'=>8,'august'=>8,'sep'=>9,'sept'=>9,'september'=>9,'oct'=>10,'october'=>10,'nov'=>11,'november'=>11,'dec'=>12,'december'=>12,
                ];
                $month = $monthNames[strtolower($m[1])] ?? null;
                if ($month) {
                    $year = (int)$m[2];
                    if ($year < 100) $year += 2000;
                    $first = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
                    return $first->modify('last day of this month')->format('Y-m-d');
                }
            }
        }

        $formats = ['Y-m-d','d-m-Y','d.m.Y','d/m/Y','m/d/Y','d-M-Y','d M Y','M d Y'];
        foreach ($formats as $format) {
            $dt = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($dt && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                // Guard against impossible ancient years caused by ambiguous parsing.
                if ((int)$dt->format('Y') < 2000 && preg_match('/\b20\d{2}\b/', $value)) continue;
                return $dt->format('Y-m-d');
            }
        }

        if ($monthYearAllowed) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                $parsed = new \DateTimeImmutable('@' . $timestamp);
                $parsed = $parsed->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                if ((int)$parsed->format('Y') >= 2000) return $parsed->format('Y-m-d');
            }
        }
        return null;
    }

    private function excelSerialToDate(float $serial): ?string
    {
        $days = (int)floor($serial);
        if ($days < 1) return null;
        $unix = ($days - 25569) * 86400;
        return gmdate('Y-m-d', $unix);
    }

    private function numberOrNull(mixed $value): ?float
    {
        if ($value === null) return null;
        $text = trim((string)$value);
        if ($text === '') return null;
        $text = str_replace([',', 'Rs.', 'Rs', '$', ' '], '', $text);
        return is_numeric($text) ? (float)$text : null;
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower(trim($text));
        return preg_replace('/[^a-z0-9$]+/', '', $text) ?? '';
    }

    private function detectDelimiter(string $line): string
    {
        $counts = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($counts);
        $delimiter = array_key_first($counts);
        return ($counts[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) if (trim((string)$value) !== '') return false;
        return true;
    }
}
