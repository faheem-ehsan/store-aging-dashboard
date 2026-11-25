<?php
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\StockController;
use App\Controllers\RawMaterialController;
use App\Controllers\ExportGoodsController;
use App\Controllers\UserController;
use App\Controllers\DivisionController;
use App\Controllers\LogController;
use App\Controllers\GradeCriteriaController;
use App\Controllers\MarketReturnController;
use App\Controllers\MarketReturnMappingController;
use App\Controllers\AdminController;
use App\Controllers\EmailNotificationController;
use App\Controllers\DailyStockReportController;
use App\Controllers\ProductMasterController;
use App\Controllers\RawMaterialMasterController;
use App\Controllers\ExportGoodsMasterController;
use App\Controllers\SapIntegrationController;

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/products', [ProductMasterController::class, 'index']);
$router->post('/admin/products/store', [ProductMasterController::class, 'store']);
$router->post('/admin/products/update', [ProductMasterController::class, 'update']);
$router->post('/admin/products/toggle', [ProductMasterController::class, 'toggle']);
$router->post('/admin/products/delete', [ProductMasterController::class, 'delete']);
$router->get('/admin/raw-materials', [RawMaterialMasterController::class, 'index']);
$router->post('/admin/raw-materials/store', [RawMaterialMasterController::class, 'store']);
$router->post('/admin/raw-materials/update', [RawMaterialMasterController::class, 'update']);
$router->post('/admin/raw-materials/toggle', [RawMaterialMasterController::class, 'toggle']);
$router->post('/admin/raw-materials/delete', [RawMaterialMasterController::class, 'delete']);
$router->get('/admin/export-goods', [ExportGoodsMasterController::class, 'index']);
$router->post('/admin/export-goods/store', [ExportGoodsMasterController::class, 'store']);
$router->post('/admin/export-goods/update', [ExportGoodsMasterController::class, 'update']);
$router->post('/admin/export-goods/toggle', [ExportGoodsMasterController::class, 'toggle']);
$router->post('/admin/export-goods/delete', [ExportGoodsMasterController::class, 'delete']);
$router->get('/admin/sap-integration', [SapIntegrationController::class, 'index']);
$router->post('/admin/sap-integration/save', [SapIntegrationController::class, 'save']);
$router->post('/admin/sap-integration/test', [SapIntegrationController::class, 'test']);
$router->post('/admin/sap-integration/preview', [SapIntegrationController::class, 'preview']);
$router->post('/admin/sap-integration/sync', [SapIntegrationController::class, 'syncNow']);
$router->post('/admin/sap-integration/mapping', [SapIntegrationController::class, 'saveMapping']);
$router->post('/admin/sap-integration/mapping/delete', [SapIntegrationController::class, 'deleteMapping']);
$router->get('/admin/email-notifications', [EmailNotificationController::class, 'index']);
$router->post('/admin/email-notifications/save', [EmailNotificationController::class, 'save']);
$router->post('/admin/email-notifications/test', [EmailNotificationController::class, 'test']);
$router->post('/admin/email-notifications/sample', [EmailNotificationController::class, 'sample']);
$router->post('/admin/email-notifications/run', [EmailNotificationController::class, 'runNow']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/daily-stock-reports', [DailyStockReportController::class, 'index']);
$router->post('/daily-stock-reports/generate', [DailyStockReportController::class, 'generate']);
$router->post('/daily-stock-reports/verify', [DailyStockReportController::class, 'verify']);
$router->get('/daily-stock-reports/export', [DailyStockReportController::class, 'export']);

$router->get('/stocks', [StockController::class, 'index']);
$router->get('/stocks/create', [StockController::class, 'create']);
$router->get('/stocks/product-options', [StockController::class, 'productOptions']);
$router->post('/stocks/store', [StockController::class, 'store']);
$router->get('/stocks/edit', [StockController::class, 'edit']);
$router->post('/stocks/update', [StockController::class, 'update']);
$router->post('/stocks/delete', [StockController::class, 'delete']);
$router->post('/stocks/delete-selected', [StockController::class, 'deleteSelected']);
$router->post('/stocks/bulk-delete', [StockController::class, 'bulkDelete']);
$router->get('/stocks/import', [StockController::class, 'importForm']);
$router->post('/stocks/import', [StockController::class, 'importCsv']);
$router->get('/stocks/import-template', [StockController::class, 'importTemplate']);

$router->get('/raw-materials', [RawMaterialController::class, 'index']);
$router->get('/raw-materials/create', [RawMaterialController::class, 'create']);
$router->get('/raw-materials/product-options', [RawMaterialController::class, 'productOptions']);
$router->post('/raw-materials/store', [RawMaterialController::class, 'store']);
$router->get('/raw-materials/edit', [RawMaterialController::class, 'edit']);
$router->post('/raw-materials/update', [RawMaterialController::class, 'update']);
$router->post('/raw-materials/delete', [RawMaterialController::class, 'delete']);
$router->post('/raw-materials/delete-selected', [RawMaterialController::class, 'deleteSelected']);
$router->post('/raw-materials/bulk-delete', [RawMaterialController::class, 'bulkDelete']);
$router->get('/raw-materials/import', [RawMaterialController::class, 'importForm']);
$router->post('/raw-materials/import', [RawMaterialController::class, 'importCsv']);
$router->get('/raw-materials/import-template', [RawMaterialController::class, 'importTemplate']);

$router->get('/export-goods', [ExportGoodsController::class, 'index']);
$router->get('/export-goods/create', [ExportGoodsController::class, 'create']);
$router->get('/export-goods/product-options', [ExportGoodsController::class, 'productOptions']);
$router->post('/export-goods/store', [ExportGoodsController::class, 'store']);
$router->get('/export-goods/edit', [ExportGoodsController::class, 'edit']);
$router->post('/export-goods/update', [ExportGoodsController::class, 'update']);
$router->post('/export-goods/delete', [ExportGoodsController::class, 'delete']);
$router->post('/export-goods/delete-selected', [ExportGoodsController::class, 'deleteSelected']);
$router->post('/export-goods/bulk-delete', [ExportGoodsController::class, 'bulkDelete']);
$router->get('/export-goods/import', [ExportGoodsController::class, 'importForm']);
$router->post('/export-goods/import', [ExportGoodsController::class, 'importCsv']);
$router->get('/export-goods/import-template', [ExportGoodsController::class, 'importTemplate']);


$router->get('/market-return', [MarketReturnController::class, 'index']);
$router->get('/market-return/create', [MarketReturnController::class, 'create']);
$router->post('/market-return/store', [MarketReturnController::class, 'store']);
$router->get('/market-return/edit', [MarketReturnController::class, 'edit']);
$router->post('/market-return/update', [MarketReturnController::class, 'update']);
$router->post('/market-return/delete', [MarketReturnController::class, 'delete']);
$router->post('/market-return/bulk-delete', [MarketReturnController::class, 'bulkDelete']);
$router->get('/market-return/import', [MarketReturnController::class, 'importForm']);
$router->post('/market-return/import', [MarketReturnController::class, 'importFile']);
$router->get('/market-return/import-template', [MarketReturnController::class, 'importTemplate']);
$router->get('/market-return/csv-template', [MarketReturnController::class, 'csvTemplate']);
$router->get('/market-return/mapping', [MarketReturnMappingController::class, 'index']);
$router->post('/market-return/mapping/store', [MarketReturnMappingController::class, 'store']);
$router->post('/market-return/mapping/delete', [MarketReturnMappingController::class, 'delete']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/edit', [UserController::class, 'edit']);
$router->post('/users/store', [UserController::class, 'store']);
$router->post('/users/update', [UserController::class, 'update']);
$router->post('/users/toggle', [UserController::class, 'toggle']);
$router->post('/users/delete', [UserController::class, 'delete']);

$router->get('/divisions', [DivisionController::class, 'index']);
$router->post('/divisions/store', [DivisionController::class, 'store']);
$router->post('/divisions/toggle', [DivisionController::class, 'toggle']);


$router->get('/grade-criteria', [GradeCriteriaController::class, 'index']);
$router->post('/grade-criteria/update', [GradeCriteriaController::class, 'update']);

$router->get('/logs', [LogController::class, 'index']);
$router->get('/logs/export', [LogController::class, 'export']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
