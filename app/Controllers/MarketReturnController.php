<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\MarketReturnItem;
use App\Models\MarketReturnDivisionMapping;
use App\Services\AuditLogger;
use App\Services\MarketReturnImporter;
use App\Services\MarketReturnDivisionResolver;

class MarketReturnController
{
    private function canManage(array $user): bool
    {
        return in_array($user['role'] ?? '', ['super_admin', 'division_admin'], true);
    }

    private function expiryBuckets(): array
    {
        return [
            'a' => [
                'label' => 'A · 2+ Years',
                'short' => 'A',
                'class' => 'grade-a',
                'range' => '730+ days remaining to Expiry Date',
            ],
            'b' => [
                'label' => 'B · 1–2 Years',
                'short' => 'B',
                'class' => 'grade-b',
                'range' => '365–729 days remaining to Expiry Date',
            ],
            'c' => [
                'label' => 'C · Less Than 1 Year',
                'short' => 'C',
                'class' => 'grade-c',
                'range' => '1–364 days remaining to Expiry Date',
            ],
            'expired' => [
                'label' => 'Expired',
                'short' => 'Expired',
                'class' => 'grade-ex',
                'range' => 'Expiry Date and after',
            ],
            'no_expiry' => [
                'label' => 'Other / No Expiry',
                'short' => 'Other',
                'class' => 'grade-no-expiry',
                'range' => 'Blank, N.A, Not Found or no valid Expiry Date',
            ],
        ];
    }

    private function expiryBucket(array $row): string
    {
        $raw = strtolower(trim((string)($row['expiry_raw'] ?? '')));
        if ($raw !== '' && preg_match('/^expired$/i', $raw)) return 'expired';

        $expiry = $this->effectiveExpiryDate($row);
        if (!$expiry) return 'no_expiry';

        $today = new \DateTimeImmutable('today');
        $days = (int)$today->diff($expiry)->format('%r%a');
        if ($days <= 0) return 'expired';
        if ($days >= 730) return 'a';
        if ($days >= 365) return 'b';
        return 'c';
    }

    /**
     * Resolve the real expiry date safely. Older imports could contain a PHP
     * month/year parsing error where "Apr 2029" was stored as 0029-04-20.
     * Prefer a correctly parsed month/year from expiry_raw when available.
     */
    private function effectiveExpiryDate(array $row): ?\DateTimeImmutable
    {
        $raw = trim((string)($row['expiry_raw'] ?? ''));
        if ($raw !== '' && !preg_match('/^(expired|n\.?a\.?|not found)$/i', $raw)) {
            $clean = preg_replace('/[.,\-\/]+/', ' ', $raw);
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
                    return (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('last day of this month');
                }
            }
        }

