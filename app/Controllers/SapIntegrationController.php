<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\SapIntegration;
use App\Services\AuditLogger;
use App\Services\SapSyncService;

class SapIntegrationController
{
    public function index(): void
    {
        $user = Auth::requireSuperAdmin();
        $model = new SapIntegration();

        View::render('admin/sap_integration', [
            'title' => 'SAP S/4HANA Integration',
            'settings' => $model->settings(),
            'mappings' => $model->mappings(),
            'divisions' => (new Division())->all(true),
            'logs' => $model->recentLogs(25),
            'stagingCounts' => $model->stagingCounts(),
            'user' => $user,
        ]);
    }

    public function save(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        $model = new SapIntegration();

        $authType = trim((string)($_POST['auth_type'] ?? 'basic'));
        if (!in_array($authType, ['basic','oauth2','api_key'], true)) {
            flash('error', 'Select a valid SAP authentication method.');
            redirect('/admin/sap-integration');
        }

        $baseUrl = trim((string)($_POST['base_url'] ?? ''));
        if ($baseUrl !== '' && !preg_match('#^https?://#i', $baseUrl)) {
            flash('error', 'SAP Base URL must begin with http:// or https://.');
            redirect('/admin/sap-integration');
        }

        if (isset($_POST['is_enabled']) && $baseUrl === '') {
            flash('error', 'Enter the SAP Base URL before enabling the integration.');
            redirect('/admin/sap-integration');
        }

        if (isset($_POST['publish_to_dashboard']) && trim((string)($_POST['stock_value_field'] ?? '')) === '') {
            flash('error', 'Before enabling Publish to Dashboard, Finance / SAP must confirm and you must enter the approved Total Stock Retail Value field.');
            redirect('/admin/sap-integration');
        }

        if (isset($_POST['is_enabled'])) {
            foreach (['product_endpoint'=>'Product API','batch_endpoint'=>'Batch API','stock_endpoint'=>'Material Stock API'] as $field=>$label) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    flash('error', $label . ' collection endpoint is required before enabling the integration.');
                    redirect('/admin/sap-integration');
                }
            }
        }

        try {
            $model->saveSettings([
                'is_enabled' => isset($_POST['is_enabled']),
                'publish_to_dashboard' => isset($_POST['publish_to_dashboard']),
                'environment_label' => $_POST['environment_label'] ?? 'SAP S/4HANA',
                'base_url' => $baseUrl,
                'auth_type' => $authType,
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'oauth_token_url' => $_POST['oauth_token_url'] ?? '',
                'client_id' => $_POST['client_id'] ?? '',
                'client_secret' => $_POST['client_secret'] ?? '',
                'api_key' => $_POST['api_key'] ?? '',
                'api_key_header' => $_POST['api_key_header'] ?? 'APIKey',
                'product_endpoint' => $_POST['product_endpoint'] ?? '',
                'batch_endpoint' => $_POST['batch_endpoint'] ?? '',
                'stock_endpoint' => $_POST['stock_endpoint'] ?? '',
                'request_timeout_sec' => (int)($_POST['request_timeout_sec'] ?? 30),
                'page_size' => (int)($_POST['page_size'] ?? 500),
                'verify_ssl' => isset($_POST['verify_ssl']),
                'stock_type_filter' => $_POST['stock_type_filter'] ?? '',
                'product_code_field' => $_POST['product_code_field'] ?? '',
                'product_name_field' => $_POST['product_name_field'] ?? '',
                'product_uom_field' => $_POST['product_uom_field'] ?? '',
                'batch_material_field' => $_POST['batch_material_field'] ?? '',
                'batch_no_field' => $_POST['batch_no_field'] ?? '',
                'manufacture_date_field' => $_POST['manufacture_date_field'] ?? '',
                'expiry_date_field' => $_POST['expiry_date_field'] ?? '',
                'last_receipt_date_field' => $_POST['last_receipt_date_field'] ?? '',
                'stock_material_field' => $_POST['stock_material_field'] ?? '',
                'stock_batch_field' => $_POST['stock_batch_field'] ?? '',
                'plant_field' => $_POST['plant_field'] ?? '',
                'storage_location_field' => $_POST['storage_location_field'] ?? '',
                'quantity_field' => $_POST['quantity_field'] ?? '',
                'stock_type_field' => $_POST['stock_type_field'] ?? '',
                'stock_value_field' => $_POST['stock_value_field'] ?? '',
                'stock_uom_field' => $_POST['stock_uom_field'] ?? '',
            ], (int)$user['id']);

            AuditLogger::log(
                'SAP_INTEGRATION_SETTINGS_UPDATE',
                'Updated SAP S/4HANA integration settings.',
                'sap_integration_settings',
                1,
                [
                    'enabled' => isset($_POST['is_enabled']),
                    'publish_to_dashboard' => isset($_POST['publish_to_dashboard']),
                    'auth_type' => $authType,
                    'environment_label' => trim((string)($_POST['environment_label'] ?? '')),
                    'verify_ssl' => isset($_POST['verify_ssl']),
                ],
                $user
            );

            flash('success', 'SAP S/4HANA integration settings saved.');
        } catch (\Throwable $e) {
            flash('error', 'SAP settings could not be saved: ' . $e->getMessage());
        }

        redirect('/admin/sap-integration');
    }

    public function test(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        try {
            $result = (new SapSyncService())->testConnection((int)$user['id']);
            AuditLogger::log(
                'SAP_CONNECTION_TEST',
                $result['message'],
                'sap_integration_settings',
                1,
                ['endpoint_samples' => $result['endpoint_samples'] ?? []],
                $user
            );
            flash('success', $result['message']);
        } catch (\Throwable $e) {
            AuditLogger::log(
                'SAP_CONNECTION_TEST_FAILED',
                'SAP connection test failed.',
                'sap_integration_settings',
                1,
                ['error' => $e->getMessage()],
                $user
            );
            flash('error', 'SAP connection test failed: ' . $e->getMessage());
        }
        redirect('/admin/sap-integration');
    }

    public function preview(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        try {
            $result = (new SapSyncService())->run((int)$user['id'], 'manual', true);
            AuditLogger::log(
                'SAP_SYNC_PREVIEW',
                $result['message'],
                'sap_sync_log',
                null,
                $result,
                $user
            );
            flash('success', $result['message']);
        } catch (\Throwable $e) {
            flash('error', 'SAP preview failed: ' . $e->getMessage());
        }
        redirect('/admin/sap-integration');
    }

    public function syncNow(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        try {
            $result = (new SapSyncService())->run((int)$user['id'], 'manual', false);
            AuditLogger::log(
                'SAP_SYNC_RUN',
                $result['message'],
                'sap_sync_log',
                null,
                $result,
                $user
            );
            flash('success', $result['message']);
        } catch (\Throwable $e) {
            flash('error', 'SAP synchronization failed: ' . $e->getMessage());
        }
        redirect('/admin/sap-integration');
    }

    public function saveMapping(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        $plant = strtoupper(trim((string)($_POST['sap_plant'] ?? '')));
        $storage = strtoupper(trim((string)($_POST['sap_storage_location'] ?? '')));
        $divisionId = (int)($_POST['division_id'] ?? 0);

        $division = $divisionId > 0 ? (new Division())->find($divisionId) : null;
        if (!$division || !(int)$division['is_active']) {
            flash('error', 'Select an active Dashboard Division.');
            redirect('/admin/sap-integration#division-mapping');
        }

        try {
            (new SapIntegration())->saveMapping($plant, $storage, $divisionId, (int)$user['id']);
            AuditLogger::log(
                'SAP_DIVISION_MAPPING_SAVE',
                'Mapped SAP Plant ' . $plant . ($storage !== '' ? ' / ' . $storage : '') . ' to Division ' . $division['code'] . '.',
                'sap_division_mapping',
                null,
                ['plant'=>$plant,'storage_location'=>$storage,'division_id'=>$divisionId],
                $user,
                $divisionId,
                (string)$division['code']
            );
            flash('success', 'SAP Plant / Storage Location mapping saved.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('/admin/sap-integration#division-mapping');
    }

    public function deleteMapping(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        $id = (int)($_POST['id'] ?? 0);

        try {
            (new SapIntegration())->deleteMapping($id);
            AuditLogger::log(
                'SAP_DIVISION_MAPPING_DELETE',
                'Deleted an SAP Plant / Storage Location mapping.',
                'sap_division_mapping',
                $id,
                [],
                $user
            );
            flash('success', 'SAP division mapping deleted.');
        } catch (\Throwable $e) {
            flash('error', 'SAP division mapping could not be deleted.');
        }

        redirect('/admin/sap-integration#division-mapping');
    }
}
