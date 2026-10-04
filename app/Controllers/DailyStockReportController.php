<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\DailyStockReport;
use App\Models\Division;
use App\Services\AuditLogger;
use App\Services\DailyStockReportService;

class DailyStockReportController
{
    private function requireAccess(): array
    {
        $user = Auth::requireLogin();
        if (!in_array($user['role'] ?? '', ['super_admin','store_manager','division_admin'], true)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
        return $user;
    }

    private function dateValue(mixed $value, ?string $fallback = null): string
    {
        $value = trim((string)$value);
        if ($value === '') return $fallback ?? (new \DateTimeImmutable('today'))->format('Y-m-d');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return (new \DateTimeImmutable($value))->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        $parsed = parse_app_date($value);
        if ($parsed !== null) return $parsed;

        return $fallback ?? (new \DateTimeImmutable('today'))->format('Y-m-d');
    }

    private function allowedDivisionId(array $user, mixed $requested): ?int
    {
        if (($user['role'] ?? '') === 'division_admin') {
            return !empty($user['division_id']) ? (int)$user['division_id'] : null;
        }

        $id = (int)$requested;
        return $id > 0 ? $id : null;
    }

    public function index(): void
    {
        $user = $this->requireAccess();
        $model = new DailyStockReport();
        $model->ensureTables();

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $reportDate = $this->dateValue($_GET['date'] ?? '', $today);
        $compareDate = $this->dateValue(
            $_GET['compare_date'] ?? '',
            (new \DateTimeImmutable($reportDate))->modify('-1 day')->format('Y-m-d')
        );

        $divisions = (new Division())->all(true);
        $divisionId = $this->allowedDivisionId($user, $_GET['division_id'] ?? 0);
        $selectedDivision = $divisionId ? (new Division())->find($divisionId) : null;

        if (($user['role'] ?? '') === 'division_admin' && !$selectedDivision) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            return;
        }

        $mode = $divisionId ? 'division' : 'all';
        $data = [
            'title' => 'Daily Stock Reports',
            'user' => $user,
            'divisions' => $divisions,
            'selectedDivisionId' => $divisionId,
            'selectedDivision' => $selectedDivision,
            'reportDate' => $reportDate,
            'compareDate' => $compareDate,
            'today' => $today,
            'mode' => $mode,
            'canGenerateToday' => $reportDate === $today,
        ];

        if ($mode === 'all') {
            $currentReports = $model->reportsForDate($reportDate);
            $compareReports = $model->reportsForDate($compareDate);

            $currentMap = [];
            foreach ($currentReports as $report) $currentMap[(int)$report['division_id']] = $report;

            $compareMap = [];
            foreach ($compareReports as $report) $compareMap[(int)$report['division_id']] = $report;

            $divisionRows = [];
            $summary = [
                'divisions' => 0,
                'available_reports' => 0,
                'total_batches' => 0,
                'total_quantity' => 0.0,
                'total_stock_value' => 0.0,
                'compare_quantity' => 0.0,
                'compare_stock_value' => 0.0,
            ];

            foreach ($divisions as $division) {
                $id = (int)$division['id'];
                $report = $currentMap[$id] ?? null;
                $compare = $compareMap[$id] ?? null;

                $summary['divisions']++;
                if ($report) {
                    $summary['available_reports']++;
                    $summary['total_batches'] += (int)$report['total_batches'];
                    $summary['total_quantity'] += (float)$report['total_quantity'];
                    $summary['total_stock_value'] += (float)$report['total_stock_value'];
                }
                if ($compare) {
                    $summary['compare_quantity'] += (float)$compare['total_quantity'];
                    $summary['compare_stock_value'] += (float)$compare['total_stock_value'];
                }

                $divisionRows[] = [
                    'division' => $division,
                    'report' => $report,
                    'compare' => $compare,
                    'qty_variance' => ($report && $compare)
                        ? (float)$report['total_quantity'] - (float)$compare['total_quantity']
                        : null,
                    'value_variance' => ($report && $compare)
                        ? (float)$report['total_stock_value'] - (float)$compare['total_stock_value']
                        : null,
                ];
            }

            $summary['qty_variance'] = $summary['total_quantity'] - $summary['compare_quantity'];
            $summary['value_variance'] = $summary['total_stock_value'] - $summary['compare_stock_value'];

            $data['divisionRows'] = $divisionRows;
            $data['summary'] = $summary;
        } else {
            $report = $model->findByDivisionDate($divisionId, $reportDate);
            $compare = $model->findByDivisionDate($divisionId, $compareDate);

            // If the exact compare date is missing, use the latest earlier snapshot
            // so comparison remains useful while still showing its actual date.
            if (!$compare) {
                $compare = $model->previousReport($divisionId, $reportDate);
            }

            $q = trim((string)($_GET['q'] ?? ''));
            $allowedSizes = [25, 50, 100];
            $perPage = (int)($_GET['per_page'] ?? 50);
            if (!in_array($perPage, $allowedSizes, true)) $perPage = 50;

            $page = max(1, (int)($_GET['page'] ?? 1));
            $itemCount = $report ? $model->itemCount((int)$report['id'], $q) : 0;
            $pageCount = max(1, (int)ceil($itemCount / $perPage));
            $page = min($page, $pageCount);

            $items = $report
                ? $model->items((int)$report['id'], $q, $perPage, ($page - 1) * $perPage)
                : [];

            $gradeSummary = [];
            if ($report && !empty($report['grade_summary_json'])) {
                $gradeSummary = json_decode((string)$report['grade_summary_json'], true) ?: [];
            }

            $productVariance = [];
            if ($report) {
                $currentProducts = $model->productTotals((int)$report['id']);
                $compareProducts = $compare ? $model->productTotals((int)$compare['id']) : [];
                $codes = array_values(array_unique(array_merge(
                    array_keys($currentProducts),
                    array_keys($compareProducts)
                )));

                foreach ($codes as $code) {
                    $cur = $currentProducts[$code] ?? null;
                    $prev = $compareProducts[$code] ?? null;

                    $row = [
                        'product_code' => $code,
                        'product_name' => (string)($cur['product_name'] ?? $prev['product_name'] ?? ''),
                        'current_qty' => (float)($cur['total_quantity'] ?? 0),
                        'previous_qty' => (float)($prev['total_quantity'] ?? 0),
                        'current_value' => (float)($cur['total_stock_value'] ?? 0),
                        'previous_value' => (float)($prev['total_stock_value'] ?? 0),
                    ];
                    $row['qty_variance'] = $row['current_qty'] - $row['previous_qty'];
                    $row['value_variance'] = $row['current_value'] - $row['previous_value'];
                    $productVariance[] = $row;
                }

                usort($productVariance, static function (array $a, array $b): int {
                    $av = abs((float)$a['value_variance']) + abs((float)$a['qty_variance']);
                    $bv = abs((float)$b['value_variance']) + abs((float)$b['qty_variance']);
                    return $bv <=> $av;
                });
            }

            $summary = [
                'total_batches' => (int)($report['total_batches'] ?? 0),
                'total_quantity' => (float)($report['total_quantity'] ?? 0),
                'total_stock_value' => (float)($report['total_stock_value'] ?? 0),
                'qty_variance' => ($report && $compare)
                    ? (float)$report['total_quantity'] - (float)$compare['total_quantity']
                    : null,
                'value_variance' => ($report && $compare)
                    ? (float)$report['total_stock_value'] - (float)$compare['total_stock_value']
                    : null,
            ];

            $data = array_merge($data, [
                'report' => $report,
                'compareReport' => $compare,
                'summary' => $summary,
                'gradeSummary' => $gradeSummary,
                'productVariance' => $productVariance,
                'items' => $items,
                'q' => $q,
                'perPage' => $perPage,
                'page' => $page,
                'pageCount' => $pageCount,
                'itemCount' => $itemCount,
            ]);
        }

        View::render('daily_reports/index', $data);
    }

