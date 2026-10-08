<?php
namespace App\Services;

use App\Core\Database;
use App\Models\SapIntegration;
use App\Models\ProductMaster;
use PDO;

class SapSyncService
{
    private SapIntegration $model;

    public function __construct()
    {
        $this->model = new SapIntegration();
        $this->model->ensureTables();
    }

    public function testConnection(?int $userId = null): array
    {
        $logId = $this->model->createLog('test', $userId);
        $stats = [];
        try {
            $settings = $this->model->clientSettings();
            $this->validateConnectionSettings($settings);
            $result = (new SapODataClient($settings))->testEndpoints();

            $message = 'SAP connection successful. Product, Batch and Material Stock endpoints responded.';
            $this->model->finishLog($logId, 'test_success', $stats, $message, ['endpoint_samples' => $result]);
            $this->model->recordTest('success', $message);
            return ['success' => true, 'message' => $message, 'endpoint_samples' => $result];
        } catch (\Throwable $e) {
            $message = $this->safeMessage($e);
            $this->model->finishLog($logId, 'test_failed', $stats, $message);
            $this->model->recordTest('failed', $message);
            throw new \RuntimeException($message);
        }
    }

    public function run(?int $userId = null, string $trigger = 'manual', bool $preview = false): array
    {
        if ($this->model->hasRecentRunningSync()) {
            throw new \RuntimeException('Another SAP synchronization is already running. Wait for it to finish before starting a new run.');
        }

        $trigger = $preview ? 'preview' : ($trigger === 'scheduled' ? 'scheduled' : 'manual');
        $logId = $this->model->createLog($trigger, $userId);
        $stats = [
            'product_rows' => 0,
            'batch_rows' => 0,
            'stock_rows' => 0,
            'mapped_stock_rows' => 0,
            'published_batches' => 0,
            'rejected_rows' => 0,
            'unmapped_rows' => 0,
        ];
        $warnings = [];

        try {
            $settings = $this->model->clientSettings();
            if (!empty($settings['publish_to_dashboard']) && trim((string)($settings['stock_value_field'] ?? '')) === '') {
                throw new \RuntimeException(
                    'Dashboard publishing is enabled but Total Stock Retail Value is not mapped. Confirm the approved SAP value field with Finance / SAP first.'
                );
            }
            if (empty($settings['is_enabled']) && !$preview) {
                throw new \RuntimeException('SAP integration is disabled. Enable it in Administration before running a synchronization.');
            }
            $this->validateConnectionSettings($settings);

            $client = new SapODataClient($settings);

            // Extraction is completed before any dashboard data is changed.
            $productRaw = $client->fetchAll((string)$settings['product_endpoint']);
            $batchRaw = $client->fetchAll((string)$settings['batch_endpoint']);
            $stockRaw = $client->fetchAll((string)$settings['stock_endpoint']);

            $stats['product_rows'] = count($productRaw);
            $stats['batch_rows'] = count($batchRaw);
            $stats['stock_rows'] = count($stockRaw);

            $products = $this->normalizeProducts($productRaw, $settings, $warnings);
            $batches = $this->normalizeBatches($batchRaw, $settings, $warnings);
            $mappingIndex = $this->model->activeMappingIndex();
            $stock = $this->normalizeStock($stockRaw, $settings, $mappingIndex, $warnings, $stats);

            if ($preview) {
                $message = sprintf(
                    'Preview successful: %s products, %s batches, %s stock rows; %s mapped, %s unmapped.',
                    number_format($stats['product_rows']),
                    number_format($stats['batch_rows']),
                    number_format($stats['stock_rows']),
                    number_format($stats['mapped_stock_rows']),
                    number_format($stats['unmapped_rows'])
                );
                $summary = [
                    'normalized_products' => count($products),
                    'normalized_batches' => count($batches),
                    'mapped_stock_rows' => $stats['mapped_stock_rows'],
                    'unmapped_stock_rows' => $stats['unmapped_rows'],
                    'warnings' => array_slice($warnings, 0, 20),
                    'publish_enabled' => !empty($settings['publish_to_dashboard']),
                ];
                $this->model->finishLog($logId, 'preview', $stats, $message, $summary);
                return array_merge($stats, ['message' => $message, 'warnings' => $warnings, 'preview' => true]);
            }

            $pdo = Database::connection();
            $pdo->beginTransaction();
            try {
                $this->replaceStaging($pdo, $logId, $products, $batches, $stock);

                if (!empty($settings['publish_to_dashboard'])) {
                    $stats['published_batches'] = $this->publishToDashboard(
                        $pdo,
                        $logId,
                        $products,
                        $batches,
                        $stock,
                        $warnings,
                        $stats
                    );
                }

                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }

            $message = sprintf(
                'SAP sync successful: %s products, %s batches, %s stock rows, %s mapped%s.',
                number_format($stats['product_rows']),
                number_format($stats['batch_rows']),
                number_format($stats['stock_rows']),
                number_format($stats['mapped_stock_rows']),
                !empty($settings['publish_to_dashboard'])
                    ? ', ' . number_format($stats['published_batches']) . ' dashboard batches published'
                    : ' (staging only)'
            );

            $alertResult = null;
            if (!empty($settings['publish_to_dashboard'])) {
                try {
                    $alertResult = (new GradeAlertService())->run(new \DateTimeImmutable('today'));
                } catch (\Throwable $alertError) {
                    $warnings[] = 'Stock sync completed, but grade alert evaluation failed: ' . $this->safeMessage($alertError);
                }
            }

            $summary = [
                'warnings' => array_slice($warnings, 0, 30),
                'publish_to_dashboard' => !empty($settings['publish_to_dashboard']),
                'grade_alert_result' => $alertResult,
            ];

            $this->model->finishLog($logId, 'success', $stats, $message, $summary);
            $this->model->recordSync('success', $message);

            return array_merge($stats, [
                'message' => $message,
                'warnings' => $warnings,
                'preview' => false,
            ]);
        } catch (\Throwable $e) {
            $message = $this->safeMessage($e);
            $this->model->finishLog($logId, 'failed', $stats, $message, ['warnings' => array_slice($warnings,0,20)]);
            $this->model->recordSync('failed', $message);
            throw new \RuntimeException($message);
        }
    }

