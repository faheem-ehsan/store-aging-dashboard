<?php
namespace App\Services;

/** Read-only SAP sandbox diagnostic. Activity types are NOT inventory stock. */
class SapSandboxDiagnostic
{
    public const BASE_URL = 'https://sandbox.api.sap.com';
    public const SAMPLE_ENDPOINT = '/s4hanacloud/sap/opu/odata/sap/API_COSTCNTRACTIVITYTYPE_CRUD_SRV/A_CostCenterActivityType';

    public static function summarize(array $payload): array
    {
        $records = $payload['d']['results'] ?? $payload['value'] ?? null;
        if (!is_array($records)) {
            throw new \RuntimeException('Expected an OData collection under d.results or value.');
        }
        $units = [];
        foreach ($records as $record) {
            if (!is_array($record) || !isset($record['CostCtrActivityType'], $record['ControllingArea'])) {
                throw new \RuntimeException('Response is not an A_CostCenterActivityType collection.');
            }
            $unit = (string)($record['CostCtrActivityTypeQtyUnit'] ?? '');
            $units[$unit !== '' ? $unit : '(blank)'] = ($units[$unit !== '' ? $unit : '(blank)'] ?? 0) + 1;
        }
        ksort($units);
        return ['count' => count($records), 'units' => $units,
                'first_id' => (string)($records[0]['CostCtrActivityType'] ?? '(none)')];
    }

    public static function sample(): array
    {
        $path = dirname(__DIR__, 2) . '/database/fixtures/sap_cost_center_activity_type_sample.json';
        return self::summarize(json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR));
    }

    public static function live(array $settings): array
    {
        $settings['base_url'] = self::BASE_URL;
        $settings['auth_type'] = 'api_key';
        $settings['api_key_header'] = 'APIKey';
        $settings['verify_ssl'] = true;
        if (empty($settings['api_key'])) {
            throw new \RuntimeException('Save your sandbox API key under Connection & Security first.');
        }
        $records = (new SapODataClient($settings))->fetchAll(self::SAMPLE_ENDPOINT, 50, 1);
        return self::summarize(['d' => ['results' => $records]]);
    }
}