    public function generate(): void
    {
        verify_csrf();
        $user = $this->requireAccess();

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $reportDate = $this->dateValue($_POST['report_date'] ?? '', $today);
        $compareDate = $this->dateValue(
            $_POST['compare_date'] ?? '',
            (new \DateTimeImmutable($reportDate))->modify('-1 day')->format('Y-m-d')
        );

        if ($reportDate !== $today) {
            flash('error', 'Historical snapshots cannot be recreated from current stock. Generate or refresh only today\'s report.');
            redirect('/daily-stock-reports?' . http_build_query([
                'date' => $reportDate,
                'compare_date' => $compareDate,
                'division_id' => (int)($_POST['division_id'] ?? 0),
            ]));
        }

        $service = new DailyStockReportService();
        $divisionId = $this->allowedDivisionId($user, $_POST['division_id'] ?? 0);

        try {
            if (Auth::isGlobalOperator($user) && !$divisionId) {
                $result = $service->generateAllActive($reportDate, $user, 'manual', false);
                $message = number_format((int)$result['generated']) . ' division snapshot(s) generated/refreshed.';
                if ((int)$result['failed'] > 0) {
                    $message .= ' ' . number_format((int)$result['failed']) . ' could not be refreshed (usually because they are verified/locked).';
                }
                flash('success', $message);
            } else {
                if (!$divisionId) throw new \RuntimeException('Division is required.');
                $result = $service->generateForDivision($divisionId, $reportDate, $user, 'manual', false);
                flash(
                    'success',
                    ($result['created'] ? 'Daily stock snapshot generated' : 'Daily stock snapshot refreshed') .
                    ' successfully.'
                );
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        $params = [
            'date' => $reportDate,
            'compare_date' => $compareDate,
        ];
        if ($divisionId) $params['division_id'] = $divisionId;

        redirect('/daily-stock-reports?' . http_build_query($params));
    }

    public function verify(): void
    {
        verify_csrf();
        $user = $this->requireAccess();

        $reportId = (int)($_POST['report_id'] ?? 0);
        $model = new DailyStockReport();
        $report = $model->find($reportId);

        if (!$report) {
            flash('error', 'Daily stock report was not found.');
            redirect('/daily-stock-reports');
        }

        if (($user['role'] ?? '') === 'division_admin' &&
            (int)$report['division_id'] !== (int)($user['division_id'] ?? 0)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            return;
        }

        $note = trim((string)($_POST['verification_note'] ?? ''));
        $model->verify($reportId, (int)$user['id'], $note);

        AuditLogger::log(
            'DAILY_STOCK_REPORT_VERIFY',
            'Daily stock report verified for ' . $report['division_code'] . ' on ' . $report['report_date'] . '.',
            'daily_stock_report',
            (string)$reportId,
            [
                'report_date' => $report['report_date'],
                'note' => $note,
            ],
            $user,
            (int)$report['division_id'],
            (string)$report['division_code']
        );

        flash('success', 'Daily stock report verified successfully.');

        redirect('/daily-stock-reports?' . http_build_query([
            'date' => $report['report_date'],
            'division_id' => (int)$report['division_id'],
        ]));
    }

    public function export(): void
    {
        $user = $this->requireAccess();

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $reportDate = $this->dateValue($_GET['date'] ?? '', $today);
        $divisionId = $this->allowedDivisionId($user, $_GET['division_id'] ?? 0);

        $rows = (new DailyStockReport())->exportRows($reportDate, $divisionId);

        $suffix = $divisionId ? '-division-' . $divisionId : '-all-divisions';
        $fileName = 'daily-stock-report-' . $reportDate . $suffix . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Report Date',
            'Division',
            'Division Name',
            'Product Code',
            'Product Name',
            'Batch No',
            'Current Stock',
            'UoM',
            'Retail Stock Value',
            'Manufacturing Date',
            'Expiry Date',
            'Grade',
            'Stock Location',
            'Generation Type',
            'Generated At',
            'Verified At',
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                format_app_date($row['report_date']),
                $row['division_code'],
                $row['division_name'],
                $row['product_code'],
                $row['product_name'],
                $row['batch_no'],
                $this->plainDecimal($row['current_qty'], 3),
                $row['uom'],
                $this->plainDecimal($row['stock_value'], 2),
                format_app_date($row['manufacture_date']),
                format_app_date($row['expiry_date']),
                $row['grade_name'],
                $row['stock_location'],
                $row['generation_type'],
                format_app_datetime($row['generated_at']),
                !empty($row['verified_at']) ? format_app_datetime($row['verified_at']) : '',
            ]);
        }

        fclose($out);
        exit;
    }

    private function plainDecimal(mixed $value, int $scale): string
    {
        $formatted = number_format((float)$value, $scale, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }
}
