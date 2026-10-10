<?php
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\SapIntegration;
use App\Services\SapSyncService;

try {
    $settings = (new SapIntegration())->settings();
    if (empty($settings['is_enabled'])) {
        echo json_encode([
            'status' => 'disabled',
            'message' => 'SAP integration is disabled; scheduled synchronization was skipped.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        exit(0);
    }

    $result = (new SapSyncService())->run(null, 'scheduled', false);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, '[SAP S/4HANA Sync] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
