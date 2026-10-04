<?php
namespace App\Services;

use App\Models\DailyStockReport;
use App\Models\Division;
use App\Models\StockBatch;

class DailyStockReportService
{
    private DailyStockReport $reports;
    private StockBatch $stock;
    private GradeService $grader;

    public function __construct()
    {
        $this->reports = new DailyStockReport();
        $this->stock = new StockBatch();
        $this->grader = new GradeService();
        $this->reports->ensureTables();
    }

    public function generateForDivision(
        int $divisionId,
        string $reportDate,
        ?array $actor = null,
        string $generationType = 'manual',
        bool $force = false
    ): array {
        $division = (new Division())->find($divisionId);
        if (!$division) {
            throw new \RuntimeException('Division not found.');
        }

        $asOf = new \DateTimeImmutable($reportDate);
        $rows = $this->stock->allAccessible(
            ['role' => 'super_admin', 'division_id' => null],
            ['division_id' => $divisionId]
        );

        $gradeSummary = [];
        foreach (['A+','A','B','C','EX'] as $code) {
            $gradeSummary[$code] = [
                'batches' => 0,
                'quantity' => 0.0,
                'stock_value' => 0.0,
            ];
        }

        $totalQtyMilli = 0;
        $totalValueCents = 0;
        $items = [];

        foreach ($rows as $row) {
            $enriched = $this->grader->enrich($row, $asOf);
            $gradeCode = (string)($enriched['grade']['grade_code'] ?? 'EX');
            $gradeName = (string)($enriched['grade']['grade_name'] ?? $gradeCode);

            $qtyMilli = (int)round(((float)($row['current_qty'] ?? 0)) * 1000);
            $valueCents = (int)round(((float)($row['stock_value'] ?? 0)) * 100);

            $totalQtyMilli += $qtyMilli;
            $totalValueCents += $valueCents;

            if (!isset($gradeSummary[$gradeCode])) {
                $gradeSummary[$gradeCode] = [
                    'batches' => 0,
                    'quantity' => 0.0,
                    'stock_value' => 0.0,
                ];
            }

            $gradeSummary[$gradeCode]['batches']++;
            $gradeSummary[$gradeCode]['quantity'] += $qtyMilli / 1000;
            $gradeSummary[$gradeCode]['stock_value'] += $valueCents / 100;

            $items[] = [
                'source_batch_id' => (int)$row['id'],
                'product_code' => (string)$row['material_code'],
                'product_name' => (string)$row['material_description'],
                'batch_no' => (string)$row['batch_no'],
                'current_qty' => $qtyMilli / 1000,
                'uom' => (string)($row['uom'] ?? ''),
                'stock_value' => $valueCents / 100,
                'manufacture_date' => (string)$row['manufacture_date'],
                'expiry_date' => (string)$row['expiry_date'],
                'grade_code' => $gradeCode,
                'grade_name' => $gradeName,
                'stock_location' => (string)($row['receiving_store'] ?? ''),
                'source_updated_at' => (string)($row['updated_at'] ?? ''),
            ];
        }

        $summary = [
            'total_batches' => count($items),
            'total_quantity' => $totalQtyMilli / 1000,
            'total_stock_value' => $totalValueCents / 100,
            'grade_summary' => $gradeSummary,
        ];

        $result = $this->reports->replaceSnapshot(
            $divisionId,
            $reportDate,
            $summary,
            $items,
            $generationType,
            isset($actor['id']) ? (int)$actor['id'] : null,
            $force
        );

        AuditLogger::log(
            'DAILY_STOCK_REPORT_GENERATE',
            ($generationType === 'automatic' ? 'Automatic' : 'Manual') .
                ' daily stock snapshot generated for ' .
                ($division['code'] ?? ('Division ' . $divisionId)) .
                ' on ' . $reportDate . '.',
            'daily_stock_report',
            (string)$result['report_id'],
            [
                'report_date' => $reportDate,
                'generation_type' => $generationType,
                'total_batches' => $summary['total_batches'],
                'total_quantity' => $summary['total_quantity'],
                'total_stock_value' => $summary['total_stock_value'],
                'created' => $result['created'],
            ],
            $actor,
            $divisionId,
            $division['code'] ?? null
        );

        return array_merge($result, [
            'division_id' => $divisionId,
            'division_code' => $division['code'] ?? '',
            'summary' => $summary,
        ]);
    }

    public function generateAllActive(
        string $reportDate,
        ?array $actor = null,
        string $generationType = 'automatic',
        bool $force = true
    ): array {
        $result = [
            'generated' => 0,
            'created' => 0,
            'refreshed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ((new Division())->all(true) as $division) {
            try {
                $one = $this->generateForDivision(
                    (int)$division['id'],
                    $reportDate,
                    $actor,
                    $generationType,
                    $force
                );
                $result['generated']++;
                if (!empty($one['created'])) $result['created']++;
                else $result['refreshed']++;
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['errors'][] = [
                    'division' => $division['code'] ?? '',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }
}