    private function validateConnectionSettings(array $settings): void
    {
        $base = trim((string)($settings['base_url'] ?? ''));
        if ($base === '' || !preg_match('#^https?://#i', $base)) {
            throw new \RuntimeException('Enter a valid SAP Base URL beginning with http:// or https://.');
        }

        foreach ([
            'Product API' => $settings['product_endpoint'] ?? '',
            'Batch API' => $settings['batch_endpoint'] ?? '',
            'Material Stock API' => $settings['stock_endpoint'] ?? '',
        ] as $label => $value) {
            if (trim((string)$value) === '') {
                throw new \RuntimeException($label . ' collection endpoint is required.');
            }
        }

        $auth = (string)($settings['auth_type'] ?? 'basic');
        if ($auth === 'basic' && trim((string)($settings['username'] ?? '')) === '') {
            throw new \RuntimeException('SAP Basic Authentication username is required.');
        }
        if ($auth === 'oauth2') {
            if (trim((string)($settings['oauth_token_url'] ?? '')) === '' || trim((string)($settings['client_id'] ?? '')) === '') {
                throw new \RuntimeException('OAuth 2.0 Token URL and Client ID are required.');
            }
        }
    }

    private function normalizeProducts(array $records, array $settings, array &$warnings): array
    {
        $out = [];
        foreach ($records as $i => $record) {
            $code = $this->stringValue($this->valueAt($record, (string)$settings['product_code_field']));
            if ($code === '') {
                if (count($warnings) < 50) $warnings[] = 'Product row ' . ($i + 1) . ' has no Product Code using field "' . $settings['product_code_field'] . '".';
                continue;
            }
            $name = $this->stringValue($this->valueAt($record, (string)$settings['product_name_field']));
            $uom = strtoupper($this->stringValue($this->valueAt($record, (string)$settings['product_uom_field'])));
            if ($name === '') $name = $code;
            $out[$code] = [
                'product_code' => $code,
                'product_name' => $name,
                'uom' => $uom,
                'raw_json' => $this->json($record),
            ];
        }
        return $out;
    }

