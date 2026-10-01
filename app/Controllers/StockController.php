<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\StockBatch;
use App\Services\GradeService;
use App\Services\AuditLogger;

class StockController
{
    private function canManage(array $user): bool
    {
        return in_array($user['role'], ['super_admin','division_admin'], true);
    }

    public function index(): void
    {
        $user = Auth::requireLogin();
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'division_id' => isset($_GET['division_id']) && $_GET['division_id'] !== '' ? (int)$_GET['division_id'] : null,
            'grade' => trim($_GET['grade'] ?? ''),
        ];
        $canReadAllDivisions = ($user['role'] ?? '') === 'super_admin'
            || (($user['role'] ?? '') === 'user' && empty($user['division_id']));
        if (!$canReadAllDivisions) $filters['division_id'] = (int)$user['division_id'];

        $grader = new GradeService();
        $rows = array_map(fn($r) => $grader->enrich($r), (new StockBatch())->allAccessible($user, $filters));
        if ($filters['grade'] !== '') {
            $rows = array_values(array_filter($rows, fn($r) => $r['grade']['grade_code'] === $filters['grade']));
        }

        $divisions = (new Division())->all(true);
        $summary = [
            'current_qty' => array_reduce($rows, fn(float $carry, array $row): float => $carry + (float)$row['current_qty'], 0.0),
            'stock_value' => array_reduce($rows, fn(float $carry, array $row): float => $carry + (float)($row['stock_value'] ?? 0), 0.0),
            'batch_count' => count($rows),
        ];

        $selectedDivisionLabel = 'All Divisions';
        if (!$canReadAllDivisions) {
            $selectedDivisionLabel = trim((string)($user['division_code'] ?? 'Division')) ?: 'Division';
        } elseif (!empty($filters['division_id'])) {
            foreach ($divisions as $division) {
                if ((int)$division['id'] === (int)$filters['division_id']) {
                    $selectedDivisionLabel = $division['code'] . ' — ' . $division['name'];
                    break;
                }
            }
        }

        $gradeLabels = [
            'A+' => 'A+ / Dark Blue',
            'A' => 'A / Green',
            'B' => 'B / Yellow',
            'C' => 'C / Red',
            'EX' => 'Expired / Black',
        ];
        $selectedGradeLabel = $filters['grade'] !== ''
            ? ($gradeLabels[$filters['grade']] ?? $filters['grade'])
            : 'All Grades';

        $gradeCriteria = !empty($filters['division_id'])
            ? $grader->criteriaForDivision((int)$filters['division_id'])
            : null;

