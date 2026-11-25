<?php
// CLI only: never allow these scheduled jobs to be triggered over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\GradeAlertService;

try {
    $result = (new GradeAlertService())->run(new \DateTimeImmutable('today'));
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(($result['failed_batches'] ?? 0) > 0 ? 2 : 0);
} catch (\Throwable $e) {
    fwrite(STDERR, '[Grade Email Alerts] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