    private function normalizeBatches(array $records, array $settings, array &$warnings): array
    {
        $out = [];
        foreach ($records as $i => $record) {
            $material = $this->stringValue($this->valueAt($record, (string)$settings['batch_material_field']));
            $batchNo = $this->stringValue($this->valueAt($record, (string)$settings['batch_no_field']));
            if ($material === '' || $batchNo === '') {
                if (count($warnings) < 50) $warnings[] = 'Batch row ' . ($i + 1) . ' is missing Material or Batch according to the configured field mapping.';
                continue;
            }

            $mfg = $this->normalizeSapDate($this->valueAt($record, (string)$settings['manufacture_date_field']));
            $exp = $this->normalizeSapDate($this->valueAt($record, (string)$settings['expiry_date_field']));
            $last = $this->normalizeSapDate($this->valueAt($record, (string)$settings['last_receipt_date_field']));

            $key = $material . '|' . $batchNo;
            $out[$key] = [
                'product_code' => $material,
                'batch_no' => $batchNo,
                'manufacture_date' => $mfg,
                'expiry_date' => $exp,
                'last_receipt_date' => $last,
                'raw_json' => $this->json($record),
            ];
        }
        return $out;
    }

    private function normalizeStock(
        array $records,
        array $settings,
        array $mappingIndex,
        array &$warnings,
        array &$stats
    ): array {
        $allowedTypes = $this->allowedStockTypes((string)($settings['stock_type_filter'] ?? ''));
        $out = [];

        foreach ($records as $i => $record) {
            $material = $this->stringValue($this->valueAt($record, (string)$settings['stock_material_field']));
            $batchNo = $this->stringValue($this->valueAt($record, (string)$settings['stock_batch_field']));
            $plant = strtoupper($this->stringValue($this->valueAt($record, (string)$settings['plant_field'])));
            $storage = strtoupper($this->stringValue($this->valueAt($record, (string)$settings['storage_location_field'])));
            $stockType = $this->stringValue($this->valueAt($record, (string)$settings['stock_type_field']));

            if ($allowedTypes && !in_array(strtoupper($stockType), $allowedTypes, true)) {
                continue;
            }

            if ($material === '' || $batchNo === '' || $plant === '') {
                $stats['rejected_rows']++;
                if (count($warnings) < 50) $warnings[] = 'Stock row ' . ($i + 1) . ' is missing Material, Batch or Plant according to the configured mapping.';
                continue;
            }

            $qty = $this->numberValue($this->valueAt($record, (string)$settings['quantity_field']));
            if ($qty === null || $qty < 0) {
                $stats['rejected_rows']++;
                if (count($warnings) < 50) $warnings[] = 'Stock row ' . ($i + 1) . ' has an invalid/negative quantity.';
                continue;
            }

            $stockValueField = trim((string)($settings['stock_value_field'] ?? ''));
            $stockValue = 0.0;
            if ($stockValueField !== '') {
                $value = $this->numberValue($this->valueAt($record, $stockValueField));
                $stockValue = $value !== null ? max(0.0, $value) : 0.0;
            }

            $uom = strtoupper($this->stringValue($this->valueAt($record, (string)$settings['stock_uom_field'])));
            $divisionId = $mappingIndex[$plant . '|' . $storage]
                ?? $mappingIndex[$plant . '|*']
                ?? null;

            if ($divisionId === null) {
                $stats['unmapped_rows']++;
            } else {
                $stats['mapped_stock_rows']++;
            }

            $syncKey = hash('sha256', implode('|', [$material,$batchNo,$plant,$storage,$stockType]));
            $out[$syncKey] = [
                'sync_key' => $syncKey,
                'product_code' => $material,
                'batch_no' => $batchNo,
                'sap_plant' => $plant,
                'sap_storage_location' => $storage,
                'stock_type' => $stockType,
                'current_qty' => $qty,
                'stock_value' => $stockValue,
                'uom' => $uom,
                'division_id' => $divisionId,
                'raw_json' => $this->json($record),
            ];
        }

        return $out;
    }