        $expiryDate = trim((string)($row['expiry_date'] ?? ''));
        if ($expiryDate === '') return null;
        try {
            $expiry = new \DateTimeImmutable($expiryDate);
            // Do not trust corrupted ancient dates from earlier month/year imports.
            if ((int)$expiry->format('Y') < 2000) return null;
            return $expiry;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function index(): void
    {
        $user = Auth::requireLogin();
        $filters = [
            'q' => trim((string)($_GET['q'] ?? '')),
            'division_id' => isset($_GET['division_id']) && $_GET['division_id'] !== '' ? (int)$_GET['division_id'] : null,
            'category' => trim((string)($_GET['category'] ?? '')),
            'value_status' => trim((string)($_GET['value_status'] ?? '')),
            'expiry_bucket' => trim((string)($_GET['expiry_bucket'] ?? '')),
        ];
        if ($user['role'] !== 'super_admin') $filters['division_id'] = (int)$user['division_id'];

        $divisions = (new Division())->all(true);
        $categories = MarketReturnImporter::CATEGORIES;
        $expiryBuckets = $this->expiryBuckets();
        if ($filters['expiry_bucket'] !== '' && !isset($expiryBuckets[$filters['expiry_bucket']])) {
            $filters['expiry_bucket'] = '';
        }

        $baseRows = [];
        $rows = [];
        $databaseReady = true;
        $databaseError = null;
        try {
            // Expiry grouping is calculated at runtime so existing Market Return data needs no migration.
            $dbFilters = $filters;
            unset($dbFilters['expiry_bucket']);
            $baseRows = (new MarketReturnItem())->allAccessible($user, $dbFilters);

            foreach ($baseRows as &$row) {
                $row['expiry_bucket'] = $this->expiryBucket($row);
            }
            unset($row);

            $rows = $filters['expiry_bucket'] === ''
                ? $baseRows
                : array_values(array_filter($baseRows, fn(array $row): bool => ($row['expiry_bucket'] ?? '') === $filters['expiry_bucket']));

            usort($rows, function (array $a, array $b): int {
                $aExpiry = $this->effectiveExpiryDate($a);
                $bExpiry = $this->effectiveExpiryDate($b);
                $aDate = $aExpiry?->format('Y-m-d');
                $bDate = $bExpiry?->format('Y-m-d');
                if ($aDate && $bDate && $aDate !== $bDate) return strcmp($aDate, $bDate);
                if ($aDate && !$bDate) return -1;
                if (!$aDate && $bDate) return 1;
                return strcasecmp((string)($a['product_name'] ?? ''), (string)($b['product_name'] ?? ''));
            });
        } catch (\Throwable $e) {
            $databaseReady = false;
            $databaseError = 'Market Return Store tables are not installed. Import database/migrations/007_add_market_return_store_module.sql once in phpMyAdmin.';
        }

        $expiryMetrics = [];
        foreach ($expiryBuckets as $code => $meta) {
            $expiryMetrics[$code] = ['lines' => 0, 'qty' => 0.0, 'value' => 0.0, 'unvalued' => 0];
        }
        foreach ($baseRows as $row) {
            $code = $row['expiry_bucket'] ?? 'no_expiry';
            if (!isset($expiryMetrics[$code])) $code = 'no_expiry';
            $qty = (float)$row['quantity'];
            $expiryMetrics[$code]['lines']++;
            $expiryMetrics[$code]['qty'] += $qty;
            if ($row['total_amount'] !== null) $expiryMetrics[$code]['value'] += (float)$row['total_amount'];
            else $expiryMetrics[$code]['unvalued']++;
        }

        // Overall summary follows the visible table, while expiry cards remain navigation totals.
        $metrics = [
            'lines' => count($rows),
            'qty' => 0.0,
            'value' => 0.0,
            'unvalued' => 0,
        ];
        foreach ($rows as $row) {
            $metrics['qty'] += (float)$row['quantity'];
            if ($row['total_amount'] !== null) $metrics['value'] += (float)$row['total_amount'];
            else $metrics['unvalued']++;
        }

        $selectedDivisionLabel = $user['role'] === 'super_admin' ? 'All Divisions' : (($user['division_code'] ?? '') . ' — ' . ($user['division_name'] ?? ''));
        if ($user['role'] === 'super_admin' && !empty($filters['division_id'])) {
            foreach ($divisions as $division) {
                if ((int)$division['id'] === (int)$filters['division_id']) {
                    $selectedDivisionLabel = $division['code'] . ' — ' . $division['name'];
                    break;
                }
            }
        }

        View::render('market_return/index', [
            'title' => 'Market Return Store',
            'user' => $user,
            'rows' => $rows,
            'baseRows' => $baseRows,
            'filters' => $filters,
            'divisions' => $divisions,
            'categories' => $categories,
            'metrics' => $metrics,
            'expiryBuckets' => $expiryBuckets,
            'expiryMetrics' => $expiryMetrics,
            'canManage' => $this->canManage($user),
            'selectedDivisionLabel' => trim($selectedDivisionLabel, ' —'),
            'databaseReady' => $databaseReady,
            'databaseError' => $databaseError,
        ]);
    }

    public function create(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        View::render('market_return/form', [
            'title' => 'Add Market Return Item',
            'user' => $user,
            'divisions' => (new Division())->all(true),
            'categories' => MarketReturnImporter::CATEGORIES,
            'item' => null,
            'selectedDivisionId' => $user['role'] === 'super_admin' ? (int)($_GET['division_id'] ?? 0) : (int)$user['division_id'],
            'selectedCategory' => trim((string)($_GET['category'] ?? '')),
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $data = $this->payload($user);
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            redirect('/market-return/create');
        }
        try {
            $id = (new MarketReturnItem())->save($data, $user);
            AuditLogger::log('MARKET_RETURN_CREATE', 'Created market-return item ' . $data['product_name'] . '.', 'market_return_item', $id, $data, $user, (int)$data['division_id']);
            flash('success', 'Market Return item added.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to save the item. Run database/migrations/007_add_market_return_store_module.sql if this module was just installed.');
        }
        redirect('/market-return');
    }

    public function edit(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $id = (int)($_GET['id'] ?? 0);
        $item = (new MarketReturnItem())->findAccessible($id, $user);
        if (!$item) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not Found']);
            return;
        }
        View::render('market_return/form', [
            'title' => 'Edit Market Return Item',
            'user' => $user,
            'divisions' => (new Division())->all(true),
            'categories' => MarketReturnImporter::CATEGORIES,
            'item' => $item,
            'selectedDivisionId' => (int)$item['division_id'],
            'selectedCategory' => $item['category_code'],
        ]);
    }

    public function update(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $id = (int)($_POST['id'] ?? 0);
        $model = new MarketReturnItem();
        $before = $model->findAccessible($id, $user);
        if (!$before) $this->forbidden();
        $data = $this->payload($user);
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            redirect('/market-return/edit?id=' . $id);
        }
        try {
            $model->save($data, $user, $id);
            AuditLogger::log('MARKET_RETURN_UPDATE', 'Updated market-return item ' . $data['product_name'] . '.', 'market_return_item', $id, ['before' => $before, 'after' => $data], $user, (int)$data['division_id']);
            flash('success', 'Market Return item updated.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to update the item.');
        }
        redirect('/market-return');
    }