        View::render('stocks/index', [
            'title' => 'Stock Aging Dashboard',
            'user' => $user,
            'rows' => $rows,
            'filters' => $filters,
            'divisions' => $divisions,
            'canManage' => $this->canManage($user),
            'summary' => $summary,
            'selectedDivisionLabel' => $selectedDivisionLabel,
            'selectedGradeLabel' => $selectedGradeLabel,
            'gradeCriteria' => $gradeCriteria,
        ]);
    }

    public function create(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();

        $divisionModel = new Division();
        $divisions = $divisionModel->all(true);
        $selectedDivisionId = $user['role'] === 'super_admin'
            ? (int)($_GET['division_id'] ?? 0)
            : (int)$user['division_id'];
        $selectedDivision = $selectedDivisionId > 0 ? $divisionModel->find($selectedDivisionId) : null;
        if ($selectedDivision && !(int)$selectedDivision['is_active']) $selectedDivision = null;

        $divisionCode = $user['role'] === 'super_admin'
            ? ($selectedDivision['code'] ?? '')
            : ($user['division_code'] ?? '');
        $title = $divisionCode !== '' ? $divisionCode . ' Add Batch' : 'Division Add Batch';

        View::render('stocks/form', [
            'title' => $title,
            'user' => $user,
            'divisions' => $divisions,
            'batch' => null,
            'selectedDivisionId' => $selectedDivisionId,
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
            redirect('/stocks/create');
        }
        try {
            $model = new StockBatch();
            $id = $model->save($data, $user);
            $saved = $model->findAccessible($id, $user);
            AuditLogger::log(
                'BATCH_CREATE',
                'Created batch ' . $data['batch_no'] . ' for material ' . $data['material_code'] . '.',
                'stock_batch',
                $id,
                [
                    'material_no' => $data['material_code'],
                    'material_description' => $data['material_description'],
                    'batch_no' => $data['batch_no'],
                    'current_qty' => (float)$data['current_qty'],
                    'stock_value' => (float)$data['stock_value'],
                    'manufacture_date' => $data['manufacture_date'],
                    'expiry_date' => $data['expiry_date'],
                    'receiving_store' => $data['receiving_store'],
                ],
                $user,
                (int)$data['division_id'],
                $saved['division_code'] ?? null
            );
            flash('success', 'Stock batch added.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to save batch. Run database/migrations/002_add_dispatch_stock_fields.sql if this upgrade was just installed.');
        }
        redirect('/stocks');
    }

    public function edit(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $id = (int)($_GET['id'] ?? 0);
        $batch = (new StockBatch())->findAccessible($id, $user);
        if (!$batch) { http_response_code(404); View::render('errors/404', ['title'=>'Not Found']); return; }
        View::render('stocks/form', [
            'title' => 'Edit Batch',
            'user' => $user,
            'divisions' => (new Division())->all(true),
            'batch' => $batch,
        ]);
    }

    public function update(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $id = (int)($_POST['id'] ?? 0);
        $data = $this->payload($user);
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            redirect('/stocks/edit?id=' . $id);
        }
        try {
            $model = new StockBatch();
            $before = $model->findAccessible($id, $user);
            $model->save($data, $user, $id);
            $after = $model->findAccessible($id, $user);
            AuditLogger::log(
                'BATCH_UPDATE',
                'Updated batch ' . $data['batch_no'] . ' for material ' . $data['material_code'] . '.',
                'stock_batch',
                $id,
                [
                    'before' => $before ? $this->batchLogSnapshot($before) : null,
                    'after' => $after ? $this->batchLogSnapshot($after) : [
                        'material_no' => $data['material_code'],
                        'batch_no' => $data['batch_no'],
                        'current_qty' => (float)$data['current_qty'],
                        'stock_value' => (float)$data['stock_value'],
                        'expiry_date' => $data['expiry_date'],
                        'receiving_store' => $data['receiving_store'],
                    ],
                ],
                $user,
                (int)$data['division_id'],
                $after['division_code'] ?? ($before['division_code'] ?? null)
            );
            flash('success', 'Stock batch updated.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to update batch. Run database/migrations/002_add_dispatch_stock_fields.sql if needed.');
        }
        redirect('/stocks');
    }

    public function delete(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $id = (int)($_POST['id'] ?? 0);
        $model = new StockBatch();
        $before = $model->findAccessible($id, $user);
        $model->delete($id, $user);
        if ($before) {
            AuditLogger::log(
                'BATCH_DELETE',
                'Deleted batch ' . $before['batch_no'] . ' for material ' . $before['material_code'] . '.',
                'stock_batch',
                $id,
                $this->batchLogSnapshot($before),
                $user,
                (int)$before['division_id'],
                $before['division_code'] ?? null
            );
        }
        flash('success', 'Batch deleted.');
        redirect('/stocks');
    }

    public function importForm(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        View::render('stocks/import', [
            'title' => 'CSV Import',
            'user' => $user,
            'divisions' => (new Division())->all(true),
            'result' => null,
            'preselectedDivisionId' => $user['role'] === 'super_admin' ? (int)($_GET['division_id'] ?? 0) : (int)$user['division_id'],
        ]);
    }

    public function importCsv(): void
    {
        verify_csrf();
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();

        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];
        $importMode = trim((string)($_POST['import_mode'] ?? 'new_only'));
        if (!in_array($importMode, ['new_only', 'update_existing'], true)) {
            $importMode = 'new_only';
        }

        $divisionModel = new Division();
        $targetDivisionId = $user['role'] === 'super_admin'
            ? (int)($_POST['target_division_id'] ?? 0)
            : (int)$user['division_id'];
        $targetDivision = null;
        if ($targetDivisionId > 0) {
            $targetDivision = $divisionModel->find($targetDivisionId);
            if (!$targetDivision || !(int)$targetDivision['is_active']) {
                $result['failed'] = 1;
                $result['errors'][] = 'The selected target division is not active or does not exist.';
                $this->renderImportResult($user, $result);
                return;
            }
            if ($user['role'] !== 'super_admin' && $targetDivisionId !== (int)$user['division_id']) {
                $result['failed'] = 1;
                $result['errors'][] = 'Target division does not match your login division.';
                $this->renderImportResult($user, $result);
                return;
            }
        }

        $file = $_FILES['csv_file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $result['failed'] = 1;
            $result['errors'][] = 'Please select a CSV file to import.';
            $this->renderImportResult($user, $result);
            return;
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            $result['failed'] = 1;
            $result['errors'][] = 'CSV file must be 5 MB or smaller.';
            $this->renderImportResult($user, $result);
            return;
        }
        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $result['failed'] = 1;
            $result['errors'][] = 'Only .csv files are accepted.';
            $this->renderImportResult($user, $result);
            return;
        }

        $handle = fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            $result['failed'] = 1;
            $result['errors'][] = 'Unable to read the uploaded CSV file.';
            $this->renderImportResult($user, $result);
            return;
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            $result['failed'] = 1;
            $result['errors'][] = 'The CSV file is empty.';
            $this->renderImportResult($user, $result);
            return;
        }
        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);
        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if (!$rawHeaders) {
            fclose($handle);
            $result['failed'] = 1;
            $result['errors'][] = 'CSV header row could not be read.';
            $this->renderImportResult($user, $result);
            return;
        }

        $headers = [];
        foreach ($rawHeaders as $index => $header) {
            if ($index === 0) $header = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header);
            $headers[] = $this->canonicalHeader((string)$header);
        }

        $required = ['material_no','material_description','current_qty','uom','stock_value','batch_no','manufacture_date','expiry_date','receiving_store'];
        $missing = array_values(array_filter($required, fn($h) => !in_array($h, $headers, true)));
        if ($user['role'] === 'super_admin' && $targetDivisionId === 0 && !in_array('division', $headers, true)) {
            $missing[] = 'division (or choose a Target Division before importing)';
        }
        if ($missing) {
            fclose($handle);
            $result['failed'] = 1;
            $result['errors'][] = 'Missing required column(s): ' . implode(', ', $missing) . '.';
            $this->renderImportResult($user, $result);
            return;
        }

        $stockModel = new StockBatch();
        $rowNumber = 1;
        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            if ($this->csvRowIsEmpty($values)) continue;

            // A common Excel mistake is to keep the Division heading but start each
            // data row with Product Code. When a target division is selected, repair
            // that one-column shift automatically instead of treating Product Code
            // as a division code.
            $values = $this->alignCsvValues($values, $headers, $divisionModel, $targetDivisionId > 0);

            $row = [];
            foreach ($headers as $i => $key) {
                if ($key !== '') $row[$key] = trim((string)($values[$i] ?? ''));
            }

            try {
                if ($user['role'] === 'super_admin') {
                    if ($targetDivisionId > 0) {
                        $divisionId = $targetDivisionId;
                    } else {
                        $divisionValue = trim((string)($row['division'] ?? ''));
                        $division = $this->resolveImportDivision($divisionModel, $divisionValue);
                        if (!$division) {
                            $hint = $divisionValue !== '' && preg_match('/^\d+$/', $divisionValue)
                                ? ' This looks like a Product Code. Select a Target Division on the import screen, or put JSD/SNW/RTD in the Division column.'
                                : ' Use a valid division code/name, or select a Target Division on the import screen.';
                            throw new \RuntimeException('Unknown or inactive division "' . $divisionValue . '".' . $hint);
                        }
                        $divisionId = (int)$division['id'];
                    }
                } else {
                    $divisionId = (int)$user['division_id'];
                    if (!empty($row['division'])) {
                        $division = $this->resolveImportDivision($divisionModel, $row['division']);
                        if (!$division || (int)$division['id'] !== $divisionId) {
                            throw new \RuntimeException('Division does not match your login division.');
                        }
                    }
                }

                $data = [
                    'division_id' => $divisionId,
                    'material_code' => trim($row['material_no'] ?? ''),
                    'material_description' => trim($row['material_description'] ?? ''),
                    'current_qty' => $this->normalizeNumber($row['current_qty'] ?? ''),
                    'stock_value' => $this->normalizeNumber($row['stock_value'] ?? ''),
                    'batch_no' => trim($row['batch_no'] ?? ''),
                    'manufacture_date' => $this->normalizeCsvDate($row['manufacture_date'] ?? ''),
                    'expiry_date' => $this->normalizeCsvDate($row['expiry_date'] ?? ''),
                    'receiving_store' => trim($row['receiving_store'] ?? ''),
                    'last_receipt_date' => !empty($row['last_receipt_date']) ? $this->normalizeCsvDate($row['last_receipt_date']) : null,
                    'uom' => trim($row['uom'] ?? 'PCS') ?: 'PCS',
                ];

                $error = $this->validate($data);
                if ($error) throw new \RuntimeException($error);

                if ($importMode === 'new_only') {
                    $existing = $stockModel->findByNaturalKey(
                        (int)$data['division_id'],
                        (string)$data['material_code'],
                        (string)$data['batch_no']
                    );
                    if ($existing) {
                        // Preserve the previously imported batch exactly as it is.
                        // Only a genuinely new Batch No. for this Division + Product is inserted.
                        $result['skipped']++;
                        continue;
                    }
                }

                $mode = $stockModel->upsertImport($data, $user);
                $result[$mode]++;
            } catch (\Throwable $e) {
                $result['failed']++;
                if (count($result['errors']) < 25) {
                    $result['errors'][] = 'Row ' . $rowNumber . ': ' . $e->getMessage();
                }
            }
        }
        fclose($handle);

        if ($result['failed'] > count($result['errors'])) {
            $result['errors'][] = 'Additional row errors were omitted from this screen.';
        }
        $this->renderImportResult($user, $result);
    }

    public function importTemplate(): void
    {
        $user = Auth::requireLogin();
        if (!$this->canManage($user)) $this->forbidden();
        $division = $user['role'] === 'super_admin' ? 'SNW' : ($user['division_code'] ?? 'SNW');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="qarshi_dispatch_stock_import_template.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        fputcsv($out, [
            'Division','Product Code','Product Name','Current Stock','UoM','Retail Stock Value','Batch No',
            'Manufacturing Date','Expiry Date','Aging (Days)','Aging Remaining (Days)','Stock Location'
        ]);
        // Example row follows the uploaded stock-aging report layout. Aging columns are
        // intentionally blank because the system calculates them when the file is imported.
        fputcsv($out, [$division,'100409','SPRINGLEY 500 ML (1*12)','2085','CS','505946.10','SP180926','2026-09-18','2027-09-18','','','FW02']);
        fclose($out);
        exit;
    }

    private function renderImportResult(array $user, array $result): void
    {
        $targetDivisionId = $user['role'] === 'super_admin'
            ? (int)($_POST['target_division_id'] ?? 0)
            : (int)$user['division_id'];
        $targetDivision = $targetDivisionId > 0 ? (new Division())->find($targetDivisionId) : null;
        $fileName = basename((string)($_FILES['csv_file']['name'] ?? ''));
        AuditLogger::log(
            'CSV_IMPORT',
            sprintf(
                'CSV import completed: %d inserted, %d updated, %d skipped existing, %d failed.',
                (int)($result['inserted'] ?? 0),
                (int)($result['updated'] ?? 0),
                (int)($result['skipped'] ?? 0),
                (int)($result['failed'] ?? 0)
            ),
            'csv_import',
            null,
            [
                'file_name' => $fileName ?: null,
                'target_division' => $targetDivision['code'] ?? ($targetDivisionId === 0 ? 'From CSV rows' : null),
                'import_mode' => trim((string)($_POST['import_mode'] ?? 'new_only')),
                'inserted' => (int)($result['inserted'] ?? 0),
                'updated' => (int)($result['updated'] ?? 0),
                'skipped_existing' => (int)($result['skipped'] ?? 0),
                'failed' => (int)($result['failed'] ?? 0),
                'errors' => array_slice((array)($result['errors'] ?? []), 0, 10),
            ],
            $user,
            $targetDivision ? (int)$targetDivision['id'] : ($user['division_id'] ? (int)$user['division_id'] : null),
            $targetDivision['code'] ?? ($user['division_code'] ?? null)
        );

        View::render('stocks/import', [
            'title' => 'CSV Import',
            'user' => $user,
            'divisions' => (new Division())->all(true),
            'result' => $result,
            'preselectedDivisionId' => $targetDivisionId,
        ]);
    }

    private function payload(array $user): array
    {
        return [
            'division_id' => $user['role'] === 'super_admin' ? (int)($_POST['division_id'] ?? 0) : (int)$user['division_id'],
            'material_code' => trim($_POST['material_code'] ?? ''),
            'material_description' => trim($_POST['material_description'] ?? ''),
            'batch_no' => trim($_POST['batch_no'] ?? ''),
            'manufacture_date' => $_POST['manufacture_date'] ?? '',
            'expiry_date' => $_POST['expiry_date'] ?? '',
            'last_receipt_date' => !empty($_POST['last_receipt_date']) ? $_POST['last_receipt_date'] : null,
            'current_qty' => $_POST['current_qty'] ?? '',
            'stock_value' => $_POST['stock_value'] ?? '0',
            'receiving_store' => trim($_POST['receiving_store'] ?? ''),
            'uom' => trim($_POST['uom'] ?? 'PCS'),
        ];
    }

    private function validate(array $d): ?string
    {
        if (!$d['division_id'] || !$d['material_code'] || !$d['material_description'] || !$d['batch_no']) {
            return 'Division, product code, product name and batch number are required.';
        }
        foreach (['manufacture_date','expiry_date'] as $f) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d[$f])) return 'Manufacture and expiry dates are required.';
        }
        if ($d['manufacture_date'] > $d['expiry_date']) return 'Manufacture date cannot be after expiry date.';
        if (!is_numeric($d['current_qty']) || (float)$d['current_qty'] < 0) return 'Current Stock must be zero or greater.';
        if (!is_numeric($d['stock_value']) || (float)$d['stock_value'] < 0) return 'Retail stock value must be zero or greater.';
        if (trim((string)$d['uom']) === '') return 'UoM is required.';
        if (trim((string)$d['receiving_store']) === '') return 'Stock Location is required.';
        return null;
    }

    private function canonicalHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace(['&', '#'], ['and', 'no'], $header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = trim((string)$header, '_');
        $map = [
            'division' => 'division', 'diviosn' => 'division',
            'material_no' => 'material_no', 'material_number' => 'material_no', 'material' => 'material_no', 'material_code' => 'material_no', 'product_code' => 'material_no', 'product_no' => 'material_no',
            'material_des' => 'material_description', 'material_description' => 'material_description', 'description' => 'material_description', 'product_name' => 'material_description', 'product_description' => 'material_description',
            'current_qty' => 'current_qty', 'current_quantity' => 'current_qty', 'current_stock' => 'current_qty', 'qty' => 'current_qty', 'stock_qty' => 'current_qty',
            'stock_value' => 'stock_value', 'retail_stock_value' => 'stock_value', 'retail_value' => 'stock_value', 'value' => 'stock_value',
            'batch_no' => 'batch_no', 'batch_number' => 'batch_no', 'batch' => 'batch_no',
            'date_of_manufactuer' => 'manufacture_date', 'date_of_manufacturer' => 'manufacture_date', 'date_of_manufacture' => 'manufacture_date', 'manufacture_date' => 'manufacture_date', 'manufacturing_date' => 'manufacture_date', 'mfg_date' => 'manufacture_date',
            'expiry_date' => 'expiry_date', 'expiration_date' => 'expiry_date',
            'aging_days' => 'aging_days', 'aging_day' => 'aging_days',
            'aging_remaining_days' => 'aging_remaining_days', 'remaining_days' => 'aging_remaining_days',
            'receiving_store' => 'receiving_store', 'receiving_store_no' => 'receiving_store', 'store' => 'receiving_store', 'stock_location' => 'receiving_store', 'location' => 'receiving_store',
            'last_receipt_date' => 'last_receipt_date', 'receipt_date' => 'last_receipt_date',
            'uom' => 'uom', 'unit' => 'uom', 'unit_of_measure' => 'uom',
        ];
        return $map[$header] ?? $header;
    }

    private function resolveImportDivision(Division $divisionModel, string $value): ?array
    {
        $value = trim($value);
        $division = $divisionModel->findByCodeOrName($value);
        if ($division) return $division;

        // The supplied SNW stock report uses SNMW as its source-system division code.
        // Treat it as SNW for imports without changing the division master itself.
        if (strtoupper($value) === 'SNMW') {
            return $divisionModel->findByCodeOrName('SNW');
        }
        return null;
    }

    private function normalizeCsvDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') throw new \RuntimeException('Date is missing.');

        if (is_numeric($value) && (float)$value > 20000 && (float)$value < 100000) {
            $unix = ((int)$value - 25569) * 86400;
            return gmdate('Y-m-d', $unix);
        }

        $formats = ['Y-m-d','d.m.Y','d/m/Y','d-m-Y','m/d/Y','m-d-Y','Y/m/d'];
        foreach ($formats as $format) {
            $dt = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($dt && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $dt->format('Y-m-d');
            }
        }
        throw new \RuntimeException('Invalid date "' . $value . '". Use YYYY-MM-DD or DD.MM.YYYY.');
    }

    private function normalizeNumber(string $value): string
    {
        $value = trim($value);
        $value = str_replace([',', ' '], '', $value);
        if ($value === '') return '0';
        if (!is_numeric($value)) throw new \RuntimeException('Invalid numeric value "' . $value . '".');
        return $value;
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($candidates);
        $delimiter = array_key_first($candidates);
        return ($candidates[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }

    private function alignCsvValues(array $values, array $headers, Division $divisionModel, bool $hasTargetDivision): array
    {
        $divisionIndex = array_search('division', $headers, true);
        if ($divisionIndex !== 0 || !$hasTargetDivision || !$values) return $values;

        $first = trim((string)($values[0] ?? ''));
        if ($first === '') return $values;

        // If the first cell already contains a valid division, no repair is needed.
        if ($this->resolveImportDivision($divisionModel, $first)) return $values;

        $headerCount = count($headers);
        $valueCount = count($values);
        $lastIsBlank = $valueCount > 0 && trim((string)$values[$valueCount - 1]) === '';

        // Row has one fewer value than the heading row, or Excel exported an extra
        // trailing blank. In both cases the Division cell was almost certainly
        // omitted and all values shifted one column to the left.
        if ($valueCount === $headerCount - 1 || ($valueCount === $headerCount && $lastIsBlank)) {
            if ($valueCount === $headerCount && $lastIsBlank) array_pop($values);
            array_unshift($values, '');
        }

        return $values;
    }

    private function csvRowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string)$value) !== '') return false;
        }
        return true;
    }

    private function batchLogSnapshot(array $batch): array
    {
        return [
            'division' => $batch['division_code'] ?? null,
            'material_no' => $batch['material_code'] ?? null,
            'material_description' => $batch['material_description'] ?? null,
            'batch_no' => $batch['batch_no'] ?? null,
            'current_qty' => isset($batch['current_qty']) ? (float)$batch['current_qty'] : null,
            'stock_value' => isset($batch['stock_value']) ? (float)$batch['stock_value'] : null,
            'manufacture_date' => $batch['manufacture_date'] ?? null,
            'expiry_date' => $batch['expiry_date'] ?? null,
            'receiving_store' => $batch['receiving_store'] ?? null,
        ];
    }

    private function forbidden(): never
    {
        http_response_code(403);
        View::render('errors/403', ['title' => 'Forbidden']);
        exit;
    }
}