    private function replaceStaging(PDO $pdo, int $logId, array $products, array $batches, array $stock): void
    {
        // A successful extract replaces the current staging snapshot atomically.
        $pdo->exec('DELETE FROM sap_stock');
        $pdo->exec('DELETE FROM sap_batches');
        $pdo->exec('DELETE FROM sap_products');

        $now = date('Y-m-d H:i:s');

        $productStmt = $pdo->prepare(
            'INSERT INTO sap_products (product_code,product_name,uom,raw_json,sync_log_id,synced_at)
             VALUES (?,?,?,?,?,?)'
        );
        foreach ($products as $row) {
            $productStmt->execute([
                $row['product_code'],$row['product_name'],$row['uom'] ?: null,
                $row['raw_json'],$logId,$now
            ]);
        }

        $batchStmt = $pdo->prepare(
            'INSERT INTO sap_batches
                (product_code,batch_no,manufacture_date,expiry_date,last_receipt_date,raw_json,sync_log_id,synced_at)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        foreach ($batches as $row) {
            $batchStmt->execute([
                $row['product_code'],$row['batch_no'],$row['manufacture_date'],$row['expiry_date'],
                $row['last_receipt_date'],$row['raw_json'],$logId,$now
            ]);
        }

        $stockStmt = $pdo->prepare(
            'INSERT INTO sap_stock
                (sync_key,product_code,batch_no,sap_plant,sap_storage_location,stock_type,current_qty,
                 stock_value,uom,division_id,raw_json,sync_log_id,synced_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        foreach ($stock as $row) {
            $stockStmt->execute([
                $row['sync_key'],$row['product_code'],$row['batch_no'],$row['sap_plant'],
                $row['sap_storage_location'],$row['stock_type'] ?: null,$row['current_qty'],
                $row['stock_value'],$row['uom'] ?: null,$row['division_id'],$row['raw_json'],$logId,$now
            ]);
        }
    }

    private function publishToDashboard(
        PDO $pdo,
        int $logId,
        array $products,
        array $batches,
        array $stock,
        array &$warnings,
        array &$stats
    ): int {
        (new ProductMaster())->ensureTable();

        // Aggregate storage-location stock to the existing dashboard natural key:
        // Division + Product + Batch. This preserves the current dashboard schema.
        $aggregate = [];
        foreach ($stock as $row) {
            if (empty($row['division_id'])) continue;

            $key = (int)$row['division_id'] . '|' . $row['product_code'] . '|' . $row['batch_no'];
            if (!isset($aggregate[$key])) {
                $aggregate[$key] = [
                    'division_id' => (int)$row['division_id'],
                    'product_code' => $row['product_code'],
                    'batch_no' => $row['batch_no'],
                    'current_qty' => 0.0,
                    'stock_value' => 0.0,
                    'uom' => $row['uom'],
                    'locations' => [],
                ];
            }

            $aggregate[$key]['current_qty'] += (float)$row['current_qty'];
            $aggregate[$key]['stock_value'] += (float)$row['stock_value'];
            if ($aggregate[$key]['uom'] === '' && $row['uom'] !== '') {
                $aggregate[$key]['uom'] = $row['uom'];
            }
            $location = $row['sap_plant'] . ($row['sap_storage_location'] !== '' ? '/' . $row['sap_storage_location'] : '');
            $aggregate[$key]['locations'][$location] = true;
        }

        $mappedDivisionIds = [];
        foreach ($this->model->mappings() as $mapping) {
            if (!empty($mapping['is_active']) && !empty($mapping['division_active'])) {
                $mappedDivisionIds[(int)$mapping['division_id']] = true;
            }
        }

        if ($mappedDivisionIds) {
            $placeholders = implode(',', array_fill(0, count($mappedDivisionIds), '?'));
            $stmt = $pdo->prepare(
                "UPDATE stock_batches
                 SET sap_last_sync_id=NULL
                 WHERE source_system='sap' AND division_id IN ($placeholders)"
            );
            $stmt->execute(array_keys($mappedDivisionIds));
        }

        $selectMaterial = $pdo->prepare('SELECT id FROM materials WHERE division_id=? AND material_code=? LIMIT 1');
        $insertMaterial = $pdo->prepare(
            'INSERT INTO materials (division_id,material_code,description,is_active) VALUES (?,?,?,1)'
        );
        $updateMaterial = $pdo->prepare('UPDATE materials SET description=?,is_active=1 WHERE id=?');

        $upsertProductMaster = $pdo->prepare(
            "INSERT INTO product_master (division_id,product_code,product_name,uom,is_active)
             VALUES (?,?,?,?,1)
             ON DUPLICATE KEY UPDATE
                product_name=VALUES(product_name),uom=VALUES(uom),is_active=1"
        );

        $findBatch = $pdo->prepare(
            'SELECT sb.id
             FROM stock_batches sb
             JOIN materials m ON m.id=sb.material_id
             WHERE sb.division_id=? AND m.material_code=? AND BINARY sb.batch_no=BINARY ?
             LIMIT 1'
        );

        $updateBatch = $pdo->prepare(
            "UPDATE stock_batches SET
                material_id=?,manufacture_date=?,expiry_date=?,last_receipt_date=?,
                current_qty=?,stock_value=?,uom=?,receiving_store=?,
                source_system='sap',sap_sync_key=?,sap_last_sync_id=?,updated_at=NOW()
             WHERE id=?"
        );
        $insertBatch = $pdo->prepare(
            "INSERT INTO stock_batches
                (division_id,material_id,batch_no,manufacture_date,expiry_date,last_receipt_date,
                 current_qty,stock_value,uom,receiving_store,source_system,sap_sync_key,sap_last_sync_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,'sap',?,?)"
        );

        $published = 0;
        foreach ($aggregate as $row) {
            if (strlen((string)$row['product_code']) > 60 || strlen((string)$row['batch_no']) > 80) {
                $stats['rejected_rows']++;
                if (count($warnings) < 50) {
                    $warnings[] = 'Not published: SAP Product Code or Batch No. exceeds the existing dashboard field length for '
                        . $row['product_code'] . ' / ' . $row['batch_no'] . '.';
                }
                continue;
            }

            $batchKey = $row['product_code'] . '|' . $row['batch_no'];
            $batch = $batches[$batchKey] ?? null;

            if (!$batch || !$batch['manufacture_date'] || !$batch['expiry_date']) {
                $stats['rejected_rows']++;
                if (count($warnings) < 50) {
                    $warnings[] = 'Not published: ' . $row['product_code'] . ' / ' . $row['batch_no']
                        . ' has no valid Manufacturing Date and Expiry Date from the configured Batch API fields.';
                }
                continue;
            }

            if ($batch['manufacture_date'] > $batch['expiry_date']) {
                $stats['rejected_rows']++;
                if (count($warnings) < 50) {
                    $warnings[] = 'Not published: ' . $row['product_code'] . ' / ' . $row['batch_no']
                        . ' has Manufacturing Date after Expiry Date.';
                }
                continue;
            }

            $product = $products[$row['product_code']] ?? null;
            $name = trim((string)($product['product_name'] ?? $row['product_code']));
            $uom = strtoupper(trim((string)($row['uom'] ?: ($product['uom'] ?? 'PCS')))) ?: 'PCS';
            $uom = substr($uom, 0, 20);

            $selectMaterial->execute([$row['division_id'],$row['product_code']]);
            $materialId = $selectMaterial->fetchColumn();
            if ($materialId) {
                $updateMaterial->execute([$name,(int)$materialId]);
                $materialId = (int)$materialId;
            } else {
                $insertMaterial->execute([$row['division_id'],$row['product_code'],$name]);
                $materialId = (int)$pdo->lastInsertId();
            }

            $upsertProductMaster->execute([$row['division_id'],$row['product_code'],$name,$uom]);

            $locations = array_keys($row['locations']);
            sort($locations, SORT_NATURAL | SORT_FLAG_CASE);
            $receivingStore = substr(implode(', ', $locations), 0, 150);
            $sapBatchKey = hash('sha256', $row['division_id'] . '|' . $row['product_code'] . '|' . $row['batch_no']);

            $findBatch->execute([$row['division_id'],$row['product_code'],$row['batch_no']]);
            $existingId = $findBatch->fetchColumn();

            if ($existingId) {
                $updateBatch->execute([
                    $materialId,$batch['manufacture_date'],$batch['expiry_date'],$batch['last_receipt_date'],
                    $row['current_qty'],$row['stock_value'],$uom,$receivingStore,$sapBatchKey,$logId,(int)$existingId
                ]);
            } else {
                $insertBatch->execute([
                    $row['division_id'],$materialId,$row['batch_no'],$batch['manufacture_date'],$batch['expiry_date'],
                    $batch['last_receipt_date'],$row['current_qty'],$row['stock_value'],$uom,$receivingStore,$sapBatchKey,$logId
                ]);
            }

            $published++;
        }

        // Remove only previously SAP-managed rows that disappeared from the latest complete snapshot.
        if ($mappedDivisionIds) {
            $placeholders = implode(',', array_fill(0, count($mappedDivisionIds), '?'));
            $stmt = $pdo->prepare(
                "DELETE FROM stock_batches
                 WHERE source_system='sap'
                   AND division_id IN ($placeholders)
                   AND sap_last_sync_id IS NULL"
            );
            $stmt->execute(array_keys($mappedDivisionIds));
        }

        return $published;
    }

    private function valueAt(array $record, string $path): mixed
    {
        $path = trim($path);
        if ($path === '') return null;

        // Exact top-level key first, because SAP field names frequently contain punctuation.
        if (array_key_exists($path, $record)) return $record[$path];

        $value = $record;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return null;
            $value = $value[$segment];
        }
        return $value;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null || is_array($value) || is_object($value)) return '';
        return trim((string)$value);
    }

    private function numberValue(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        if (is_string($value)) {
            $value = str_replace([',',' '], '', trim($value));
        }
        return is_numeric($value) ? (float)$value : null;
    }

    private function normalizeSapDate(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (is_array($value)) return null;

        $text = trim((string)$value);
        if ($text === '') return null;

        if (preg_match('#^/Date\((-?\d+)(?:[+-]\d+)?\)/$#', $text, $m)) {
            $seconds = (int)floor(((int)$m[1]) / 1000);
            return gmdate('Y-m-d', $seconds);
        }
        if (preg_match('/^\d{8}$/', $text)) {
            $y = (int)substr($text,0,4);
            $m = (int)substr($text,4,2);
            $d = (int)substr($text,6,2);
            return checkdate($m,$d,$y) ? sprintf('%04d-%02d-%02d',$y,$m,$d) : null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $text, $m)) {
            return checkdate((int)$m[2],(int)$m[3],(int)$m[1])
                ? $m[1] . '-' . $m[2] . '-' . $m[3]
                : null;
        }

        try {
            return (new \DateTimeImmutable($text))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function allowedStockTypes(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];
        $parts = preg_split('/[,;\r\n]+/', $text) ?: [];
        $parts = array_map(static fn($v) => strtoupper(trim((string)$v)), $parts);
        return array_values(array_unique(array_filter($parts, static fn($v) => $v !== '')));
    }

    private function json(array $record): string
    {
        $json = json_encode($record, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        return $json === false ? '{}' : $json;
    }

    private function safeMessage(\Throwable $e): string
    {
        $message = trim($e->getMessage());
        if ($message === '') return 'SAP synchronization failed.';
        $message = preg_replace('#https?://\S+#i', '[SAP endpoint]', $message);
        return substr((string)$message, 0, 500);
    }
}
