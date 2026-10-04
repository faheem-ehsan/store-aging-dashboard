<?php
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\DailyStockReportService;

try {
    $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
    $result = (new DailyStockReportService())->generateAllActive(
        $today,
        null,
        'automatic',
        true
    );

    echo json_encode(
        array_merge(['report_date' => $today], $result),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ) . PHP_EOL;

    exit(($result['failed'] ?? 0) > 0 ? 2 : 0);
} catch (\Throwable $e) {
    fwrite(STDERR, '[Daily Stock Reports] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
