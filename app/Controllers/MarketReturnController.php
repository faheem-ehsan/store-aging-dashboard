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

    private function marketReturnReturnParams(array $source): array
    {
        $params = [];

        $q = trim((string)($source['return_q'] ?? ''));
        if ($q !== '') $params['q'] = $q;

        $divisionId = (int)($source['return_division_id'] ?? 0);
        if ($divisionId > 0) $params['division_id'] = $divisionId;

        $category = trim((string)($source['return_category'] ?? ''));
        if ($category !== '') $params['category'] = $category;

        $expiryBucket = trim((string)($source['return_expiry_bucket'] ?? ''));
        if (in_array($expiryBucket, ['a','b','c','expired','no_expiry'], true)) {
            $params['expiry_bucket'] = $expiryBucket;
        }

        $valueStatus = trim((string)($source['return_value_status'] ?? ''));
        if (in_array($valueStatus, ['valued','unvalued'], true)) {
            $params['value_status'] = $valueStatus;
        }

        $page = max(1, (int)($source['return_page'] ?? 1));
        if ($page > 1) $params['page'] = $page;

        return $params;
    }

    private function marketReturnReturnPath(array $params): string
    {
        return '/market-return' . ($params ? '?' . http_build_query($params) : '');
    }

    private function marketReturnInputParams(array $params): array
    {
        $result = [];
        $map = [
            'q' => 'return_q',
            'division_id' => 'return_division_id',
            'category' => 'return_category',
            'expiry_bucket' => 'return_expiry_bucket',
            'value_status' => 'return_value_status',
            'page' => 'return_page',
        ];

        foreach ($map as $key => $inputName) {
            if (array_key_exists($key, $params)) {
                $result[$inputName] = $params[$key];
            }
        }

        return $result;
    }

    private function marketReturnCategories(array $user, ?int $divisionId = null): array
    {
        $categories = MarketReturnImporter::CATEGORIES;

        try {
            $dynamic = (new MarketReturnItem())->categoryOptions($user, $divisionId);
            foreach ($dynamic as $row) {
                $code = trim((string)($row['category_code'] ?? ''));
                if ($code === '') continue;

                if (!isset($categories[$code])) {
                    $label = trim((string)($row['category_label'] ?? ''));
                    $categories[$code] = [
                        'label' => $label !== '' ? $label : $code,
                        'sheet' => $label !== '' ? $label : $code,
                        'class' => 'default',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Keep the original built-in list when the module tables are not installed yet.
        }

        return $categories;
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
        $canReadAllDivisions = ($user['role'] ?? '') === 'super_admin'
            || (($user['role'] ?? '') === 'user' && empty($user['division_id']));
        if (!$canReadAllDivisions) $filters['division_id'] = (int)$user['division_id'];

        $divisions = (new Division())->all(true);
        $categories = $this->marketReturnCategories($user, $filters['division_id']);
        $expiryBuckets = $this->expiryBuckets();
        if ($filters['expiry_bucket'] !== '' && !isset($expiryBuckets[$filters['expiry_bucket']])) {
            $filters['expiry_bucket'] = '';
        }

        $baseRows = [];
        $rows = [];
        $bulkDeleteStats = [];
        $databaseReady = true;
        $databaseError = null;
        $marketReturnModel = new MarketReturnItem();
        try {
            // Expiry grouping is calculated at runtime so existing Market Return data needs no migration.
            $dbFilters = $filters;
            unset($dbFilters['expiry_bucket']);
            $baseRows = $marketReturnModel->allAccessible($user, $dbFilters);

            foreach ($baseRows as &$row) {
                $row['expiry_bucket'] = $this->expiryBucket($row);
                $resolvedExpiry = $this->effectiveExpiryDate($row);
                $row['expiry_display'] = $resolvedExpiry
                    ? $resolvedExpiry->format('d/M/Y')
                    : (trim((string)($row['expiry_raw'] ?? '')) ?: '—');
            }
            unset($row);

            $rows = $filters['expiry_bucket'] === ''
                ? $baseRows
                : array_values(array_filter($baseRows, fn(array $row): bool => ($row['expiry_bucket'] ?? '') === $filters['expiry_bucket']));

            if (($user['role'] ?? '') === 'super_admin') {
                $bulkDeleteStats = $marketReturnModel->bulkDeleteDivisionStats();
            }

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

        $selectedDivisionLabel = $canReadAllDivisions
            ? 'All Divisions'
            : (($user['division_code'] ?? '') . ' — ' . ($user['division_name'] ?? ''));
        if ($canReadAllDivisions && !empty($filters['division_id'])) {
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
            'bulkDeleteStats' => $bulkDeleteStats,
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
            'categories' => $this->marketReturnCategories(
                $user,
                $user['role'] === 'super_admin' ? (int)($_GET['division_id'] ?? 0) ?: null : (int)$user['division_id']
            ),
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
        $returnParams = $this->marketReturnReturnParams($_GET);

        View::render('market_return/form', [
            'title' => 'Edit Market Return Item',
            'user' => $user,
            'divisions' => (new Division())->all(true),
            'categories' => $this->marketReturnCategories($user, (int)$item['division_id']),
            'item' => $item,
            'selectedDivisionId' => (int)$item['division_id'],
            'selectedCategory' => $item['category_code'],
            'returnParams' => $returnParams,
        ]);
    }

    public function update(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $id = (int)($_POST['id'] ?? 0);
        $returnParams = $this->marketReturnReturnParams($_POST);
        $returnPath = $this->marketReturnReturnPath($returnParams);
        $editReturnQuery = http_build_query(
            array_merge(['id' => $id], $this->marketReturnInputParams($returnParams))
        );

        $model = new MarketReturnItem();
        $before = $model->findAccessible($id, $user);
        if (!$before) $this->forbidden();
        $data = $this->payload($user);
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            redirect('/market-return/edit?' . $editReturnQuery);
        }
        try {
            $model->save($data, $user, $id);
            AuditLogger::log('MARKET_RETURN_UPDATE', 'Updated market-return item ' . $data['product_name'] . '.', 'market_return_item', $id, ['before' => $before, 'after' => $data], $user, (int)$data['division_id']);
            flash('success', 'Market Return item updated.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to update the item.');
        }
        redirect($returnPath);
    }

    public function delete(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $id = (int)($_POST['id'] ?? 0);
        $returnParams = $this->marketReturnReturnParams($_POST);
        $returnPath = $this->marketReturnReturnPath($returnParams);

        $model = new MarketReturnItem();
        $row = $model->findAccessible($id, $user);
        if ($row) {
            $model->delete($id, $user);
            AuditLogger::log('MARKET_RETURN_DELETE', 'Deleted market-return item ' . ($row['product_name'] ?? '') . '.', 'market_return_item', $id, $row, $user, (int)$row['division_id'], $row['division_code'] ?? null);
            flash('success', 'Market Return item deleted.');
        }
        redirect($returnPath);
    }

    public function bulkDelete(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();

        $divisionIds = $_POST['division_ids'] ?? [];
        if (!is_array($divisionIds)) $divisionIds = [];

        $divisionIds = array_values(array_unique(array_filter(
            array_map('intval', $divisionIds),
            static fn(int $id): bool => $id > 0
        )));

        if (!$divisionIds) {
            flash('error', 'Select at least one Division for Market Return bulk deletion.');
            redirect('/market-return');
        }

        if (strtoupper(trim((string)($_POST['confirm_text'] ?? ''))) !== 'DELETE') {
            flash('error', 'Bulk delete was not completed. Type DELETE to confirm.');
            redirect('/market-return');
        }

        try {
            $model = new MarketReturnItem();
            $result = $model->bulkDeleteByDivisions($divisionIds);

            foreach (($result['divisions'] ?? []) as $division) {
                $itemCount = (int)($division['item_count'] ?? 0);
                if ($itemCount <= 0) continue;

                AuditLogger::log(
                    'MARKET_RETURN_BULK_DELETE',
                    'Bulk deleted ' . number_format($itemCount) . ' Market Return item(s) for division ' .
                        ($division['division_code'] ?? '') . '.',
                    'market_return_division_bulk_delete',
                    (string)($division['division_id'] ?? ''),
                    [
                        'deleted_items' => $itemCount,
                        'total_quantity' => (float)($division['total_qty'] ?? 0),
                        'stock_retail_value' => (float)($division['stock_value'] ?? 0),
                    ],
                    $user,
                    (int)($division['division_id'] ?? 0),
                    $division['division_code'] ?? null
                );
            }

            $deleted = (int)($result['deleted_items'] ?? 0);

            if ($deleted > 0) {
                flash(
                    'success',
                    number_format($deleted) . ' Market Return record(s) deleted from the selected Division(s). ' .
                    'Finish Goods stock, Divisions, Users, Import History and Division Mapping were not deleted.'
                );
            } else {
                flash('success', 'No Market Return records were found in the selected Division(s).');
            }
        } catch (\Throwable $e) {
            flash('error', 'Market Return bulk delete could not be completed. No partial deletion was saved.');
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
        $categories = $this->marketReturnCategories($user);
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
        fputcsv($out, ['Division','Category','Section','Product Name','Quantity','Unit','Expiry','Retail Price','Total Amount','Remarks','Aux Label','Aux Value','Report Date']);
        fputcsv($out, ['JSD','Utilizable Stock','Utilizable Stock - Market Return Store','Jam-e-Shirin 500ml','10','Bottles',"\t31/Aug/2027",'750','7500','','','',"\t24/Sep/2026"]);
        fputcsv($out, ['SNW','Fresh Saleable Stock','Fresh Saleable Stock','Springley 500ml','24','Bottles',"\t30/Apr/2029",'100','2400','','','',"\t24/Sep/2026"]);
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
            'expiry_date' => $this->normalizeManualDate($_POST['expiry_date'] ?? ''),
            'retail_price' => $retail === '' ? null : $this->number($retail),
            'total_amount' => $amount === '' ? null : $this->number($amount),
            'amount_text' => trim((string)($_POST['amount_text'] ?? '')),
            'remarks' => trim((string)($_POST['remarks'] ?? '')),
            'aux_label' => trim((string)($_POST['aux_label'] ?? '')),
            'aux_value' => trim((string)($_POST['aux_value'] ?? '')),
            'report_date' => $this->normalizeManualDate($_POST['report_date'] ?? ''),
        ];
    }

    private function validate(array $data): ?string
    {
        if ((int)$data['division_id'] <= 0) return 'Division is required.';
        if (trim((string)$data['category_code']) === '') return 'Category is required.';
        if ($data['product_name'] === '') return 'Product Name / Description is required.';
        if ((float)$data['quantity'] < 0) return 'Quantity cannot be negative.';
        if ($data['unit'] === '') return 'Unit is required.';
        foreach (['expiry_date' => 'Expiry Date', 'report_date' => 'Report Date'] as $field => $label) {
            if ($data[$field] !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$data[$field])) {
                return $label . ' must use DD/MON/YYYY, for example 01/Sep/2026.';
            }
        }
        return null;
    }

    private function normalizeManualDate(mixed $value): mixed
    {
        $value = trim((string)$value);
        if ($value === '') return null;
        return \parse_app_date($value) ?? $value;
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