    public function delete(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $id = (int)($_POST['id'] ?? 0);
        $model = new MarketReturnItem();
        $row = $model->findAccessible($id, $user);
        if ($row) {
            $model->delete($id, $user);
            AuditLogger::log('MARKET_RETURN_DELETE', 'Deleted market-return item ' . ($row['product_name'] ?? '') . '.', 'market_return_item', $id, $row, $user, (int)$row['division_id'], $row['division_code'] ?? null);
            flash('success', 'Market Return item deleted.');
        }
        redirect('/market-return');
    }

    public function importForm(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        View::render('market_return/import', [
            'title' => 'Market Return Store Import',
            'user' => $user,
            'divisions' => (new Division())->all(true),
            'categories' => MarketReturnImporter::CATEGORIES,
            'result' => null,
        ]);
    }

    public function importFile(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $divisions = (new Division())->all(true);
        $categories = MarketReturnImporter::CATEGORIES;
        $result = [
            'inserted' => 0,
            'deleted_previous' => 0,
            'warnings' => [],
            'errors' => [],
            'unmatched' => [],
            'division_stats' => [],
            'categories' => [],
        ];

        try {
            $file = $_FILES['market_return_file'] ?? null;
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new \RuntimeException('Choose an XLSX or CSV file to import.');
            if (($file['size'] ?? 0) > 15 * 1024 * 1024) throw new \RuntimeException('Import file must be 15 MB or smaller.');
            $fileName = basename((string)($file['name'] ?? 'market-return-import'));
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'csv'], true)) throw new \RuntimeException('Only .xlsx and .csv files are accepted.');

            $importer = new MarketReturnImporter();
            if ($ext === 'xlsx') {
                $parsed = $importer->parseXlsx($file['tmp_name']);
            } else {
                $parsed = $importer->parseCsv($file['tmp_name'], $fileName);
            }

            $resolver = new MarketReturnDivisionResolver();
            $resolved = $resolver->resolveItems($parsed['items'], $user);
            $result['unmatched'] = $resolved['unmatched'];
            $result['division_stats'] = $resolved['stats'];
            $result['categories'] = $parsed['categories'];
            $result['warnings'] = $parsed['warnings'] ?? [];

            // Learn exact Market Return mappings only from authoritative assignments:
            // a Division value in the file or a Division Admin's locked login division.
            // This never reads from or writes to the Stock Aging material master.
            try {
                $learnedMappings = (new MarketReturnDivisionMapping())->learnFromResolvedItems($resolved['items'], $user);
                if ($learnedMappings > 0) {
                    $result['warnings'][] = $learnedMappings . ' exact Market Return product/division mapping(s) were learned for future imports.';
                }
            } catch (\Throwable $mappingError) {
                $result['warnings'][] = 'Independent Market Return mapping table is not installed yet. Import database/migrations/008_add_market_return_division_mapping.sql to enable automatic learning.';
            }

            if (!$resolved['items']) {
                throw new \RuntimeException('No rows could be assigned to a division. Add a Division column to the file or configure the independent Market Return Division Mapping.');
            }

            $replaceRequested = isset($_POST['replace_existing']) && $_POST['replace_existing'] === '1';
            $replace = $replaceRequested;
            if ($replaceRequested && !empty($resolved['unmatched'])) {
                $replace = false;
                $result['warnings'][] = 'Replace Existing was automatically disabled because some rows could not be assigned to a division. This prevents accidental deletion of existing Market Return data.';
            }

            $grouped = [];
            foreach ($resolved['items'] as $item) {
                $divisionId = (int)$item['division_id'];
                if (!isset($grouped[$divisionId])) $grouped[$divisionId] = [];
                $grouped[$divisionId][] = $item;
            }

            $model = new MarketReturnItem();
            $importIds = [];
            $divisionResultById = [];
            foreach ($grouped as $divisionId => $items) {
                $division = (new Division())->find((int)$divisionId);
                if (!$division || !(int)$division['is_active']) continue;

                $groupCategories = [];
                foreach ($items as $item) $groupCategories[(string)$item['category_code']] = true;
                $saved = $model->importSnapshot(
                    (int)$divisionId,
                    $items,
                    array_keys($groupCategories),
                    $user,
                    $fileName,
                    $ext,
                    $replace
                );

                $result['inserted'] += (int)$saved['inserted'];
                $result['deleted_previous'] += (int)$saved['deleted_previous'];
                $importIds[] = (int)$saved['import_id'];
                $divisionResultById[(int)$divisionId] = [
                    'division_id' => (int)$divisionId,
                    'division_code' => $division['code'],
                    'division_name' => $division['name'],
                    'inserted' => (int)$saved['inserted'],
                    'deleted_previous' => (int)$saved['deleted_previous'],
                    'categories' => array_keys($groupCategories),
                ];
            }

            foreach ($result['division_stats'] as &$stat) {
                $saved = $divisionResultById[(int)$stat['division_id']] ?? null;
                $stat['inserted'] = $saved['inserted'] ?? 0;
                $stat['deleted_previous'] = $saved['deleted_previous'] ?? 0;
                $stat['categories'] = $saved['categories'] ?? [];
            }
            unset($stat);

            if ($result['inserted'] <= 0) throw new \RuntimeException('The file was read, but no rows were saved.');

            if (!empty($result['unmatched'])) {
                $result['warnings'][] = count($result['unmatched']) . ' row(s) were not imported because their division could not be identified safely.';
            }

            $singleDivision = count($divisionResultById) === 1 ? array_values($divisionResultById)[0] : null;
            $result['division'] = $singleDivision ? (new Division())->find((int)$singleDivision['division_id']) : null;

            AuditLogger::log(
                'MARKET_RETURN_IMPORT',
                sprintf('Market Return import completed: %d rows imported across %d division(s).', (int)$result['inserted'], count($divisionResultById)),
                'market_return_import',
                $importIds ? implode(',', $importIds) : null,
                [
                    'file_name' => $fileName,
                    'file_type' => $ext,
                    'automatic_division_assignment' => true,
                    'division_results' => array_values($divisionResultById),
                    'categories' => $parsed['categories'],
                    'inserted' => (int)$result['inserted'],
                    'deleted_previous' => (int)$result['deleted_previous'],
                    'replace_requested' => $replaceRequested,
                    'replace_effective' => $replace,
                    'unmatched_rows' => count($result['unmatched']),
                    'warnings' => $result['warnings'],
                ],
                $user
            );
        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
        }

        View::render('market_return/import', [
            'title' => 'Market Return Store Import',
            'user' => $user,
            'divisions' => $divisions,
            'categories' => $categories,
            'result' => $result,
        ]);
    }

    public function importTemplate(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $path = ROOT_PATH . '/database/templates/Market_Return_Store_Items_Template.xlsx';
        if (!is_file($path)) {
            http_response_code(404);
            exit('Market Return Store template file not found.');
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Market_Return_Store_Items_Template.xlsx"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function csvTemplate(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="market_return_store_auto_import_template.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['Division','Category','Section','S. No.','Product Name','Quantity','Unit','Expiry','Retail Price','Total Amount','Remarks','Aux Label','Aux Value','Report Date']);
        fputcsv($out, ['JSD','Utilizable Stock','Utilizable Stock - Market Return Store','1','Jam-e-Shirin 500ml','10','Bottles','Aug 2027','750','7500','','','','2026-09-24']);
        fputcsv($out, ['SNW','Fresh Saleable Stock','Fresh Saleable Stock','2','Springley 500ml','24','Bottles','Apr 2029','100','2400','','','','2026-09-24']);
        fclose($out);
        exit;
    }

    private function payload(array $user): array
    {
        $divisionId = $user['role'] === 'super_admin' ? (int)($_POST['division_id'] ?? 0) : (int)$user['division_id'];
        $retail = trim((string)($_POST['retail_price'] ?? ''));
        $amount = trim((string)($_POST['total_amount'] ?? ''));
        return [
            'division_id' => $divisionId,
            'category_code' => trim((string)($_POST['category_code'] ?? '')),
            'source_sheet' => trim((string)($_POST['source_sheet'] ?? 'Manual Entry')) ?: 'Manual Entry',
            'section_name' => trim((string)($_POST['section_name'] ?? '')),
            'serial_no' => trim((string)($_POST['serial_no'] ?? '')),
            'product_name' => trim((string)($_POST['product_name'] ?? '')),
            'quantity' => $this->number($_POST['quantity'] ?? '0'),
            'unit' => trim((string)($_POST['unit'] ?? 'Nos')) ?: 'Nos',
            'expiry_raw' => trim((string)($_POST['expiry_raw'] ?? '')),
            'expiry_date' => trim((string)($_POST['expiry_date'] ?? '')) ?: null,
            'retail_price' => $retail === '' ? null : $this->number($retail),
            'total_amount' => $amount === '' ? null : $this->number($amount),
            'amount_text' => trim((string)($_POST['amount_text'] ?? '')),
            'remarks' => trim((string)($_POST['remarks'] ?? '')),
            'aux_label' => trim((string)($_POST['aux_label'] ?? '')),
            'aux_value' => trim((string)($_POST['aux_value'] ?? '')),
            'report_date' => trim((string)($_POST['report_date'] ?? '')) ?: null,
        ];
    }

    private function validate(array $data): ?string
    {
        if ((int)$data['division_id'] <= 0) return 'Division is required.';
        if (!isset(MarketReturnImporter::CATEGORIES[$data['category_code']])) return 'Choose a valid Market Return category.';
        if ($data['product_name'] === '') return 'Product Name / Description is required.';
        if ((float)$data['quantity'] < 0) return 'Quantity cannot be negative.';
        if ($data['unit'] === '') return 'Unit is required.';
        return null;
    }

    private function number(mixed $value): float
    {
        $value = str_replace([',', ' '], '', trim((string)$value));
        if ($value === '') return 0.0;
        if (!is_numeric($value)) throw new \RuntimeException('Invalid numeric value: ' . $value);
        return (float)$value;
    }

    private function forbidden(): never
    {
        http_response_code(403);
        View::render('errors/403', ['title' => 'Forbidden']);
        exit;
    }
}
